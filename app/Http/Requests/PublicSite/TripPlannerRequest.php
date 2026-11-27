<?php

namespace App\Http\Requests\PublicSite;

use App\Enums\ExperienceLevel;
use App\Enums\TripActivity;
use App\Enums\TripServiceMode;
use App\Enums\TripSpecialNeed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TripPlannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Destinasi fase ini fixed server-side. Hidden/input client tidak
        // dapat mengubah tujuan paket Gunung Prau.
        $this->merge([
            'destination' => 'Gunung Prau',
            'location' => is_string($this->location) ? trim($this->location) : $this->location,
            'basecamp' => is_string($this->basecamp) ? trim($this->basecamp) : $this->basecamp,
            'basecamp_other' => is_string($this->basecamp_other) ? trim($this->basecamp_other) : $this->basecamp_other,
            'special_needs' => array_values(array_filter((array) $this->input('special_needs', []))),
            'mode' => $this->filled('mode') ? $this->input('mode') : TripServiceMode::Sewa->value,
            'experience' => $this->filled('experience') ? $this->input('experience') : ExperienceLevel::Pemula->value,
        ]);

        $this->deriveReturnDate();
        $this->deriveBasecamp();
    }

    /**
     * Basecamp dipilih dari daftar resmi. Pilihan "Lainnya" memakai
     * `basecamp_other` yang tervalidasi, sehingga `location` yang tersimpan
     * selalu berasal dari nilai yang sudah disetujui server.
     */
    private function deriveBasecamp(): void
    {
        $selected = (string) $this->input('basecamp');
        $other = trim((string) $this->input('basecamp_other'));
        $otherValue = (string) config('gearora.trip.basecamp_other_value', 'lainnya');

        if ($selected === '') {
            // Klien lama hanya mengirim `location`; pertahankan kompatibilitas.
            $legacy = trim((string) $this->input('location'));

            if ($legacy === '') {
                $this->merge(['basecamp' => null, 'basecamp_other' => null]);

                return;
            }

            if (in_array($legacy, $this->basecampOptions(), true)) {
                $this->merge(['basecamp' => $legacy]);

                return;
            }

            $this->merge(['basecamp' => $otherValue, 'basecamp_other' => $legacy]);

            return;
        }

        if ($selected === $otherValue) {
            $this->merge(['basecamp_other' => $other, 'location' => $other]);

            return;
        }

        $this->merge(['basecamp_other' => null, 'location' => $selected]);
    }

    /**
     * @return array<int, string>
     */
    private function basecampOptions(): array
    {
        return array_values((array) config('gearora.trip.basecamps', []));
    }

    /**
     * Tanggal kembali selalu dihitung server dari tanggal berangkat + durasi.
     * Dengan begitu jumlah hari sewa (RentalPeriod) selalu identik dengan durasi
     * yang ditampilkan Trip Planner; end_date dari klien tidak dipercaya.
     */
    private function deriveReturnDate(): void
    {
        $start = trim((string) $this->input('start_date'));
        $duration = (int) $this->input('duration_days');

        if ($start === '' || $duration < 1) {
            return;
        }

        try {
            $return = Carbon::parse($start)->startOfDay()->addDays($duration)->toDateString();
        } catch (\Throwable) {
            return;
        }

        $this->merge(['end_date' => $return]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxDays = (int) config('gearora.trip.max_duration_days', 60);
        $maxParticipants = (int) config('gearora.trip.max_participants', 100);
        $otherValue = (string) config('gearora.trip.basecamp_other_value', 'lainnya');
        $today = Carbon::now(config('app.timezone'))->toDateString();

        return [
            'activity' => ['required', 'string', Rule::in(TripActivity::values())],
            'destination' => ['required', 'string', Rule::in(['Gunung Prau'])],
            'location' => ['nullable', 'string', 'max:160'],
            'basecamp' => ['nullable', 'string', Rule::in(array_merge($this->basecampOptions(), [$otherValue]))],
            'basecamp_other' => [
                'nullable',
                'required_if:basecamp,'.$otherValue,
                'string',
                'max:120',
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:'.$maxDays],
            'participants' => ['required', 'integer', 'min:1', 'max:'.$maxParticipants],
            'special_needs' => ['nullable', 'array'],
            'special_needs.*' => ['string', Rule::in(TripSpecialNeed::values())],
            'mode' => ['required', 'string', Rule::in(TripServiceMode::customerValues())],
            'experience' => ['required', 'string', Rule::in(ExperienceLevel::values())],
            'departure_time' => ['nullable', 'date_format:H:i'],
            // Tanggal lampau ditolak di server (zona waktu aplikasi, Asia/Jakarta).
            // Hari ini tetap diperbolehkan.
            'start_date' => ['nullable', 'date', 'after_or_equal:'.$today, 'required_with:end_date'],
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
            'basecamp.in' => 'Basecamp yang dipilih tidak dikenal. Pilih dari daftar atau gunakan opsi Lainnya.',
            'basecamp_other.required_if' => 'Tulis nama basecamp lain yang akan kamu gunakan.',
            'basecamp_other.max' => 'Nama basecamp maksimal 120 karakter.',
            'start_date.after_or_equal' => 'Tanggal keberangkatan tidak boleh sebelum hari ini.',
            'start_date.date' => 'Format tanggal keberangkatan tidak valid.',
        ];
    }
}
