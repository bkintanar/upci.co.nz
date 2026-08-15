<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'church_id',
        'role',
        'access_level',
        'region_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'role' => UserRole::class,
            'access_level' => AccessLevel::class,
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the attendance records created by this user.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the church this user belongs to.
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * Get the region this user is assigned to (for regional access).
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function isLocal(): bool
    {
        return $this->access_level === AccessLevel::LOCAL;
    }

    public function isRegional(): bool
    {
        return $this->access_level === AccessLevel::REGIONAL;
    }

    public function isNational(): bool
    {
        return $this->access_level === AccessLevel::NATIONAL;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->access_level;
    }

    /**
     * Whether the user is authorised to see/act on a given church,
     * based only on access_level + scope columns (not role).
     */
    public function canAccessChurch(?Church $church): bool
    {
        if (! $church) {
            return false;
        }

        return match (true) {
            $this->isNational() => true,
            $this->isRegional() => $church->region_id === $this->region_id,
            $this->isLocal() => $church->id === $this->church_id,
            default => false,
        };
    }
}
