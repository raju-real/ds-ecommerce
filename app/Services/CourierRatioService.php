<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Customer courier success ratio (fraud check) — the same check as on the vendor panel:
 * POST {url} {"number": "01XXXXXXXXX"} with the vendor panel's authorization token (Settings → Courier check).
 * The answer is read flexibly: any list of couriers with total / delivered / cancelled numbers becomes the table;
 * anything else is shown raw, so nothing is lost if the provider changes its format.
 */
class CourierRatioService
{
    public const DEFAULT_URL = 'https://api.easydrop.asia/api/fraud/check-customer';
    protected const CACHE_HOURS = 6;

    public function url(): string
    {
        return trim((string) setting('courier_check_url')) ?: self::DEFAULT_URL;
    }

    /** The saved token (decrypted), or null. */
    public function token(): ?string
    {
        $stored = setting('courier_check_token');
        if (! $stored) {
            return null;
        }
        try {
            return Crypt::decryptString($stored);
        } catch (Throwable) {
            return null;
        }
    }

    public function configured(): bool
    {
        return $this->token() !== null;
    }

    /**
     * @return array{ok: bool, message: string, rows: list<array{name: string, total: int, delivered: int, cancelled: int, ratio: float}>,
     *               summary: ?array{total: int, delivered: int, cancelled: int, ratio: float}, raw: mixed, cached_at: ?string}
     */
    public function check(string $mobile, bool $fresh = false): array
    {
        $number = preg_replace('/\D/', '', $mobile);
        if (str_starts_with($number, '880')) {
            $number = substr($number, 2);
        }
        if (! preg_match('/^01[3-9]\d{8}$/', $number)) {
            return $this->fail('This is not a valid 11 digit mobile number.');
        }
        if (! $this->configured()) {
            return $this->fail('Courier check is not set up yet: paste the authorization token in Settings → Courier check.');
        }

        $key = 'courier-ratio:'.md5($this->url().$number);
        if (! $fresh && ($hit = Cache::get($key))) {
            return $hit;
        }

        try {
            $token = $this->token();
            $headers = ['Accept' => 'application/json'];
            // Paste the token as shown in the vendor panel's request ("Bearer …" or just the token).
            $headers['Authorization'] = preg_match('/^(Bearer|Token|Basic)\s/i', $token) ? $token : 'Bearer '.$token;
            $response = Http::timeout(15)->connectTimeout(6)->withHeaders($headers)->asJson()->post($this->url(), ['number' => $number]);
            $json = $response->json();
        } catch (Throwable $e) {
            return $this->fail('Could not reach the courier check service: '.Str::limit($e->getMessage(), 150));
        }

        if (! is_array($json)) {
            return $this->fail('The service answered HTTP '.$response->status().' without data.');
        }
        $flag = strtoupper((string) ($json['flag'] ?? ($json['status'] ?? '')));
        if ($response->status() >= 400 || in_array($flag, ['FAIL', 'FAILED', 'ERROR', 'FALSE'], true) || ($json['success'] ?? null) === false) {
            $msg = (string) ($json['msg'] ?? $json['message'] ?? $json['error'] ?? 'HTTP '.$response->status());
            if (! empty($json['logout']) || stripos($msg, 'authoriz') !== false) {
                $msg .= ' — the token has expired or is wrong: copy a new one from the vendor panel into Settings → Courier check.';
            }
            Log::warning('Courier check failed: '.$msg);

            return $this->fail($msg, $json);
        }

        $rows = $this->rows($json);
        $summary = $rows ? $this->summary($json, $rows) : null;
        $result = [
            'ok' => true,
            'message' => $rows ? 'Courier history found.' : 'The service answered, but no courier list was found — see the data below.',
            'rows' => $rows,
            'summary' => $summary,
            'raw' => $rows ? null : $json,
            'number' => $number,
            'cached_at' => now()->toIso8601String(),
        ];
        Cache::put($key, $result, now()->addHours(self::CACHE_HOURS));

        return $result;
    }

