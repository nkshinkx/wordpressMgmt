<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpPost extends Model
{
    use HasFactory;

    protected $table = 'wp_posts';

    protected $fillable = [
        'wp_site_id',
        'user_id',
        'title',
        'content',
        'excerpt',
        'featured_image_id',
        'wp_post_id',
        'status',
        'wp_status',
        'categories',
        'tags',
        'wp_author_id',
        'published_at',
        'scheduled_at',
        'last_synced_at',
        'error_message',
    ];

    protected $casts = [
        'wp_site_id' => 'integer',
        'user_id' => 'integer',
        'featured_image_id' => 'integer',
        'wp_post_id' => 'integer',
        'wp_author_id' => 'integer',
        'categories' => 'array',
        'tags' => 'array',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function wpSite()
    {
        return $this->belongsTo(WpSites::class, 'wp_site_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function featuredImage()
    {
        return $this->belongsTo(WpMedia::class, 'featured_image_id');
    }

    public function history()
    {
        return $this->hasMany(WpPostHistory::class, 'wp_post_id');
    }

    public function isSynced()
    {
        return !is_null($this->wp_post_id) &&
               in_array($this->status, ['pushed_draft', 'published']);
    }

    public function needsSync()
    {
        return $this->status === 'out_of_sync';
    }
}
