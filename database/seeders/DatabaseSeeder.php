<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $userRole = Role::findOrCreate('user');
        $adminRole = Role::findOrCreate('admin');

        $this->call(SpecialistSeeder::class);

        $assignmentRoles = Role::query()
            ->whereIn('name', $this->assignmentRoleNames())
            ->orderBy('name')
            ->get()
            ->values();

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
            ->each(function (User $user, int $index) use ($assignmentRoles): void {
                $user->syncRoles([$assignmentRoles[$index % $assignmentRoles->count()]]);
            });

        $this->call(InquirySeeder::class);
    }

    /**
     * @return array<int, string>
     */
    private function assignmentRoleNames(): array
    {
        return [
            'legal_counsel',
            'hr_specialist',
            'security_investigator',
            'physical_security_specialist',
            'information_security_specialist',
            'economic_security_specialist',
            'compliance_officer',
            'ethics_officer',
            'occupational_safety_specialist',
            'procurement_control_specialist',
        ];
    }
}
