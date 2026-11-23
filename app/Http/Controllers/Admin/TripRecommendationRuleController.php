<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TripActivity;
use App\Enums\TripServiceMode;
use App\Enums\TripSpecialNeed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TripRecommendationRuleRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\TripRecommendationRule;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripRecommendationRuleController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TripRecommendationRule::class);

        $rules = TripRecommendationRule::query()
            ->with(['category', 'product', 'variant'])
            ->when($request->filled('activity'), fn ($query) => $query->where('activity', $request->string('activity')))
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderByDesc('priority')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.trip-rules.index', [
            'rules' => $rules,
            'activities' => TripActivity::ruleValues(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', TripRecommendationRule::class);

        return view('admin.trip-rules.form', $this->formData(new TripRecommendationRule([
            'activity' => TripActivity::ANY,
            'min_duration_days' => 1,
            'min_participants' => 1,
            'recommended_quantity' => 1,
            'priority' => 100,
            'is_active' => true,
        ])));
    }

    public function store(TripRecommendationRuleRequest $request): RedirectResponse
    {
        $this->authorize('create', TripRecommendationRule::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->getKey();
        $data['updated_by'] = $request->user()->getKey();

        $rule = TripRecommendationRule::query()->create($data);
        $this->logger->log('trip_rule.created', $rule, "Aturan trip {$rule->name} dibuat.", ['priority' => $rule->priority]);

        return redirect()->route('admin.trip-recommendations.index')->with('status', 'Aturan dibuat.');
    }

    public function edit(TripRecommendationRule $tripRecommendation): View
    {
        $this->authorize('update', $tripRecommendation);

        return view('admin.trip-rules.form', $this->formData($tripRecommendation));
    }

    public function update(TripRecommendationRuleRequest $request, TripRecommendationRule $tripRecommendation): RedirectResponse
    {
        $this->authorize('update', $tripRecommendation);

        $data = $request->validated();
        $data['updated_by'] = $request->user()->getKey();

        $tripRecommendation->fill($data)->save();
        $this->logger->log('trip_rule.updated', $tripRecommendation, "Aturan trip {$tripRecommendation->name} diperbarui.");

        return redirect()->route('admin.trip-recommendations.index')->with('status', 'Aturan diperbarui.');
    }

    public function destroy(TripRecommendationRule $tripRecommendation): RedirectResponse
    {
        $this->authorize('delete', $tripRecommendation);

        $name = $tripRecommendation->name;
        $tripRecommendation->delete();
        $this->logger->log('trip_rule.deleted', null, "Aturan trip {$name} dihapus.", ['name' => $name]);

        return redirect()->route('admin.trip-recommendations.index')->with('status', 'Aturan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(TripRecommendationRule $rule): array
    {
        return [
            'rule' => $rule,
            'activities' => TripActivity::ruleValues(),
            'specialNeeds' => TripSpecialNeed::cases(),
            'modes' => TripServiceMode::ruleValues(),
            'categories' => Category::query()->orderBy('name')->get(),
            'products' => Product::query()->orderBy('name')->limit(500)->get(),
        ];
    }
}
