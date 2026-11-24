<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CadetType extends Model
{
    protected $table = 'cadet_types';
    protected $primaryKey = 'cadet_type_id';
    public $timestamps = false;
    protected $guarded = [];
}
