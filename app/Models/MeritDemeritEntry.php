<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeritDemeritEntry extends Model
{
    protected $table = 'merit_demerit_entries';
    protected $primaryKey = 'entry_id';
    public $timestamps = false;
    protected $guarded = [];

    public function enrollment() { return $this->belongsTo(Enrollment::class, 'enrollment_id'); }

    public function loggedBy() { return $this->belongsTo(UserAccount::class, 'logged_by_user_id'); }
}
