<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Bangla pages: replaces the site's fixed texts (menus, buttons, labels, headings …) with their translation from
 * lang/bn.json. Only a whole piece of text that exactly matches an entry is replaced, so product names, prices,
 * customer data and anything typed in the admin stay as they are. <script>, <style>, <textarea>, <pre> and
 * <code> are never touched. English pages are sent unchanged.
 */
class TranslateStaticText
{
    protected const ATTRS = 'placeholder|title|aria-label|alt|data-placeholder|data-confirm|data-copy-msg|data-bs-original-title|data-label|data-msg-[a-z-]+';

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        // The same address can be English or Bangla: tell any cache in between (pages only).
        if (! $response instanceof JsonResponse && str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html')) {
            $response->headers->set('Vary', trim($response->headers->get('Vary').', Accept-Language, Cookie', ', '));
        }
        if (app()->getLocale() === 'en' || ! ($dict = Locale::dictionary())) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            // AJAX pieces of HTML ("Load more", etc.) and short messages.
            $data = $response->getData(true);
            if (is_array($data)) {
                foreach (['html', 'message'] as $key) {
                    if (isset($data[$key]) && is_string($data[$key])) {
                        $data[$key] = $key === 'html' ? $this->translate($data[$key], $dict) : ($dict[$data[$key]] ?? $data[$key]);
                    }
                }
                $response->setData($data);
            }

            return $response;
        }

        $type = (string) $response->headers->get('Content-Type');
        if ($type !== '' && ! str_contains($type, 'text/html')) {
            return $response;
        }
        $content = $response->getContent();
        if (is_string($content) && $content !== '') {
            $response->setContent($this->translate($content, $dict));
        }

        return $response;
    }

    public function translate(string $html, array $dict): string
    {
        // Never risk a blank page: if any step fails (e.g. broken UTF-8 in some content), send the original.
        try {
            $out = $this->replace($html, $dict);
        } catch (\Throwable $e) {
            $out = null;
        }

        return is_string($out) && $out !== '' ? $out : $html;
    }

    protected function replace(string $html, array $dict): ?string
    {
        // Protect blocks that must not change: code/scripts first, then names marked translate="no"
        // (logo, the admin's name). Each becomes a placeholder and is put back unchanged at the end.
        $kept = [];
        $keep = function ($m) use (&$kept) {
            $kept[] = $m[0];

            return "\x01".(count($kept) - 1)."\x02";
        };
        foreach (['#<(script|style|pre|code)\b[^>]*>.*?</\1>#is', '#<([a-z][a-z0-9]*)\b[^>]*\stranslate="no"[^>]*>.*?</\1>#is'] as $re) {
            $html = preg_replace_callback($re, $keep, $html);
            if ($html === null) {
                return null;
            }
        }
        // <textarea>: what is typed in it stays as is; its placeholder is still translated.
        $html = preg_replace_callback('#(<textarea\b[^>]*>)(.*?)(</textarea>)#is', fn ($m) => $m[1].$keep([$m[2]]).$m[3], $html);
        if ($html === null) {
            return null;
        }

        // Text between tags: replace when the trimmed text is an entry (spaces around it are kept).
        $html = preg_replace_callback('/>([^<>\x01]+)</u', function ($m) use ($dict) {
            $text = $m[1];
            $core = trim($text);
            if ($core === '' || ! preg_match('/\p{L}/u', $core)) {
                return $m[0];
            }
            $found = $this->lookup($core, $dict);
            if ($found === null) {
                return $m[0];
            }
            $lead = substr($text, 0, strlen($text) - strlen(ltrim($text)));
            $trail = substr($text, strlen(rtrim($text)));

            return '>'.$lead.htmlspecialchars($found, ENT_QUOTES, 'UTF-8').$trail.'<';
        }, $html);
        if ($html === null) {
            return null;
        }

        // Attributes people read: placeholders, tooltips, labels, validation messages.
        $html = preg_replace_callback('/\s('.self::ATTRS.')="([^"]*)"/u', function ($m) use ($dict) {
            $found = $this->lookup($m[2], $dict);

            return $found !== null ? ' '.$m[1].'="'.htmlspecialchars($found, ENT_QUOTES, 'UTF-8').'"' : $m[0];
        }, $html);
        if ($html === null) {
            return null;
        }

        // Put kept blocks back (twice: a translate="no" block can hold a kept <script>).
        for ($i = 0; $i < 2 && $html !== null && str_contains($html, "\x01"); $i++) {
            $html = preg_replace_callback("/\x01(\d+)\x02/", fn ($m) => $kept[(int) $m[1]] ?? $m[0], $html);
        }

        return $html;
    }

    /**
     * Translation of one piece of text, or null. Line breaks/double spaces from the templates don't matter.
     * Entries with ":n" cover counts: ":n items" matches "6 items" → "6 টি আইটেম".
     */
    protected function lookup(string $raw, array $dict): ?string
    {
        $key = trim(preg_replace('/\s+/u', ' ', html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        if ($key === '') {
            return null;
        }
        if (isset($dict[$key])) {
            return $dict[$key];
        }
        if (preg_match('/^((?:৳\s?)?[\d,.]+)\s+(.+)$/u', $key, $p) && isset($dict[':n '.$p[2]])) {
            return str_replace(':n', $p[1], $dict[':n '.$p[2]]);
        }
        // "Orders · Shop Admin" (page titles), "Vendor · Brand": translate the parts that are entries.
        if (str_contains($key, ' · ')) {
            $parts = explode(' · ', $key);
            $done = array_map(fn ($part) => $part !== '' && ! str_contains($part, ' · ') ? ($this->lookup($part, $dict) ?? $part) : $part, $parts);
            if ($done !== $parts) {
                return implode(' · ', $done);
            }
        }

        return null;
    }
}
