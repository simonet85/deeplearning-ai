<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AgentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function valid(array $overrides = []): array
    {
        return [
            'name' => 'Pixel',
            'agent_type' => 'Coding assistant',
            'email' => 'pixel@agents.test',
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
            ...$overrides,
        ];
    }

    private function fill(array $data)
    {
        $component = Volt::test('pages.auth.register');

        foreach ($data as $field => $value) {
            $component->set($field, $value);
        }

        return $component;
    }

    public function test_the_registration_page_is_available_to_guests(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSeeVolt('pages.auth.register')
            ->assertSee('Join the clinic')
            ->assertSee('name="viewport" content="width=device-width, initial-scale=1"', false);
    }

    public function test_the_login_and_welcome_pages_link_to_registration(): void
    {
        $this->get('/login')->assertSee(route('register'), false)->assertSee('Create an agent account');
        $this->get('/')->assertSee(route('register'), false);
    }

    public function test_signed_in_users_are_sent_away_from_registration(): void
    {
        $this->actingAs(User::factory()->create())->get('/register')->assertRedirect('/dashboard');
    }

    public function test_an_agent_can_register_and_is_signed_in(): void
    {
        Event::fake([Registered::class]);

        $this->fill($this->valid())
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('agent.home', absolute: false));

        $user = User::firstWhere('email', 'pixel@agents.test');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(Role::Agent, $user->role);
        $this->assertSame('Pixel', $user->name);
        $this->assertTrue(Hash::check('a-long-password', $user->password));
        Event::assertDispatched(Registered::class);
    }

    public function test_registering_creates_the_linked_agent_record(): void
    {
        $this->fill($this->valid())->call('register');

        $agent = Agent::sole();
        $user = User::sole();

        $this->assertSame($user->id, $agent->user_id);
        $this->assertSame('Pixel', $agent->name);
        $this->assertSame('Coding assistant', $agent->agent_type);
        $this->assertSame('pixel@agents.test', $agent->email);
        $this->assertNull($agent->bio);
    }

    public function test_a_new_agent_reaches_their_area_and_not_the_staff_pages(): void
    {
        $this->fill($this->valid())->call('register');

        $this->get('/me')->assertRedirect('/profile');
        $this->get('/profile')->assertOk();
        $this->get('/ailments')->assertForbidden();
    }

    public function test_registration_never_links_to_an_existing_agent_with_the_same_email(): void
    {
        $existing = Agent::factory()->create(['email' => 'pixel@agents.test']);

        $this->fill($this->valid())->call('register')->assertHasNoErrors();

        $this->assertSame(2, Agent::count());
        $this->assertNull($existing->fresh()->user_id);
        $this->assertSame('pixel@agents.test', User::sole()->agent->email);
        $this->assertNotSame($existing->id, User::sole()->agent->id);
    }

    public function test_every_field_is_required(): void
    {
        Volt::test('pages.auth.register')
            ->call('register')
            ->assertHasErrors(['name' => 'required', 'agent_type' => 'required', 'email' => 'required', 'password' => 'required']);

        $this->assertSame(0, User::count());
        $this->assertSame(0, Agent::count());
        $this->assertGuest();
    }

    public function test_the_email_must_be_valid_lowercase_and_unique(): void
    {
        User::factory()->create(['email' => 'taken@agents.test']);

        $this->fill($this->valid(['email' => 'not-an-email']))->call('register')->assertHasErrors(['email' => 'email']);
        $this->fill($this->valid(['email' => 'Pixel@Agents.test']))->call('register')->assertHasErrors(['email' => 'lowercase']);
        $this->fill($this->valid(['email' => 'taken@agents.test']))->call('register')->assertHasErrors(['email' => 'unique']);

        $this->assertSame(1, User::count());
    }

    public function test_the_password_must_be_long_enough_and_confirmed(): void
    {
        $this->fill($this->valid(['password' => 'short', 'password_confirmation' => 'short']))
            ->call('register')
            ->assertHasErrors('password')
            ->assertSee('at least 8 characters');

        $this->fill($this->valid(['password_confirmation' => 'something-else']))
            ->call('register')
            ->assertHasErrors(['password' => 'confirmed']);

        $this->assertSame(0, User::count());
    }

    public function test_a_failed_registration_leaves_no_half_created_records(): void
    {
        $this->fill($this->valid(['agent_type' => str_repeat('x', 300)]))
            ->call('register')
            ->assertHasErrors(['agent_type' => 'max']);

        $this->assertSame(0, User::count());
        $this->assertSame(0, Agent::count());
    }

    public function test_registration_is_rate_limited(): void
    {
        $component = Volt::test('pages.auth.register');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $component->call('register')->assertHasErrors(['name' => 'required']);
        }

        foreach ($this->valid() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('register')->assertHasErrors('email');

        $this->assertSame(0, User::count());
        $this->assertGuest();
    }
}
