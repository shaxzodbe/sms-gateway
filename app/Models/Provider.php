<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    protected $fillable = [
        'name',
        'nickname',
        'priority',
        'is_active',
        'login',
        'password',
        'token',
        'endpoint',
        'batch_size',
        'rps_limit',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
