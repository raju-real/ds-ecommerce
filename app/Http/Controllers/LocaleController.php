<?php

namespace App\Http\Controllers;

use App\Support\Locale;

/** /lang/{en|bn} — language switcher (shop + admin): remembered for a year, back to the same page. */
class LocaleController extends Controller
{
    public function switch(string $locale)
    {
        abort_unless(isset(Locale::SUPPORTED[$locale]), 404);
        $back = url()->previous();
        // Only go back inside this site.
        $home = rtrim(url('/'), '/');
        if (! $back || ($back !== $home && ! str_starts_with($back, $home.'/') && ! str_starts_with($back, $home.'?'))) {
            $back = url('/');
        }

        return redirect($back)->withCookie(cookie()->forever(Locale::COOKIE, $locale, null, null, null, false));
    }
}
