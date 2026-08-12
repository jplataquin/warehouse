<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemType extends Model
{
    protected $fillable = ['name', 'base_behavior'];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
