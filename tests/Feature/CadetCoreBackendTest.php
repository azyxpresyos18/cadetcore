<?php

namespace Tests\Feature;

use App\Models\{AcademicYear, Enrollment, UserAccount};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CadetCoreBackendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function as(string $serial)
    {
        $user = UserAccount::where('username', $serial)->firstOrFail();
        return $this->actingAs($user)->withSession(['display_name' => 'Test Officer']);
    }

    public function test_every_role_can_log_in_and_open_its_pages(): void
    {
        foreach ([
            'OFF-0001' => ['adjutant', 'adjutant.dashboard'], 'OFF-0002' => ['corps_s1', 'corps.dashboard'],
            'OFF-0003' => ['battalion_s1', 'battalion.dashboard'], 'OFF-0004' => ['platoon_leader', 'platoon.dashboard'],
            'CDT-0001' => ['cadet', 'cadet.attendance'],
        ] as $serial => [$role, $home]) {
            $this->post('/login', ['role' => $role, 'login' => $serial, 'password' => 'password123'])->assertRedirect(route($home));
            $this->post('/logout');
        }

        $this->post('/login', ['role' => 'cadet', 'login' => 'OFF-0001', 'password' => 'password123'])->assertSessionHasErrors('role');
        $this->post('/login', ['role' => 'adjutant', 'login' => 'OFF-0001', 'password' => 'wrong'])->assertSessionHasErrors('login');

        foreach (['dashboard', 'training-schedule', 'merit-demerit', 'cadet-records', 'attendance', 'reports', 'yearly-update', 'academic-years'] as $p) {
            $this->as('OFF-0001')->get("/adjutant/$p")->assertOk();
        }
        foreach (['corps' => 'OFF-0002', 'battalion' => 'OFF-0003'] as $prefix => $serial) {
            foreach (['dashboard', 'training-schedule', 'merit-demerit', 'cadet-records', 'attendance', 'reports'] as $p) {
                $this->as($serial)->get("/$prefix/$p")->assertOk();
            }
        }
        foreach (['dashboard', 'training-schedule', 'attendance', 'merit-demerit'] as $p) {
            $this->as('OFF-0004')->get("/platoon/$p")->assertOk();
        }
        foreach (['attendance', 'training-schedule', 'merit-demerit'] as $p) {
            $this->as('CDT-0001')->get("/cadet/$p")->assertOk();
        }
        // role walls
        $this->as('CDT-0001')->get('/adjutant/dashboard')->assertForbidden();
        $this->as('OFF-0003')->postJson('/battalion/training-schedule', [])->assertStatus(405); // battalion S1 can read the schedule, not change it
    }

    public function test_schedule_crud_and_audience(): void
    {
        $body = ['title' => 'PT Run', 'date' => '2026-12-10', 'time' => '06:00 - 08:30', 'location' => 'Oval', 'type' => 'Physical', 'audience' => 'All Cadets'];
        $id = $this->as('OFF-0001')->postJson('/adjutant/training-schedule', $body)->assertCreated()
            ->assertJsonPath('audience', 'All Cadets')->assertJsonPath('time', '06:00 - 08:30')->json('id');

        $this->as('OFF-0002')->putJson("/corps/training-schedule/$id", ['audience' => 'Regular Cadet'] + $body)->assertOk()->assertJsonPath('audience', 'Regular Cadet');
        $this->as('OFF-0001')->postJson('/adjutant/training-schedule', ['time' => '09:00 - 08:00'] + $body)->assertStatus(422);
        $this->as('OFF-0001')->postJson('/adjutant/training-schedule', ['audience' => 'Nobody'] + $body)->assertStatus(422);

        // a Regular cadet sees it, an Aspiring one would not
        $this->as('CDT-0001')->get('/cadet/training-schedule')->assertSee('PT Run');

        $this->as('OFF-0001')->deleteJson("/adjutant/training-schedule/$id")->assertOk();
    }

    public function test_cadet_roster_add_duplicate_remove(): void
    {
        $body = ['cadet_type' => 'Aspiring Cadet', 'last_name' => 'Lopez', 'first_name' => 'Maria Elena', 'middle_name' => 'Bautista', 'serial' => 'CE-R25-002444', 'battalion' => '1st Battalion', 'company' => 'Alpha Company', 'platoon' => '1st Platoon', 'course' => 'Bachelor of Science in Information Technology-1'];
        $res = $this->as('OFF-0001')->postJson('/adjutant/cadet-records', $body)->assertCreated();
        $eid = $res->json('row.10');
        $this->assertSame('Lopez, Maria Elena B.', $res->json('row.1'));
        $this->assertSame('Bachelor of Science in Information Technology - Year 1', $res->json('row.6'));
        $this->assertDatabaseHas('cadets', ['serial_no' => 'CE-R25-002444', 'last_name' => 'Lopez', 'middle_name' => 'Bautista']);
        $this->assertSame('Aspiring Cadet', \App\Models\Enrollment::find($eid)->cadetType->type_name);

        $this->as('OFF-0001')->postJson('/adjutant/cadet-records', $body)->assertStatus(422);
        $this->as('OFF-0001')->postJson('/adjutant/cadet-records', ['serial' => 'bad'] + $body)->assertStatus(422);
        $this->as('OFF-0001')->postJson('/adjutant/cadet-records', ['platoon' => '9th Platoon', 'serial' => 'CE-R25-009999'] + $body)->assertStatus(422);
        $this->as('OFF-0001')->get('/adjutant/cadet-records')->assertSee('Lopez, Maria Elena B.');

        $this->as('OFF-0001')->deleteJson("/adjutant/cadet-records/$eid")->assertOk();
        $this->assertDatabaseHas('enrollments', ['enrollment_id' => $eid, 'enrollment_status' => 'removed']);
        $this->as('OFF-0001')->get('/adjutant/cadet-records')->assertDontSee('Lopez, Maria Elena B.');
    }

    public function test_platoon_attendance_and_merits_flow_through_to_cadet_and_reports(): void
    {
        $sid = \App\Models\TrainingSession::value('session_id');
        $this->as('OFF-0004')->postJson('/platoon/attendance', [
            'session_id' => $sid, 'substitute' => 'Lt. Sub',
            'rows' => [['serial' => 'CDT-0001', 'status' => 'Late', 'time_in' => '07:15', 'remark' => 'Traffic']],
        ])->assertOk();
        $this->as('OFF-0004')->postJson('/platoon/attendance', ['session_id' => $sid, 'rows' => [['serial' => 'NOT-A-CADET', 'status' => 'Present']]])->assertStatus(422);
        $this->as('OFF-0004')->getJson("/platoon/attendance/$sid/records")->assertJsonPath('CDT-0001.status', 'Late')->assertJsonPath('CDT-0001.time_in', '07:15');

        // saving again updates instead of duplicating
        $this->as('OFF-0004')->postJson('/platoon/attendance', ['session_id' => $sid, 'rows' => [['serial' => 'CDT-0001', 'status' => 'Present']]])->assertOk();
        $this->assertSame(1, \App\Models\AttendanceRecord::count());

        $eid = Enrollment::whereHas('cadet', fn ($q) => $q->where('serial_no', 'CDT-0001'))->value('enrollment_id');
        // demerit points come from the server's offense list, not from the browser
        $this->as('OFF-0004')->postJson('/platoon/merit-demerit', ['enrollment_id' => $eid, 'type' => 'Demerit', 'points' => 99, 'offenses' => ['Late', 'Dirty face'], 'remarks' => 'twice'])
            ->assertCreated()->assertJsonPath('row.4', 5);
        $this->as('OFF-0004')->postJson('/platoon/merit-demerit', ['enrollment_id' => $eid, 'type' => 'Merit', 'points' => 8, 'reason' => 'Best in drill'])->assertCreated();
        $this->as('OFF-0004')->postJson('/platoon/merit-demerit', ['enrollment_id' => $eid, 'type' => 'Merit', 'points' => 8])->assertStatus(422);
        $this->as('OFF-0004')->postJson('/platoon/merit-demerit', ['enrollment_id' => $eid, 'type' => 'Demerit', 'offenses' => ['Made up offense']])->assertStatus(422);

        $this->as('CDT-0001')->get('/cadet/merit-demerit')->assertOk()->assertSee('Best in drill')->assertSee('Late; Dirty face (twice)')->assertSee('+3'); // 8 merit - 5 demerit
        $this->as('CDT-0001')->get('/cadet/attendance')->assertOk()->assertSee('Drill Practice');
        $this->as('OFF-0001')->get('/adjutant/merit-demerit')->assertSee('Best in drill');
        $this->as('OFF-0001')->get('/adjutant/attendance')->assertSee('Dela Cruz');

        $q = ['type' => 'Cadet entry history', 'battalion' => 'All battalions', 'from' => '2026-01-01', 'to' => '2027-12-31'];
        $csv = $this->as('OFF-0001')->get('/adjutant/reports/export?'.http_build_query($q + ['format' => 'Excel']))->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('content-type'));
        $this->assertStringContainsString('Best in drill', $csv->streamedContent());
        $this->as('OFF-0001')->get('/adjutant/reports/export?'.http_build_query($q + ['format' => 'PDF']))->assertOk()->assertSee('Cadet entry history');
        foreach (['Battalion strength', 'Merit / demerit analysis', 'Attendance summary'] as $t) {
            $this->as('OFF-0002')->get('/corps/reports/export?'.http_build_query(['type' => $t] + $q + ['format' => 'PDF']))->assertOk();
        }
        $this->as('OFF-0001')->get('/adjutant/reports/export?'.http_build_query(['to' => '2025-01-01'] + $q + ['format' => 'PDF']))->assertSessionHasErrors('to');
    }

    public function test_yearly_rollover(): void
    {
        $this->as('OFF-0001')->get('/adjutant/yearly-update')->assertOk()->assertSee('graduating Officer records');

        // officers graduate (type 3, year level 4), so "Keep" on them must be refused and nothing may change
        $keepAll = ['archive' => [3], 'officers' => [['key' => 'adjutant', 'action' => 'Keep']]];
        $this->as('OFF-0001')->postJson('/adjutant/yearly-update', $keepAll)->assertStatus(422);
        $this->assertSame('2026-2027', AcademicYear::where('is_current', true)->value('year_label'));
        $this->assertSame(1, AcademicYear::count());

        $unit = \App\Models\Platoon::value('platoon_id');
        $payload = [
            'archive'     => [3],
            'cadets'      => [['sn' => 'CE-R26-000001', 'name' => 'Tan, Mark A.', 'bn' => '1st Battalion', 'co' => 'Alpha Company', 'pl' => '1st Platoon', 'cy' => 'BSIT-1']],
            'officers'    => [
                ['key' => 'adjutant', 'action' => 'Replace', 'name' => 'Ramos, Liza P.', 'sn' => 'CE-R25-000100'],
                ['key' => 'corps_s1', 'action' => 'Deactivate'],
                ['key' => 'battalion_s1:1', 'action' => 'Deactivate'],
                ['key' => "platoon_leader:$unit", 'action' => 'Keep'],
            ],
            'newOfficers' => [['name' => 'Uy, Carl D.', 'sn' => 'CE-R25-000101', 'pos' => 'Corps S1']],
        ];
        $res = $this->as('OFF-0001')->postJson('/adjutant/yearly-update', $payload)->assertOk();
        $this->assertCount(2, $res->json('tempPasswords'));

        $this->assertSame('2027-2028', AcademicYear::where('is_current', true)->value('year_label'));
        $this->assertSame(2, AcademicYear::count());
        $new = AcademicYear::where('is_current', true)->value('academic_year_id');
        $old = AcademicYear::where('is_current', false)->value('academic_year_id');

        // old records stay, graduating officers are archived, the regular cadet moved up a year
        $this->assertSame(5, Enrollment::where('academic_year_id', $old)->count());
        $this->assertSame('graduated', Enrollment::where('academic_year_id', $old)->whereHas('cadet', fn ($q) => $q->where('serial_no', 'OFF-0001'))->value('enrollment_status'));
        $cadet = Enrollment::where('academic_year_id', $new)->whereHas('cadet', fn ($q) => $q->where('serial_no', 'CDT-0001'))->first();
        $this->assertSame(3, (int) $cadet->year_level);
        $this->assertTrue(Enrollment::where('academic_year_id', $new)->whereHas('cadet', fn ($q) => $q->where('serial_no', 'CE-R26-000001'))->exists());

        // new roles resolve in the new year
        $pw = collect($res->json('tempPasswords'))->firstWhere('serial', 'CE-R25-000100')['password'];
        $this->post('/login', ['role' => 'adjutant', 'login' => 'CE-R25-000100', 'password' => $pw])->assertRedirect(route('adjutant.dashboard'));
        $this->post('/logout');
        $this->post('/login', ['role' => 'adjutant', 'login' => 'OFF-0001', 'password' => 'password123'])->assertSessionHasErrors('login'); // deactivated (graduated)
        $this->assertSame('platoon_leader', UserAccount::where('username', 'OFF-0004')->first()->role());
        $this->assertSame('corps_s1', UserAccount::where('username', 'CE-R25-000101')->first()->role());

        // archive page shows the closed year, read-only
        $this->as('CE-R25-000100')->get('/adjutant/academic-years')->assertOk()->assertSee('2026-2027')->assertSee('Dela Cruz');
        $this->as('CE-R25-000100')->postJson('/adjutant/yearly-update', ['archive' => []])->assertOk(); // can roll again (2028-2029)
        $this->assertSame(3, AcademicYear::count());
    }

    public function test_officers_page_and_cadet_records_exclude_officers(): void
    {
        $this->as('OFF-0001')->get('/adjutant/officers')->assertOk()->assertSee('Santos, Maria')->assertSee('Garcia, Pedro')->assertSee('Platoon Leader')->assertSee('1st Battalion · Alpha Company · 1st Platoon')->assertSee('NA');
        $this->as('OFF-0002')->get('/corps/officers')->assertOk()->assertSee('Reyes, Juan');
        $this->as('OFF-0003')->get('/battalion/officers')->assertNotFound();

        // officers are not cadets: they must not appear in cadet records
        $this->as('OFF-0001')->get('/adjutant/cadet-records')->assertSee('Dela Cruz')->assertDontSee('Santos, Maria')->assertDontSee('Garcia, Pedro');
    }

    public function test_add_new_officer(): void
    {
        $this->artisan('cadetcore:structure')->assertSuccessful();
        $base = ['last_name' => 'Uy', 'first_name' => 'Carl', 'middle_name' => '', 'course' => 'Bachelor of Science in Information Systems-3'];

        // Platoon Leader: battalion + company + platoon are used as the assigned unit
        $res = $this->as('OFF-0001')->postJson('/adjutant/officers', $base + ['serial' => 'CE-R25-000201', 'position' => 'Platoon Leader',
            'battalion' => '2nd Battalion', 'company' => 'Bravo Company', 'platoon' => '3rd Platoon'])->assertCreated();
        $this->assertNotEmpty($res->json('password'));
        $this->assertSame('platoon_leader', UserAccount::where('username', 'CE-R25-000201')->first()->role());
        $this->assertDatabaseHas('cadets', ['serial_no' => 'CE-R25-000201', 'middle_name' => null]);
        $this->as('OFF-0001')->get('/adjutant/officers')->assertSee('Uy, Carl')->assertSee('2nd Battalion · Bravo Company · 3rd Platoon');
        $this->as('OFF-0001')->get('/adjutant/cadet-records')->assertDontSee('Uy, Carl');

        // Battalion S1: only a battalion is needed
        $this->as('OFF-0002')->postJson('/corps/officers', ['first_name' => 'Ana', 'middle_name' => 'Lim', 'serial' => 'CE-R25-000202', 'position' => 'Battalion S1', 'battalion' => '3rd Battalion'] + $base)->assertCreated();
        $this->assertSame('battalion_s1', UserAccount::where('username', 'CE-R25-000202')->first()->role());
        $this->as('OFF-0001')->get('/adjutant/officers')->assertSee('3rd Battalion');

        // Corps S1 has no unit; the seeded Corps S1 already holds the position this year
        $this->as('OFF-0001')->postJson('/adjutant/officers', $base + ['serial' => 'CE-R25-000203', 'position' => 'Corps S1'])->assertStatus(422);

        // filled slot, duplicate serial, missing unit and Battalion S1 access are all refused
        $this->as('OFF-0001')->postJson('/adjutant/officers', $base + ['serial' => 'CE-R25-000204', 'position' => 'Platoon Leader',
            'battalion' => '2nd Battalion', 'company' => 'Bravo Company', 'platoon' => '3rd Platoon'])->assertStatus(422);
        $this->as('OFF-0001')->postJson('/adjutant/officers', $base + ['serial' => 'CE-R25-000201', 'position' => 'Battalion S1', 'battalion' => '1st Battalion'])->assertStatus(422);
        $this->as('OFF-0001')->postJson('/adjutant/officers', $base + ['serial' => 'CE-R25-000205', 'position' => 'Battalion S1'])->assertStatus(422);
        $this->as('OFF-0003')->postJson('/battalion/officers', $base + ['serial' => 'CE-R25-000206', 'position' => 'Corps S1'])->assertStatus(404);
    }

    public function test_edit_and_remove_cadet_and_officer(): void
    {
        $this->artisan('cadetcore:structure')->assertSuccessful();
        $eid = Enrollment::whereHas('cadet', fn ($q) => $q->where('serial_no', 'CDT-0001'))->value('enrollment_id');
        $body = ['cadet_type' => 'Aspiring Cadet', 'last_name' => 'Dela Cruz', 'first_name' => 'Jose', 'middle_name' => 'Ramos', 'serial' => 'CE-R25-000301',
            'battalion' => '2nd Battalion', 'company' => 'Alpha Company', 'platoon' => '2nd Platoon', 'course' => 'Bachelor of Science in Information Systems-3'];
        $this->as('OFF-0001')->putJson("/adjutant/cadet-records/$eid", $body)->assertOk();
        $this->assertDatabaseHas('cadets', ['serial_no' => 'CE-R25-000301', 'middle_name' => 'Ramos']);
        $this->assertDatabaseHas('user_accounts', ['username' => 'CE-R25-000301']);
        $this->assertSame(3, (int) Enrollment::find($eid)->year_level);
        $this->as('OFF-0001')->putJson("/adjutant/cadet-records/$eid", ['serial' => 'OFF-0001'] + $body)->assertStatus(422);
        $this->as('OFF-0003')->putJson("/battalion/cadet-records/$eid", $body)->assertStatus(404);

        // officer: move the platoon leader to Corps level is refused (Corps S1 is taken), a battalion S1 move is fine after the slot is free
        $pl = \App\Models\Cadet::where('serial_no', 'OFF-0004')->value('cadet_id');
        $off = ['last_name' => 'Garcia', 'first_name' => 'Pedro', 'serial' => 'OFF-0004', 'email' => 'pedro@example.com', 'course' => 'Bachelor of Science in Information Systems-3'];
        $this->as('OFF-0001')->putJson("/adjutant/officers/$pl", $off + ['position' => 'Corps S1'])->assertStatus(422);
        $this->as('OFF-0001')->putJson("/adjutant/officers/$pl", $off + ['position' => 'Platoon Leader', 'battalion' => '2nd Battalion', 'company' => 'Bravo Company', 'platoon' => '1st Platoon'])->assertOk();
        $this->as('OFF-0001')->get('/adjutant/officers')->assertSee('2nd Battalion · Bravo Company · 1st Platoon');
        $this->assertSame('pedro@example.com', UserAccount::where('username', 'OFF-0004')->value('email'));

        // the Adjutant cannot be edited or removed; others can be removed (login switched off)
        $adj = \App\Models\Cadet::where('serial_no', 'OFF-0001')->value('cadet_id');
        $this->as('OFF-0001')->deleteJson("/adjutant/officers/$adj")->assertStatus(403);
        $this->as('OFF-0001')->deleteJson("/adjutant/officers/$pl")->assertOk();
        $this->assertSame(0, (int) UserAccount::where('username', 'OFF-0004')->value('is_active'));
        $this->as('OFF-0001')->get('/adjutant/officers')->assertDontSee('Garcia, Pedro');
    }

    public function test_sidebar_name_has_no_cadet_prefix_and_forgot_password_works(): void
    {
        $this->post('/login', ['role' => 'cadet', 'login' => 'CDT-0001', 'password' => 'password123']);
        $this->assertSame('Dela Cruz, Jose', session('display_name'));
        $this->post('/logout');

        UserAccount::where('username', 'CDT-0001')->update(['email' => 'jose@example.com']);
        \Illuminate\Support\Facades\Mail::fake();
        $this->postJson('/forgot-password', ['email' => 'jose@example.com'])->assertOk();
        $this->postJson('/forgot-password', ['email' => 'nobody@example.com'])->assertOk();
        \Illuminate\Support\Facades\Mail::assertSentCount(0); // raw mail is not a Mailable; the token row proves it ran
        $this->assertDatabaseCount('password_reset_tokens', 1);

        $token = 'known-token-value';
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->update(['token' => hash('sha256', $token), 'created_at' => now()]);
        $this->post('/reset-password', ['token' => 'wrong', 'email' => 'jose@example.com', 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1'])->assertSessionHasErrors('email');
        $this->post('/reset-password', ['token' => $token, 'email' => 'jose@example.com', 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1'])->assertRedirect(route('login'));
        $this->post('/login', ['role' => 'cadet', 'login' => 'jose@example.com', 'password' => 'newpassword1'])->assertRedirect(route('cadet.attendance'));
    }

    public function test_structure_command_builds_battalions_companies_and_platoons(): void
    {
        $this->artisan('cadetcore:structure')->assertSuccessful();
        $this->assertSame(3, \App\Models\Battalion::count());
        $this->assertSame(6, \App\Models\Company::count());
        $this->assertSame(18, \App\Models\Platoon::count());

        $this->artisan('cadetcore:structure')->assertSuccessful(); // safe to run twice
        $this->assertSame(18, \App\Models\Platoon::count());

        $this->as('OFF-0001')->get('/adjutant/cadet-records')->assertSee('2nd Battalion')->assertSee('Bravo Company')->assertSee('3rd Platoon');
    }
}
