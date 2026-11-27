<?php

namespace App\Http\Controllers\PublicSite;

use App\Enums\GuideStatus;
use App\Http\Controllers\Controller;
use App\Models\EquipmentGuide;
use App\Models\GuideCategory;
use App\Support\GuideContentRenderer;
use App\Support\GuideTableOfContents;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(Request $request): View
    {
        $guides = EquipmentGuide::query()
            ->published()
            ->search($request->string('q')->toString())
            ->when($request->filled('kategori'), fn ($query) => $query->whereHas('category', fn ($category) => $category->where('slug', $request->string('kategori'))))
            ->with('category')
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $featured = EquipmentGuide::query()
            ->published()
            ->featured()
            ->with('category')
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('guides.index', [
            'guides' => $guides,
            'featured' => $featured,
            'categories' => GuideCategory::query()->active()->orderBy('sort_order')->get(),
            'keyword' => $request->string('q')->toString(),
            'activeCategory' => $request->string('kategori')->toString(),
        ]);
    }

    public function show(EquipmentGuide $guide): View
    {
        abort_unless(
            $guide->status === GuideStatus::Published
                && $guide->published_at !== null
                && $guide->published_at->lessThanOrEqualTo(now()),
            404,
        );

        $guide->load(['category', 'sources', 'author']);

        $related = EquipmentGuide::query()
            ->published()
            ->whereKeyNot($guide->getKey())
            ->when($guide->guide_category_id, fn ($query) => $query->where('guide_category_id', $guide->guide_category_id))
            ->latest('published_at')
            ->limit(3)
            ->get();

        $rendered = GuideTableOfContents::build(GuideContentRenderer::render($guide->content));

        return view('guides.show', [
            'guide' => $guide,
            'related' => $related,
            'renderedContent' => $rendered['html'],
            'toc' => $rendered['toc'],
        ]);
    }
}
