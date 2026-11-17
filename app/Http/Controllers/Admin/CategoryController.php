<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->withCount('products')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.form', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $category = Category::query()->create($request->validated());
        $this->logger->log('category.created', $category, "Kategori {$category->name} dibuat.", ['name' => $category->name]);

        return redirect()->route('admin.categories.index')->with('status', 'Kategori dibuat.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.form', ['category' => $category]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $category->fill($request->validated())->save();
        $this->logger->log('category.updated', $category, "Kategori {$category->name} diperbarui.");

        return redirect()->route('admin.categories.index')->with('status', 'Kategori diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->products()->exists()) {
            $category->forceFill(['is_active' => false])->save();
            $this->logger->log('category.deactivated', $category, "Kategori {$category->name} dinonaktifkan (memiliki produk).");

            return redirect()->route('admin.categories.index')
                ->with('status', 'Kategori memiliki produk sehingga dinonaktifkan, bukan dihapus.');
        }

        $name = $category->name;
        $category->delete();
        $this->logger->log('category.deleted', null, "Kategori {$name} dihapus.", ['name' => $name]);

        return redirect()->route('admin.categories.index')->with('status', 'Kategori dihapus.');
    }
}
