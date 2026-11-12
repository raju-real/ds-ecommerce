<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * SMS gateway (Admin → Settings → SMS).
 *  - log:       writes the message to storage/logs (development)
 *  - bulksmsbd: BulkSMSBD API, POST to /api/smsapi (api_key, type, number, senderid, message).
 *               Its answer is always HTTP 200; success is response_code 202, anything else is an error code.
 *  - http:      any other provider: GET to a URL template with {api_key} {sender_id} {number} {message}
 */
class SmsService
{
    public const STATUS_KEY = 'sms_last';
    public const BALANCE_KEY = 'sms_balance';
    public const DEFAULT_TEMPLATE = 'Your {brand} OTP is {otp}';

    /** BulkSMSBD response codes (from their API documentation). */
    public const BULKSMSBD_CODES = [
        202 => 'SMS submitted successfully',
        1001 => 'Invalid number',
        1002 => 'Sender ID is not correct or is disabled',
        1003 => 'Required fields are missing — contact your SMS provider',
        1005 => 'Internal error at the SMS provider',
        1006 => 'Balance validity has expired',
        1007 => 'Balance is not enough',
        1011 => 'User ID not found',
        1012 => 'Masking SMS must be sent in Bengali',
        1013 => 'Sender ID has no gateway for this API key',
        1014 => 'Sender type name not found for this sender ID',
        1015 => 'Sender ID has no valid gateway for this API key',
        1016 => 'No active price for this sender ID',
        1017 => 'No price set for this sender ID',
        1018 => 'The owner of this account is disabled',
        1019 => 'The price of this sender type is disabled',
        1020 => 'The parent of this account was not found',
        1021 => 'The parent of this account has no active price',
        1031 => 'Your account is not verified — contact BulkSMSBD',
        1032 => 'This server\'s IP address is not whitelisted — add it in your BulkSMSBD panel',
    ];

    public function driver(): string
    {
        $driver = (string) setting('sms_driver', config('shop.sms.driver'));
        // Older setups: "HTTP API" with a bulksmsbd.net URL → use the proper BulkSMSBD integration.
        if ($driver === 'http' && str_contains((string) setting('sms_api_url', config('shop.sms.url')), 'bulksmsbd.net')) {
            return 'bulksmsbd';
        }

        return $driver;
    }

    public function isLogDriver(): bool
    {
        return ! in_array($this->driver(), ['bulksmsbd', 'http'], true);
    }

    /** The OTP text in the format the SMS provider has approved, e.g. "Your Neatly OTP is 123456". */
    public function otpMessage(string $code): string
    {
        $template = trim((string) setting('sms_otp_template')) ?: self::DEFAULT_TEMPLATE;
        $brand = trim((string) setting('sms_brand')) ?: setting('site_name', config('app.name'));

        return strtr($template, ['{brand}' => $brand, '{otp}' => $code]);
    }

    public const ADMIN_STATUS_KEY = 'sms_admin_last';
    public const ADMIN_MAX_NUMBERS = 10;

