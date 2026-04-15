<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'content', 'topic', 'status', 'user_id',
        'media', 'scheduled_at', 'published_at'
    ];

    protected $casts = [
        'media' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function platforms()
    {
        return $this->belongsToMany(Platform::class, 'post_platform')
                    ->withPivot('status', 'error_message', 'external_post_id', 'api_response', 'published_at')
                    ->withTimestamps();
    }

    public function logs()
    {
        return $this->hasMany(PublicationLog::class);
    }
}