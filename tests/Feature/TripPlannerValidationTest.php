<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\TripActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Validasi Trip Planner: tanggal keberangkatan tidak boleh lampau (Asia/Jakarta)
 * dan basecamp harus berasal dari daftar tervalidasi (atau opsi "Lainnya").
 */
class TripPlannerValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            '_token' => csrf_token(),
            'activity' => TripActivity::Hiking->value,
            'destination' => 'Gunung Prau',
            'duration_days' => 2,
            'participants' => 2,
            'mode' => 'sewa',
            'experience' => ExperienceLevel::Pemula->value,
        ], $overrides);
    }

    public function test_past_departure_date_is_rejected(): void
    {
        $this->post('/rekomendasi-trip', $this->payload([
            'start_date' => Carbon::yesterday()->toDateString(),
        ]))->assertSessionHasErrors('start_date');
    }

    public function test_today_departure_date_is_accepted(): void
    {
        $this->post('/rekomendasi-trip', $this->payload([
            'start_date' => Carbon::today()->toDateString(),
        ]))->assertSessionDoesntHaveErrors('start_date');
    }

    public function test_future_departure_date_is_accepted(): void
    {
        $this->post('/rekomendasi-trip', $this->payload([
            'start_date' => Carbon::today()->addDays(3)->toDateString(),
        ]))->assertSessionDoesntHaveErrors('start_date');
    }

    public function test_past_date_error_message_is_indonesian(): void
    {
        $response = $this->post('/rekomendasi-trip', $this->payload([
            'start_date' => Carbon::yesterday()->toDateString(),
        ]));

        $response->assertSessionHasErrors([
            'start_date' => 'Tanggal keberangkatan tidak boleh sebelum hari ini.',
        ]);
    }

    public function test_known_basecamp_is_accepted(): void
    {
        $this->post('/rekomendasi-trip', $this->payload([
            'basecamp' => 'Patak Banteng',
            'start_date' => Carbon::today()->addDay()->toDateString(),
        ]))->assertSessionDoesntHaveErrors(['basecamp', 'basecamp_other']);
    }

    public function test_unknown_basecamp_is_rejected(): void
    {
        $this->post('/rekomendasi-trip', $this->payload([
            'basecamp' => 'Basecamp Palsu',
            'start_date' => Carbon::today()->addDay()->toDateString(),
        ]))->assertSessionHasErrors('basecamp');
    }

    public function test_other_basecamp_requires_extra_input(): void
    {
        $this->post('/rekomendasi-trip', $this->payload([
            'basecamp' => 'lainnya',
            'start_date' => Carbon::today()->addDay()->toDateString(),
        ]))->assertSessionHasErrors('basecamp_other');
    }

    public function test_other_basecamp_with_extra_input_is_accepted(): void
    {
        $this->post('/rekomendasi-trip', $this->payload([
            'basecamp' => 'lainnya',
            'basecamp_other' => 'Basecamp Kaliurang',
            'start_date' => Carbon::today()->addDay()->toDateString(),
        ]))->assertSessionDoesntHaveErrors(['basecamp', 'basecamp_other']);
    }

    public function test_trip_page_lists_official_basecamps(): void
    {
        $html = $this->get('/rekomendasi-trip')->assertOk()->getContent();

        foreach (['Patak Banteng', 'Dieng', 'Kalilembu', 'Wates', 'Igirmranak'] as $basecamp) {
            $this->assertStringContainsString('>'.$basecamp.'</option>', $html);
        }

        $this->assertStringContainsString('Lainnya', $html);
    }
}
