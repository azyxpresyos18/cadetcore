<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BattalionS1Assignment extends Model
{
    protected $table = 'battalion_s1_assignments';
    protected $primaryKey = 'battalion_id';
    public $timestamps = false;
    protected $guarded = [];
    public $incrementing = false;
}
