<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpSites extends Model
{
    use HasFactory;

    protected $table = 'wp_sites';

    protected $fillable = [
        'site_name',
        'domain',
        'rest_path',
        'username',
        'password',
        'jwt_token',
        'jwt_expires_at',
        'status',
        'connection_error',
        'last_connected_at',
        'auto_refresh'
    ];

    protected $casts = [
        'auto_refresh' => 'boolean',
        'jwt_expires_at' => 'datetime',
        'last_connected_at' => 'datetime'
    ];

    protected $attributes = [
        'rest_path' => '/wp-json/wp/v2/',
        'auto_refresh' => true
    ];

    public function posts()
    {
        return $this->hasMany(WpPost::class, 'wp_site_id');
    }

    public function media()
    {
        return $this->hasMany(WpMedia::class, 'wp_site_id');
    }
}
