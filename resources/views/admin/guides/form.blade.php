@php($editing = $guide->exists)
<x-layouts.admin :title="$editing ? 'Ubah panduan' : 'Panduan baru'">
    @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
    <div class="panel">
        <form class="stack-form" method="post" enctype="multipart/form-data" action="{{ $editing ? route('admin.guides.update', $guide) : route('admin.guides.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif
            <label><span>Judul</span><input name="title" value="{{ old('title', $guide->title) }}" required maxlength="200"></label>
            <label><span>Slug URL</span><input name="slug" value="{{ old('slug', $guide->slug) }}" placeholder="otomatis dari judul"><span class="field-hint">Slug terbit tidak berubah saat judul diubah, kecuali diisi ulang.</span></label>
            <label><span>Kategori</span><select name="guide_category_id"><option value="">Tanpa kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((int) old('guide_category_id', $guide->guide_category_id) === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label><span>Ringkasan</span><textarea name="excerpt" rows="2" maxlength="500">{{ old('excerpt', $guide->excerpt) }}</textarea></label>
            <label><span>Konten (Markdown sederhana)</span><textarea name="content" rows="14" required>{{ old('content', $guide->content) }}</textarea><span class="field-hint">Mendukung judul #, daftar, **tebal**, dan `kode`. HTML akan diamankan.</span></label>
            <label><span>Sampul (JPG, PNG, WEBP)</span><input name="cover" type="file" accept="image/jpeg,image/png,image/webp">@if($guide->cover_image_path)<span class="field-hint">Sampul saat ini tersimpan.</span>@endif</label>
            <div class="form-row">
                <label><span>SEO title</span><input name="seo_title" value="{{ old('seo_title', $guide->seo_title) }}" maxlength="200"></label>
                <label><span>SEO description</span><input name="seo_description" value="{{ old('seo_description', $guide->seo_description) }}" maxlength="320"></label>
            </div>
            <label><span>Status</span><select name="status">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $guide->status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
            <div class="form-actions"><button class="btn-primary" type="submit">{{ $editing ? 'Simpan perubahan' : 'Buat panduan' }}</button><a class="text-link" href="{{ route('admin.guides.index') }}">Batal</a></div>
        </form>
        @if($editing)
            <div class="form-actions">
                @if($guide->status->value === 'published')
                    <form method="post" action="{{ route('admin.guides.unpublish', $guide) }}">@csrf<button class="text-link" type="submit">Tarik jadi draf</button></form>
                @else
                    <form method="post" action="{{ route('admin.guides.publish', $guide) }}">@csrf<button class="text-link" type="submit">Terbitkan</button></form>
                @endif
                <form method="post" action="{{ route('admin.guides.archive', $guide) }}">@csrf<button class="text-link" type="submit">Arsipkan</button></form>
            </div>
        @endif
    </div>
</x-layouts.admin>
