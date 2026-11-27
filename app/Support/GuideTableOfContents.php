<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Menambahkan anchor id pada heading hasil GuideContentRenderer dan
 * menghasilkan daftar isi untuk halaman detail panduan.
 *
 * Renderer sengaja tidak menambahkan id (agar output Markdown tetap minimal);
 * anotasi id dilakukan di lapisan tampilan setelah konten dirender aman.
 */
final class GuideTableOfContents
{
    /**
     * @return array{toc: array<int, array{id: string, text: string, level: int}>, html: string}
     */
    public static function build(string $html): array
    {
        $toc = [];
        $used = [];

        $annotated = preg_replace_callback(
            '~<(h2|h3)>(.*?)</\1>~s',
            static function (array $matches) use (&$toc, &$used): string {
                $level = (int) substr($matches[1], 1);
                $text = trim(strip_tags($matches[2]));

                if ($text === '') {
                    return $matches[0];
                }

                $base = Str::slug($text);

                if ($base === '') {
                    $base = 'bagian';
                }

                $slug = $base;
                $suffix = 2;

                while (isset($used[$slug])) {
                    $slug = $base.'-'.$suffix;
                    $suffix++;
                }

                $used[$slug] = true;
                $toc[] = ['id' => $slug, 'text' => $text, 'level' => $level];

                return '<'.$matches[1].' id="'.$slug.'">'.$matches[2].'</'.$matches[1].'>';
            },
            $html,
        ) ?? $html;

        return ['toc' => $toc, 'html' => $annotated];
    }
}
