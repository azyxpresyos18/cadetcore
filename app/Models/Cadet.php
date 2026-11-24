<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cadet extends Model
{
    protected $table = 'cadets';
    protected $primaryKey = 'cadet_id';
    public $timestamps = false;
    protected $guarded = [];

    public function enrollments() { return $this->hasMany(Enrollment::class, 'cadet_id'); }
    public function officer() { return $this->hasOne(Officer::class, 'cadet_id'); }
}
