<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Officer extends Model
{
    protected $table = 'officers';
    protected $primaryKey = 'cadet_id';
    public $timestamps = false;
    protected $guarded = [];
    public $incrementing = false;
}
