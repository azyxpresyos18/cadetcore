<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $table = 'enrollments';
    protected $primaryKey = 'enrollment_id';
    public $timestamps = false;
    protected $guarded = [];

    public function cadet() { return $this->belongsTo(Cadet::class, 'cadet_id'); }
    public function platoon() { return $this->belongsTo(Platoon::class, 'platoon_id'); }
    public function rank() { return $this->belongsTo(Rank::class, 'rank_id'); }
    public function cadetType() { return $this->belongsTo(CadetType::class, 'cadet_type_id'); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class, 'academic_year_id'); }
}
