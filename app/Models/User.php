<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_country_code',
        'phone_verified_at',
        'date_of_birth',
        'gender',
        'avatar',
        'bio',
        'user_type',
        'status',
        'locale',
        'timezone',
        'currency',
        'notification_preferences',
        'last_login_at',
        'last_login_ip',
        'login_count',
        'failed_login_attempts',
        'locked_until',
        'password_changed_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'company_name',
        'tax_id',
        'business_type',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'password_changed_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'notification_preferences' => 'array',
        'login_count' => 'integer',
        'failed_login_attempts' => 'integer',
        'is_default' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Get user by email or phone.
     *
     * @param array $input
     * @return Builder|Model|object|null
     */
    final public function getUserEmailOrPhone(array $input): Builder|Model|null
    {
        return self::query()->where('email', $input['email'])->orWhere('phone', $input['email'])->first();
    }

    /**
     * Get the user's addresses.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * Get the user's default address.
     */
    public function defaultAddress(): HasOne
    {
        return $this->hasOne(UserAddress::class)->where('is_default', true);
    }

    /**
     * Get the user's vendor profile.
     */
    public function vendorProfile(): HasOne
    {
        return $this->hasOne(VendorProfile::class);
    }

    /**
     * Get the user's corporate profile.
     */
    public function corporateProfile(): HasOne
    {
        return $this->hasOne(CorporateProfile::class);
    }

    /**
     * Get the user's shop access.
     */
    public function shopAccess(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'user_shop_access', 'user_id', 'shop_id')
            ->withPivot('role', 'is_primary', 'granted_at', 'revoked_at')
            ->withTimestamps();
    }

    /**
     * Get the user's primary shop.
     */
    public function primaryShop()
    {
        return $this->shopAccess()
            ->wherePivot('is_primary', true)
            ->wherePivotNull('revoked_at')
            ->first();
    }

    /**
     * Get shops where user has access.
     */
    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'user_shop_access', 'user_id', 'shop_id')
            ->wherePivotNull('revoked_at')
            ->withPivot('role', 'is_primary', 'granted_at')
            ->withTimestamps();
    }

    /**
     * Get the user's social logins.
     */
    public function socialLogins(): HasMany
    {
        return $this->hasMany(SocialLogin::class);
    }

    /**
     * Get the user's activity logs.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class);
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is locked.
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Record login activity.
     */
    public function recordLogin(string $ipAddress = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress ?? request()->ip(),
            'login_count' => $this->login_count + 1,
            'failed_login_attempts' => 0,
        ]);

        // Log activity
        $this->activityLogs()->create([
            'action' => 'login',
            'description' => 'User logged in',
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Record failed login attempt.
     */
    public function recordFailedLogin(): void
    {
        $attempts = $this->failed_login_attempts + 1;
        $this->update(['failed_login_attempts' => $attempts]);

        // Lock account after 5 failed attempts
        if ($attempts >= 5) {
            $this->update(['locked_until' => now()->addMinutes(30)]);
        }
    }

    /**
     * Check if user has access to a shop.
     */
    public function hasShopAccess(int $shopId): bool
    {
        return $this->shopAccess()
            ->where('shops.id', $shopId)
            ->wherePivotNull('revoked_at')
            ->exists();
    }

    /**
     * Get shop ID (for backward compatibility).
     * Returns primary shop ID or first shop ID.
     * Note: This is a computed property, not a database column.
     */
    public function getShopIdAttribute()
    {
        // Check if we already have a cached shop_id from a previous query
        if (isset($this->attributes['shop_id'])) {
            return $this->attributes['shop_id'];
        }

        // Get from user_shop_access table
        $primaryShop = $this->shopAccess()
            ->wherePivot('is_primary', true)
            ->wherePivotNull('revoked_at')
            ->first();
        
        if ($primaryShop) {
            return $primaryShop->id;
        }

        $firstShop = $this->shopAccess()
            ->wherePivotNull('revoked_at')
            ->first();
        
        return $firstShop ? $firstShop->id : null;
    }
}
