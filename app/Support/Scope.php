<?php

namespace App\Support;

use App\Models\{AcademicYear, BattalionS1Assignment, Enrollment, PlatoonLeaderAssignment, TrainingSession, UserAccount};
use Illuminate\Database\Eloquent\Builder;

/**
 * What the signed-in user is allowed to see this academic year:
 * adjutant / corps S1 = everyone, battalion S1 = their battalion(s), platoon leader = their platoon(s), cadet = themselves.
 */
class Scope
{
    public string $role;
    public ?AcademicYear $year;
    public array $battalionIds = [];
    public array $platoonIds = [];
    public ?Enrollment $enrollment = null;

    public function __construct(public UserAccount $user)
    {
        $this->role = $user->role();
        $this->year = Ctx::year();
        $yid = $this->year?->academic_year_id;

        if ($this->role === 'battalion_s1') {
            $this->battalionIds = BattalionS1Assignment::where('academic_year_id', $yid)->where('user_id', $user->user_id)->pluck('battalion_id')->all();
        }
        if ($this->role === 'platoon_leader') {
            $this->platoonIds = PlatoonLeaderAssignment::where('academic_year_id', $yid)->where('user_id', $user->user_id)->pluck('platoon_id')->all();
        }
        if ($this->role === 'cadet') {
            $this->enrollment = Enrollment::where('academic_year_id', $yid)->where('cadet_id', $user->cadet_id)->first();
        }
    }

    public static function for(UserAccount $user): self
    {
        return new self($user);
    }

    public function yearId(): ?int
    {
        return $this->year?->academic_year_id;
    }

    /** Current-year enrollments this user may see (removed records are kept in the database but hidden). */
    public function enrollments(): Builder
    {
        $q = Enrollment::query()
            ->with(['cadet', 'platoon.company.battalion', 'rank', 'cadetType'])
            ->where('academic_year_id', $this->yearId())
            ->where('enrollment_status', '!=', 'removed')
            // Officers are listed on the Officers page, not in cadet data.
            ->whereHas('cadetType', fn ($t) => $t->where('type_name', '!=', 'Officer'));

        return match ($this->role) {
            'battalion_s1'   => $q->whereHas('platoon.company', fn ($c) => $c->whereIn('battalion_id', $this->battalionIds)),
            'platoon_leader' => $q->whereIn('platoon_id', $this->platoonIds),
            'cadet'          => $q->where('enrollment_id', $this->enrollment?->enrollment_id),
            default          => $q,
        };
    }

    /** Training sessions of the current year. Cadets only see sessions meant for their cadet type. */
    public function sessions(): Builder
    {
        $q = TrainingSession::query()->with('audiences')->where('academic_year_id', $this->yearId());

        if ($this->role === 'cadet') {
            $typeId = $this->enrollment?->cadet_type_id;
            $q->whereHas('audiences', fn ($a) => $a->where('cadet_types.cadet_type_id', $typeId));
        }
        return $q;
    }

    public function canEditSchedule(): bool
    {
        return in_array($this->role, ['adjutant', 'corps_s1'], true);
    }
}
