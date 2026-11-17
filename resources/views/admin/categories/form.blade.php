@php($editing = $category->exists)
<x-layouts.admin :title="$editing ? 'Ubah kategori' : 'Kategori baru'">
    @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
    <div class="panel">
        <form class="stack-form" method="post" action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif
            <label><span>Nama</span><input name="name" type="text" value="{{ old('name', $category->name) }}" required></label>
            <label><span>Slug</span><input name="slug" type="text" value="{{ old('slug', $category->slug) }}" placeholder="otomatis dari nama"></label>
            <label><span>Deskripsi</span><textarea name="description" rows="3">{{ old('description', $category->description) }}</textarea></label>
            <div class="form-row">
                <label><span>Ikon</span><input name="icon" type="text" value="{{ old('icon', $category->icon) }}" placeholder="tent / bag / fire"></label>
                <label><span>Urutan</span><input name="sort_order" type="number" min="0" value="{{ old('sort_order', $category->sort_order) }}"></label>
            </div>
            <label class="check-row"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $category->is_active))> <span>Aktif</span></label>
            <div class="form-actions"><button class="btn-primary" type="submit">{{ $editing ? 'Simpan perubahan' : 'Buat kategori' }}</button><a class="text-link" href="{{ route('admin.categories.index') }}">Batal</a></div>
        </form>
        @if($editing)
            <form class="danger-zone" method="post" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Hapus atau nonaktifkan kategori ini?')">
                @csrf @method('DELETE')
                <button class="btn-danger" type="submit">Hapus kategori</button>
                <p class="field-hint">Kategori yang masih memiliki produk akan dinonaktifkan, bukan dihapus.</p>
            </form>
        @endif
    </div>
</x-layouts.admin>
