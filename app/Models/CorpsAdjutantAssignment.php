<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorpsAdjutantAssignment extends Model
{
    protected $table = 'corps_adjutant_assignments';
    protected $primaryKey = 'academic_year_id';
    public $timestamps = false;
    protected $guarded = [];
    public $incrementing = false;
}
