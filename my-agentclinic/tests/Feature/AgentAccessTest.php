<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Http\Middleware\EnsureUserHasRole;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AgentAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function staffPages(): array
    {
        return [
            'ailments' => ['/ailments'],
            'therapies' => ['/therapies'],
            'availability' => ['/availability'],
            'appointments' => ['/appointments'],
        ];
    }

    #[DataProvider('staffPages')]
    public function test_agents_cannot_open_staff_pages(string $url): void
    {
        $this->actingAs(User::factory()->agent()->create())->get($url)->assertForbidden();
    }

    #[DataProvider('staffPages')]
    public function test_staff_still_open_staff_pages(string $url): void
    {
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
        $this->actingAs(User::factory()->create())->get($url)->assertOk();
    }

    #[DataProvider('staffPages')]
    public function test_guests_are_redirected_to_login_from_staff_pages(string $url): void
    {
        $this->get($url)->assertRedirect('/login');
    }

    public function test_the_dashboard_sends_agents_to_their_area_and_staff_see_it(): void
    {
        $this->actingAs(User::factory()->agent()->create())
            ->get('/dashboard')
            ->assertRedirect(route('agent.home'));

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertOk();
    }

    public function test_the_agent_area_is_for_agents_only(): void
    {
        $this->actingAs(User::factory()->agent()->create())->get('/me')->assertRedirect('/profile');

        $this->actingAs(User::factory()->create())->get('/me')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/me')->assertForbidden();
        $this->flushSession();
        auth()->logout();
        $this->get('/me')->assertRedirect('/login');
    }

    public function test_every_role_can_open_the_profile_page(): void
    {
        foreach ([User::factory()->agent(), User::factory(), User::factory()->admin()] as $factory) {
            $this->actingAs($factory->create())->get('/profile')->assertOk();
        }
    }

    public function test_navigation_shows_staff_links_only_to_staff(): void
    {
        $staffLinks = ['/ailments', '/therapies', '/availability', '/appointments'];

        $agentPage = $this->actingAs(User::factory()->agent()->create())->get('/profile')->assertOk();
        foreach ($staffLinks as $link) {
            $agentPage->assertDontSee('href="'.url($link).'"', false);
        }

        $staffPage = $this->actingAs(User::factory()->create())->get('/profile')->assertOk();
        foreach ($staffLinks as $link) {
            $staffPage->assertSee('href="'.url($link).'"', false);
        }
    }

    public function test_the_role_check_also_applies_to_livewire_update_requests(): void
    {
        $this->assertContains(EnsureUserHasRole::class, Livewire::getPersistentMiddleware());
    }

    public function test_the_agent_role_helpers(): void
    {
        $agent = User::factory()->agent()->create();

        $this->assertTrue($agent->isAgent());
        $this->assertSame(Role::Agent, $agent->fresh()->role);
        $this->assertFalse(User::factory()->create()->isAgent());
        $this->assertFalse(User::factory()->admin()->create()->isAgent());
    }

    public function test_an_agent_account_is_linked_to_its_agent_record(): void
    {
        $user = User::factory()->agent()->create(['name' => 'Pixel', 'email' => 'pixel@agents.test']);

        $this->assertSame(1, Agent::count());
        $this->assertSame('Pixel', $user->agent->name);
        $this->assertSame('pixel@agents.test', $user->agent->email);
        $this->assertTrue($user->agent->user->is($user));
    }

    public function test_an_agent_factory_for_a_user_does_not_create_extra_agents(): void
    {
        $agent = Agent::factory()->forUser()->create();

        $this->assertSame(1, Agent::count());
        $this->assertSame(Role::Agent, $agent->user->role);
    }

    public function test_staff_created_agents_need_no_account(): void
    {
        $agent = Agent::factory()->create();

        $this->assertNull($agent->user_id);
        $this->assertNull($agent->user);
    }

    public function test_an_account_can_own_only_one_agent_record(): void
    {
        $user = User::factory()->agent()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        Agent::factory()->create(['user_id' => $user->id]);
    }

    public function test_deleting_an_account_keeps_the_agent_record(): void
    {
        $user = User::factory()->agent()->create();
        $agent = $user->agent;

        $user->delete();

        $this->assertNotNull($agent->fresh());
        $this->assertNull($agent->fresh()->user_id);
    }
}
