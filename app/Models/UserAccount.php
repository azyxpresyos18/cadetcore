<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class UserAccount extends Authenticatable
{
    protected $table = 'user_accounts';
    protected $primaryKey = 'user_id';
    public $timestamps = false;
    protected $guarded = [];
    protected $hidden = ['password_hash'];

    // Our password column is password_hash, so tell Laravel.
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function cadet() { return $this->belongsTo(Cadet::class, 'cadet_id'); }

    // Returns: adjutant, corps_s1, battalion_s1, platoon_leader, or cadet
    public function role(): string
    {
        $year = AcademicYear::where('is_current', true)->value('academic_year_id');
        if (CorpsAdjutantAssignment::where('academic_year_id', $year)->where('user_id', $this->user_id)->exists()) return 'adjutant';
        if (CorpsS1Assignment::where('academic_year_id', $year)->where('user_id', $this->user_id)->exists()) return 'corps_s1';
        if (BattalionS1Assignment::where('academic_year_id', $year)->where('user_id', $this->user_id)->exists()) return 'battalion_s1';
        if (PlatoonLeaderAssignment::where('academic_year_id', $year)->where('user_id', $this->user_id)->exists()) return 'platoon_leader';
        return 'cadet';
    }
}
