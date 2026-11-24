<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Battalion extends Model
{
    protected $table = 'battalions';
    protected $primaryKey = 'battalion_id';
    public $timestamps = false;
    protected $guarded = [];
}
