<?php

namespace App\Http\Controllers\PublicSite;

use App\Data\TripPlanData;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSite\TripPlannerRequest;
use App\Services\TripRecommendationService;
use Illuminate\View\View;

class TripPlannerController extends Controller
{
    public function __construct(private readonly TripRecommendationService $recommendations) {}

    public function index(): View
    {
        return view('trip-planner.index');
    }

    public function recommend(TripPlannerRequest $request): View
    {
        $plan = TripPlanData::fromArray($request->validated());

        return view('trip-planner.index', [
            'plan' => $plan,
            'results' => $this->recommendations->recommend($plan),
        ]);
    }
}
