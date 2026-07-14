<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'data.masyarakat',
            'create.data.warga',
            'edit.data.warga',
            'delete.data.warga',
            'pengaduan.view',
            'pengaduan.create',
            'pengaduan.edit',
            'pengaduan.delete',
            'pengaduan.respond',
            'pengaduan.verify',
            'pengaduan.lonceng',
            'pengaduan.ingatkan',
            'pengaduan.notifikasi-email',
            'program.view',
            'program.create',
            'program.edit',
            'program.delete',
            'dokumentasi.view',
            'dokumentasi.create',
            'dokumentasi.edit',
            'dokumentasi.delete',
            'role.view',
            'role.create',
            'role.edit',
            'role.delete',
            'permission.view',
            'permission.create',
            'permission.edit',
            'permission.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $bendesaAdat = Role::firstOrCreate(['name' => 'Bendesa Adat', 'guard_name' => 'web']);
        $masyarakat = Role::firstOrCreate(['name' => 'Masyarakat', 'guard_name' => 'web']);

        $superAdmin->syncPermissions($permissions);

        $admin->syncPermissions([
            'dashboard.view',
            'users.view',
            'data.masyarakat',
            'create.data.warga',
            'edit.data.warga',
            'delete.data.warga',
            'role.view',
            'pengaduan.view',
            'pengaduan.create',
            'pengaduan.edit',
            'pengaduan.delete',
            'pengaduan.respond',
            'pengaduan.verify',
            'pengaduan.notifikasi-email',
            'program.view',
            'program.create',
            'program.edit',
            'program.delete',
            'dokumentasi.view',
            'dokumentasi.create',
            'dokumentasi.edit',
            'dokumentasi.delete',
        ]);

        $bendesaAdat->syncPermissions([
            'dashboard.view',
            'users.view',
            'data.masyarakat',
            'pengaduan.view',
            'pengaduan.respond',
            'pengaduan.verify',
            'pengaduan.notifikasi-email',
            'program.view',
            'program.edit',
            'dokumentasi.view',
            'dokumentasi.edit',
        ]);

        $masyarakat->syncPermissions([
            'dashboard.view',
            'pengaduan.view',
            'pengaduan.create',
            'pengaduan.ingatkan',
            'program.view',
            'dokumentasi.view',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
