<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User 1
        User::factory()->create([
            'name' => 'Test User 1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'balance' => '100000.00000000',
        ]);

        $user1 = User::where('email', 'user1@example.com')->first();
        $user1->assets()->create([
            'symbol' => 'BTC',
            'amount' => '10.00000000',
        ]);
        $user1->assets()->create([
            'symbol' => 'ETH',
            'amount' => '100.00000000',
        ]);

        // User 2
        User::factory()->create([
            'name' => 'Test User 2',
            'email' => 'user2@example.com',
            'password' => Hash::make('password'),
            'balance' => '200000.00000000',
        ]);

        $user2 = User::where('email', 'user2@example.com')->first();
        $user2->assets()->create([
            'symbol' => 'BTC',
            'amount' => '20.00000000',
        ]);
        $user2->assets()->create([
            'symbol' => 'ETH',
            'amount' => '200.00000000',
        ]);

        // User 3
        User::factory()->create([
            'name' => 'Test User 3',
            'email' => 'user3@example.com',
            'password' => Hash::make('password'),
            'balance' => '300000.00000000',
        ]);

        $user3 = User::where('email', 'user3@example.com')->first();
        $user3->assets()->create([
            'symbol' => 'BTC',
            'amount' => '30.00000000',
        ]);
        $user3->assets()->create([
            'symbol' => 'ETH',
            'amount' => '300.00000000',
        ]);
    }
}
