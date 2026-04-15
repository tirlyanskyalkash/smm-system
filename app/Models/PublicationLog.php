<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id', 'platform_id', 'action', 'status',
        'request_data', 'response_data', 'error_message', 'ip_address'
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
}