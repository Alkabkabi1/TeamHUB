<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\University;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoUsersSeeder extends Seeder
{
    /**
     * The fixed demo accounts used to log in during a walkthrough. Names are
     * presentable in any roster (the QA account included), so member and
     * attendee lists never surface a stray "Test User" or honorific-laden
     * faker name.
     *
     * @var list<array{email: string, name: string, role: UserRole}>
     */
    private array $accounts = [
        // Generic QA login kept for walkthroughs; reads as a real person in rosters.
        ['email' => 'test@example.com', 'name' => 'زياد الحسن', 'role' => UserRole::Student],
        // Project supervisor (group teacher) — platform-wide university staff.
        ['email' => 'admin@teamhub.test', 'name' => 'د. غفران طلبه', 'role' => UserRole::UniversityStaff],
        // Group members. Their club/committee roles are granted in
        // ClubMembershipsSeeder and CommitteesSeeder; emails name each demo
        // account's purpose so a walkthrough login is unambiguous.
        //
        // Globally a student; ClubLead (club leader) of نادي الحاسبات.
        ['email' => 'club-leader@teamhub.test', 'name' => 'وئام راشد', 'role' => UserRole::Student],
        // Globally a student and an ordinary club member; leads a committee only
        // (CommitteeLead of اللجنة العلمية) — proving committee leadership is
        // independent of club leadership.
        ['email' => 'committee-leader@teamhub.test', 'name' => 'قائد اللجنة', 'role' => UserRole::Student],
        // Globally a student; granted only the Attendance Scanner club role so
        // they can scan QR codes but manage nothing else.
        ['email' => 'scanner@teamhub.test', 'name' => 'ماسح الحضور', 'role' => UserRole::Student],
        // Plain student members.
        ['email' => 'student@teamhub.test', 'name' => 'طالب 1', 'role' => UserRole::Student],
        ['email' => 'member@teamhub.test', 'name' => 'طالب 2', 'role' => UserRole::Student],
    ];

    /**
     * A curated roster of realistic Saudi student names (first + family, no
     * faker honorifics) so club members, attendees and post authors read as
     * real people. Emails are deterministic to keep the seeder idempotent.
     *
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
        ['name' => 'سلطان الرشيدي', 'email' => 's.alrashidi@teamhub.test'],
        ['name' => 'رغد المطيري', 'email' => 'r.almutairi@teamhub.test'],
        ['name' => 'يوسف الحارثي', 'email' => 'y.alharthi@teamhub.test'],
        ['name' => 'دانة الخالدي', 'email' => 'd.alkhalidi@teamhub.test'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $universityId = University::query()->orderBy('id')->value('id');

        foreach ([...$this->accounts, ...$this->withStudentDefaults()] as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'role' => $data['role'],
                    'university_id' => $universityId,
                    'qr_token' => (string) Str::uuid(),
                ]
            );
        }
    }

    /**
     * The curated students as full account rows (all are verified students).
     *
     * @return list<array{email: string, name: string, role: UserRole}>
     */
    private function withStudentDefaults(): array
    {
        return array_map(fn (array $student): array => [
            'email' => $student['email'],
            'name' => $student['name'],
            'role' => UserRole::Student,
        ], $this->students);
    }
}
