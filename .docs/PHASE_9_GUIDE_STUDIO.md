# PHASE 9 — Equipment Guides Editorial and Studio

Tanggal: 20 November 2026

## Public editorial

- Hero editorial.
- Panduan unggulan (`is_featured`, maksimum tiga).
- Kartu artikel dengan cover/fallback, excerpt, kategori, tanggal, dan waktu baca.
- Detail punya lede, related guides, sumber, metadata SEO, serta disclaimer informasi umum.

## Admin Studio

- Toolbar Markdown: bold, italic, heading, list, link.
- Unggah gambar inline (JPG/PNG/WEBP) → sisip `![alt](/media/...)`; endpoint `admin.guides.media.store` dengan throttle.
- Tempel URL YouTube (watch/youtu.be/shorts) → otomatis jadi `@youtube[VIDEO_ID]` tervalidasi.
- Preview live via `admin.guides.preview`.
- Estimasi waktu baca server-rendered.
- Checkbox featured.

## Content safety

Renderer escape-first (`GuideContentRenderer`):

- HTML mentah aman sebagai teks.
- Tautan hanya URL `http(s)`, `mailto:`, atau internal.
- Inline image hanya `/media/...` atau `media/...`; hotlink ditolak.
- YouTube hanya `@youtube[VIDEO_ID]`, menghasilkan `youtube-nocookie.com/embed/{validated-id}`.
- Iframe arbitrer tidak didukung.

## Coverage

`GuideContentRendererTest`, `AdminGuideStudioTest`, `GuideMediaUploadTest`, `EquipmentGuideTest`, dan `AdminEquipmentGuideTest`.
