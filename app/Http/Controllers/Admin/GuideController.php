<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GuideStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuideRequest;
use App\Models\EquipmentGuide;
use App\Models\GuideCategory;
use App\Services\GuideService;
use App\Support\GuideContentRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function __construct(private readonly GuideService $guides) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EquipmentGuide::class);

        $guides = EquipmentGuide::query()
            ->with(['category', 'author'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->string('q').'%'))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.guides.index', [
            'guides' => $guides,
            'statuses' => GuideStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', EquipmentGuide::class);

        return view('admin.guides.form', [
            'guide' => new EquipmentGuide(['status' => GuideStatus::Draft]),
            'categories' => GuideCategory::query()->orderBy('sort_order')->get(),
            'statuses' => [GuideStatus::Draft, GuideStatus::Published],
        ]);
    }

    public function store(GuideRequest $request): RedirectResponse
    {
        $this->authorize('create', EquipmentGuide::class);

        $data = $request->validated();

        if ($data['status'] === GuideStatus::Published->value) {
            $this->authorize('publish', new EquipmentGuide);
        }

        $guide = $this->guides->create($data, $request->user());

        return redirect()->route('admin.guides.index')->with('status', "Panduan {$guide->title} dibuat.");
    }

    public function edit(EquipmentGuide $guide): View
    {
        $this->authorize('update', $guide);

        return view('admin.guides.form', [
            'guide' => $guide->load('sources'),
            'categories' => GuideCategory::query()->orderBy('sort_order')->get(),
            'statuses' => [GuideStatus::Draft, GuideStatus::Published],
        ]);
    }

    public function update(GuideRequest $request, EquipmentGuide $guide): RedirectResponse
    {
        $this->authorize('update', $guide);

        $data = $request->validated();

        if ($data['status'] === GuideStatus::Published->value && $guide->status !== GuideStatus::Published) {
            $this->authorize('publish', $guide);
        }

        $this->guides->update($guide, $data, $request->user());

        return redirect()->route('admin.guides.index')->with('status', 'Panduan diperbarui.');
    }

    public function publish(Request $request, EquipmentGuide $guide): RedirectResponse
    {
        $this->authorize('publish', $guide);
        $this->guides->publish($guide, $request->user());

        return back()->with('status', 'Panduan diterbitkan.');
    }

    public function unpublish(Request $request, EquipmentGuide $guide): RedirectResponse
    {
        $this->authorize('publish', $guide);
        $this->guides->unpublish($guide, $request->user());

        return back()->with('status', 'Panduan ditarik menjadi draf.');
    }

    public function archive(Request $request, EquipmentGuide $guide): RedirectResponse
    {
        $this->authorize('archive', $guide);
        $this->guides->archive($guide, $request->user());

        return back()->with('status', 'Panduan diarsipkan.');
    }

    public function preview(Request $request): JsonResponse
    {
        // Pratinjau untuk panduan yang sudah ada cukup membutuhkan izin update;
        // panduan baru membutuhkan izin create.
        $guide = $request->filled('guide_id')
            ? EquipmentGuide::query()->find($request->integer('guide_id'))
            : null;

        if ($guide !== null) {
            $this->authorize('update', $guide);
        } else {
            $this->authorize('create', EquipmentGuide::class);
        }

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:'.(int) config('gearora.guides.max_content_kb', 200) * 1024],
        ]);

        $content = (string) ($validated['content'] ?? '');

        return response()->json([
            'html' => GuideContentRenderer::render($content),
            'minutes' => GuideContentRenderer::readingMinutes($content),
        ]);
    }

    public function destroy(Request $request, EquipmentGuide $guide): RedirectResponse
    {
        $this->authorize('delete', $guide);

        $this->guides->deleteCover($guide);
        $this->guides->delete($guide, $request->user());

        return redirect()->route('admin.guides.index')->with('status', 'Panduan dihapus.');
    }
}
