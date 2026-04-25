<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('database seeder uses configured admin credentials', function () {
    config()->set('linguapath.seed_admin.email', 'configured-admin@example.com');
    config()->set('linguapath.seed_admin.password', 'configured-password');

    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'configured-admin@example.com')->firstOrFail();

    expect($admin->role)->toBe('admin')
        ->and(Hash::check('configured-password', $admin->password))->toBeTrue();
});
