<?php

namespace Tests\Feature;

use App\Mail\AppointmentBooked;
use App\Mail\AppointmentReminder;
use App\Models\Agent;
use App\Models\AgentAilment;
use App\Models\Ailment;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Therapy;
use App\Models\TherapyRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Finds English text that was never wrapped in __().
 *
 * Every page is rendered in a made-up language, "xx", that has no translations at all. In that language each string
 * that goes through __() comes out as ‹the key›, so any visible text left outside those marks is hard-coded. Data
 * the test creates itself (names, e-mail addresses, dates) is allowed.
 */
class HardCodedTextTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> text the test created, which is data and not part of the interface */
    private array $data = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.supported_locales' => ['en', 'fr', 'xx']]);
        Lang::handleMissingKeysUsing(fn (string $key) => '‹'.$key.'›');
        app()->setLocale('xx');
    }

    protected function tearDown(): void
    {
        Lang::handleMissingKeysUsing(null);

        parent::tearDown();
    }

    // ---- fixtures ----

    private function remember(string ...$values): void
    {
        array_push($this->data, ...$values);
    }

    /** Everything the pages show, so that what is left over can only be interface text. */
    private function fixture(): array
    {
        $this->remember(
            'Orla Quince', 'orla@clinic.test', 'Bram Tallis', 'bram@clinic.test', 'Wexley', 'wexley@agents.test',
            'Planner', 'Plans quietly', 'Zorp Fatigue', 'Zorp description', 'Zenith Retreat', 'Zenith description',
            'Notes about the zorp', 'Desk Staff', 'Rated', 'AgentClinic', 'Agent',
        );

        $admin = User::factory()->admin()->create(['name' => 'Orla Quince', 'email' => 'orla@clinic.test', 'locale' => 'xx']);
        $therapist = User::factory()->create(['name' => 'Bram Tallis', 'email' => 'bram@clinic.test']);
        $agentUser = User::factory()->agent()->create(['name' => 'Wexley', 'email' => 'wexley@agents.test']);
        $agentUser->agent->update(['agent_type' => 'Planner', 'bio' => 'Plans quietly']);
        $agent = $agentUser->agent;

        $ailment = Ailment::factory()->create(['name' => 'Zorp Fatigue', 'description' => 'Zorp description']);
        $therapy = Therapy::factory()->create(['name' => 'Zenith Retreat', 'description' => 'Zenith description', 'type' => 'Planner', 'duration' => 45]);
        $therapy->ailments()->attach($ailment);
        TherapyRating::factory()->create(['agent_id' => $agent->id, 'therapy_id' => $therapy->id, 'rating' => 4]);
        AgentAilment::factory()->create(['agent_id' => $agent->id, 'ailment_id' => $ailment->id, 'notes' => 'Notes about the zorp']);

        $booked = Appointment::factory()->create(['agent_id' => $agent->id, 'therapy_id' => $therapy->id, 'reminder_sent_at' => now()]);
        $past = Appointment::factory()->create(['agent_id' => $agent->id, 'therapy_id' => $therapy->id]);
        $past->update(['datetime' => now()->subDays(3)]);
        Appointment::factory()->cancelled()->create(['agent_id' => $agent->id, 'therapy_id' => $therapy->id]);
        Availability::factory()->create(['therapist_id' => $therapist->id]);

        $role = Role::create(['name' => 'Desk Staff', 'guard_name' => 'web']);

        $this->remember(
            $therapist->name, $booked->therapist->name, $past->therapist->name, $agent->name,
            ...User::pluck('name')->all(), ...User::pluck('email')->all(), ...Agent::pluck('name')->all(),
            ...Therapy::pluck('name')->all(), ...Ailment::pluck('name')->all(),
        );

        return compact('admin', 'therapist', 'agentUser', 'agent', 'therapy', 'role');
    }

    /**
     * What is stored in the database is data, not interface text: names, e-mail addresses, bios and so on. The
     * built-in role names are left out because the interface does translate them.
     *
     * @return list<string>
     */
    private function databaseText(): array
    {
        $columns = [
            'users' => ['name', 'email'],
            'agents' => ['name', 'agent_type', 'bio', 'email'],
            'ailments' => ['name', 'description'],
            'therapies' => ['name', 'description', 'type'],
            'agent_ailment' => ['notes'],
        ];

        $values = [];
        foreach ($columns as $table => $names) {
            foreach (DB::table($table)->get($names) as $row) {
                array_push($values, ...array_values(array_filter((array) $row, 'is_string')));
            }
        }

        array_push($values, ...DB::table('roles')->whereNotIn('name', ['admin', 'therapist', 'agent'])->pluck('name')->all());

        return $values;
    }

    // ---- detection ----

    /** The text a visitor can read, with everything wrapped in __() removed. */
    private function leftover(string $html): array
    {
        $html = preg_replace('#<(script|style|head)\b.*?</\1>#is', ' ', $html);

        preg_match_all('/\b(?:placeholder|aria-label|alt|title|label)="([^"]*)"/i', $html, $attributes);

        $text = implode(' ', $attributes[1]).' '.strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/‹[^›]*›/u', ' ', $text);

        // A plural string ("1 user|:count users") is cut in two by its own "|", leaving half a mark on each side.
        $text = preg_replace(['/‹[^‹›\n]*/u', '/[^‹›\n]*›/u'], ' ', $text);

        $allowed = collect($this->databaseText())
            ->merge($this->data)
            ->flatMap(fn (string $value) => preg_split('/[^\p{L}]+/u', mb_strtolower($value), -1, PREG_SPLIT_NO_EMPTY))
            ->merge([
                // Language codes in the switcher, and the day and month names of the date styles (English fallback).
                'agentclinic', 'en', 'fr', 'xx', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun',
                'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec',
            ])
            ->flip();

        preg_match_all('/\p{L}{3,}/u', $text, $words);

        return collect($words[0])
            ->reject(fn (string $word) => $allowed->has(mb_strtolower($word)))
            ->unique()
            ->values()
            ->all();
    }

    private function assertAllWrapped(string $html, string $where): void
    {
        $this->assertSame([], $this->leftover($html), "Text not wrapped in __() on $where");
    }

    private function page(?User $as, string $url): string
    {
        $request = $as ? $this->actingAs($as) : $this;

        $response = $request->withCookies(['locale' => 'xx'])->get($url);
        $response->assertOk();

        return $response->getContent();
    }

    // ---- the pages ----

    public function test_the_detector_itself_finds_hard_coded_text(): void
    {
        $this->assertSame(['Hardcoded'], $this->leftover('<p>‹Wrapped text›</p><p>Hardcoded</p>'));
        $this->assertSame(['Placeholder'], $this->leftover('<input placeholder="Placeholder"><p>‹x›</p>'));
        $this->assertSame([], $this->leftover('<p>‹Wrapped›</p><p>EN</p><script>var hello = 1</script>'));
    }

    public function test_the_guest_pages_have_no_hard_coded_text(): void
    {
        foreach (['/', '/login', '/register', '/forgot-password', '/reset-password/some-token?email=a%40b.test'] as $url) {
            $this->assertAllWrapped($this->page(null, $url), $url);
        }
    }

    public function test_the_staff_pages_have_no_hard_coded_text(): void
    {
        $f = $this->fixture();

        foreach (['/dashboard', '/ailments', '/therapies', '/availability', '/appointments', '/admin/users', '/admin/roles', '/profile', '/confirm-password'] as $url) {
            $this->assertAllWrapped($this->page($f['admin'], $url), $url);
        }

        $this->assertAllWrapped($this->page($f['therapist'], '/appointments'), '/appointments as a therapist');
        $this->assertAllWrapped($this->page($f['therapist'], '/therapies'), '/therapies as a therapist');
    }

    public function test_the_agent_pages_have_no_hard_coded_text(): void
    {
        $f = $this->fixture();

        foreach (['/me/appointments', '/me/ailments', '/profile'] as $url) {
            $this->assertAllWrapped($this->page($f['agentUser'], $url), $url.' as an agent');
        }
    }

    public function test_the_error_pages_have_no_hard_coded_text(): void
    {
        $f = $this->fixture();

        foreach ([403, 404, 419, 429, 500, 503] as $status) {
            foreach ([null, $f['admin'], $f['agentUser']] as $visitor) {
                // ?retry adds the waiting time, so the hints are checked too.
                $this->assertAllWrapped($this->errorPage($visitor, "/_errors/$status?retry=125", $status), "/_errors/$status");
            }
        }
    }

    /** An error page is not a 200, so it is fetched on its own. */
    private function errorPage(?User $as, string $url, int $status): string
    {
        $request = $as ? $this->actingAs($as) : $this;

        return $request->withCookies(['locale' => 'xx'])->get($url)->assertStatus($status)->getContent();
    }

    public function test_the_verification_page_has_no_hard_coded_text(): void
    {
        $unverified = User::factory()->unverified()->create(['name' => 'Orla Quince']);
        $this->remember('Orla Quince');

        $this->assertAllWrapped($this->page($unverified, '/verify-email'), '/verify-email');
    }

    // ---- states a plain page load does not reach ----

    public function test_the_forms_in_their_other_states_have_no_hard_coded_text(): void
    {
        $f = $this->fixture();
        $admin = $f['admin'];

        $this->assertAllWrapped(Volt::actingAs($admin)->test('roles')->call('startRenaming', $f['role']->id)->html(), 'roles, renaming');
        $this->assertAllWrapped(Volt::actingAs($admin)->test('roles')->call('deleteRole', Role::findByName('therapist')->id)->html(), 'roles, a refusal');
        $this->assertAllWrapped(Volt::actingAs($admin)->test('users')->call('togglePanel', $f['therapist']->id)->html(), 'users, permissions panel');
        $this->assertAllWrapped(Volt::actingAs($admin)->test('users')->call('changeRole', $admin->id, 'agent')->html(), 'users, a refusal');
        $this->assertAllWrapped(Volt::actingAs($admin)->test('therapies')->call('edit', $f['therapy']->id)->html(), 'therapies, editing');
        $this->assertAllWrapped(Volt::actingAs($admin)->test('agent-list')->call('edit', $f['agent']->id)->html(), 'agents, editing an e-mail');
        $this->assertAllWrapped(Volt::actingAs($admin)->test('appointments')->set('filterAgentId', $f['agent']->id + 100)->html(), 'appointments, no match');
        $this->assertAllWrapped(Volt::actingAs($admin)->test('availability')->html(), 'availability');
    }

    public function test_the_empty_states_have_no_hard_coded_text(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Orla Quince']);
        $agent = User::factory()->agent()->create(['name' => 'Wexley']);
        $this->remember('Orla Quince', 'Wexley');

        foreach (['dashboard' => '/dashboard', 'ailments' => '/ailments', 'therapies' => '/therapies', 'availability' => '/availability', 'appointments' => '/appointments'] as $url) {
            $this->assertAllWrapped($this->page($admin, $url), "$url, empty");
        }

        foreach (['/me/appointments', '/me/ailments'] as $url) {
            $this->assertAllWrapped($this->page($agent, $url), "$url, empty");
        }
    }

    // ---- the e-mails ----

    public function test_the_emails_have_no_hard_coded_text(): void
    {
        $f = $this->fixture();
        $appointment = Appointment::factory()->create(['agent_id' => $f['agent']->id, 'therapy_id' => $f['therapy']->id]);
        $this->remember($appointment->therapist->name);

        foreach ([new AppointmentBooked($appointment), new AppointmentReminder($appointment)] as $mail) {
            $this->assertAllWrapped($mail->render(), class_basename($mail));
            $this->assertStringContainsString('‹', $mail->envelope()->subject);
        }
    }
}
