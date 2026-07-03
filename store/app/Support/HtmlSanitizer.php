<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Lightweight allowlist HTML sanitizer for blog post bodies.
 *
 * The store has no HTMLPurifier dependency, so this uses DOMDocument to strip
 * anything not on the allowlist. Post bodies come from two semi-trusted sources
 * — the admin Trix editor and imported WordPress content — so we defend against
 * stored XSS (script/style/iframe, on* handlers, javascript: URLs) while keeping
 * the rich structure (headings, lists, links, images, blockquotes, tables).
 *
 * WordPress Gutenberg block comments (<!-- wp:... -->) are preserved.
 */
class HtmlSanitizer
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'span', 'div',
        'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'b', 'em', 'i', 'u', 's', 'small', 'sub', 'sup', 'mark',
        'a', 'img', 'figure', 'figcaption',
        'ul', 'ol', 'li', 'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',
    ];

    /** @var array<string, list<string>> tag => allowed attributes */
    private const ALLOWED_ATTRS = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan', 'scope'],
        '*' => ['class'],
    ];

    public static function clean(?string $html): ?string
    {
        $html = (string) $html;

        if (trim($html) === '') {
            return null;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');

        // Wrap so we can extract the body reliably; suppress libxml HTML5 warnings.
        $wrapped = '<?xml encoding="UTF-8"><div id="__sanitizer_root__">'.$html.'</div>';

        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('__sanitizer_root__');

        if (! $root) {
            return null;
        }

        self::sanitizeChildren($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        $out = trim($out);

        return $out === '' ? null : $out;
    }

    private static function sanitizeChildren(DOMNode $node): void
    {
        // Iterate over a static copy — we mutate the live child list.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                self::sanitizeElement($child);
            }
            // Text and comment nodes are kept as-is (comments carry Gutenberg markers).
        }
    }

    private static function sanitizeElement(DOMElement $el): void
    {
        $tag = strtolower($el->tagName);

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            // Disallowed tag: drop the element but keep its (sanitized) children,
            // except for script/style whose text content is itself dangerous.
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button'], true)) {
                $el->parentNode?->removeChild($el);

                return;
            }

            self::sanitizeChildren($el);
            self::unwrap($el);

            return;
        }

        $allowed = array_merge(self::ALLOWED_ATTRS['*'], self::ALLOWED_ATTRS[$tag] ?? []);

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $value = $attr->nodeValue;

            $drop = ! in_array($name, $allowed, true)
                || str_starts_with($name, 'on')
                || self::isDangerousUrl($name, $value);

            if ($drop) {
                $el->removeAttribute($attr->nodeName);
            }
        }

        // Harden external links.
        if ($tag === 'a' && $el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }

        self::sanitizeChildren($el);
    }

    private static function isDangerousUrl(string $attr, ?string $value): bool
    {
        if (! in_array($attr, ['href', 'src'], true) || $value === null) {
            return false;
        }

        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;

        return str_starts_with($normalized, 'javascript:')
            || str_starts_with($normalized, 'data:text/html')
            || str_starts_with($normalized, 'vbscript:');
    }

    /**
     * Replace an element with its children (keep content, drop the wrapper tag).
     */
    private static function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;

        if (! $parent) {
            return;
        }

        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }

        $parent->removeChild($el);
    }
}
