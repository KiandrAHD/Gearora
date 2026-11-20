<?php

namespace App\Support;

/**
 * Renderer konten panduan yang aman (subset Markdown).
 *
 * Seluruh HTML di-escape terlebih dahulu, lalu hanya sintaks terbatas yang
 * diubah menjadi markup. Dengan demikian konten yang diketik Admin tidak dapat
 * menyuntikkan script/HTML berbahaya (stored XSS).
 *
 * Sintaks didukung: `#`/`##`/`###`, daftar `-`/`1.`, blockquote `>`, pemisah
 * `---`, **tebal**, *miring*, `kode`, [tautan](url), gambar inline lokal
 * `![alt](path)`, embed aman `@youtube[VIDEO_ID]`, serta URL YouTube mandiri
 * (watch/shorts/youtu.be) yang dinormalisasi menjadi embed.
 *
 * Keamanan:
 * - Tidak ada HTML mentah/iframe arbitrer; hanya iframe YouTube yang dibentuk
 *   dari ID tervalidasi ke `https://www.youtube-nocookie.com/embed/{id}`.
 * - Gambar inline hanya untuk aset lokal (`/media/...` atau path relatif aplikasi),
 *   bukan hotlink eksternal.
 * - Tautan hanya skema `http(s)`, `mailto:`, atau path internal.
 */
final class GuideContentRenderer
{
    private const YOUTUBE_ID = '[A-Za-z0-9_-]{11}';

    public static function render(?string $content): string
    {
        $content = trim((string) $content);

        if ($content === '') {
            return '';
        }

        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // URL YouTube mandiri (satu baris) dinormalisasi menjadi penanda embed
        // SEBELUM escaping. Hanya ID 11 karakter tervalidasi yang dipakai ulang,
        // sehingga URL/parameter tambahan tidak pernah masuk ke output.
        $content = preg_replace_callback(
            '~^[ \t]*https?://(?:www\.)?(?:youtube\.com/(?:watch\?(?:[^\s]*&)?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[^\s]*)[ \t]*$~mi',
            static fn (array $matches): string => '@youtube['.$matches[1].']',
            $content,
        ) ?? $content;

        $escaped = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $blocks = preg_split("/\n{2,}/", $escaped) ?: [];
        $html = [];

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            if (preg_match('~^(?:-{3,}|\*{3,}|_{3,})$~', $block) === 1) {
                $html[] = '<hr>';

                continue;
            }

            // Embed YouTube sebagai blok mandiri (bukan dibungkus <p>).
            if (preg_match('~^@youtube\['.self::YOUTUBE_ID.'\]$~', $block) === 1) {
                $html[] = self::youtubeEmbed($block);

                continue;
            }

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

            $lines = explode("\n", $block);

            if (self::isList($lines, '~^&gt;[ \t]?~')) {
                $items = array_map(
                    fn (string $line) => self::inline(preg_replace('~^&gt;[ \t]?~', '', $line, 1) ?? $line),
                    $lines,
                );
                $html[] = '<blockquote><p>'.implode('<br>', $items).'</p></blockquote>';

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

        // Gambar inline: hanya aset lokal, bukan hotlink eksternal.
        $text = preg_replace_callback(
            '~!\[([^\]]*)\]\(([^)\s]+)\)~',
            static function (array $matches): string {
                if (! self::isLocalMedia($matches[2])) {
                    return $matches[1];
                }

                return '<img src="'.$matches[2].'" alt="'.$matches[1].'" loading="lazy" decoding="async">';
            },
            $text,
        ) ?? $text;

        // Embed YouTube aman (nocookie, ID tervalidasi).
        $text = preg_replace_callback(
            '~@youtube\['.self::YOUTUBE_ID.'\]~',
            static fn (array $matches): string => self::youtubeEmbed($matches[0]),
            $text,
        ) ?? $text;

        // Tautan aman.
        $text = preg_replace_callback(
            '~\[([^\]]+)\]\(([^)\s]+)\)~',
            static function (array $matches): string {
                if (! self::isSafeUrl($matches[2])) {
                    return $matches[1];
                }

                $external = ! str_starts_with($matches[2], '/') && ! str_starts_with($matches[2], 'mailto:');

                return '<a href="'.$matches[2].'"'
                    .($external ? ' target="_blank" rel="noopener noreferrer nofollow"' : '')
                    .'>'.$matches[1].'</a>';
            },
            $text,
        ) ?? $text;

        $text = preg_replace('~[*]([^*\n]+)[*]~', '<em>$1</em>', $text) ?? $text;
        $text = preg_replace('~`(.+?)`~s', '<code>$1</code>', $text) ?? $text;

        return $text;
    }

    private static function youtubeEmbed(string $marker): string
    {
        if (preg_match('~^@youtube\[('.self::YOUTUBE_ID.')\]$~', $marker, $id) !== 1) {
            return '';
        }

        return '<span class="guide-embed"><iframe src="https://www.youtube-nocookie.com/embed/'.$id[1]
            .'" title="Video panduan" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></span>';
    }

    /**
     * Ambil ID video dari URL YouTube yang didukung, atau null bila tidak valid.
     * Dipakai Studio untuk mengubah URL menjadi penanda embed yang aman.
     */
    public static function youtubeId(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (preg_match('~^(?:https?://)?(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:[^\s]*&)?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[?&#][^\s]*)?$~i', $url, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /** Path media internal aplikasi (bukan URL absolut eksternal). */
    private static function isLocalMedia(string $url): bool
    {
        if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $url) === 1 || str_starts_with($url, '//')) {
            return false;
        }

        return str_starts_with($url, '/media/') || str_starts_with($url, 'media/');
    }

    private static function isSafeUrl(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return true;
        }

        return preg_match('~^(?:https?://|mailto:)~i', $url) === 1;
    }

    /** Teks polos untuk meta description / ringkasan. */
    public static function plain(?string $content, int $limit = 180): string
    {
        $text = trim(preg_replace("/[ \t\n\r]+/", ' ', strip_tags((string) $content)) ?? '');

        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit - 1).'...' : $text;
    }

    /** Estimasi waktu baca dalam menit (>= 1). */
    public static function readingMinutes(?string $content, int $wordsPerMinute = 200): int
    {
        $text = trim(preg_replace("/\s+/", ' ', strip_tags((string) $content)) ?? '');
        $words = $text === '' ? 0 : count(preg_split('/\s+/', $text) ?: []);

        return max(1, (int) ceil($words / max(1, $wordsPerMinute)));
    }
}
