<?php

namespace Tests\Feature\Livewire;

use App\Models\Ailment;
use App\Models\Therapy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class TherapiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/therapies')->assertRedirect('/login');
    }

    public function test_staff_can_view_the_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/therapies')
            ->assertOk()
            ->assertSeeLivewire('therapies');
    }

    public function test_it_shows_an_empty_state(): void
    {
        Volt::actingAs(User::factory()->create())->test('therapies')
            ->assertSee('The catalog is empty');
    }

    public function test_catalog_lists_therapies_alphabetically_with_treated_ailments(): void
    {
        $ailment = Ailment::factory()->create(['name' => 'Token Fatigue']);
        Therapy::factory()->create(['name' => 'Spa', 'duration' => 60, 'type' => 'Rest'])->ailments()->attach($ailment);
        Therapy::factory()->create(['name' => 'Circle']);

        Volt::actingAs(User::factory()->create())->test('therapies')
            ->assertSeeInOrder(['Circle', 'Spa'])
            ->assertSee('Rest · 60 min')
            ->assertSee('Treats: Token Fatigue');
    }

    public function test_therapists_see_a_read_only_catalog(): void
    {
        Therapy::factory()->create(['name' => 'Spa']);

        Volt::actingAs(User::factory()->create())->test('therapies')
            ->assertSee('Spa')
            ->assertDontSee('Add a therapy')
            ->assertDontSee('Delete');
    }

    public function test_therapists_cannot_manage_therapies(): void
    {
        $therapy = Therapy::factory()->create();
        $therapist = User::factory()->create();

        Volt::actingAs($therapist)->test('therapies')->call('save')->assertForbidden();
        Volt::actingAs($therapist)->test('therapies')->call('edit', $therapy->id)->assertForbidden();
        Volt::actingAs($therapist)->test('therapies')->call('delete', $therapy->id)->assertForbidden();

        $this->assertDatabaseCount('therapies', 1);
    }

    public function test_admin_sees_management_controls(): void
    {
        Therapy::factory()->create();

        Volt::actingAs(User::factory()->admin()->create())->test('therapies')
            ->assertSee('Add a therapy')
            ->assertSee('Edit')
            ->assertSee('Delete');
    }

    public function test_admin_creates_a_therapy_and_syncs_ailments(): void
    {
        $ailments = Ailment::factory()->count(2)->create();

        Volt::actingAs(User::factory()->admin()->create())->test('therapies')
            ->set('name', 'Context Window Spa')
            ->set('description', '')
            ->set('duration', 60)
            ->set('type', 'Rest')
            ->set('ailmentIds', $ailments->pluck('id')->all())
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Context Window Spa')
            ->assertSet('name', '')
            ->assertSet('editingId', null);

        $therapy = Therapy::firstWhere('name', 'Context Window Spa');
        $this->assertNull($therapy->description);
        $this->assertCount(2, $therapy->ailments);
    }

    public function test_admin_edits_a_therapy_and_changes_its_ailments(): void
    {
        $old = Ailment::factory()->create();
        $new = Ailment::factory()->create();
        $therapy = Therapy::factory()->create(['name' => 'Old', 'description' => 'Calm.']);
        $therapy->ailments()->attach($old);

        Volt::actingAs(User::factory()->admin()->create())->test('therapies')
            ->call('edit', $therapy->id)
            ->assertSet('editingId', $therapy->id)
            ->assertSet('name', 'Old')
            ->assertSet('description', 'Calm.')
            ->assertSet('ailmentIds', [$old->id])
            ->assertSee('Edit therapy')
            ->set('name', 'New')
            ->set('ailmentIds', [$new->id])
            ->call('save')
            ->assertHasNoErrors();

        $therapy->refresh();
        $this->assertSame('New', $therapy->name);
        $this->assertSame([$new->id], $therapy->ailments()->pluck('ailments.id')->all());
        $this->assertDatabaseCount('therapies', 1);
    }

    public function test_admin_can_keep_a_therapys_own_name_when_editing(): void
    {
        $therapy = Therapy::factory()->create(['name' => 'Same']);

        Volt::actingAs(User::factory()->admin()->create())->test('therapies')
            ->call('edit', $therapy->id)
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_cancel_clears_the_form(): void
    {
        $therapy = Therapy::factory()->create();

        Volt::actingAs(User::factory()->admin()->create())->test('therapies')
            ->call('edit', $therapy->id)
            ->call('cancel')
            ->assertSet('editingId', null)
            ->assertSet('name', '');
    }

    public function test_admin_deletes_a_therapy(): void
    {
        $therapy = Therapy::factory()->create();

        Volt::actingAs(User::factory()->admin()->create())->test('therapies')
            ->call('delete', $therapy->id)
            ->assertSee('The catalog is empty');

        $this->assertDatabaseCount('therapies', 0);
    }

    public function test_deleting_the_therapy_being_edited_clears_the_form(): void
    {
        $therapy = Therapy::factory()->create();

        Volt::actingAs(User::factory()->admin()->create())->test('therapies')
            ->call('edit', $therapy->id)
            ->call('delete', $therapy->id)
            ->assertSet('editingId', null);
    }

    public function test_validation_rules(): void
    {
        Therapy::factory()->create(['name' => 'Taken']);

        Volt::actingAs(User::factory()->admin()->create())->test('therapies')
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'duration' => 'required', 'type' => 'required']);

        Volt::actingAs(User::factory()->admin()->create())->test('therapies')
            ->set('name', 'Taken')
            ->set('duration', 1)
            ->set('type', 'Rest')
            ->set('ailmentIds', [999])
            ->call('save')
            ->assertHasErrors(['name' => 'unique', 'duration' => 'min', 'ailmentIds.0' => 'exists']);
    }

    public function test_pivot_relationship_works_from_both_sides(): void
    {
        $ailment = Ailment::factory()->create();
        $therapy = Therapy::factory()->create();
        $ailment->therapies()->attach($therapy);

        $this->assertTrue($therapy->ailments->first()->is($ailment));
        $this->assertTrue($ailment->therapies->first()->is($therapy));
    }
}
