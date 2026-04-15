<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'name', 'external_id', 'access_token',
        'extra_data', 'is_active', 'last_checked_at', 'comment'
    ];

    protected $casts = [
        'extra_data' => 'array',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_platform')
                    ->withPivot('status', 'error_message', 'external_post_id', 'api_response', 'published_at')
                    ->withTimestamps();
    }

    public function logs()
    {
        return $this->hasMany(PublicationLog::class);
    }
}