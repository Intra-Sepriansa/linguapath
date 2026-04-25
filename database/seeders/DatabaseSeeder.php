<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LinguaPathSeeder::class);

        $adminEmail = (string) config('linguapath.seed_admin.email');
        $adminPassword = (string) config('linguapath.seed_admin.password');

        User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
            'role' => 'user',
        ]);

        $admin = User::query()->where('email', $adminEmail)->first()
            ?? User::query()->where('email', 'admin@example.com')->first();

        if ($admin) {
            $admin->update([
                'name' => 'Admin User',
                'email' => $adminEmail,
                'password' => $adminPassword,
                'role' => 'admin',
            ]);
        } else {
            User::query()->create([
                'email' => $adminEmail,
                'name' => 'Admin User',
                'password' => $adminPassword,
                'role' => 'admin',
            ]);
        }

        User::query()
            ->where('email', 'admin@example.com')
            ->where('email', '!=', $adminEmail)
            ->where('role', 'admin')
            ->update(['role' => 'user']);
    }
}
