<?php

namespace App\Support;

use ArPHP\I18N\Arabic;
use DOMDocument;
use DOMXPath;

/**
 * Prepares Arabic text for DomPDF, which has no Arabic glyph shaping or
 * bidirectional reordering of its own. Strings are converted to their Arabic
 * presentation forms (connected letters) and reordered for correct visual
 * right-to-left display using the ar-php library.
 */
class ArabicText
{
    private static ?Arabic $arabic = null;

    /**
     * Shape and reorder a single string. Strings without Arabic characters are
     * returned unchanged, so it is safe to apply to any value.
     */
    public static function forPdf(?string $text): string
    {
        $text = (string) $text;

        if ($text === '' || ! preg_match('/\p{Arabic}/u', $text)) {
            return $text;
        }

        // A large max-chars keeps the value on one logical line (DomPDF handles
        // wrapping via CSS); hindo=false preserves Western digits (e.g. 2026).
        return self::arabic()->utf8Glyphs($text, 10000, false);
    }

    /**
     * Shape every text node of an HTML document so both static labels and
     * dynamic data render correctly, then return the serialized HTML.
     */
    public static function shapeHtml(string $html): string
    {
        if (! preg_match('/\p{Arabic}/u', $html)) {
            return $html;
        }

        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8"?>'.$html,
            LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);

        // Skip <style>/<script> text so CSS/JS is never reshaped.
        foreach ($xpath->query('//text()[not(ancestor::style) and not(ancestor::script)]') as $node) {
            if ($node->nodeValue !== null && preg_match('/\p{Arabic}/u', $node->nodeValue)) {
                $node->nodeValue = self::forPdf($node->nodeValue);
            }
        }

        return (string) preg_replace('/^<\?xml.*?\?>\s*/', '', (string) $document->saveHTML());
    }

    private static function arabic(): Arabic
    {
        return self::$arabic ??= new Arabic;
    }
}
