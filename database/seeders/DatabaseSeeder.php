<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Administrador',
            'Supervisor',
            'Empleado',
            'Visitante',
        ];

        foreach ($roles as $roleName) {
            Role::query()->firstOrCreate(['name' => $roleName]);
        }

        $adminRoleId = Role::query()->where('name', 'Administrador')->value('id');

        if ($adminRoleId !== null) {
            User::query()->updateOrCreate(
                ['email' => 'admin@tema.com.pe'],
                [
                    'password' => Hash::make('Admin123*'),
                    'role_id' => $adminRoleId,
                ]
            );
        }
    }
}
