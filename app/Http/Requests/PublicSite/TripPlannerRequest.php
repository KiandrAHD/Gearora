<?php

namespace App\Http\Requests\PublicSite;

use App\Enums\ExperienceLevel;
use App\Enums\TripActivity;
use App\Enums\TripServiceMode;
use App\Enums\TripSpecialNeed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TripPlannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'destination' => is_string($this->destination) ? trim($this->destination) : $this->destination,
            'location' => is_string($this->location) ? trim($this->location) : $this->location,
            'special_needs' => array_values(array_filter((array) $this->input('special_needs', []))),
            'mode' => $this->filled('mode') ? $this->input('mode') : TripServiceMode::Sewa->value,
            'experience' => $this->filled('experience') ? $this->input('experience') : ExperienceLevel::Pemula->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxDays = (int) config('gearora.trip.max_duration_days', 60);
        $maxParticipants = (int) config('gearora.trip.max_participants', 100);

        return [
            'activity' => ['required', 'string', Rule::in(TripActivity::values())],
            'destination' => ['required', 'string', 'min:2', 'max:120'],
            'location' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:'.$maxDays],
            'participants' => ['required', 'integer', 'min:1', 'max:'.$maxParticipants],
            'special_needs' => ['nullable', 'array'],
            'special_needs.*' => ['string', Rule::in(TripSpecialNeed::values())],
            'mode' => ['required', 'string', Rule::in(TripServiceMode::customerValues())],
            'experience' => ['required', 'string', Rule::in(ExperienceLevel::values())],
            'start_date' => ['nullable', 'date', 'required_with:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'required_with:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'activity.in' => 'Aktivitas tidak dikenal.',
            'mode.in' => 'Mode perlengkapan tidak valid.',
            'duration_days.min' => 'Durasi minimal 1 hari.',
            'participants.min' => 'Jumlah peserta minimal 1 orang.',
        ];
    }
}
