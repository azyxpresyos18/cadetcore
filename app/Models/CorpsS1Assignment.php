<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorpsS1Assignment extends Model
{
    protected $table = 'corps_s1_assignments';
    protected $primaryKey = 'academic_year_id';
    public $timestamps = false;
    protected $guarded = [];
    public $incrementing = false;
}
