<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionAudience extends Model
{
    protected $table = 'session_audiences';
    protected $primaryKey = 'session_id';
    public $timestamps = false;
    protected $guarded = [];
    public $incrementing = false;
}
