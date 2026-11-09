<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Set by the Facebook Pixel script in the browser (plain text); read for the Conversions API.
        '_fbp',
        '_fbc',
    ];
}
