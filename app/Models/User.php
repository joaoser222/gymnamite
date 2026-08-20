<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasVisibility, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_user',
            'user_id',
            'permission_id'
        )->withTimestamps();
    }

    public function permissionsVersion(): string
    {
        $permissionsFingerprint = $this->permissions()
            ->select(['permissions.id'])
            ->selectRaw('permission_user.updated_at as permission_updated_at')
            ->orderBy('permissions.id')
            ->get()
            ->map(fn (Permission $permission): string => $permission->id.':'.($permission->getAttribute('permission_updated_at') ?? ''))
            ->implode('|');

        return hash('sha256', implode('|', [
            $this->id,
            $this->updated_at?->toISOString() ?? '',
            $permissionsFingerprint,
        ]));
    }

    /**
     * @return Collection<int, string>
     */
    public function permissionNames(): Collection
    {
        return $this->permissions()
            ->pluck('name')
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    public function editablePermissionIds(): Collection
    {
        if ($this->role === null) {
            return collect();
        }

        return $this->role->permissions()->pluck('permissions.id')->values();
    }

    /**
     * @return Collection<int, int>
     */
    public function effectivePermissionIds(): Collection
    {
        return $this->permissions()->pluck('permissions.id')->values();
    }
}
