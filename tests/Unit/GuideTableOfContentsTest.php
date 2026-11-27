<?php

namespace Tests\Unit;

use App\Support\GuideTableOfContents;
use PHPUnit\Framework\TestCase;

class GuideTableOfContentsTest extends TestCase
{
    public function test_build_adds_stable_heading_anchors_and_preserves_safe_html(): void
    {
        $result = GuideTableOfContents::build('<h2>Pilih Tenda</h2><p>Aman.</p><h3>Cek ukuran</h3>');

        $this->assertSame([
            ['id' => 'pilih-tenda', 'text' => 'Pilih Tenda', 'level' => 2],
            ['id' => 'cek-ukuran', 'text' => 'Cek ukuran', 'level' => 3],
        ], $result['toc']);
        $this->assertSame('<h2 id="pilih-tenda">Pilih Tenda</h2><p>Aman.</p><h3 id="cek-ukuran">Cek ukuran</h3>', $result['html']);
    }

    public function test_duplicate_heading_gets_unique_anchor(): void
    {
        $result = GuideTableOfContents::build('<h2>Checklist</h2><h2>Checklist</h2>');

        $this->assertSame('checklist', $result['toc'][0]['id']);
        $this->assertSame('checklist-2', $result['toc'][1]['id']);
    }
}
