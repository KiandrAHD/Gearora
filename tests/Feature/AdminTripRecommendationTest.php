<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\TripActivity;
use App\Models\Category;
use App\Models\TripRecommendationRule;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTripRecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_can_create_update_and_delete_rule(): void
    {
        $owner = $this->owner();
        $category = Category::factory()->create();

        $this->actingAs($owner)->post(route('admin.trip-recommendations.store'), $this->payload($category))
            ->assertRedirect(route('admin.trip-recommendations.index'));

        $rule = TripRecommendationRule::query()->firstOrFail();
        $this->assertSame($owner->id, $rule->created_by);

        $this->actingAs($owner)->put(route('admin.trip-recommendations.update', $rule), $this->payload($category, [
            'name' => 'Aturan berubah',
            'priority' => 300,
        ]))->assertRedirect();

        $this->assertDatabaseHas('trip_recommendation_rules', ['id' => $rule->id, 'name' => 'Aturan berubah', 'priority' => 300]);

        $this->actingAs($owner)->delete(route('admin.trip-recommendations.destroy', $rule))->assertRedirect();
        $this->assertDatabaseMissing('trip_recommendation_rules', ['id' => $rule->id]);
    }

    public function test_rule_requires_at_least_one_gear_target(): void
    {
        $owner = $this->owner();
        $payload = $this->payload(null);
        $payload['product_id'] = null;
        $payload['product_variant_id'] = null;
        $payload['product_category_id'] = null;

        $this->actingAs($owner)->post(route('admin.trip-recommendations.store'), $payload)
            ->assertSessionHasErrors('product_id');
    }

    public function test_invalid_activity_rejected(): void
    {
        $owner = $this->owner();
        $payload = $this->payload(Category::factory()->create());
        $payload['activity'] = 'other';

        $this->actingAs($owner)->post(route('admin.trip-recommendations.store'), $payload)
            ->assertSessionHasErrors('activity');
    }

    public function test_maximum_range_must_not_be_below_minimum(): void
    {
        $owner = $this->owner();
        $payload = $this->payload(Category::factory()->create());
        $payload['min_duration_days'] = 5;
        $payload['max_duration_days'] = 2;

        $this->actingAs($owner)->post(route('admin.trip-recommendations.store'), $payload)
            ->assertSessionHasErrors('max_duration_days');
    }

    public function test_customer_cannot_open_admin_rules(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)->get(route('admin.trip-recommendations.index'))->assertForbidden();
    }

    public function test_owner_can_view_rule_index(): void
    {
        $owner = $this->owner();
        TripRecommendationRule::factory()->create(['name' => 'Aturan tampak']);

        $this->actingAs($owner)->get(route('admin.trip-recommendations.index'))
            ->assertOk()
            ->assertSee('Aturan tampak');
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Owner);

        return $user;
    }

    /** @param array<string, mixed> $overrides */
    private function payload(?Category $category, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Aturan camping',
            'description' => 'Aturan test.',
            'activity' => TripActivity::Camping->value,
            'min_duration_days' => 1,
            'max_duration_days' => null,
            'min_participants' => 1,
            'max_participants' => null,
            'product_category_id' => $category?->id,
            'product_id' => null,
            'product_variant_id' => null,
            'service_mode' => 'sewa',
            'recommended_quantity' => 1,
            'priority' => 100,
            'reason_template' => 'Untuk :activity',
            'is_active' => 1,
        ], $overrides);
    }
}
