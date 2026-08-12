<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiCredential extends Model
{
    protected $fillable = [
        'service',
        'base_url',
        'api_key',
        'secret_key',
        'is_active',
    ];

    protected $casts = [
        'secret_key' => 'encrypted',
        'is_active' => 'boolean',
    ];
}
