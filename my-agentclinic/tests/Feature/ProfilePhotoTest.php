<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::PHOTO_DISK);
    }

    private function form(User $user)
    {
        return Volt::actingAs($user)->test('profile.update-profile-photo-form');
    }

    /** Puts a real PNG on the fake disk and points the user at it. */
    private function givePhoto(User $user, string $name = 'current.png'): string
    {
        $path = $name;
        $file = UploadedFile::fake()->image($name, 40, 40);
        Storage::disk(User::PHOTO_DISK)->put($path, file_get_contents($file->getRealPath()));
        $user->update(['profile_photo_path' => $path]);

        return $path;
    }

    // ---- the form ----

    public function test_the_profile_page_offers_the_photo_form_to_every_role(): void
    {
        foreach ([User::factory()->agent(), User::factory(), User::factory()->admin()] as $factory) {
            $this->actingAs($factory->create())->get('/profile')
                ->assertOk()
                ->assertSee('Profile Photo')
                ->assertSee('type="file"', false)
                ->assertDontSee('Remove photo');
        }
    }

    public function test_a_user_uploads_a_photo(): void
    {
        $user = User::factory()->create();

        $this->form($user)
            ->set('photo', UploadedFile::fake()->image('me.png', 300, 300))
            ->assertSee('Save photo')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('photo', null)
            ->assertDispatched('profile-photo-updated')
            ->assertSee('Remove photo');

        $path = $user->fresh()->profile_photo_path;
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+.png$/', $path);
        Storage::disk(User::PHOTO_DISK)->assertExists($path);
    }

    public function test_agents_and_staff_can_both_upload(): void
    {
        foreach ([User::factory()->agent()->create(), User::factory()->admin()->create()] as $user) {
            $this->form($user)->set('photo', UploadedFile::fake()->image('me.jpg'))->call('save')->assertHasNoErrors();

            $this->assertNotNull($user->fresh()->profile_photo_path);
        }
    }

    public function test_a_new_photo_replaces_and_deletes_the_old_one(): void
    {
        $user = User::factory()->create();
        $old = $this->givePhoto($user, 'old.png');

        $this->form($user)->set('photo', UploadedFile::fake()->image('new.webp'))->call('save')->assertHasNoErrors();

        $new = $user->fresh()->profile_photo_path;
        $this->assertNotSame($old, $new);
        Storage::disk(User::PHOTO_DISK)->assertMissing($old);
        Storage::disk(User::PHOTO_DISK)->assertExists($new);
    }

    public function test_a_large_upload_is_resized_and_converted_to_webp(): void
    {
        $user = User::factory()->create();

        $this->form($user)
            ->set('photo', UploadedFile::fake()->image('huge.png', 2400, 1800))
            ->call('save')
            ->assertHasNoErrors();

        $path = $user->fresh()->profile_photo_path;
        $this->assertStringEndsWith('.webp', $path);

        [$width, $height, $type] = getimagesizefromstring(Storage::disk(User::PHOTO_DISK)->get($path));
        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertSame(800, $width);
        $this->assertSame(600, $height);
    }

    public function test_the_photo_is_required_and_must_be_a_jpg_png_or_webp_image_under_ten_megabytes(): void
    {
        $user = User::factory()->create();

        $this->form($user)->call('save')->assertHasErrors(['photo' => 'required']);
        $this->form($user)->set('photo', UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'))
            ->call('save')->assertHasErrors(['photo' => 'image']);
        $this->form($user)->set('photo', UploadedFile::fake()->image('anim.gif'))
            ->call('save')->assertHasErrors(['photo' => 'mimes']);
        $this->form($user)->set('photo', UploadedFile::fake()->image('big.png')->size(10241))
            ->call('save')->assertHasErrors(['photo' => 'max']);

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_a_user_removes_their_photo(): void
    {
        $user = User::factory()->create();
        $path = $this->givePhoto($user);

        $this->form($user)
            ->call('remove')
            ->assertDispatched('profile-photo-updated')
            ->assertDontSee('Remove photo');

        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk(User::PHOTO_DISK)->assertMissing($path);
    }

    public function test_removing_when_there_is_no_photo_changes_nothing(): void
    {
        $user = User::factory()->create();

        $this->form($user)->call('remove')->assertHasNoErrors();

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_deleting_an_account_deletes_its_photo(): void
    {
        $user = User::factory()->agent()->create();
        $path = $this->givePhoto($user);

        Volt::actingAs($user)->test('profile.delete-user-form')->set('password', 'password')->call('deleteUser');

        Storage::disk(User::PHOTO_DISK)->assertMissing($path);
    }

    // ---- serving the image ----

    public function test_a_user_sees_their_own_photo(): void
    {
        $user = User::factory()->agent()->create();
        $this->givePhoto($user);

        $response = $this->actingAs($user)->get(route('users.photo', $user))->assertOk();

        $this->assertStringContainsString('image/png', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }

    public function test_staff_see_any_photo(): void
    {
        $agent = User::factory()->agent()->create();
        $this->givePhoto($agent);

        $this->actingAs(User::factory()->create())->get(route('users.photo', $agent))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('users.photo', $agent))->assertOk();
    }

    public function test_an_agent_cannot_see_another_users_photo(): void
    {
        $other = User::factory()->agent()->create();
        $this->givePhoto($other);
        $staff = User::factory()->create();
        $this->givePhoto($staff, 'staff.png');

        $viewer = User::factory()->agent()->create();

        $this->actingAs($viewer)->get(route('users.photo', $other))->assertForbidden();
        $this->actingAs($viewer)->get(route('users.photo', $staff))->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $user = User::factory()->create();
        $this->givePhoto($user);

        $this->get(route('users.photo', $user))->assertRedirect('/login');
    }

    public function test_there_is_a_404_without_a_photo_or_when_the_file_is_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('users.photo', $user))->assertNotFound();

        $user->update(['profile_photo_path' => 'gone.png']);

        $this->actingAs($user)->get(route('users.photo', $user))->assertNotFound();
    }

    // ---- where the photo shows ----

    public function test_the_avatar_falls_back_to_an_initial(): void
    {
        $this->blade('<x-avatar name="Pixel" class="h-8 w-8" />')
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('>P<', false)
            ->assertDontSee('<img', false);

        $this->blade('<x-avatar :user="$user" />', ['user' => User::factory()->make(['name' => 'zed'])])
            ->assertSee('>Z<', false);
    }

    public function test_the_avatar_shows_the_photo_when_there_is_one(): void
    {
        $user = User::factory()->create();
        $this->givePhoto($user);

        $this->blade('<x-avatar :user="$user" class="h-8 w-8" />', ['user' => $user->fresh()])
            ->assertSee('<img src="'.route('users.photo', $user).'?v=', false)
            ->assertDontSee('aria-hidden', false);
    }

    public function test_the_navigation_shows_the_signed_in_users_photo(): void
    {
        $user = User::factory()->create();
        $this->givePhoto($user);

        $this->actingAs($user)->get('/profile')->assertSee(route('users.photo', $user), false);
    }

    public function test_the_navigation_refreshes_when_the_photo_changes(): void
    {
        $user = User::factory()->create();

        $component = Volt::actingAs($user)->test('layout.navigation')->assertDontSee('<img', false);

        $this->givePhoto($user);

        $component->dispatch('profile-photo-updated')->assertSee(route('users.photo', $user), false);
    }

    public function test_staff_see_an_agents_photo_on_the_dashboard_and_initials_for_agents_without_an_account(): void
    {
        $account = User::factory()->agent()->create(['name' => 'Pixel']);
        $this->givePhoto($account);
        \App\Models\Agent::factory()->create(['name' => 'Beacon']);

        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertOk()
            ->assertSee(route('users.photo', $account), false)
            ->assertSee('>B<', false);
    }
}
