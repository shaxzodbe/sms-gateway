<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    protected $fillable = [
        'name',
        'content',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }
}
