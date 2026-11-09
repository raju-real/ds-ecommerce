<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Support\Bots;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Facebook (Meta) Pixel + Conversions API.
 *
 * Every event is tracked once here and goes two ways with the same event_id, so Facebook counts it once:
 *  - browser: printed by the layout (partials/facebook-pixel) as fbq('track', …, {eventID}), on this page or,
 *    when the request redirects, on the next page (kept in the session);
 *  - server:  sent to the Conversions API after the response has gone to the visitor (never slows a page),
 *    only when "Server events" is switched on in Settings and a token is saved.
 *
 * Content IDs are product codes, the same IDs a product catalog feed uses.
 */
class FacebookPixel
{
    public const SESSION_KEY = 'fb_pixel_events';
    public const STATUS_KEY = 'fb_capi_last';
    protected const SESSION_MAX = 10;

    /** Browser events for the page being rendered. */
    protected array $page = [];

    /** Server events waiting to be sent when the request ends. */
    protected array $server = [];

    protected bool $flushRegistered = false;

    public function pixelId(): ?string
    {
        $id = setting('facebook_pixel_id');

        return $id && ctype_digit((string) $id) ? (string) $id : null;
    }

    public function enabled(): bool
    {
        return $this->pixelId() !== null;
    }

    public function token(): ?string
    {
        $stored = setting('facebook_capi_token');
        if (! $stored) {
            return null;
        }
        try {
            return Crypt::decryptString($stored);
        } catch (Throwable) {
            return null; // saved with another APP_KEY — enter it again in Settings
        }
    }

    public function serverEnabled(): bool
    {
        return $this->enabled() && setting('facebook_capi_enabled') === '1' && $this->token() !== null;
    }

    /**
     * Track an event. $nextPage: show the browser part on the next page (use before a redirect).
     * $user: customer details for the server event (see userData()). Returns the event_id.
     */
    public function track(string $event, array $custom = [], array $user = [], bool $nextPage = false, ?string $eventId = null): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $eventId ??= Str::lower($event).'.'.Str::random(16);
        $browser = ['event' => $event, 'data' => $custom, 'id' => $eventId];

        if ($nextPage) {
            $queued = array_slice(array_merge(session(self::SESSION_KEY, []), [$browser]), -self::SESSION_MAX);
            session()->put(self::SESSION_KEY, $queued);
        } else {
            $this->page[] = $browser;
        }

        $request = request();
        if ($this->serverEnabled() && ! Bots::isLinkPreview($request->userAgent())) {
            $this->server[] = array_filter([
                'event_name' => $event,
                'event_time' => time(),
                'event_id' => $eventId,
                'event_source_url' => $request->expectsJson() ? ($request->headers->get('referer') ?: $request->fullUrl()) : $request->fullUrl(),
                'action_source' => 'website',
                'user_data' => $this->userData($user),
                'custom_data' => $custom ?: null,
            ], fn ($v) => $v !== null);
            $this->registerFlush();
        }

