<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpTag extends Model
{
    use HasFactory;

    protected $table = 'wp_tags_cache';

    protected $fillable = [
        'wp_site_id',
        'wp_tag_id',
        'name',
        'slug',
        'count',
        'synced_at',
    ];

    protected $casts = [
        'wp_site_id' => 'integer',
        'wp_tag_id' => 'integer',
        'count' => 'integer',
        'synced_at' => 'datetime',
    ];

    public function wpSite()
    {
        return $this->belongsTo(WpSites::class, 'wp_site_id');
    }
}
