<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A route that simply reports the language the middleware chose.
        Route::middleware('web')->get('/_locale', fn () => app()->getLocale());
    }

    private function localeFor(array $headers = [], array $cookies = [], ?User $user = null): string
    {
        $request = $user ? $this->actingAs($user) : $this;

        return $request->withCookies($cookies)->get('/_locale', $headers)->getContent();
    }

    // ---- the supported languages ----

    public function test_english_and_french_are_supported_and_english_is_the_default(): void
    {
        $this->assertSame(['en', 'fr'], Locales::supported());
        $this->assertSame('en', Locales::default());
        $this->assertTrue(Locales::isSupported('fr'));
        $this->assertFalse(Locales::isSupported('de'));
        $this->assertFalse(Locales::isSupported(null));
        $this->assertFalse(Locales::isSupported(['fr']));
    }

    // ---- detection from the browser ----

    /** @return array<string, array{string, string}> */
    public static function browsers(): array
    {
        return [
            'no header' => ['', 'en'],
            'french' => ['fr', 'fr'],
            'english' => ['en', 'en'],
            'regional french' => ['fr-CA', 'fr'],
            'regional english' => ['en-GB', 'en'],
            'french first' => ['fr-FR,fr;q=0.9,en;q=0.8', 'fr'],
            'english first' => ['en-US,en;q=0.9,fr;q=0.5', 'en'],
            'french preferred by weight' => ['en;q=0.3,fr;q=0.9', 'fr'],
            'unsupported then french' => ['de-DE,de;q=0.9,fr;q=0.5', 'fr'],
            'only unsupported' => ['de,es;q=0.8', 'en'],
            'wildcard' => ['*', 'en'],
        ];
    }

    #[DataProvider('browsers')]
    public function test_the_browser_language_is_detected(string $acceptLanguage, string $expected): void
    {
        $headers = $acceptLanguage === '' ? [] : ['Accept-Language' => $acceptLanguage];

        $this->assertSame($expected, $this->localeFor($headers));
    }

    // ---- precedence ----

    public function test_a_cookie_wins_over_the_browser(): void
    {
        $this->assertSame('fr', $this->localeFor(['Accept-Language' => 'en'], ['locale' => 'fr']));
        $this->assertSame('en', $this->localeFor(['Accept-Language' => 'fr'], ['locale' => 'en']));
    }

    public function test_an_invalid_cookie_is_ignored(): void
    {
        $this->assertSame('fr', $this->localeFor(['Accept-Language' => 'fr'], ['locale' => 'xx']));
        $this->assertSame('en', $this->localeFor([], ['locale' => '<script>']));
    }

    public function test_the_account_language_wins_over_the_cookie_and_the_browser(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->assertSame('en', $this->localeFor(['Accept-Language' => 'fr'], ['locale' => 'fr'], $user));
    }

    public function test_an_account_without_a_language_falls_back_to_the_cookie_then_the_browser(): void
    {
        $user = User::factory()->create(['locale' => null]);

        $this->assertSame('fr', $this->localeFor(['Accept-Language' => 'en'], ['locale' => 'fr'], $user));
        $this->assertSame('fr', $this->localeFor(['Accept-Language' => 'fr'], [], $user));
    }

    public function test_an_unsupported_account_language_is_ignored(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['locale' => 'de'])->save();

        $this->assertSame('fr', $this->localeFor(['Accept-Language' => 'fr'], [], $user));
    }

    // ---- where the language applies ----

    public function test_pages_declare_their_language(): void
    {
        $this->get('/login', ['Accept-Language' => 'fr'])->assertSee('<html lang="fr"', false);
        $this->get('/login', ['Accept-Language' => 'en'])->assertSee('<html lang="en"', false);
    }

    public function test_the_middleware_also_runs_for_livewire_update_requests(): void
    {
        $route = Route::getRoutes()->getByName('default.livewire.update');

        $this->assertContains(SetLocale::class, app('router')->gatherRouteMiddleware($route));
    }

    public function test_the_language_also_sets_the_date_names(): void
    {
        $this->get('/_locale', ['Accept-Language' => 'fr']);
        $this->assertSame('lundi', \Carbon\Carbon::parse('2026-10-05')->dayName);

        $this->get('/_locale', ['Accept-Language' => 'en']);
        $this->assertSame('Monday', \Carbon\Carbon::parse('2026-10-05')->dayName);
    }

    public function test_the_middleware_marks_the_request_as_handled(): void
    {
        Route::middleware('web')->get('/_marked', fn (\Illuminate\Http\Request $request) => $request->attributes->get('locale_resolved') ? 'yes' : 'no');

        $this->get('/_marked')->assertSee('yes');
    }

    public function test_an_unknown_url_runs_the_web_middleware_so_the_404_is_localized(): void
    {
        $this->get('/no/such/page', ['Accept-Language' => 'fr'])->assertNotFound();
        $this->assertSame('fr', app()->getLocale());

        $this->get('/no/such/page', ['Accept-Language' => 'en'])->assertNotFound();
        $this->assertSame('en', app()->getLocale());
    }

    // ---- the switcher route ----

    public function test_choosing_a_language_sets_a_one_year_cookie_and_goes_back(): void
    {
        $response = $this->from('/login')->get('/locale/fr');

        $response->assertRedirect('/login')->assertCookie('locale', 'fr');
        $cookie = $response->getCookie('locale', false);
        $this->assertEqualsWithDelta(now()->addYear()->timestamp, $cookie->getExpiresTime(), 120);
    }

    public function test_without_a_previous_page_it_goes_home(): void
    {
        $this->get('/locale/en')->assertRedirect('/')->assertCookie('locale', 'en');
    }

    public function test_an_unsupported_language_is_rejected(): void
    {
        $this->get('/locale/de')->assertNotFound()->assertCookieMissing('locale');
        $this->get('/locale/<script>')->assertNotFound();
    }

    public function test_a_signed_in_user_has_the_choice_saved_on_their_account(): void
    {
        $user = User::factory()->create(['locale' => null]);

        $this->actingAs($user)->from('/profile')->get('/locale/fr')->assertRedirect('/profile');
        $this->assertSame('fr', $user->fresh()->locale);

        $this->actingAs($user->fresh())->get('/locale/en');
        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_a_guest_choice_is_only_a_cookie(): void
    {
        $this->get('/locale/fr');

        $this->assertSame(0, User::whereNotNull('locale')->count());
    }

    public function test_the_choice_then_applies_to_the_next_request_and_beats_the_browser(): void
    {
        $this->from('/')->get('/locale/fr');

        $this->assertSame('fr', $this->withCookies(['locale' => 'fr'])->get('/_locale', ['Accept-Language' => 'en'])->getContent());
    }

    // ---- registration ----

    public function test_a_new_account_keeps_the_language_the_visitor_was_reading(): void
    {
        app()->setLocale('fr');

        Volt::test('pages.auth.register')
            ->set('name', 'Pixel')
            ->set('agent_type', 'Planner')
            ->set('email', 'pixel@agents.test')
            ->set('password', 'a-long-password')
            ->set('password_confirmation', 'a-long-password')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertSame('fr', User::firstWhere('email', 'pixel@agents.test')->locale);
    }

    // ---- the switcher component ----

    public function test_the_switcher_lists_both_languages_and_marks_the_current_one(): void
    {
        app()->setLocale('fr');

        $view = $this->blade('<x-locale-switcher />');

        $view->assertSee('href="'.route('locale', 'en').'"', false);
        $view->assertSee('href="'.route('locale', 'fr').'"', false);
        $view->assertSee('hreflang="fr"', false);
        $this->assertSame(1, substr_count((string) $view, 'aria-current="true"'));
        $this->assertMatchesRegularExpression('/aria-current="true"[^>]*>fr</', (string) $view);
    }

    public function test_the_switcher_appears_on_the_guest_pages_the_welcome_page_and_both_menus(): void
    {
        foreach (['/login', '/register', '/forgot-password', '/'] as $url) {
            $this->get($url)->assertSee('hreflang="fr"', false);
        }

        $html = $this->actingAs(User::factory()->create())->get('/profile')->getContent();

        // One in the desktop menu and one in the collapsed mobile menu.
        $this->assertSame(2, substr_count($html, 'hreflang="fr"'));
    }
}
