<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use App\Support\Access;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class TranslationsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> the French translations, keyed by the English text */
    private function french(): array
    {
        $decoded = json_decode(File::get(lang_path('fr.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);

        return $decoded;
    }

    /**
     * Every string passed to __() or trans_choice() in the app and the views.
     *
     * @return list<string>
     */
    private function usedStrings(): array
    {
        $pattern = '/(?:__|trans_choice)\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)")/s';
        $strings = [];

        foreach ([app_path(), resource_path('views')] as $directory) {
            foreach (File::allFiles($directory) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                preg_match_all($pattern, File::get($file->getPathname()), $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $text = $match[1] !== ''
                        ? str_replace(["\\'", '\\\\'], ["'", '\\'], $match[1])
                        : str_replace(['\\"', '\\\\'], ['"', '\\'], $match[2] ?? '');

                    // A string built from a variable (such as the date styles) cannot be checked here.
                    if (! str_contains($text, '$')) {
                        $strings[] = $text;
                    }
                }
            }
        }

        return array_values(array_unique($strings));
    }

    /**
     * Strings that reach __() through a variable, so the scan above cannot see them: the permission labels and
     * groups, the built-in role names (as stored and as shown in a badge) and the appointment statuses.
     *
     * @return list<string>
     */
    private function dynamicStrings(): array
    {
        $labels = [];
        foreach (Access::PERMISSIONS as $meta) {
            $labels[] = $meta['label'];
            $labels[] = $meta['group'];
        }

        $roles = Access::builtInRoles();
        $statuses = array_map(fn (AppointmentStatus $status) => ucfirst($status->value), AppointmentStatus::cases());

        return array_values(array_unique([...$labels, ...$roles, ...array_map('ucfirst', $roles), ...$statuses]));
    }

    private function isTranslated(string $text, array $french): bool
    {
        // Plain English text lives in lang/fr.json; a dotted key such as auth.password lives in lang/fr/*.php.
        return array_key_exists($text, $french) || Lang::hasForLocale($text, 'fr');
    }

    // ---- completeness ----

    public function test_the_french_file_is_valid_and_not_empty(): void
    {
        $french = $this->french();

        $this->assertGreaterThan(150, count($french));

        foreach ($french as $english => $translation) {
            $this->assertIsString($translation);
            $this->assertNotSame('', trim($translation), "Empty translation for: $english");
        }
    }

    public function test_every_string_the_app_uses_has_a_french_translation(): void
    {
        $french = $this->french();
        $used = [...$this->usedStrings(), ...$this->dynamicStrings()];

        $this->assertGreaterThan(150, count($used), 'The scan found suspiciously few strings');

        $missing = array_values(array_filter($used, fn (string $text) => ! $this->isTranslated($text, $french)));

        $this->assertSame([], $missing, "Missing from lang/fr.json:\n- ".implode("\n- ", $missing));
    }

    public function test_the_french_file_has_no_unused_entries(): void
    {
        $used = [...$this->usedStrings(), ...$this->dynamicStrings()];

        $unused = array_values(array_diff(array_keys($this->french()), $used));

        $this->assertSame([], $unused, "Not used anywhere, remove from lang/fr.json:\n- ".implode("\n- ", $unused));
    }

    public function test_placeholders_and_plural_forms_match_between_english_and_french(): void
    {
        foreach ($this->french() as $english => $translation) {
            preg_match_all('/:[a-z_]+/i', $english, $inEnglish);
            preg_match_all('/:[a-z_]+/i', $translation, $inFrench);
            $this->assertEqualsCanonicalizing(array_unique($inEnglish[0]), array_unique($inFrench[0]), "Placeholders differ for: $english");

            $this->assertSame(substr_count($english, '|'), substr_count($translation, '|'), "Plural forms differ for: $english");
        }
    }

    public function test_the_framework_translation_files_exist_for_every_supported_language(): void
    {
        foreach (config('app.supported_locales') as $locale) {
            foreach (['auth', 'passwords', 'pagination', 'validation', 'dates'] as $file) {
                $this->assertFileExists(lang_path("$locale/$file.php"));
            }
        }

        $this->assertFileExists(lang_path('fr.json'));
    }

    // ---- dates and times ----

    public function test_dates_follow_the_language(): void
    {
        $date = Carbon::parse('2026-10-05 14:30');

        app()->setLocale('en');
        $this->assertSame('Mon, Oct 5', $date->localized('short'));
        $this->assertSame('Mon, Oct 5 · 14:30', $date->localized('datetime'));
        $this->assertSame('Oct 5, 14:30', $date->localized('stamp'));
        $this->assertSame('Oct 5', $date->localized('day'));
        $this->assertSame('Monday, October 5, 2026', $date->localized('long'));

        app()->setLocale('fr');
        $this->assertSame('lun. 5 oct.', $date->localized('short'));
        $this->assertSame('lun. 5 oct. · 14:30', $date->localized('datetime'));
        $this->assertSame('5 oct., 14:30', $date->localized('stamp'));
        $this->assertSame('5 oct.', $date->localized('day'));
        $this->assertSame('lundi 5 octobre 2026', $date->localized('long'));
    }

    public function test_localizing_a_date_does_not_change_the_date_itself(): void
    {
        app()->setLocale('fr');
        $date = Carbon::parse('2026-10-05 14:30');
        $before = $date->locale;

        $this->assertSame('lundi 5 octobre 2026', $date->localized('long'));

        $this->assertSame($before, $date->locale);
        $this->assertSame('2026-10-05 14:30', $date->format('Y-m-d H:i'));
        $this->assertSame('Monday', Carbon::parse('2026-10-05')->locale('en')->dayName);
    }

    public function test_times_stay_on_a_24_hour_clock_in_both_languages(): void
    {
        $date = Carbon::parse('2026-10-05 16:05');

        foreach (['en', 'fr'] as $locale) {
            app()->setLocale($locale);
            $this->assertStringContainsString('16:05', $date->localized('datetime'));
        }
    }

    // ---- the pages in French ----

    private function inFrench(?User $user = null)
    {
        $request = $user ? $this->actingAs($user) : $this;

        return $request->withCookies(['locale' => 'fr']);
    }

    public function test_the_guest_pages_are_in_french(): void
    {
        $this->inFrench()->get('/')->assertSee('Un endroit où les agents IA')->assertSee('Se connecter')->assertSee("S'inscrire")->assertDontSee('Log in');
        $this->inFrench()->get('/login')->assertSee('Mot de passe oublié ?')->assertSee('Se souvenir de moi')->assertSee('Créer un compte agent');
        $this->inFrench()->get('/register')->assertSee('Rejoindre la clinique')->assertSee('Confirmer le mot de passe');
        $this->inFrench()->get('/forgot-password')->assertSee('Envoyer le lien de réinitialisation');
    }

    public function test_the_staff_pages_are_in_french(): void
    {
        $admin = User::factory()->admin()->create();

        $this->inFrench($admin)->get('/dashboard')
            ->assertSee('Tableau de bord AgentClinic')
            ->assertSee('Bon retour, '.$admin->name.'.')
            ->assertSee('Séances à venir')
            ->assertSee('Administrateur')
            ->assertSee('Patients en salle')
            ->assertDontSee('Upcoming sessions');

        $this->inFrench($admin)->get('/ailments')->assertSee('Maux enregistrés')->assertSee('Enregistrer le mal');
        $this->inFrench($admin)->get('/therapies')->assertSee('Catalogue des thérapies')->assertSee('Noter une thérapie');
        $this->inFrench($admin)->get('/availability')->assertSee('Calendrier des disponibilités');
        $this->inFrench($admin)->get('/appointments')->assertSee('Prendre un rendez-vous')->assertSee('Rapport des rendez-vous');
        $this->inFrench($admin)->get('/admin/users')->assertSee('Rechercher des utilisateurs')->assertSee('Permissions supplémentaires');
        $this->inFrench($admin)->get('/admin/roles')->assertSee('Rôles et leurs permissions')->assertSee('Gérer les rôles et leurs permissions')->assertSee('toujours actif');
        $this->inFrench($admin)->get('/profile')->assertSee('Photo de profil')->assertSee('Informations du profil')->assertSee('Supprimer le compte');
    }

    public function test_the_navigation_is_in_french_for_staff_and_for_agents(): void
    {
        $this->inFrench(User::factory()->admin()->create())->get('/profile')
            ->assertSee('Thérapies')->assertSee('Disponibilités')->assertSee('Utilisateurs')->assertSee('Rôles')
            ->assertSee('Se déconnecter')->assertDontSee('>Therapies<', false);

        $this->inFrench(User::factory()->agent()->create())->get('/profile')
            ->assertSee('Mes rendez-vous')->assertSee('Mes maux');
    }

    public function test_the_agent_pages_are_in_french(): void
    {
        $agent = User::factory()->agent()->create();

        $this->inFrench($agent)->get('/me/appointments')->assertSee('Réserver une séance')->assertSee('Pas encore de séances');
        $this->inFrench($agent)->get('/me/ailments')->assertSee('Vos maux')->assertSee('Gravité');
    }

    public function test_appointment_dates_and_statuses_are_shown_in_french(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = Appointment::factory()->create();
        $appointment->update(['datetime' => Carbon::parse('2030-03-04 09:15')]); // a Monday

        $page = $this->inFrench($admin)->get('/appointments');

        $page->assertSee('lun. 4 mars · 09:15')->assertSee('Réservé')->assertSee('Annuler')->assertDontSee('Booked');

        Appointment::factory()->cancelled()->create();
        $this->inFrench($admin)->get('/appointments')->assertSee('Annulé');
    }

    public function test_pages_stay_in_english_by_default(): void
    {
        $this->get('/login')->assertSee('Forgot your password?')->assertDontSee('Mot de passe oublié');
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertSee('Upcoming sessions')->assertSee('Admin');
    }

    public function test_the_choice_in_the_switcher_changes_the_whole_page(): void
    {
        $user = User::factory()->admin()->create(['locale' => null]);

        $this->actingAs($user)->from('/appointments')->get('/locale/fr');

        $this->actingAs($user->fresh())->get('/appointments')->assertSee('Prendre un rendez-vous');

        $this->actingAs($user->fresh())->get('/locale/en');
        $this->actingAs($user->fresh())->get('/appointments')->assertSee('Book an appointment');
    }

    public function test_translated_text_is_escaped_in_the_page(): void
    {
        // The French strings contain apostrophes; they must come out HTML-escaped, never raw.
        $html = $this->inFrench(User::factory()->agent()->create())->get('/me/ailments')->getContent();

        $this->assertStringNotContainsString("Qu'est-ce", $html);
        $this->assertStringContainsString('Qu&#039;est-ce qui vous pèse ?', $html);
    }
}
