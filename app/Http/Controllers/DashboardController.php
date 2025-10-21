<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\WpSites;
use App\Models\WpPost;
use App\Models\WpMedia;
use App\Models\User;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index()
    {
        $user = Auth::user();

        $stats = [
            'total_sites' => WpSites::count(),
            'active_sites' => WpSites::where('status', 'connected')->count(),
            'total_posts' => WpPost::count(),
            'draft_posts' => WpPost::where('status', 'draft')->count(),
            'published_posts' => WpPost::where('status', 'published')->count(),
            'scheduled_posts' => WpPost::where('status', 'scheduled')->count(),
            'total_media' => WpMedia::count(),
            'total_users' => User::count(),
        ];

        $userStats = [
            'user_posts' => WpPost::where('user_id', $user->id)->count(),
            'user_media' => WpMedia::where('user_id', $user->id)->count(),
        ];

        $recentPosts = WpPost::with(['wpSite', 'user'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $sites = WpSites::withCount('posts')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $recentActivity = collect();

        $recentPostsActivity = WpPost::with(['wpSite', 'user'])
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($post) {
                return [
                    'type' => 'post',
                    'title' => $post->title,
                    'user' => $post->user->name ?? 'Unknown',
                    'site' => $post->wpSite->site_name ?? 'Unknown',
                    'status' => $post->status,
                    'updated_at' => $post->updated_at,
                ];
            });

        $recentActivity = $recentActivity->merge($recentPostsActivity);

        $recentActivity = $recentActivity->sortByDesc('updated_at')->take(5);

        return view('dashboard', compact('user', 'stats', 'userStats', 'recentPosts', 'sites', 'recentActivity'));
    }
}
