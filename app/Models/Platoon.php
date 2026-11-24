<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platoon extends Model
{
    protected $table = 'platoons';
    protected $primaryKey = 'platoon_id';
    public $timestamps = false;
    protected $guarded = [];
}
