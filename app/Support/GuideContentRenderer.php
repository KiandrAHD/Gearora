<?php

namespace App\Support;

/**
 * Renderer konten panduan yang aman (subset Markdown).
 *
 * Seluruh HTML di-escape terlebih dahulu, lalu hanya sintaks terbatas yang
 * diubah menjadi markup. Dengan demikian konten yang diketik Admin tidak dapat
 * menyuntikkan script/HTML berbahaya (stored XSS).
 */
final class GuideContentRenderer
{
    public static function render(?string $content): string
    {
        $content = trim((string) $content);

        if ($content === '') {
            return '';
        }

        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $escaped = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $blocks = preg_split("/\n{2,}/", $escaped) ?: [];
        $html = [];

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            $lines = explode("\n", $block);

            if (preg_match('~^###[ \t]+(.+)$~', $block, $matches) === 1) {
                $html[] = '<h4>'.self::inline($matches[1]).'</h4>';

                continue;
            }

            if (preg_match('~^##[ \t]+(.+)$~', $block, $matches) === 1) {
                $html[] = '<h3>'.self::inline($matches[1]).'</h3>';

                continue;
            }

            if (preg_match('~^#[ \t]+(.+)$~', $block, $matches) === 1) {
                $html[] = '<h2>'.self::inline($matches[1]).'</h2>';

                continue;
            }

            if (self::isList($lines, '~^-[ \t]+~')) {
                $items = array_map(
                    fn (string $line) => '<li>'.self::inline(self::stripPrefix($line, '~^-[ \t]+~')).'</li>',
                    $lines,
                );
                $html[] = '<ul>'.implode('', $items).'</ul>';

                continue;
            }

            if (self::isList($lines, '~^[0-9]+[.][ \t]+~')) {
                $items = array_map(
                    fn (string $line) => '<li>'.self::inline(self::stripPrefix($line, '~^[0-9]+[.][ \t]+~')).'</li>',
                    $lines,
                );
                $html[] = '<ol>'.implode('', $items).'</ol>';

                continue;
            }

            $html[] = '<p>'.implode('<br>', array_map([self::class, 'inline'], $lines)).'</p>';
        }

        return implode("\n", $html);
    }

    /** @param array<int, string> $lines */
    private static function isList(array $lines, string $pattern): bool
    {
        if ($lines === []) {
            return false;
        }

        foreach ($lines as $line) {
            if (preg_match($pattern, $line) !== 1) {
                return false;
            }
        }

        return true;
    }

    private static function stripPrefix(string $line, string $pattern): string
    {
        return preg_replace($pattern, '', $line, 1) ?? $line;
    }

    private static function inline(string $text): string
    {
        // Teks sudah di-escape; hanya penanda aman yang diubah.
        $text = preg_replace('~[*]{2}(.+?)[*]{2}~s', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('~`(.+?)`~s', '<code>$1</code>', $text) ?? $text;

        return $text;
    }

    /** Teks polos untuk meta description / ringkasan. */
    public static function plain(?string $content, int $limit = 180): string
    {
        $text = trim(preg_replace("/[ \t\n\r]+/", ' ', strip_tags((string) $content)) ?? '');

        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit - 1).'...' : $text;
    }
}
