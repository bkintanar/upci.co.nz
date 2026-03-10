<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Church extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'street',
        'suburb',
        'city',
        'region',
        'zip',
        'country',
        'latitude',
        'longitude',
        'phone',
        'email',
        'website',
        'facebook',
        'twitter',
        'instagram',
        'youtube',
        'service_times',
        'description',
        'is_active',
        'is_featured',
        'assigned_leadership', // Virtual field for form
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_times' => 'array',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the users that belong to this church.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the pastor(s) of this church.
     */
    public function pastors(): HasMany
    {
        return $this->hasMany(User::class)->whereIn('role', [
            \App\Enums\UserRole::PASTOR->value,
            \App\Enums\UserRole::SENIOR_PASTOR->value,
            \App\Enums\UserRole::ASSISTANT_PASTOR->value
        ]);
    }

    /**
     * Get all church leadership (pastors, elders, deacons, etc.).
     */
    public function leadership(): HasMany
    {
        return $this->hasMany(User::class)->whereIn('role', [
            \App\Enums\UserRole::PASTOR->value,
            \App\Enums\UserRole::SENIOR_PASTOR->value,
            \App\Enums\UserRole::ASSISTANT_PASTOR->value,
            \App\Enums\UserRole::ELDER->value,
            \App\Enums\UserRole::DEACON->value,
            \App\Enums\UserRole::USHER->value,
            \App\Enums\UserRole::ADMINISTRATOR->value
        ]);
    }

    /**
     * Get the attendance records for this church.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Scope to get only active churches.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get featured churches.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get churches by region.
     */
    public function scopeByRegion($query, $region)
    {
        return $query->whereRaw('LOWER(region) = ?', [strtolower($region)]);
    }

    /**
     * Scope to get churches with coordinates.
     */
    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    /**
     * Get the full address as a single string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->suburb,
            $this->city,
            $this->zip,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if church has coordinates.
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Get service times formatted for display.
     */
    public function getFormattedServiceTimesAttribute(): array
    {
        if (!$this->service_times) {
            return [];
        }

        return collect($this->service_times)->map(function ($service) {
            $serviceType = $service['service_type'] ?? 'Service';
            $time = $service['time'] ?? '';
            $days = $service['days'] ?? [];

            $dayLabels = [
                'monday' => 'Monday',
                'tuesday' => 'Tuesday',
                'wednesday' => 'Wednesday',
                'thursday' => 'Thursday',
                'friday' => 'Friday',
                'saturday' => 'Saturday',
                'sunday' => 'Sunday',
            ];

            $formattedDays = collect($days)->map(function ($day) use ($dayLabels) {
                return $dayLabels[$day] ?? ucfirst($day);
            })->join(', ');

            return [
                'service_type' => $serviceType,
                'time' => $time,
                'days' => $formattedDays,
                'days_array' => $days,
            ];
        })->toArray();
    }

    /**
     * Get assigned leadership IDs for form.
     */
    public function getAssignedLeadershipAttribute(): array
    {
        return $this->leadership()->pluck('id')->toArray();
    }

    /**
     * Set assigned leadership from form.
     */
    public function setAssignedLeadershipAttribute($value): void
    {
        // This will be handled by the form's afterStateUpdated callback
        // We don't need to do anything here since it's a virtual field
    }
}
