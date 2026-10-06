<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
use Livewire\Volt\Volt;
use Tests\TestCase;

class FrameworkMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_errors_are_in_the_visitors_language(): void
    {
        app()->setLocale('fr');
        Volt::test('pages.auth.register')->call('register')
            ->assertSee('Le champ nom est obligatoire.')
            ->assertSee('Le champ e-mail est obligatoire.')
            ->assertSee('Le champ mot de passe est obligatoire.');

        app()->setLocale('en');
        Volt::test('pages.auth.register')->call('register')
            ->assertSee('The name field is required.')
            ->assertSee('The password field is required.');
    }

    public function test_field_names_read_naturally_in_both_languages(): void
    {
        $admin = User::factory()->admin()->create();

        app()->setLocale('en');
        Volt::actingAs($admin)->test('appointments')->call('book')
            ->assertSee('The patient field is required.')
            ->assertSee('The slot field is required.');

        app()->setLocale('fr');
        Volt::actingAs($admin)->test('appointments')->call('book')
            ->assertSee('Le champ patient est obligatoire.')
            ->assertSee('Le champ créneau est obligatoire.')
            ->assertSee('Le champ thérapie est obligatoire.');
    }

    public function test_size_and_format_rules_are_translated(): void
    {
        app()->setLocale('fr');

        Volt::test('pages.auth.register')
            ->set('name', 'Pixel')
            ->set('agent_type', 'Planner')
            ->set('email', 'not-an-email')
            ->set('password', 'short')
            ->set('password_confirmation', 'other')
            ->call('register')
            ->assertSee('Le champ e-mail doit être une adresse e-mail valide.')
            ->assertSee('La confirmation du champ mot de passe ne correspond pas.');
    }

    public function test_the_uniqueness_message_is_translated(): void
    {
        User::factory()->create(['email' => 'taken@agents.test']);
        app()->setLocale('fr');

        Volt::test('pages.auth.register')
            ->set('name', 'Pixel')
            ->set('agent_type', 'Planner')
            ->set('email', 'taken@agents.test')
            ->set('password', 'a-long-password')
            ->set('password_confirmation', 'a-long-password')
            ->call('register')
            ->assertSee('La valeur du champ e-mail est déjà utilisée.');
    }

    public function test_the_login_failure_and_throttle_messages_are_translated(): void
    {
        $this->assertSame('Ces identifiants ne correspondent à aucun compte.', trans('auth.failed', [], 'fr'));
        $this->assertSame('These credentials do not match our records.', trans('auth.failed', [], 'en'));
        $this->assertSame('Trop de tentatives de connexion. Veuillez réessayer dans 30 secondes.', trans('auth.throttle', ['seconds' => 30], 'fr'));

        app()->setLocale('fr');
        $user = User::factory()->create();
        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong')
            ->call('login')
            ->assertSee('Ces identifiants ne correspondent à aucun compte.');
    }

    public function test_the_password_reset_messages_are_translated(): void
    {
        app()->setLocale('fr');

        $this->assertSame('Aucun compte ne correspond à cette adresse e-mail.', trans(Password::INVALID_USER));
        $this->assertSame('Veuillez patienter avant de réessayer.', trans(Password::RESET_THROTTLED));
        $this->assertSame('Votre mot de passe a été réinitialisé.', trans(Password::PASSWORD_RESET));
        $this->assertSame('Ce jeton de réinitialisation du mot de passe est invalide.', trans(Password::INVALID_TOKEN));
    }

    public function test_pagination_labels_are_translated(): void
    {
        $this->assertSame('&laquo; Précédent', trans('pagination.previous', [], 'fr'));
        $this->assertSame('Suivant &raquo;', trans('pagination.next', [], 'fr'));
    }

    public function test_the_french_validation_file_covers_every_english_rule(): void
    {
        $english = Lang::get('validation', [], 'en');
        $french = Lang::get('validation', [], 'fr');

        $missing = array_diff(array_keys($english), array_keys($french));

        $this->assertSame([], array_values($missing), 'Rules missing from lang/fr/validation.php');

        foreach (['between', 'gt', 'gte', 'lt', 'lte', 'max', 'min', 'size'] as $rule) {
            $this->assertSame(array_keys($english[$rule]), array_keys($french[$rule]), "Sub-rules of $rule differ");
        }

        $this->assertSame(array_keys($english['attributes']), array_keys($french['attributes']), 'Friendly field names differ between languages');
    }
}
