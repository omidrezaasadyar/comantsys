<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected static function booted(): void
    {
        // Clean up the private avatar file when the user is deleted.
        static::deleting(function (User $user): void {
            if ($user->avatar_path) {
                Storage::disk('local')->delete($user->avatar_path);
            }
        });
    }

    /**
     * Panel access. Every user in this system is an operator of the admin
     * panel, so access is unconditional — tighten here (flag / role / email)
     * when non-admin users are introduced.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Secure, authenticated route to the avatar on the private disk.
     * Returns null when none is set, so Filament falls back to the initials avatar.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_path
            ? route('user.avatar', $this)
            : null;
    }

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
        ];
    }
}
