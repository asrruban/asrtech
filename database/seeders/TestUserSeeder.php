<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Test Client One', 'email' => 'client1@localhost'],
            ['name' => 'Test Client Two', 'email' => 'client2@localhost'],
        ] as $client) {
            User::query()->updateOrCreate(
                ['email' => $client['email']],
                [
                    'name' => $client['name'],
                    'password' => 'ClientTest123',
                ],
            )->forceFill(['email_verified_at' => now()])->save();
        }
    }
}
