<?php

namespace Tests\Feature;

use App\Models\AgentAilment;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Therapy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MvpResponsiveTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWPORT = 'name="viewport" content="width=device-width, initial-scale=1"';

    /** @return array<string, array{string}> */
    public static function pages(): array
    {
        return [
            'ailments' => ['/ailments'],
            'therapies' => ['/therapies'],
            'availability' => ['/availability'],
            'appointments' => ['/appointments'],
        ];
    }

    #[DataProvider('pages')]
    public function test_new_pages_declare_the_viewport_and_use_touch_targets(string $url): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get($url)
            ->assertOk()
            ->assertSee(self::VIEWPORT, false)
            ->assertSee('touch-target', false);
    }

    #[DataProvider('pages')]
    public function test_new_pages_have_a_mobile_navigation_entry(string $url): void
    {
        $html = $this->actingAs(User::factory()->create())->get($url)->getContent();

        // One link in the desktop menu and one in the collapsed mobile menu.
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'href="'.url($url).'"'));
    }

    public function test_lists_stack_on_small_screens_and_add_columns_with_width(): void
    {
        $grid = 'grid gap-4 sm:grid-cols-2 lg:grid-cols-3';
        AgentAilment::factory()->create();
        Therapy::factory()->create();
        Availability::factory()->create();
        Appointment::factory()->create();
        $admin = User::factory()->admin()->create();

        foreach (['/ailments', '/therapies', '/availability', '/appointments'] as $url) {
            $this->actingAs($admin)->get($url)->assertSee($grid, false);
        }
    }

    public function test_dashboard_tiles_and_report_are_responsive(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertSee('grid gap-4 sm:grid-cols-3', false);

        $this->actingAs(User::factory()->create())
            ->get('/appointments')
            ->assertSee('grid gap-6 sm:grid-cols-2', false);
    }
}
