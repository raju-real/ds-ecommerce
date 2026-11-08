<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    public function __construct()
    {
        // Behind Cloudflare / a load balancer set TRUSTED_PROXIES (e.g. "*" or comma-separated IPs) so
        // HTTPS and the real host are detected. Off by default: trusting every proxy lets clients fake their IP.
        $proxies = env('TRUSTED_PROXIES');
        $this->proxies = $proxies === '*' ? '*' : ($proxies ? array_map('trim', explode(',', $proxies)) : null);
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
