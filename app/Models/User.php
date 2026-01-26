<?php


namespace App\Models;

use App\Models\Organization;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    // Many-to-many: organizations
    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasPanelShield;
    use HasRoles;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->hasRole(config('filament-shield.super_admin.name', 'super_admin'))) {
            return true;
        }

        return match ($panel->getId()) {
            'admin' => $this->hasRole('admin') || $this->hasPermissionTo('panel_admin'),
            'user' => $this->hasRole('user')
                || $this->hasPermissionTo('panel_user')
                || $this->hasRole('admin')
                || $this->hasPermissionTo('panel_admin'),
            default => false,
        };
    }
}
