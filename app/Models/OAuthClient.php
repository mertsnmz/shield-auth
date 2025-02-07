<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OAuthClient extends Model
{
    protected $table = 'oauth_clients';

    protected $fillable = [
        'client_id',
        'client_secret',
        'name',
        'redirect_uri',
        'grant_types',
        'scope',
    ];

    public function scopes(): BelongsToMany
    {
        return $this->belongsToMany(
            OAuthScope::class,
            'oauth_client_scopes',
            'client_id',
            'scope_name',
            'client_id',
            'name'
        );
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(OAuthAccessToken::class, 'client_id', 'client_id');
    }

    public function authCodes(): HasMany
    {
        return $this->hasMany(OAuthAuthCode::class, 'client_id', 'client_id');
    }
}
