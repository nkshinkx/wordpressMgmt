<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpCategory extends Model
{
    use HasFactory;

    protected $table = 'wp_categories_cache';

    protected $fillable = [
        'wp_site_id',
        'wp_category_id',
        'name',
        'slug',
        'parent_id',
        'count',
        'synced_at',
    ];

    protected $casts = [
        'wp_site_id' => 'integer',
        'wp_category_id' => 'integer',
        'parent_id' => 'integer',
        'count' => 'integer',
        'synced_at' => 'datetime',
    ];

    public function wpSite()
    {
        return $this->belongsTo(WpSites::class, 'wp_site_id');
    }

    public function parent()
    {
        return $this->belongsTo(WpCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(WpCategory::class, 'parent_id');
    }
}
