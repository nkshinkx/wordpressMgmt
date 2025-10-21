<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpPostHistory extends Model
{
    use HasFactory;

    protected $table = 'wp_post_history';

    protected $fillable = [
        'wp_post_id',
        'user_id',
        'action',
        'changes',
        'notes',
    ];

    protected $casts = [
        'wp_post_id' => 'integer',
        'user_id' => 'integer',
        'changes' => 'array',
    ];

    public function wpPost()
    {
        return $this->belongsTo(WpPost::class, 'wp_post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