        return $eventId;
    }

    /** Browser events to print on this page: queued from the previous request + this one. Clears the queue. */
    public function browserEvents(): array
    {
        return array_merge(session()->pull(self::SESSION_KEY, []), $this->page);
    }

    /** For JSON (AJAX) responses: this request's browser events, fired by shop.js. */
    public function pageEvents(): array
    {
        $events = $this->page;
        $this->page = [];

        return $events;
    }

    /* ------------------------------------------------------------------ event data */

    public static function contentId(Product $product): string
    {
        return (string) ($product->code ?: 'P'.$product->id);
    }

    public function productData(Product $product, int $qty = 1): array
    {
        $price = (float) $product->selling_price;

        return array_filter([
            'content_ids' => [self::contentId($product)],
            'content_type' => 'product',
            'content_name' => $product->name,
            'content_category' => $product->category?->name,
            'contents' => [['id' => self::contentId($product), 'quantity' => $qty, 'item_price' => $price]],
            'value' => round($price * $qty, 2),
            'currency' => 'BDT',
        ], fn ($v) => $v !== null);
    }

    /** Cart lines from CartService::items(). */
    public function cartData(Collection $lines): array
    {
        return [
            'content_ids' => $lines->map(fn ($l) => self::contentId($l->product))->values()->all(),
            'content_type' => 'product',
            'contents' => $lines->map(fn ($l) => ['id' => self::contentId($l->product), 'quantity' => (int) $l->qty, 'item_price' => (float) $l->price])->values()->all(),
            'num_items' => (int) $lines->sum('qty'),
            'value' => round((float) $lines->sum('total'), 2),
            'currency' => 'BDT',
        ];
    }

    public function orderData(Order $order): array
    {
        $items = $order->items;

        return [
            'content_ids' => $items->map(fn ($i) => (string) ($i->product_code ?: 'P'.$i->product_id))->values()->all(),
            'content_type' => 'product',
            'contents' => $items->map(fn ($i) => ['id' => (string) ($i->product_code ?: 'P'.$i->product_id), 'quantity' => (int) $i->quantity, 'item_price' => (float) $i->unit_price])->values()->all(),
            'num_items' => (int) $items->sum('quantity'),
            'value' => round((float) $order->total, 2),
            'currency' => 'BDT',
            'order_id' => $order->order_number,
        ];
    }

    /** Customer details of an order, for matching (hashed in userData()). */
    public function orderCustomer(Order $order): array
    {
        $parts = preg_split('/\s+/', trim((string) $order->customer_name)) ?: [];

        return [
            'phone' => $order->mobile,
            'first_name' => $parts[0] ?? null,
            'last_name' => count($parts) > 1 ? end($parts) : null,
            'city' => $order->district?->name,
        ];
    }

    /* ------------------------------------------------------------------ Conversions API */

    /**
     * user_data as Meta expects it: personal details normalised + SHA-256 hashed;
     * IP, browser and the _fbp/_fbc cookies as they are.
     */
    public function userData(array $user = []): array
    {
        $request = request();
        $hash = fn (?string $v) => $v !== null && $v !== '' ? hash('sha256', $v) : null;
        $clean = fn (?string $v) => $v !== null ? preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower(trim($v))) : null;

        $phone = isset($user['phone']) ? preg_replace('/\D/', '', $user['phone']) : null;
        if ($phone && str_starts_with($phone, '01')) {
            $phone = '88'.$phone; // 01XXXXXXXXX → 8801XXXXXXXXX
        }

        return array_filter([
            'client_ip_address' => $request->ip(),
            'client_user_agent' => $request->userAgent(),
            'fbp' => $request->cookie('_fbp'),
            'fbc' => $this->clickId(),
            'ph' => $phone ? [$hash($phone)] : null,
            'external_id' => $phone ? [$hash($phone)] : null,
            'fn' => ($v = $hash($clean($user['first_name'] ?? null))) ? [$v] : null,
            'ln' => ($v = $hash($clean($user['last_name'] ?? null))) ? [$v] : null,
            'ct' => ($v = $hash($clean($user['city'] ?? null))) ? [$v] : null,
            'country' => [$hash('bd')],
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** The Facebook click ID: _fbc cookie, or built from ?fbclid= on the landing page (remembered for the visit). */
    public function clickId(): ?string
    {
        $request = request();
        if ($fbc = $request->cookie('_fbc')) {
            return $fbc;
        }
        if (($fbclid = $request->query('fbclid')) && is_string($fbclid) && strlen($fbclid) <= 500) {
            $fbc = 'fb.1.'.(int) (microtime(true) * 1000).'.'.$fbclid;
            if ($request->hasSession()) {
                $request->session()->put('fb_fbc', $fbc);
            }

            return $fbc;
        }

        return $request->hasSession() ? $request->session()->get('fb_fbc') : null;
    }

    /** Remember ?fbclid= when a visitor lands from an ad (called on every page by the layout). */
    public function rememberClick(): void
    {
        if ($this->enabled() && request()->query('fbclid')) {
            $this->clickId();
        }
    }

    protected function registerFlush(): void
    {
        if ($this->flushRegistered) {
            return;
        }
        $this->flushRegistered = true;
        // Runs after the response has been sent (php-fpm), so visitors never wait for Facebook.
        app()->terminating(function () {
            $events = $this->server;
            $this->server = [];
            if ($events) {
                $this->send($events);
            }
        });
    }

    /** Send events to the Conversions API now. Returns ['ok' => bool, 'message' => string]. Never throws. */
    public function send(array $events, ?string $testCode = null): array
    {
        $token = $this->token();
        if (! $this->pixelId() || ! $token) {
            return ['ok' => false, 'message' => 'Pixel ID or access token is missing.'];
        }

        $payload = ['data' => array_values($events)];
        if ($code = $testCode ?? setting('facebook_test_event_code')) {
            $payload['test_event_code'] = $code;
        }

        try {
            $response = Http::timeout(5)->connectTimeout(3)->asJson()
                ->post(rtrim(config('shop.facebook.graph_url'), '/').'/'.config('shop.facebook.graph_version').'/'.$this->pixelId().'/events?access_token='.urlencode($token), $payload);
            $ok = $response->successful();
            $message = $ok
                ? 'Facebook received '.($response->json('events_received') ?? count($events)).' event(s).'
                : ($response->json('error.error_user_msg') ?: $response->json('error.message') ?: 'HTTP '.$response->status());
        } catch (Throwable $e) {
            $ok = false;
            $message = 'Could not reach Facebook: '.Str::limit($e->getMessage(), 150);
        }

        if (! $ok) {
            Log::warning('Facebook Conversions API: '.$message, ['events' => array_column($events, 'event_name')]);
        }
        Cache::put(self::STATUS_KEY, [
            'ok' => $ok, 'message' => $message, 'at' => now()->toIso8601String(),
            'events' => implode(', ', array_unique(array_column($events, 'event_name'))),
            'test' => isset($payload['test_event_code']),
        ], now()->addDays(30));

        return ['ok' => $ok, 'message' => $message];
    }

    /** Last Conversions API result, for the Settings page. */
    public function lastStatus(): ?array
    {
        return Cache::get(self::STATUS_KEY);
    }
}
