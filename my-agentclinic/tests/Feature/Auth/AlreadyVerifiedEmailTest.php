<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AlreadyVerifiedEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_already_verified_user_is_redirected_without_a_new_verified_event(): void
    {
        Event::fake([Verified::class]);

        $user = User::factory()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        Event::assertNotDispatched(Verified::class);
    }
}
