<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_render_header_main_and_footer_landmarks(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('<nav', false)
            ->assertSee('class="site-heading-bar"', false)
            ->assertSee('class="site-main"', false)
            ->assertSee('class="site-footer"', false);
    }

    public function test_layout_links_the_built_stylesheet(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertSee('rel="stylesheet"', false)
            ->assertSee('build/assets/app-', false);
    }

    public function test_footer_shows_the_app_name(): void
    {
        config(['app.name' => 'AgentClinic']);

        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk()
            ->assertSee('Relief for agents');
    }
}
