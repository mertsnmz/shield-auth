<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password_hash',
        'two_factor_secret',
        'two_factor_enabled',
        'failed_login_attempts',
        'last_login_at',
        'password_changed_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'two_factor_enabled' => 'boolean',
            'failed_login_attempts' => 'integer',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function passwordHistory(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function oauthAccessTokens(): HasMany
    {
        return $this->hasMany(OAuthAccessToken::class);
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function incrementLoginAttempts()
    {
        $this->increment('failed_login_attempts');
        $this->save();
    }

    public function resetLoginAttempts()
    {
        $this->failed_login_attempts = 0;
        $this->save();
    }

    public function updateLastLogin()
    {
        $this->last_login_at = now();
        $this->save();
    }
}
