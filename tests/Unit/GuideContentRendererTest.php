<?php

namespace Tests\Unit;

use App\Support\GuideContentRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Renderer konten panduan harus aman: semua HTML mentah di-escape, iframe
 * arbitrer tidak pernah lolos, embed hanya YouTube nocookie dengan ID valid,
 * dan gambar hanya aset media internal.
 */
class GuideContentRendererTest extends TestCase
{
    public function test_raw_html_and_iframe_are_escaped(): void
    {
        $html = GuideContentRenderer::render('<script>alert(1)</script>'."\n\n".'<iframe src="https://evil.test"></iframe>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<iframe src="https://evil.test"', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;iframe', $html);
    }

    public function test_expected_markdown_is_rendered(): void
    {
        $html = GuideContentRenderer::render("# Judul\n\n**tebal** dan *miring* dan `kode`\n\n- satu\n- dua\n\n1. a\n2. b");

        $this->assertStringContainsString('<h2>Judul</h2>', $html);
        $this->assertStringContainsString('<strong>tebal</strong>', $html);
        $this->assertStringContainsString('<em>miring</em>', $html);
        $this->assertStringContainsString('<code>kode</code>', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<ol>', $html);
    }

    public function test_safe_links_render_and_javascript_scheme_is_stripped(): void
    {
        $html = GuideContentRenderer::render('[aman](https://gearora.test) dan [palsu](javascript:alert(1))');

        $this->assertStringContainsString('href="https://gearora.test"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringContainsString('palsu', $html);
    }

    public function test_youtube_embed_uses_nocookie_and_valid_id_only(): void
    {
        $html = GuideContentRenderer::render('@youtube[dQw4w9WgXcQ]');
        $this->assertStringContainsString('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $html);

        $bad = GuideContentRenderer::render('@youtube[short]');
        $this->assertStringNotContainsString('<iframe', $bad);
    }

    public function test_inline_images_allow_local_media_only(): void
    {
        $local = GuideContentRenderer::render('![tenda](/media/tenda.jpg)');
        $this->assertStringContainsString('src="/media/tenda.jpg"', $local);

        $remote = GuideContentRenderer::render('![tenda](https://evil.test/tenda.jpg)');
        $this->assertStringNotContainsString('<img', $remote);
    }

    public function test_reading_minutes_and_plain_text(): void
    {
        $this->assertSame(1, GuideContentRenderer::readingMinutes('kata'));
        $this->assertSame('', GuideContentRenderer::plain(null));
        $this->assertSame('a b', GuideContentRenderer::plain("<p>a\n b</p>"));
    }
}
