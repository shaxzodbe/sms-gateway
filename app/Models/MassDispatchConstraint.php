<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MassDispatchConstraint extends Model
{
    protected $fillable = [
        'start_time',
        'end_time',
    ];
}
