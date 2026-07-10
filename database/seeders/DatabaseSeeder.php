<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $userRole = Role::findOrCreate('user');
        $adminRole = Role::findOrCreate('admin');

        User::factory()
            ->count(50)
            ->create([
                'type' => 'regular',
                'status' => 'active',
            ])
            ->each(function (User $user) use ($userRole): void {
                $user->syncRoles([$userRole]);
            });

        User::factory()
            ->count(25)
            ->create([
                'type' => 'system',
                'status' => 'active',
            ])
            ->each(function (User $user) use ($adminRole): void {
                $user->syncRoles([$adminRole]);
            });

        User::factory()
            ->count(25)
            ->create([
                'type' => 'system',
                'status' => 'active',
            ])
            ->each(function (User $user) use ($userRole): void {
                $user->syncRoles([$userRole]);
            });
    }
}
