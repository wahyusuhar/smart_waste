<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Rina Wulandari',
            'email' => 'admin@smartwaste.test',
            'phone' => '081227340011',
        ]);

        $users = [
            ['Budi Santoso', 'budi@smartwaste.test', '081325118842'],
            ['Siti Rahmawati', 'siti@smartwaste.test', '085640227315'],
            ['Agus Prasetyo', 'agus@smartwaste.test', '082136904478'],
            ['Dewi Lestari', 'dewi@smartwaste.test', '087731456209'],
        ];

        foreach ($users as [$name, $email, $phone]) {
            User::factory()->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
            ]);
        }
    }
}
