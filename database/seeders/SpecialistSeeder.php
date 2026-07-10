<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SpecialistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect($this->roles())
            ->each(function (array $role): void {
                $model = Role::firstOrCreate([
                    'name' => $role['name'],
                    'guard_name' => 'web',
                ]);

                $uuid = $model->uuid ?: (string) Str::uuid();

                $model->forceFill([
                    'uuid' => $uuid,
                    'label_key' => "roles.{$uuid}.label",
                    'fallback_label' => $role['fallback_label'],
                    'ai_description' => $role['ai_description'],
                    'is_protected' => false,
                ])->save();
            });

        collect($this->specialists())
            ->each(function (array $specialist): void {
                $user = User::updateOrCreate(
                    ['email' => $specialist['email']],
                    [
                        'name' => $specialist['name'],
                        'type' => 'system',
                        'status' => 'active',
                        'password' => Hash::make('password'),
                    ]
                );

                $user->syncRoles([Role::findOrCreate($specialist['role'], 'web')]);
            });
    }

    /**
     * @return array<int, array{name: string, fallback_label: string, ai_description: string}>
     */
    private function roles(): array
    {
        return [
            [
                'name' => 'legal_counsel',
                'fallback_label' => 'Legal Counsel',
                'ai_description' => 'Reviews inquiries with legal risk, contracts, regulatory issues, labor law questions, personal data concerns, and prepares legally safe response guidance.',
            ],
            [
                'name' => 'hr_specialist',
                'fallback_label' => 'HR Specialist',
                'ai_description' => 'Handles inquiries about employment relations, workplace conduct, conflicts between employees, labor discipline, schedules, leave, onboarding, and HR policy questions.',
            ],
            [
                'name' => 'security_investigator',
                'fallback_label' => 'Security Investigator',
                'ai_description' => 'Investigates incidents involving threats, misconduct, internal violations, suspicious behavior, access misuse, and coordinates fact-finding before response preparation.',
            ],
            [
                'name' => 'physical_security_specialist',
                'fallback_label' => 'Physical Security Specialist',
                'ai_description' => 'Handles inquiries about site access, guards, badges, restricted areas, physical incidents, property protection, visitor control, and perimeter security.',
            ],
            [
                'name' => 'information_security_specialist',
                'fallback_label' => 'Information Security Specialist',
                'ai_description' => 'Handles inquiries about cybersecurity, account access, data leaks, phishing, device misuse, system access violations, and information protection risks.',
            ],
            [
                'name' => 'economic_security_specialist',
                'fallback_label' => 'Economic Security Specialist',
                'ai_description' => 'Handles inquiries about fraud, theft, financial abuse, conflicts of interest, supplier risks, asset misuse, and suspicious economic activity.',
            ],
            [
                'name' => 'compliance_officer',
                'fallback_label' => 'Compliance Officer',
                'ai_description' => 'Triages ethics, compliance, corruption, conflict of interest, policy breach, and whistleblowing inquiries and decides appropriate assignment or escalation.',
            ],
            [
                'name' => 'ethics_officer',
                'fallback_label' => 'Ethics Officer',
                'ai_description' => 'Handles inquiries about respectful conduct, discrimination, harassment, retaliation, ethical concerns, and workplace culture issues.',
            ],
            [
                'name' => 'occupational_safety_specialist',
                'fallback_label' => 'Occupational Safety Specialist',
                'ai_description' => 'Handles inquiries about workplace safety, health risks, unsafe conditions, equipment hazards, PPE, incidents, and safety procedure violations.',
            ],
            [
                'name' => 'procurement_control_specialist',
                'fallback_label' => 'Procurement Control Specialist',
                'ai_description' => 'Handles inquiries about procurement violations, supplier complaints, unfair tendering, delivery issues, conflicts of interest in purchasing, and contract execution risks.',
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, email: string, role: string}>
     */
    private function specialists(): array
    {
        return [
            [
                'name' => 'Aigerim Sadykova',
                'email' => 'legal@speakup.test',
                'role' => 'legal_counsel',
            ],
            [
                'name' => 'Dana Akhmetova',
                'email' => 'hr@speakup.test',
                'role' => 'hr_specialist',
            ],
            [
                'name' => 'Sabina Ibrayeva',
                'email' => 'compliance@speakup.test',
                'role' => 'compliance_officer',
            ],
            [
                'name' => 'Murat Yessenov',
                'email' => 'ethics@speakup.test',
                'role' => 'ethics_officer',
            ],
            [
                'name' => 'Arman Nurgaliyev',
                'email' => 'security.investigations@speakup.test',
                'role' => 'security_investigator',
            ],
            [
                'name' => 'Timur Bektasov',
                'email' => 'physical.security@speakup.test',
                'role' => 'physical_security_specialist',
            ],
            [
                'name' => 'Olzhas Karimov',
                'email' => 'information.security@speakup.test',
                'role' => 'information_security_specialist',
            ],
            [
                'name' => 'Nurlan Saparov',
                'email' => 'economic.security@speakup.test',
                'role' => 'economic_security_specialist',
            ],
            [
                'name' => 'Madina Tulegenova',
                'email' => 'safety@speakup.test',
                'role' => 'occupational_safety_specialist',
            ],
            [
                'name' => 'Dias Omarov',
                'email' => 'procurement.control@speakup.test',
                'role' => 'procurement_control_specialist',
            ],
        ];
    }
}
