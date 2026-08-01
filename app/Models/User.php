<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'nis', 'no_kk', 'phone', 'avatar_path', 'password', 'must_change_password', 'pin'])]
#[Hidden(['password', 'remember_token', 'pin'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

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
            'must_change_password' => 'boolean',
            'pin' => 'hashed',
            'pin_set_at' => 'datetime',
        ];
    }

    public function hasPin(): bool
    {
        return $this->pin !== null;
    }

    /**
     * Deliberately excludes password/pin/remember_token/pin_set_at even
     * though they're fillable - those are already hashed by the time
     * they're saved, but there's no reason to write hash values into the
     * audit log at all (just noise, and one more place a hash could leak
     * from). "Kata sandi diubah"/"PIN diubah" is a more useful log entry
     * than a hash diff anyway - PinService/Profil already flash their own
     * user-facing confirmation for those specifically.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'nis', 'no_kk', 'phone', 'must_change_password'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function santri(): HasOne
    {
        return $this->hasOne(Santri::class);
    }

    public function unitUsahaDikelola(): HasOne
    {
        return $this->hasOne(UnitUsaha::class, 'pengelola_user_id');
    }

    public function waliSantris(): HasMany
    {
        return $this->hasMany(WaliSantri::class);
    }

    /**
     * @return BelongsToMany<Santri, User>
     */
    public function anakAsuh(): BelongsToMany
    {
        return $this->belongsToMany(Santri::class, 'wali_santris')
            ->withPivot(['hubungan', 'is_auto_generated', 'is_primary'])
            ->withTimestamps();
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(WaliDeviceToken::class);
    }

    public function perangkatKios(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'device_petugas')
            ->withPivot(['aktif', 'ditugaskan_oleh', 'ditugaskan_at'])
            ->withTimestamps();
    }

    public function lembagasDikelola(): BelongsToMany
    {
        return $this->belongsToMany(Lembaga::class, 'unit_user')
            ->wherePivot('aktif', true)->withPivot(['akses', 'aktif'])->withTimestamps();
    }

    public function rayonsDikelola(): BelongsToMany
    {
        return $this->belongsToMany(Rayon::class, 'unit_user')
            ->wherePivot('aktif', true)->withPivot(['akses', 'aktif'])->withTimestamps();
    }

    public function waliNotifications(): HasMany
    {
        return $this->hasMany(WaliNotification::class);
    }
}
