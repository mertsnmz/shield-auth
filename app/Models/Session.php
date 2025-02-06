<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 