@php($editing = $guide->exists)
<x-layouts.admin :title="$editing ? 'Ubah panduan' : 'Panduan baru'">
    @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
    <div class="panel guide-studio" x-data="guideEditor({ previewUrl: '{{ route('admin.guides.preview') }}', uploadUrl: '{{ route('admin.guides.media.store') }}', guideId: {{ $editing ? $guide->getKey() : 'null' }}, initial: @js(old('content', $guide->content)) })">
        <div class="panel-head"><div><h2>{{ $editing ? 'Studio panduan' : 'Studio panduan baru' }}</h2><p class="field-hint">Editor Markdown aman. Pratinjau memakai renderer publik yang sama; HTML mentah tidak pernah dieksekusi.</p></div><span class="field-hint" x-text="minutes + ' menit baca'"></span></div>
        <p class="guide-studio-note">Alur singkat: tulis judul, susun kerangka dengan heading, tambahkan gambar lokal (bukan hotlink), lalu pratinjau sebelum menerbitkan.</p>
        <form class="stack-form" method="post" enctype="multipart/form-data" action="{{ $editing ? route('admin.guides.update', $guide) : route('admin.guides.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif
            <label><span>Judul</span><input name="title" value="{{ old('title', $guide->title) }}" required maxlength="200"></label>
            <div class="form-row">
                <label><span>Slug URL</span><input name="slug" value="{{ old('slug', $guide->slug) }}" placeholder="otomatis dari judul"><span class="field-hint">Slug terbit stabil sampai diubah manual.</span></label>
                <label><span>Kategori</span><select name="guide_category_id"><option value="">Tanpa kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((int) old('guide_category_id', $guide->guide_category_id) === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
            </div>
            <label><span>Ringkasan</span><textarea name="excerpt" rows="2" maxlength="500">{{ old('excerpt', $guide->excerpt) }}</textarea></label>

            <div class="guide-editor-toolbar" role="toolbar" aria-label="Format konten">
                <button type="button" @click="wrap('**', '**', 'teks tebal')"><strong>B</strong></button>
                <button type="button" @click="wrap('*', '*', 'teks miring')"><em>I</em></button>
                <button type="button" @click="prefix('# ', 'Judul bagian')">H2</button>
                <button type="button" @click="prefix('- ', 'Item daftar')">Daftar</button>
                <button type="button" @click="wrap('[', '](https://example.com)', 'teks tautan')">Tautan</button>
                <button type="button" @click="$refs.inlineImage.click()">
                    Unggah gambar
                </button>
                <button type="button" @click="insertYoutubeUrl()">YouTube URL</button>
                <button type="button" @click="loadPreview()">Pratinjau</button>
                <span class="field-hint" x-show="uploading" x-cloak>Gambar sedang diunggah…</span>
            </div>
            <p class="field-hint" x-show="uploadError" x-text="uploadError" role="alert" x-cloak></p>
            <input type="file" x-ref="inlineImage" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="uploadImage($event)">
            <label><span>Konten (Markdown aman)</span><textarea x-ref="content" x-model="content" @input.debounce.600ms="loadPreview()" name="content" rows="18" required></textarea><span class="field-hint">HTML dan iframe mentah selalu jadi teks. Gambar hanya `/media/...`; YouTube: `@youtube[VIDEO_ID]` (ID 11 karakter).</span></label>
            <section class="guide-preview-panel" aria-live="polite"><div class="panel-head"><h3>Pratinjau publik</h3><span class="field-hint" x-show="loading">Memuat…</span></div><div class="guide-content" x-html="html"></div></section>

            <label><span>Sampul (JPG, PNG, WEBP)</span><input name="cover" type="file" accept="image/jpeg,image/png,image/webp">@if($guide->cover_image_path)<span class="field-hint">Sampul saat ini tersimpan.</span>@endif</label>
            <label class="checkbox-label"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $guide->is_featured))> <span>Tampilkan sebagai panduan unggulan</span></label>
            <div class="form-row">
                <label><span>SEO title</span><input name="seo_title" value="{{ old('seo_title', $guide->seo_title) }}" maxlength="200"></label>
                <label><span>SEO description</span><input name="seo_description" value="{{ old('seo_description', $guide->seo_description) }}" maxlength="320"></label>
            </div>
            <label><span>Status</span><select name="status">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $guide->status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
            <div class="form-actions"><button class="btn-primary" type="submit">{{ $editing ? 'Simpan perubahan' : 'Buat panduan' }}</button><a class="text-link" href="{{ route('admin.guides.index') }}">Batal</a></div>
        </form>
        @if($editing)
            <div class="form-actions">
                @if($guide->status->value === 'published')<form method="post" action="{{ route('admin.guides.unpublish', $guide) }}">@csrf<button class="text-link" type="submit">Tarik jadi draf</button></form>@else<form method="post" action="{{ route('admin.guides.publish', $guide) }}">@csrf<button class="text-link" type="submit">Terbitkan</button></form>@endif
                <form method="post" action="{{ route('admin.guides.archive', $guide) }}">@csrf<button class="text-link" type="submit">Arsipkan</button></form>
            </div>
        @endif
    </div>
</x-layouts.admin>
