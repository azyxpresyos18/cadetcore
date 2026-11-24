<?php

namespace App\Console\Commands;

use App\Models\{AcademicYear, Battalion, Cadet, CadetType, Company, CorpsAdjutantAssignment,
    Enrollment, Officer, Platoon, Rank, UserAccount};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Hash};

class SetupCadetCore extends Command
{
    protected $signature = 'cadetcore:setup';
    protected $description = 'First-time setup: academic year, ranks, cadet types, one unit and the Corps Adjutant account';

    public function handle(): int
    {
        if (AcademicYear::exists() || UserAccount::exists()) {
            $this->error('This database already has data. Run this only on an empty database.');
            return self::FAILURE;
        }

        $label = $this->ask('Academic year label', '2026-2027');
        $start = $this->ask('Start date (YYYY-MM-DD)', '2026-08-01');
        $end   = $this->ask('End date (YYYY-MM-DD)', '2027-05-31');
        $bn    = $this->ask('First battalion name');
        $co    = $this->ask('First company name');
        $pl    = $this->ask('First platoon name');

        $this->info('Corps Adjutant account (use the real serial format, e.g. CE-R25-002444)');
        $serial = $this->ask('Serial number');
        $last   = $this->ask('Last name');
        $first  = $this->ask('First name');
        $course = $this->ask('Course (full name, e.g. Bachelor of Science in Information Technology)');
        $pass   = $this->secret('Password (min 8 characters)');
        if (strlen((string) $pass) < 8 || $pass !== $this->secret('Confirm password')) {
            $this->error('Password too short or the two entries do not match.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($label, $start, $end, $bn, $co, $pl, $serial, $last, $first, $course, $pass) {
            $year = AcademicYear::create([
                'year_label' => $label, 'start_date' => $start, 'end_date' => $end, 'is_current' => true,
            ]);

            foreach (['Cadet', 'Corps Adjutant', 'Corps S1 Personnel Officer', 'Battalion S1 Officer', 'Platoon Leader'] as $r) {
                Rank::create(['rank_name' => $r]);
            }
            foreach (['Regular Cadet', 'Aspiring Cadet', 'Officer'] as $t) {
                CadetType::create(['type_name' => $t]);
            }

            $battalion = Battalion::create(['battalion_name' => $bn]);
            $company   = Company::create(['battalion_id' => $battalion->battalion_id, 'company_name' => $co]);
            $platoon   = Platoon::create(['company_id' => $company->company_id, 'platoon_name' => $pl]);

            $cadet = Cadet::create([
                'serial_no' => $serial, 'last_name' => $last, 'first_name' => $first, 'course' => $course,
            ]);
            Officer::create(['cadet_id' => $cadet->cadet_id, 'officer_serial_no' => $serial]);

            $user = UserAccount::create([
                'cadet_id' => $cadet->cadet_id, 'username' => $serial,
                'password_hash' => Hash::make($pass), 'is_active' => true,
            ]);

            Enrollment::create([
                'academic_year_id' => $year->academic_year_id, 'platoon_id' => $platoon->platoon_id,
                'rank_id' => Rank::where('rank_name', 'Corps Adjutant')->value('rank_id'),
                'cadet_type_id' => CadetType::where('type_name', 'Officer')->value('cadet_type_id'),
                'cadet_id' => $cadet->cadet_id, 'year_level' => 4, 'enrollment_status' => 'active',
            ]);

            CorpsAdjutantAssignment::create([
                'academic_year_id' => $year->academic_year_id, 'user_id' => $user->user_id,
            ]);
        });

        $this->call('cadetcore:structure');

        $this->info('Done. Sign in as Corps Adjutant with the serial number and password you just entered.');
        return self::SUCCESS;
    }
}
