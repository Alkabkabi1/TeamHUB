<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * @var list<array{email: string, name: string, role: UserRole}>
     */
    private array $accounts = [
        ['email' => 'test@example.com', 'name' => 'زياد الحسن', 'role' => UserRole::Member],
        ['email' => 'admin@teamhub.test', 'name' => 'مدير المنصة', 'role' => UserRole::Admin],
        ['email' => 'workspace-lead@teamhub.test', 'name' => 'وئام راشد', 'role' => UserRole::Member],
        ['email' => 'project-lead@teamhub.test', 'name' => 'قائد المشروع', 'role' => UserRole::Member],
        ['email' => 'staff@teamhub.test', 'name' => 'عضو الفريق', 'role' => UserRole::Member],
        ['email' => 'student@teamhub.test', 'name' => 'طالب 1', 'role' => UserRole::Member],
        ['email' => 'member@teamhub.test', 'name' => 'طالب 2', 'role' => UserRole::Member],
    ];

    /**
     * @var list<array{name: string, email: string}>
     */
    private array $students = [
        ['name' => 'محمد العتيبي', 'email' => 'm.alotaibi@teamhub.test'],
        ['name' => 'نورة القحطاني', 'email' => 'n.alqahtani@teamhub.test'],
        ['name' => 'عبدالله الغامدي', 'email' => 'a.alghamdi@teamhub.test'],
        ['name' => 'ريم الشهري', 'email' => 'r.alshehri@teamhub.test'],
        ['name' => 'فيصل الدوسري', 'email' => 'f.aldosari@teamhub.test'],
        ['name' => 'سارة الحربي', 'email' => 's.alharbi@teamhub.test'],
        ['name' => 'خالد المالكي', 'email' => 'k.almalki@teamhub.test'],
        ['name' => 'جواهر السبيعي', 'email' => 'j.alsubaie@teamhub.test'],
        ['name' => 'تركي الزهراني', 'email' => 't.alzahrani@teamhub.test'],
        ['name' => 'لمى العنزي', 'email' => 'l.alanazi@teamhub.test'],
        ['name' => 'ماجد الشمري', 'email' => 'm.alshammari@teamhub.test'],
        ['name' => 'هند البقمي', 'email' => 'h.albaqami@teamhub.test'],
    ];

    public function run(): void
    {
        foreach ([...$this->accounts, ...$this->withMemberDefaults()] as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'role' => $data['role'],
                ],
            );
        }
    }

    /**
     * @return list<array{email: string, name: string, role: UserRole}>
     */
    private function withMemberDefaults(): array
    {
        return array_map(fn (array $student): array => [
            'email' => $student['email'],
            'name' => $student['name'],
            'role' => UserRole::Member,
        ], $this->students);
    }
}
