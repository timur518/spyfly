<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        if (Role::query()->doesntExist()) {
            $now = now();

            DB::table('roles')->insert([
                [
                    'id' => 1,
                    'name' => 'super_admin',
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => 2,
                    'name' => 'user',
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => 3,
                    'name' => 'panel_user',
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            return;
        }

        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('user', 'web');
        Role::findOrCreate('panel_user', 'web');
    }
}
