<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ErrorPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{int, string, string, string, string}> status, English title, French title, English message, French message */
    public static function pages(): array
    {
        return [
            '403' => [403, 'Access denied', 'Accès refusé', 'This room is reserved for someone else', 'Cette salle est réservée à la séance'],
            '404' => [404, 'Page not found', 'Page introuvable', 'wandered off to therapy', 'Cette page est partie en thérapie'],
            '419' => [419, 'Page expired', 'Page expirée', 'nodded off', 's\'est endormie'],
            '429' => [429, 'Too many requests', 'Trop de requêtes', 'asking for a lot all at once', 'Vous demandez beaucoup d\'un coup'],
            '500' => [500, 'Something went wrong on our side', 'Un souci de notre côté', 'wellness program is clearly not working', 'ne fonctionne visiblement pas'],
            '503' => [503, 'Back soon', 'Bientôt de retour', 'closed for maintenance', 'fermée pour maintenance'],
        ];
    }

    // ---- every page, both languages ----

    #[DataProvider('pages')]
    public function test_each_page_renders_in_english_with_its_status(int $status, string $title, string $frenchTitle, string $message, string $frenchMessage): void
    {
        $response = $this->get("/_errors/$status", ['Accept-Language' => 'en'])->assertStatus($status);

        $response->assertSee('<html lang="en"', false)
            ->assertSee('<title>'.$status.' · '.$title.' · AgentClinic</title>', false)
            ->assertSee('<h1', false)
            ->assertSee($title)
            ->assertSee($message, false)
            ->assertSee('name="viewport" content="width=device-width, initial-scale=1"', false)
            ->assertSee('<meta name="robots" content="noindex">', false)
            ->assertSee('aria-label="AgentClinic"', false)
            ->assertDontSee($frenchTitle);
    }

    #[DataProvider('pages')]
    public function test_each_page_renders_in_french_with_its_status(int $status, string $title, string $frenchTitle, string $message, string $frenchMessage): void
    {
        $response = $this->get("/_errors/$status", ['Accept-Language' => 'fr'])->assertStatus($status);

        $response->assertSee('<html lang="fr"', false)
            ->assertSee('<title>'.$status.' · '.$frenchTitle.' · AgentClinic</title>', false)
            ->assertSee($frenchTitle)
            ->assertSee($frenchMessage)
            ->assertDontSee($title);
    }

    #[DataProvider('pages')]
    public function test_the_saved_language_applies_to_the_page(int $status, string $title, string $frenchTitle, string $message, string $frenchMessage): void
    {
        $this->withCookies(['locale' => 'fr'])->get("/_errors/$status", ['Accept-Language' => 'en'])
            ->assertStatus($status)
            ->assertSee($frenchTitle);
    }

    #[DataProvider('pages')]
    public function test_each_page_is_laid_out_for_small_screens(int $status, string $title, string $frenchTitle, string $message, string $frenchMessage): void
    {
        $html = $this->get("/_errors/$status")->getContent();

        $this->assertStringContainsString('flex-col gap-3 sm:flex-row', $html, 'buttons stack on a phone');
        $this->assertStringContainsString('max-w-lg', $html);
        $this->assertStringContainsString('touch-target', $html);
        $this->assertStringContainsString('w-full', $html);
        $this->assertStringNotContainsString('width="', $html);
    }

    // ---- the real thing, not only the gallery ----

    public function test_an_unknown_url_gives_the_designed_404_in_the_visitors_language(): void
    {
        $this->get('/no/such/page', ['Accept-Language' => 'en'])->assertNotFound()->assertSee('Page not found');
        $this->get('/no/such/page', ['Accept-Language' => 'fr'])->assertNotFound()->assertSee('Page introuvable')->assertSee('<html lang="fr"', false);
    }

    public function test_a_forbidden_page_shows_the_designed_403(): void
    {
        $this->actingAs(User::factory()->agent()->create())->get('/ailments')
            ->assertForbidden()
            ->assertSee('Access denied')
            ->assertSee('ask an administrator');
    }

    public function test_a_throttled_request_shows_the_designed_429_with_how_long_to_wait(): void
    {
        Route::middleware(['web', 'throttle:1,1'])->get('/_throttled', fn () => 'fine');

        $this->get('/_throttled')->assertOk();
        $response = $this->get('/_throttled')->assertStatus(429)->assertSee('Too many requests');

        $this->assertMatchesRegularExpression('/Please wait \d+ seconds? before trying again\./', $response->getContent());
    }

    public function test_an_unhandled_exception_shows_the_designed_500_and_no_technical_detail(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/_boom', fn () => throw new RuntimeException('secret database password'));

        $html = $this->get('/_boom')->assertStatus(500)->assertSee('Something went wrong on our side')->getContent();

        $this->assertStringNotContainsString('secret database password', $html);
        $this->assertStringNotContainsString('RuntimeException', $html);
        $this->assertStringNotContainsString('Stack trace', $html);
        $this->assertStringNotContainsString('vendor/', $html);
    }

    // ---- the way out, by who is looking ----

    private function links(string $html): array
    {
        preg_match_all('#<a href="([^"]*)"[^>]*class="[^"]*(?:bg-indigo-600|border-gray-300)[^"]*">([^<]*)</a>#', $html, $matches, PREG_SET_ORDER);

        return array_map(fn (array $m) => [html_entity_decode($m[2]), html_entity_decode($m[1])], $matches);
    }

    public function test_a_visitor_is_sent_to_the_welcome_page(): void
    {
        $this->assertSame(
            [['Back to the home page', url('/')], ['Go back', 'javascript:history.back()']],
            $this->links($this->get('/_errors/404')->getContent()),
        );
    }

    public function test_staff_are_sent_to_the_dashboard(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())->get('/_errors/403')->getContent();

        $this->assertSame([['Dashboard', route('dashboard')], ['Go back', 'javascript:history.back()']], $this->links($html));
    }

    public function test_an_agent_is_sent_to_their_appointments(): void
    {
        $html = $this->actingAs(User::factory()->agent()->create())->get('/_errors/404')->getContent();

        $this->assertSame([['My appointments', route('agent.home')], ['Go back', 'javascript:history.back()']], $this->links($html));
    }

    public function test_someone_who_can_open_neither_area_is_sent_to_their_profile(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(Role::create(['name' => 'visitor', 'guard_name' => 'web']));

        $html = $this->actingAs($user)->get('/_errors/403')->getContent();

        $this->assertSame([['Profile', route('profile')], ['Go back', 'javascript:history.back()']], $this->links($html));
    }

    public function test_the_way_out_is_in_the_visitors_language(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())->get('/_errors/403', ['Accept-Language' => 'fr'])->getContent();

        $this->assertSame([['Tableau de bord', route('dashboard')], ['Retour', 'javascript:history.back()']], $this->links($html));
    }

    public function test_an_expired_page_offers_a_reload_and_a_new_login_to_a_visitor(): void
    {
        $this->assertSame(
            [['Reload the page', url('/_errors/419')], ['Log in again', url('/login')]],
            $this->links($this->get('/_errors/419')->getContent()),
        );
    }

    public function test_an_expired_page_offers_a_reload_and_the_way_out_to_a_signed_in_user(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())->get('/_errors/419')->getContent();

        $this->assertSame([['Reload the page', url('/_errors/419')], ['Dashboard', route('dashboard')]], $this->links($html));
        $this->assertStringContainsString('onclick="location.reload(); return false;"', $html);
    }

    public function test_the_server_error_and_rate_limit_pages_offer_a_retry_and_a_way_out(): void
    {
        foreach ([429, 500] as $status) {
            $this->assertSame(
                [['Try again', url("/_errors/$status")], ['Back to the home page', url('/')]],
                $this->links($this->get("/_errors/$status")->getContent()),
            );
        }
    }

    // ---- the waiting time ----

    public function test_a_rate_limit_page_says_how_long_to_wait(): void
    {
        $this->get('/_errors/429?retry=42')->assertSee('Please wait 42 seconds before trying again.');
        $this->get('/_errors/429?retry=1')->assertSee('Please wait 1 second before trying again.');
        $this->get('/_errors/429?retry=42', ['Accept-Language' => 'fr'])->assertSee('Veuillez patienter 42 secondes avant de réessayer.');
        $this->get('/_errors/429?retry=1', ['Accept-Language' => 'fr'])->assertSee('Veuillez patienter 1 seconde avant de réessayer.');
        $this->get('/_errors/429')->assertDontSee('Please wait');
    }

    public function test_the_maintenance_page_says_when_to_come_back_and_offers_only_a_retry(): void
    {
        $html = $this->get('/_errors/503?retry=300')->assertStatus(503)->assertSee('Please try again in about 5 minutes.')->getContent();

        $this->assertSame([['Try again', url('/_errors/503')]], $this->links($html));
        $this->assertStringNotContainsString('hreflang', $html, 'No language switcher: the switcher route is not available during maintenance');

        $this->get('/_errors/503?retry=30')->assertSee('Please try again in about 1 minute.');
        $this->get('/_errors/503?retry=300', ['Accept-Language' => 'fr'])->assertSee('Merci de réessayer dans environ 5 minutes.');
        $this->get('/_errors/503')->assertDontSee('Please try again in about');
    }

    public function test_every_other_page_has_the_language_switcher(): void
    {
        foreach ([403, 404, 419, 429, 500] as $status) {
            $this->get("/_errors/$status")->assertSee('hreflang="fr"', false)->assertSee('hreflang="en"', false);
        }
    }

    public function test_the_retry_after_value_is_read_safely(): void
    {
        $this->assertNull(ErrorPage::retryAfter(null));
        $this->assertNull(ErrorPage::retryAfter(new RuntimeException('not an HTTP error')));
        $this->assertNull(ErrorPage::retryAfter(new HttpException(429)));
        $this->assertNull(ErrorPage::retryAfter(new HttpException(429, '', null, ['Retry-After' => 'Wed, 21 Oct 2026 07:28:00 GMT'])));
        $this->assertNull(ErrorPage::retryAfter(new HttpException(429, '', null, ['Retry-After' => '-5'])));
        $this->assertNull(ErrorPage::retryAfter(new HttpException(429, '', null, ['Retry-After' => '0'])));
        $this->assertSame(90, ErrorPage::retryAfter(new HttpException(503, '', null, ['Retry-After' => '90'])));
        $this->assertSame(7, ErrorPage::retryAfter(new HttpException(503, '', null, ['Retry-After' => 7])));
    }

    // ---- the language when the web middleware did not run ----

    public function test_pages_that_run_before_the_middleware_use_the_browsers_language(): void
    {
        $request = Request::create('/', 'GET', server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9,en;q=0.5']);

        ErrorPage::ensureLocale($request);

        $this->assertSame('fr', app()->getLocale());
    }

    public function test_pages_that_ran_the_middleware_keep_their_language(): void
    {
        app()->setLocale('en');
        $request = Request::create('/', 'GET', server: ['HTTP_ACCEPT_LANGUAGE' => 'fr']);
        $request->attributes->set('locale_resolved', true);

        ErrorPage::ensureLocale($request);

        $this->assertSame('en', app()->getLocale());
    }

    // ---- the development gallery ----

    public function test_the_gallery_is_available_in_local_and_testing(): void
    {
        foreach (['testing', 'local'] as $environment) {
            $this->app['env'] = $environment;

            $this->get('/_errors/500')->assertStatus(500)->assertSee('Something went wrong on our side');
        }
    }

    public function test_the_gallery_does_not_exist_in_production(): void
    {
        $this->app['env'] = 'production';

        foreach ([403, 419, 500, 503] as $status) {
            $this->get("/_errors/$status")->assertNotFound()->assertSee('Page not found');
        }
    }

    public function test_the_gallery_only_knows_the_designed_codes(): void
    {
        $this->get('/_errors/418')->assertNotFound();
        $this->get('/_errors/abc')->assertNotFound();
        $this->get('/_errors/200')->assertNotFound();
    }
}
