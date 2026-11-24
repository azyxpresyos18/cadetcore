<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingSession extends Model
{
    protected $table = 'training_sessions';
    protected $primaryKey = 'session_id';
    public $timestamps = false;
    protected $guarded = [];

    public function audiences() { return $this->belongsToMany(CadetType::class, 'session_audiences', 'session_id', 'cadet_type_id'); }
}
