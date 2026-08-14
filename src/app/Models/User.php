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

#[Fillable(['name', 'email', 'password', 'avatar_path', 'is_portal_user'])]
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
     * Panel access, decided per panel.
     *
     * The two audiences are mutually exclusive: a portal user belongs to the
     * 'portal' panel and nowhere else; everyone else belongs to the operator
     * panels (admin, and any other panel added later).
     *
     * There is deliberately NO super_admin bypass — is_portal_user is absolute,
     * so a portal account can never reach the admin panel by holding a role.
     *
     * The 'portal' branch is dormant until that panel provider is registered;
     * the default branch is live now. This method must keep returning true for
     * operators because APP_ENV=production makes Filament enforce FilamentUser.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // (bool) is a type guard, not a policy change: the attribute is null on an
        // instance whose column was never hydrated (a just-created model, or a
        // partial select), and a bare null would break the bool return type.
        // Null therefore reads as "not a portal user" on both branches.
        return match ($panel->getId()) {
            'portal' => (bool) $this->is_portal_user,
            default  => ! $this->is_portal_user,
        };
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
            'is_portal_user' => 'boolean',
        ];
    }
}