    protected function fail(string $message, mixed $raw = null): array
    {
        return ['ok' => false, 'message' => $message, 'rows' => [], 'summary' => null, 'raw' => $raw, 'cached_at' => null];
    }

    /** Find the courier list anywhere in the answer: items (or keyed objects) that carry parcel counts. */
    protected function rows(array $json): array
    {
        $best = [];
        $walk = function ($node, ?string $parentKey = null) use (&$walk, &$best) {
            if (! is_array($node)) {
                return;
            }
            $found = [];
            foreach ($node as $k => $item) {
                if (is_array($item) && ($row = $this->row($item, is_string($k) ? $k : null))) {
                    $found[] = $row;
                }
            }
            if (count($found) > count($best)) {
                $best = $found;
            }
            foreach ($node as $k => $child) {
                if (is_array($child)) {
                    $walk($child, is_string($k) ? $k : null);
                }
            }
        };
        $walk($json);

        return $best;
    }

    /** One courier: a name + total / delivered (success) / cancelled (return) numbers, or null. */
    protected function row(array $item, ?string $key): ?array
    {
        // Most exact key names first; $not = parts a key must not contain (e.g. "total_delivered" is not the total).
        $num = function (array $names, string $not = '') use ($item) {
            foreach ($names as $n) {
                foreach ($item as $k => $v) {
                    $kk = strtolower((string) $k);
                    if (is_numeric($v) && preg_match($n, $kk) && ($not === '' || ! preg_match($not, $kk))) {
                        return (float) $v;
                    }
                }
            }

            return null;
        };
        $total = $num(['/^total_?(parcels?|orders?|count)?$/', '/^(parcels?|orders?|count)$/', '/total/'], '/deliver|success|cancel|return|fail|ratio|rate/');
        $delivered = $num(['/deliver/', '/success/', '/complete/'], '/ratio|rate|percent/');
        $cancelled = $num(['/cancel/', '/return/', '/fail/'], '/ratio|rate|percent/');
        if ($total === null || ($delivered === null && $cancelled === null)) {
            return null;
        }
        $name = null;
        foreach (['name', 'courier', 'courier_name', 'title', 'provider'] as $n) {
            if (! empty($item[$n]) && is_string($item[$n])) {
                $name = $item[$n];
                break;
            }
        }
        $name ??= $key ? Str::headline($key) : 'Courier';
        if (in_array(strtolower($name), ['summary', 'total', 'totals', 'overall'], true)) {
            return null; // the overall line, not a courier
        }
        $ratio = $num(['/ratio/', '/rate/', '/percent/']);
        $delivered ??= max(0, $total - $cancelled);
        $cancelled ??= max(0, $total - $delivered);

        return [
            'name' => $name,
            'total' => (int) $total,
            'delivered' => (int) $delivered,
            'cancelled' => (int) $cancelled,
            'ratio' => round($ratio ?? ($total > 0 ? $delivered / $total * 100 : 0), 2),
        ];
    }

    /** Totals: the provider's own summary when it has one, else the sum of the rows. */
    protected function summary(array $json, array $rows): array
    {
        $total = array_sum(array_column($rows, 'total'));
        $delivered = array_sum(array_column($rows, 'delivered'));
        $cancelled = array_sum(array_column($rows, 'cancelled'));
        $ratio = $total > 0 ? round($delivered / $total * 100, 2) : 0;
        foreach (['summary', 'totals', 'total', 'overall'] as $k) {
            if (isset($json[$k]) && is_array($json[$k]) && ($r = $this->row(['name' => 'all'] + $json[$k], null))) {
                return ['total' => $r['total'], 'delivered' => $r['delivered'], 'cancelled' => $r['cancelled'], 'ratio' => $r['ratio']];
            }
        }
        foreach (['ratio', 'success_ratio', 'successRatio', 'success_rate'] as $k) {
            if (isset($json[$k]) && is_numeric($json[$k])) {
                $ratio = round((float) $json[$k], 2);
            }
        }

        return compact('total', 'delivered', 'cancelled', 'ratio');
    }
}
