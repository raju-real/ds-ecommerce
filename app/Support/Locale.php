<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Site language (English default, Bangla): ?lang= → the "lang" cookie (set by the switcher) → the browser's
 * preferred language → English. Static text is translated from lang/bn.json (see TranslateStaticText).
 */
class Locale
{
    public const SUPPORTED = ['en' => 'English', 'bn' => 'বাংলা'];
    public const COOKIE = 'lang';

    public static function detect(Request $request): string
    {
        foreach ([$request->query('lang'), $request->cookie(self::COOKIE)] as $choice) {
            if (is_string($choice) && isset(self::SUPPORTED[$choice])) {
                return $choice;
            }
        }
        // Browser: the first supported language in Accept-Language (e.g. "bn-BD,bn;q=0.9,en;q=0.8" → bn).
        foreach ($request->getLanguages() as $lang) {
            $code = strtolower(substr((string) $lang, 0, 2));
            if (isset(self::SUPPORTED[$code])) {
                return $code;
            }
        }

        return 'en';
    }

    /**
     * The texts JavaScript writes into pages (lang/bn.js.json — the part of bn.json used in public/js),
     * printed as window.I18N on Bangla pages so toasts and dialogs follow the language too.
     */
    public static function jsDictionary(): array
    {
        if (app()->getLocale() === 'en') {
            return [];
        }
        $file = lang_path('bn.js.json');

        return is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
    }

    /** English → Bangla dictionary (lang/bn.json), loaded once per request. */
    public static function dictionary(): array
    {
        static $dict = null;
        if ($dict === null) {
            $file = lang_path('bn.json');
            $dict = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        }

        return $dict;
    }
}
