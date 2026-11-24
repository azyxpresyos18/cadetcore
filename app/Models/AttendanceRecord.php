<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $table = 'attendance_records';
    protected $primaryKey = 'attendance_id';
    public $timestamps = false;
    protected $guarded = [];

    public function session() { return $this->belongsTo(TrainingSession::class, 'session_id'); }
    public function enrollment() { return $this->belongsTo(Enrollment::class, 'enrollment_id'); }

    public function loggedBy() { return $this->belongsTo(UserAccount::class, 'logged_by_user_id'); }
}