    /** Admin numbers for new-order alerts (Settings → SMS), as saved: 01XXXXXXXXX each. */
    public function adminNumbers(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) setting('sms_admin_numbers'))),
            fn ($n) => preg_match('/^01[3-9]\d{8}$/', $n)));
    }

    public function adminNotifyEnabled(): bool
    {
        return setting('sms_admin_notify') === '1' && $this->adminNumbers() !== [];
    }

    /** "New order placed - NT-00012" */
    public static function adminOrderMessage(string $orderNumber): string
    {
        return 'New order placed - '.$orderNumber;
    }

    /**
     * A short SMS to every admin number when a customer places an order (after their OTP SMS).
     * Sent after the response, so the customer never waits for it. Does nothing when switched off.
     */
    public function notifyAdminsOfOrder(\App\Models\Order $order): void
    {
        if (! $this->adminNotifyEnabled()) {
            return;
        }
        $numbers = $this->adminNumbers();
        $message = self::adminOrderMessage($order->order_number);
        app()->terminating(function () use ($numbers, $message) {
            $sent = 0;
            $errors = [];
            foreach ($numbers as $number) {
                $r = $this->sendWithResult($number, $message, self::ADMIN_STATUS_KEY);
                $r['ok'] ? $sent++ : $errors[] = $number.': '.$r['message'];
            }
            Cache::put(self::ADMIN_STATUS_KEY, [
                'ok' => $errors === [], 'at' => now()->toIso8601String(), 'to' => count($numbers).' number(s)',
                'message' => $errors === [] ? "\"$message\" sent to $sent number(s)." : "Sent $sent of ".count($numbers).'. '.implode(' · ', $errors),
            ], now()->addDays(30));
        });
    }

    public function lastAdminStatus(): ?array
    {
        return Cache::get(self::ADMIN_STATUS_KEY);
    }

    /** Send and return only whether it worked (used by the checkout). */
    public function send(string $mobile, string $message): bool
    {
        return $this->sendWithResult($mobile, $message)['ok'];
    }

    /**
     * $statusKey: STATUS_KEY = shown as "Last SMS" on the SMS tab; admin alerts write their own combined status instead.
     *
     * @return array{ok: bool, message: string}
     */
    public function sendWithResult(string $mobile, string $message, string $statusKey = self::STATUS_KEY): array
    {
        if ($this->isLogDriver()) {
            Log::info("[SMS to $mobile] $message");

            return ['ok' => true, 'message' => 'Written to the log (driver "Log only").'];
        }

        $number = self::number($mobile);
        try {
            $result = $this->driver() === 'bulksmsbd'
                ? $this->bulkSmsBd($number, $message)
                : $this->httpTemplate($number, $message);
        } catch (Throwable $e) {
            $result = ['ok' => false, 'message' => 'Could not reach the SMS provider: '.Str::limit($e->getMessage(), 150)];
        }

        if (! $result['ok']) {
            Log::error('SMS failed: '.$result['message'], ['to' => $number]);
        }
        if ($statusKey === self::STATUS_KEY) Cache::put(self::STATUS_KEY, $result + ['at' => now()->toIso8601String(), 'to' => substr($number, 0, 5).'****'.substr($number, -2)], now()->addDays(30));

        return $result;
    }

    /** SMS balance from BulkSMSBD. @return array{ok: bool, message: string, balance?: float} */
    public function balance(): array
    {
        if ($this->driver() !== 'bulksmsbd') {
            return ['ok' => false, 'message' => 'Balance check works with the BulkSMSBD driver.'];
        }
        if (! $key = (string) setting('sms_api_key')) {
            return ['ok' => false, 'message' => 'Save the API key first.'];
        }

        try {
            $response = Http::timeout(10)->connectTimeout(5)->asForm()->post(config('shop.sms.bulksmsbd.balance_url'), ['api_key' => $key]);
            $json = $response->json();
            $balance = is_array($json) ? ($json['balance'] ?? null) : null;
            $code = is_array($json) ? (int) ($json['response_code'] ?? 0) : 0;
            if ($balance !== null && is_numeric($balance) && ($code === 0 || $code === 202)) {
                $result = ['ok' => true, 'balance' => (float) $balance, 'message' => 'Balance: ৳'.number_format((float) $balance, 2)];
                Cache::put(self::BALANCE_KEY, $result + ['at' => now()->toIso8601String()], now()->addDays(30));

                return $result;
            }

            return ['ok' => false, 'message' => $this->errorText($json, $response->body())];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Could not reach BulkSMSBD: '.Str::limit($e->getMessage(), 150)];
        }
    }

    public function lastStatus(): ?array
    {
        return Cache::get(self::STATUS_KEY);
    }

    public function lastBalance(): ?array
    {
        return Cache::get(self::BALANCE_KEY);
    }

    /** 01XXXXXXXXX / +8801XXXXXXXXX / 8801XXXXXXXXX / 1XXXXXXXXX → 8801XXXXXXXXX */
    public static function number(string $mobile): string
    {
        $digits = preg_replace('/\D/', '', $mobile);

        return match (true) {
            str_starts_with($digits, '880') => $digits,
            str_starts_with($digits, '0') => '88'.$digits,
            default => '880'.$digits,
        };
    }

    protected function bulkSmsBd(string $number, string $message): array
    {
        $response = Http::timeout(10)->connectTimeout(5)->asForm()->post(config('shop.sms.bulksmsbd.send_url'), [
            'api_key' => (string) setting('sms_api_key', config('shop.sms.api_key')),
            'type' => 'text',
            'number' => $number,
            'senderid' => (string) setting('sms_sender_id', config('shop.sms.sender_id')),
            'message' => $message,
        ]);
        $json = $response->json();
        $code = is_array($json) ? (int) ($json['response_code'] ?? 0) : 0;

        return $response->successful() && $code === 202
            ? ['ok' => true, 'message' => 'SMS sent (BulkSMSBD accepted it).']
            : ['ok' => false, 'message' => $this->errorText($json, $response->body(), $response->status())];
    }

    protected function httpTemplate(string $number, string $message): array
    {
        $url = strtr((string) setting('sms_api_url', config('shop.sms.url')), [
            '{api_key}' => urlencode((string) setting('sms_api_key', config('shop.sms.api_key'))),
            '{sender_id}' => urlencode((string) setting('sms_sender_id', config('shop.sms.sender_id'))),
            '{number}' => urlencode($number),
            '{message}' => urlencode($message),
        ]);
        if (! preg_match('#^https?://#', $url)) {
            return ['ok' => false, 'message' => 'The API URL is not set.'];
        }
        $response = Http::timeout(10)->connectTimeout(5)->get($url);
        $json = $response->json();
        // Many BD providers answer 200 with an error code inside; treat a response_code other than 202 as an error.
        $code = is_array($json) && isset($json['response_code']) ? (int) $json['response_code'] : null;
        $ok = $response->successful() && ($code === null || $code === 202);

        return $ok ? ['ok' => true, 'message' => 'SMS sent.'] : ['ok' => false, 'message' => $this->errorText($json, $response->body(), $response->status())];
    }

    protected function errorText(mixed $json, string $body, int $status = 200): string
    {
        if (is_array($json)) {
            $code = (int) ($json['response_code'] ?? 0);
            $text = $json['error_message'] ?? $json['message'] ?? $json['success_message'] ?? null;
            if ($code && isset(self::BULKSMSBD_CODES[$code])) {
                return self::BULKSMSBD_CODES[$code]." (code $code)";
            }
            if ($text || $code) {
                return trim(($text ?: 'Error').($code ? " (code $code)" : ''));
            }
        }

        return $status >= 400 ? "HTTP $status" : (Str::limit(trim(strip_tags($body)), 150) ?: 'Unexpected answer from the SMS provider.');
    }
}
