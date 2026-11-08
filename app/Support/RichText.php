<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Editor HTML (Summernote) for blogs and product descriptions.
 *
 *  sanitize() — on save: keeps a safe allow-list of tags/attributes/styles, drops scripts and
 *               javascript: links, only allows YouTube/Vimeo/Facebook video frames, turns H1 into H2
 *               (every page already has its own H1).
 *  render()   — on display: points uploaded images at the current domain (so content moved from
 *               localhost to production keeps working), lazy-loads them, refreshes "related blog"
 *               cards from the live blog list and wraps tables for small screens.
 */
class RichText
{
    protected const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'strike' => [], 'sub' => [], 'sup' => [],
        'span' => [], 'div' => [], 'blockquote' => [], 'pre' => [], 'code' => [], 'small' => [], 'mark' => [],
        'ul' => [], 'ol' => ['start'], 'li' => [],
        'a' => ['href', 'target', 'rel', 'data-slug'],
        'img' => ['src', 'alt', 'width', 'height'],
        'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [], 'caption' => [], 'colgroup' => [],
        'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'], 'col' => ['span'],
        'iframe' => ['src', 'width', 'height', 'allowfullscreen', 'frameborder'],
    ];

    /** Removed together with everything inside them. */
    protected const DROP = ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'option',
        'link', 'meta', 'base', 'svg', 'math', 'template', 'noscript', 'head', 'title', 'applet', 'frame', 'frameset'];

    protected const GLOBAL_ATTRS = ['style', 'class', 'title'];

    protected const CLASSES = ['blog-ref', 'note-float-left', 'note-float-right', 'table', 'table-bordered'];

    protected const STYLES = ['color', 'background-color', 'font-size', 'font-weight', 'font-style', 'text-decoration',
        'text-align', 'line-height', 'width', 'height', 'max-width', 'float', 'margin', 'margin-left', 'margin-right',
        'margin-top', 'margin-bottom', 'padding', 'list-style-type', 'vertical-align', 'border', 'border-collapse'];

    protected const VIDEO_HOSTS = '#^https://(www\.)?(youtube\.com/embed/|youtube-nocookie\.com/embed/|player\.vimeo\.com/video/|facebook\.com/plugins/video\.php)#i';

    public static function sanitize(?string $html): string
    {
        [$doc, $root] = static::load((string) $html);
        static::clean($root, $doc);

        return static::inner($root);
    }

    /**
     * @param  callable|null  $blogLookup  fn(string $slug): ?array{title: string, url: string} — live published blogs
     */
    public static function render(?string $html, ?callable $blogLookup = null, string $altFallback = ''): string
    {
        if (trim((string) $html) === '') {
            return '';
        }
        [$doc, $root] = static::load((string) $html);
        $xp = new DOMXPath($doc);

        foreach ($xp->query('.//img', $root) as $img) {
            /** @var DOMElement $img */
            $src = $img->getAttribute('src');
            if (($pos = strpos($src, '/uploads/')) !== false) {
                $img->setAttribute('src', asset(ltrim(substr($src, $pos), '/')));
            }
            $img->setAttribute('loading', 'lazy');
            $img->setAttribute('decoding', 'async');
            if (trim($img->getAttribute('alt')) === '') {
                $img->setAttribute('alt', $altFallback);
            }
        }

        foreach (iterator_to_array($xp->query('.//a[contains(concat(" ", normalize-space(@class), " "), " blog-ref ")]', $root)) as $a) {
            /** @var DOMElement $a */
            $target = $blogLookup ? $blogLookup($a->getAttribute('data-slug')) : null;
            if (! $target) {
                // The linked post was deleted or unpublished: drop the card (and its now-empty paragraph).
                $parent = $a->parentNode;
                $parent->removeChild($a);
                if ($parent instanceof DOMElement && $parent !== $root && trim($parent->textContent) === '' && ! $parent->getElementsByTagName('img')->length) {
                    $parent->parentNode->removeChild($parent);
                }
                continue;
            }
            $a->setAttribute('href', $target['url']);
            while ($a->firstChild) {
                $a->removeChild($a->firstChild);
            }
            $a->appendChild($doc->createTextNode($target['title']));
            $a->removeAttribute('target');
        }

        foreach (iterator_to_array($xp->query('.//table', $root)) as $table) {
            $wrap = $doc->createElement('div');
            $wrap->setAttribute('class', 'table-responsive');
            $table->parentNode->replaceChild($wrap, $table);
            $wrap->appendChild($table);
        }

        foreach ($xp->query('.//a[@target="_blank"]', $root) as $a) {
            $a->setAttribute('rel', 'noopener noreferrer');
        }

        return static::inner($root);
    }

    protected static function load(string $html): array
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="__rt">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();

        return [$doc, $doc->getElementById('__rt') ?? $doc->documentElement];
    }

    protected static function inner(DOMNode $root): string
    {
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $root->ownerDocument->saveHTML($child);
        }

        return trim($out);
    }

    protected static function clean(DOMNode $node, DOMDocument $doc): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);

            if (in_array($tag, static::DROP, true)) {
                $node->removeChild($child);
                continue;
            }
            if ($tag === 'h1') {
                $child = static::rename($child, 'h2', $doc);
                $tag = 'h2';
            }
            if (! array_key_exists($tag, static::ALLOWED)) {
                // Unknown wrapper (font, center, section…): keep its content, lose the tag.
                static::clean($child, $doc);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            static::cleanAttributes($child, $tag);

            if ($tag === 'iframe' && ! preg_match(static::VIDEO_HOSTS, $child->getAttribute('src'))) {
                $node->removeChild($child);
                continue;
            }
            if ($tag === 'img' && $child->getAttribute('src') === '') {
                $node->removeChild($child);
                continue;
            }
            static::clean($child, $doc);
        }
    }

    protected static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = array_merge(static::GLOBAL_ATTRS, static::ALLOWED[$tag]);

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);

            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
                continue;
            }
            $clean = match ($name) {
                'href' => static::safeUrl($value, true),
                'src' => static::safeUrl(str_starts_with($value, '//') ? 'https:'.$value : $value, false),
                'style' => static::safeStyle($value),
                'class' => implode(' ', array_intersect(preg_split('/\s+/', $value), static::CLASSES)),
                'target' => $value === '_blank' ? '_blank' : null,
                'data-slug' => preg_match('/^[a-z0-9-]{1,180}$/', $value) ? $value : null,
                'width', 'height', 'colspan', 'rowspan', 'span', 'start' => preg_match('/^\d{1,4}(%|px)?$/', $value) ? $value : null,
                default => $value,
            };
            $clean === null || $clean === '' ? $el->removeAttribute($attr->name) : $el->setAttribute($attr->name, $clean);
        }

        if ($tag === 'a' && $el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /** http(s), relative and anchors; mailto/tel for links. No javascript:, data:, vbscript: … */
    protected static function safeUrl(string $url, bool $isLink): ?string
    {
        $decoded = strtolower(preg_replace('/[\x00-\x20]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5)));
        if ($decoded === '') {
            return null;
        }
        if (preg_match('/^[a-z][a-z0-9+.-]*:/', $decoded, $m)) {
            $scheme = rtrim($m[0], ':');
            $ok = in_array($scheme, $isLink ? ['http', 'https', 'mailto', 'tel'] : ['http', 'https'], true);

            return $ok ? $url : null;
        }

        return $url; // relative, root-relative, #anchor or ?query
    }

    protected static function safeStyle(string $style): ?string
    {
        $out = [];
        foreach (explode(';', $style) as $rule) {
            if (! str_contains($rule, ':')) {
                continue;
            }
            [$prop, $value] = array_map('trim', explode(':', $rule, 2));
            $prop = strtolower($prop);
            if (in_array($prop, static::STYLES, true) && ! preg_match('/url\s*\(|expression|javascript|@import|[<>"\\\\]/i', $value)) {
                $out[] = $prop.': '.$value;
            }
        }

        return $out ? implode('; ', $out) : null;
    }

    protected static function rename(DOMElement $el, string $tag, DOMDocument $doc): DOMElement
    {
        $new = $doc->createElement($tag);
        foreach (iterator_to_array($el->attributes) as $attr) {
            $new->setAttribute($attr->name, $attr->value);
        }
        while ($el->firstChild) {
            $new->appendChild($el->firstChild);
        }
        $el->parentNode->replaceChild($new, $el);

        return $new;
    }
}
