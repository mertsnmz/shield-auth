<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OAuthScope extends Model
{
    protected $table = 'oauth_scopes';

    protected $fillable = [
        'name',
        'description',
        'grant_type',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(
            OAuthClient::class,
            'oauth_client_scopes',
            'scope_name',
            'client_id',
            'name',
            'client_id'
        );
    }
}
