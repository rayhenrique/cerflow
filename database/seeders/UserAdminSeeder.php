<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'coordenacao@cerflow.gov.br'],
            [
                'name' => 'Coordenação Geral CER',
                'password' => Hash::make('password'),
                'ativo' => true,
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['coordenacao']);
    }
}
