<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'needs_password_change'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'needs_password_change' => 'boolean',
        ];
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_loggers');
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isSupervisor()
    {
        return $this->role === 'supervisor';
    }

    public function isLogger()
    {
        return $this->role === 'logger';
    }

    public function isViewer()
    {
        return $this->role === 'viewer';
    }

    public function hasWarehouseAccess($warehouseId)
    {
        if ($this->isAdmin() || $this->isSupervisor()) {
            return true;
        }

        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse) {
            return false;
        }

        $isAssigned = $this->warehouses()->where('warehouses.id', $warehouseId)->active()->exists();
        if ($isAssigned) {
            return true;
        }

        if ($warehouse->parent_id) {
            return $this->warehouses()->where('warehouses.id', $warehouse->parent_id)->active()->exists();
        }

        return false;
    }
}
