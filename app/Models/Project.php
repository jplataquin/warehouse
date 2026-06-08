<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'mapped_to_project_id'];

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }
}
