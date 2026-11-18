@php($editing = $category->exists)
<x-layouts.admin :title="$editing ? 'Ubah kategori panduan' : 'Kategori panduan baru'">
    @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
    <div class="panel">
        <form class="stack-form" method="post" action="{{ $editing ? route('admin.guide-categories.update', $category) : route('admin.guide-categories.store') }}">
            @csrf @if($editing) @method('PUT') @endif
            <label><span>Nama</span><input name="name" value="{{ old('name', $category->name) }}" required></label>
            <label><span>Slug</span><input name="slug" value="{{ old('slug', $category->slug) }}" placeholder="otomatis dari nama"></label>
            <label><span>Deskripsi</span><textarea name="description" rows="3">{{ old('description', $category->description) }}</textarea></label>
            <label><span>Urutan</span><input type="number" name="sort_order" min="0" value="{{ old('sort_order', $category->sort_order) }}"></label>
            <label class="check-row"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $category->is_active))> <span>Aktif</span></label>
            <div class="form-actions"><button class="btn-primary" type="submit">Simpan</button><a class="text-link" href="{{ route('admin.guide-categories.index') }}">Batal</a></div>
        </form>
    </div>
</x-layouts.admin>
