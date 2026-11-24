<?php

namespace Database\Seeders;

use App\Models\{AcademicYear, Battalion, Company, Platoon, Rank, CadetType, Cadet, Officer, UserAccount,
    Enrollment, CorpsS1Assignment, CorpsAdjutantAssignment, BattalionS1Assignment, PlatoonLeaderAssignment,
    TrainingSession, SessionAudience};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Academic year
        $year = AcademicYear::create([
            'year_label' => '2026-2027', 'start_date' => '2026-08-01',
            'end_date' => '2027-05-31', 'is_current' => true,
        ]);

        // Ranks and cadet types
        foreach (['Cadet', 'Corporal', 'Sergeant', 'Second Lieutenant', 'First Lieutenant', 'Captain'] as $r) {
            Rank::create(['rank_name' => $r]);
        }
        foreach (['Basic', 'Advanced', 'Officer'] as $t) {
            CadetType::create(['type_name' => $t]);
        }

        // Battalion > Company > Platoon
        $bn = Battalion::create(['battalion_name' => '1st Battalion']);
        $co = Company::create(['battalion_id' => $bn->battalion_id, 'company_name' => 'Alpha Company']);
        $pl = Platoon::create(['company_id' => $co->company_id, 'platoon_name' => '1st Platoon']);

        // People: [serial, last, first, rank_id, cadet_type_id, year_level]
        $people = [
            'adjutant'       => ['OFF-0001', 'Santos', 'Maria', 6, 3, 4],
            'corps_s1'       => ['OFF-0002', 'Reyes', 'Juan', 5, 3, 4],
            'battalion_s1'   => ['OFF-0003', 'Cruz', 'Ana', 5, 3, 4],
            'platoon_leader' => ['OFF-0004', 'Garcia', 'Pedro', 4, 3, 3],
            'cadet'          => ['CDT-0001', 'Dela Cruz', 'Jose', 1, 1, 2],
        ];

        $users = [];
        foreach ($people as $role => [$serial, $last, $first, $rank, $type, $level]) {
            $cadet = Cadet::create(['serial_no' => $serial, 'last_name' => $last, 'first_name' => $first, 'course' => 'BSIS']);
            if ($role !== 'cadet') {
                Officer::create(['cadet_id' => $cadet->cadet_id, 'officer_serial_no' => $serial]);
            }
            $users[$role] = UserAccount::create([
                'cadet_id' => $cadet->cadet_id,
                'username' => $serial,
                'password_hash' => Hash::make('password123'),
                'is_active' => true,
            ]);
            Enrollment::create([
                'academic_year_id' => $year->academic_year_id, 'platoon_id' => $pl->platoon_id,
                'rank_id' => $rank, 'cadet_type_id' => $type, 'cadet_id' => $cadet->cadet_id,
                'year_level' => $level, 'enrollment_status' => 'active',
            ]);
        }

        // Officer assignments for the current year
        CorpsAdjutantAssignment::create(['academic_year_id' => $year->academic_year_id, 'user_id' => $users['adjutant']->user_id]);
        CorpsS1Assignment::create(['academic_year_id' => $year->academic_year_id, 'user_id' => $users['corps_s1']->user_id]);
        BattalionS1Assignment::create(['battalion_id' => $bn->battalion_id, 'academic_year_id' => $year->academic_year_id, 'user_id' => $users['battalion_s1']->user_id]);
        PlatoonLeaderAssignment::create(['platoon_id' => $pl->platoon_id, 'academic_year_id' => $year->academic_year_id, 'user_id' => $users['platoon_leader']->user_id]);

        // One sample training session for Basic cadets
        $session = TrainingSession::create([
            'academic_year_id' => $year->academic_year_id, 'posted_by_user_id' => $users['adjutant']->user_id,
            'title' => 'Drill Practice', 'session_date' => '2026-12-05',
            'start_time' => '07:00', 'end_time' => '11:00', 'venue' => 'School Grounds',
        ]);
        SessionAudience::create(['session_id' => $session->session_id, 'cadet_type_id' => 1]);
    }
}
