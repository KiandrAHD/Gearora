<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuideCategoryRequest;
use App\Models\GuideCategory;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GuideCategoryController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(): View
    {
        $this->authorize('viewAny', GuideCategory::class);

        return view('admin.guide-categories.index', [
            'categories' => GuideCategory::query()->withCount('guides')->orderBy('sort_order')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', GuideCategory::class);

        return view('admin.guide-categories.form', ['category' => new GuideCategory]);
    }

    public function store(GuideCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', GuideCategory::class);

        $category = GuideCategory::query()->create($request->validated());
        $this->logger->log('guide_category.created', $category, "Kategori panduan {$category->name} dibuat.");

        return redirect()->route('admin.guide-categories.index')->with('status', 'Kategori panduan dibuat.');
    }

    public function edit(GuideCategory $guideCategory): View
    {
        $this->authorize('update', $guideCategory);

        return view('admin.guide-categories.form', ['category' => $guideCategory]);
    }

    public function update(GuideCategoryRequest $request, GuideCategory $guideCategory): RedirectResponse
    {
        $this->authorize('update', $guideCategory);

        $guideCategory->fill($request->validated())->save();
        $this->logger->log('guide_category.updated', $guideCategory, "Kategori panduan {$guideCategory->name} diperbarui.");

        return redirect()->route('admin.guide-categories.index')->with('status', 'Kategori panduan diperbarui.');
    }

    public function destroy(GuideCategory $guideCategory): RedirectResponse
    {
        $this->authorize('delete', $guideCategory);

        if ($guideCategory->guides()->exists()) {
            $guideCategory->forceFill(['is_active' => false])->save();
            $this->logger->log('guide_category.deactivated', $guideCategory, "Kategori panduan {$guideCategory->name} dinonaktifkan (memiliki panduan).");

            return redirect()->route('admin.guide-categories.index')
                ->with('status', 'Kategori memiliki panduan sehingga dinonaktifkan, bukan dihapus.');
        }

        $name = $guideCategory->name;
        $guideCategory->delete();
        $this->logger->log('guide_category.deleted', null, "Kategori panduan {$name} dihapus.", ['name' => $name]);

        return redirect()->route('admin.guide-categories.index')->with('status', 'Kategori panduan dihapus.');
    }
}
