<?php

namespace Tests\Feature;

use App\Models\Pengaduan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PengaduanVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_masyarakat_can_view_other_public_pengaduan_but_not_private_pengaduan(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $owner->assignRole('Masyarakat');
        $viewer->assignRole('Masyarakat');

        $publicPengaduan = Pengaduan::factory()
            ->for($owner)
            ->create(['visibilitas' => Pengaduan::VISIBILITAS_PUBLIK]);

        $privatePengaduan = Pengaduan::factory()
            ->for($owner)
            ->create(['visibilitas' => Pengaduan::VISIBILITAS_PRIVAT]);

        $this->assertTrue($viewer->can('view', $publicPengaduan));
        $this->assertFalse($viewer->can('view', $privatePengaduan));
    }

    public function test_pengaduan_scope_for_masyarakat_includes_owned_and_public_pengaduan_only(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $owner->assignRole('Masyarakat');
        $viewer->assignRole('Masyarakat');

        $ownedPrivatePengaduan = Pengaduan::factory()
            ->for($viewer)
            ->create(['visibilitas' => Pengaduan::VISIBILITAS_PRIVAT]);

        $publicPengaduan = Pengaduan::factory()
            ->for($owner)
            ->create(['visibilitas' => Pengaduan::VISIBILITAS_PUBLIK]);

        $otherPrivatePengaduan = Pengaduan::factory()
            ->for($owner)
            ->create(['visibilitas' => Pengaduan::VISIBILITAS_PRIVAT]);

        $visibleIds = Pengaduan::query()
            ->visibleTo($viewer)
            ->pluck('id')
            ->all();

        $this->assertContains($ownedPrivatePengaduan->id, $visibleIds);
        $this->assertContains($publicPengaduan->id, $visibleIds);
        $this->assertNotContains($otherPrivatePengaduan->id, $visibleIds);
    }
}
