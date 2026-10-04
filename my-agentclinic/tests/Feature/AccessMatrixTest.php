<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Who can open what. Every route is walked as a guest, an agent, a therapist and an administrator.
 */
class AccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function visitor(string $role): ?User
    {
        return match ($role) {
            'guest' => null,
            'agent' => User::factory()->agent()->create(),
            'therapist' => User::factory()->create(),
            'admin' => User::factory()->admin()->create(),
        };
    }

    /**
     * Each entry is [role, url, expected]. Expected is an HTTP status, or "redirect:<location>".
     *
     * @return array<string, array{string, string, int|string}>
     */
    public static function matrix(): array
    {
        $login = 'redirect:/login';
        $rows = [
            // Public pages: guests only (signed-in users are sent on to their own area).
            ['guest', '/', 200], ['agent', '/', 200], ['therapist', '/', 200], ['admin', '/', 200],
            ['guest', '/login', 200], ['agent', '/login', 'redirect:/dashboard'], ['therapist', '/login', 'redirect:/dashboard'], ['admin', '/login', 'redirect:/dashboard'],
            ['guest', '/register', 200], ['agent', '/register', 'redirect:/dashboard'], ['therapist', '/register', 'redirect:/dashboard'], ['admin', '/register', 'redirect:/dashboard'],

            // The dashboard: staff only; agents are sent to their own area.
            ['guest', '/dashboard', $login], ['agent', '/dashboard', 'redirect:/me'], ['therapist', '/dashboard', 200], ['admin', '/dashboard', 200],

            // Profile: any signed-in user.
            ['guest', '/profile', $login], ['agent', '/profile', 200], ['therapist', '/profile', 200], ['admin', '/profile', 200],
        ];

        foreach (['/ailments', '/therapies', '/availability', '/appointments'] as $staffPage) {
            array_push($rows, ['guest', $staffPage, $login], ['agent', $staffPage, 403], ['therapist', $staffPage, 200], ['admin', $staffPage, 200]);
        }

        foreach (['/me/appointments', '/me/ailments'] as $agentPage) {
            array_push($rows, ['guest', $agentPage, $login], ['agent', $agentPage, 200], ['therapist', $agentPage, 403], ['admin', $agentPage, 403]);
        }

        array_push($rows,
            ['guest', '/me', $login], ['agent', '/me', 'redirect:/me/appointments'], ['therapist', '/me', 403], ['admin', '/me', 403],
        );

        $named = [];
        foreach ($rows as [$role, $url, $expected]) {
            $named["$role $url"] = [$role, $url, $expected];
        }

        return $named;
    }

    #[DataProvider('matrix')]
    public function test_each_role_gets_the_expected_response(string $role, string $url, int|string $expected): void
    {
        $user = $this->visitor($role);
        $response = $user ? $this->actingAs($user)->get($url) : $this->get($url);

        if (is_string($expected)) {
            $response->assertRedirect(str_replace('redirect:', '', $expected));
        } else {
            $response->assertStatus($expected);
        }
    }

    // ---- Livewire update requests ----

    /** The signed snapshot of a component, as the browser receives it when the page first renders. */
    private function snapshotOf(string $html, string $component): string
    {
        preg_match_all('/wire:snapshot="([^"]*)"/', $html, $matches);

        foreach ($matches[1] as $encoded) {
            $snapshot = html_entity_decode($encoded, ENT_QUOTES);

            if ((json_decode($snapshot, true)['memo']['name'] ?? null) === $component) {
                return $snapshot;
            }
        }

        $this->fail("No snapshot found for the [$component] component.");
    }

    private function callRequest(User $user, string $snapshot, string $method)
    {
        return $this->actingAs($user)->postJson(route('default.livewire.update'), [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => [],
                'calls' => [['path' => '', 'method' => $method, 'params' => []]],
            ]],
        ], ['X-Livewire' => 'true']);
    }

    public function test_a_staff_component_cannot_be_driven_by_an_agent_through_a_livewire_request(): void
    {
        $admin = User::factory()->admin()->create();
        $snapshot = $this->snapshotOf($this->actingAs($admin)->get('/ailments')->getContent(), 'ailments');

        // Control: the same request is accepted for an administrator, so the 403 below is the role check.
        $this->callRequest($admin, $snapshot, 'save')->assertOk();

        $agent = User::factory()->agent()->create();
        $this->callRequest($agent, $snapshot, 'save')->assertForbidden();
    }

    public function test_the_agent_components_cannot_be_driven_by_staff_through_a_livewire_request(): void
    {
        $agent = User::factory()->agent()->create();
        $snapshot = $this->snapshotOf($this->actingAs($agent)->get('/me/appointments')->getContent(), 'my-appointments');

        $this->callRequest($agent, $snapshot, 'book')->assertOk();
        $this->callRequest(User::factory()->admin()->create(), $snapshot, 'book')->assertForbidden();
    }

    public function test_one_agent_cannot_cancel_another_agents_appointment_through_a_livewire_request(): void
    {
        $mine = User::factory()->agent()->create();
        $theirs = Appointment::factory()->create(['agent_id' => User::factory()->agent()->create()->agent->id]);
        $snapshot = $this->snapshotOf($this->actingAs($mine)->get('/me/appointments')->getContent(), 'my-appointments');

        $this->actingAs($mine)->postJson(route('default.livewire.update'), [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => [],
                'calls' => [['path' => '', 'method' => 'cancel', 'params' => [$theirs->id]]],
            ]],
        ], ['X-Livewire' => 'true'])->assertNotFound();

        $this->assertSame('booked', $theirs->fresh()->status->value);
        $this->assertNotNull($theirs->fresh()->availability_id);
    }
}
