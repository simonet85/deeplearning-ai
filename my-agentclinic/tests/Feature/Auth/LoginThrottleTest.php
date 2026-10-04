<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_locked_out_after_five_failed_attempts(): void
    {
        Event::fake([Lockout::class]);

        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $component->call('login')->assertHasErrors(['form.email']);
        }

        Event::assertNotDispatched(Lockout::class);

        $component
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors(['form.email']);

        Event::assertDispatched(Lockout::class);
        $this->assertGuest();
    }
}
