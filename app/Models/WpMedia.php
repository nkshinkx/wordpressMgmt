<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpMedia extends Model
{
    use HasFactory;

    protected $table = 'wp_media';

    protected $fillable = [
        'wp_site_id',
        'user_id',
        'original_filename',
        'local_path',
        'wp_media_id',
        'wp_url',
        'upload_status',
        'file_size',
        'mime_type',
        'error_message',
    ];

    protected $casts = [
        'wp_site_id' => 'integer',
        'user_id' => 'integer',
        'wp_media_id' => 'integer',
        'file_size' => 'integer',
    ];

    public function wpSite()
    {
        return $this->belongsTo(WpSites::class, 'wp_site_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function posts()
    {
        return $this->hasMany(WpPost::class, 'featured_image_id');
    }
}
