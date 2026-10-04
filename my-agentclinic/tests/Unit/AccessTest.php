<?php

namespace Tests\Unit;

use App\Support\Access;
use PHPUnit\Framework\TestCase;

/** The parts of Access that need neither the framework nor a database. */
class AccessTest extends TestCase
{
    public function test_permissions_are_grouped_by_domain_with_labels(): void
    {
        $grouped = Access::grouped();

        $this->assertSame(
            ['Dashboard', 'Ailments', 'Therapies', 'Availability', 'Appointments', 'Agent area', 'Administration'],
            array_keys($grouped),
        );
        $this->assertSame(Access::permissionNames(), array_merge(...array_map('array_keys', array_values($grouped))));

        foreach ($grouped as $permissions) {
            foreach ($permissions as $label) {
                $this->assertNotSame('', $label);
            }
        }
    }

    public function test_there_are_fourteen_permissions_each_with_a_group_and_a_label(): void
    {
        $this->assertCount(14, Access::PERMISSIONS);

        foreach (Access::PERMISSIONS as $name => $meta) {
            $this->assertMatchesRegularExpression('/^[a-z-]+\.[a-z-]+$/', $name);
            $this->assertNotSame('', $meta['label']);
            $this->assertNotSame('', $meta['group']);
        }
    }

    public function test_built_in_roles_are_known(): void
    {
        $this->assertSame(['admin', 'therapist', 'agent'], Access::builtInRoles());
        $this->assertTrue(Access::isBuiltIn('admin'));
        $this->assertTrue(Access::isBuiltIn('agent'));
        $this->assertFalse(Access::isBuiltIn('reception'));
    }

    public function test_the_administrator_role_always_keeps_the_permissions_that_manage_access(): void
    {
        $this->assertSame(['roles.manage', 'users.manage'], Access::ADMIN_LOCKED);

        foreach (Access::ADMIN_LOCKED as $name) {
            $this->assertArrayHasKey($name, Access::PERMISSIONS);
        }
    }

    public function test_the_administrator_starts_without_the_agent_area_permissions(): void
    {
        $admin = Access::adminPermissions();

        $this->assertCount(12, $admin);
        $this->assertNotContains('my-ailments.use', $admin);
        $this->assertNotContains('my-appointments.use', $admin);
        $this->assertContains('roles.manage', $admin);
    }

    public function test_every_default_role_permission_exists(): void
    {
        foreach (Access::ROLES as $role => $permissions) {
            foreach ($permissions === 'admin' ? Access::adminPermissions() : $permissions as $name) {
                $this->assertArrayHasKey($name, Access::PERMISSIONS, "$role refers to an unknown permission $name");
            }
        }
    }
}
