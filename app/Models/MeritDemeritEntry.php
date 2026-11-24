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
}
