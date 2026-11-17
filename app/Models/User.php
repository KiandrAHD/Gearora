<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Support\Rbac\RbacDefaults;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'password',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * @return HasMany<RentalOrder, $this>
     */
    public function rentalOrders(): HasMany
    {
        return $this->hasMany(RentalOrder::class, 'customer_id');
    }

    public function hasRole(string|RoleName $role): bool
    {
        $name = $role instanceof RoleName ? $role->value : $role;
        $this->loadMissing('roles');

        return $this->roles->contains(fn (Role $assigned) => $assigned->name === $name);
    }

    /**
     * @param  array<int, string|RoleName>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isOwner(): bool
    {
        return $this->hasRole(RoleName::Owner);
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(RoleName::staffNames());
    }

    public function hasPermission(string $permission): bool
    {
        $this->loadMissing('roles.permissions');

        foreach ($this->roles as $role) {
            if ($role->permissions->contains(fn (Permission $granted) => $granted->name === $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function assignRole(string|RoleName $role): void
    {
        $name = $role instanceof RoleName ? $role->value : $role;
        $model = Role::query()->where('name', $name)->firstOrFail();

        $this->roles()->syncWithoutDetaching([$model->id]);
        $this->unsetRelation('roles');
    }

    public function hasCustomerRole(): bool
    {
        return $this->hasRole(RoleName::Customer);
    }

    /**
     * Owner memegang kontrol tertinggi dan selalu lolos otorisasi.
     */
    public function isSuperAdmin(): bool
    {
        return $this->isOwner();
    }

    /**
     * @return array<int, string>
     */
    public function roleNames(): array
    {
        $this->loadMissing('roles');

        return $this->roles->pluck('name')->all();
    }

    /**
     * Nama role pertama yang bisa ditampilkan sebagai identitas staf.
     */
    public function primaryStaffRoleLabel(): ?string
    {
        $this->loadMissing('roles');

        foreach (RbacDefaults::staffRoleOrder() as $name) {
            $match = $this->roles->firstWhere('name', $name);

            if ($match !== null) {
                return $match->label;
            }
        }

        return null;
    }
}
