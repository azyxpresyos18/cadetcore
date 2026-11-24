<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatoonLeaderAssignment extends Model
{
    protected $table = 'platoon_leader_assignments';
    protected $primaryKey = 'platoon_id';
    public $timestamps = false;
    protected $guarded = [];
    public $incrementing = false;
}
