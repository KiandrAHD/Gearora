<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::query()
            ->active()
            ->withCount(['products' => fn ($query) => $query->active()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(4)
            ->get();

        $featured = Product::query()
            ->active()
            ->with(['category', 'variants', 'primaryImage'])
            ->where('is_featured', true)
            ->orderBy('name')
            ->take(4)
            ->get();

        if ($featured->count() < 4) {
            $filled = Product::query()
                ->active()
                ->with(['category', 'variants', 'primaryImage'])
                ->whereNotIn('id', $featured->pluck('id'))
                ->latest()
                ->take(4 - $featured->count())
                ->get();

            $featured = $featured->concat($filled);
        }

        return view('home', [
            'categories' => $categories,
            'featuredProducts' => $featured,
        ]);
    }
}
