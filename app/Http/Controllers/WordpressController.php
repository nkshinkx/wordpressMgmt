<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WpSites;
use App\Models\WpPost;
use App\Models\WpCategory;
use App\Models\WpMedia;
use App\Models\WpTag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Services\WordpressPostService;




class WordpressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function getWordpressSites(Request $request)
    {
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'last_published_at'); // Default sort by last_published_at
        $sortOrder = $request->input('sort_order', 'desc'); // Default desc (newest first)

        // Validate sort column
        $allowedSorts = ['id', 'site_name', 'domain', 'status', 'last_published_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'last_published_at';
        }

        // Validate sort order
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $query = WpSites::select('wp_sites.*')
            ->leftJoin('wp_posts', function($join) {
                $join->on('wp_sites.id', '=', 'wp_posts.wp_site_id')
                    ->whereNotNull('wp_posts.published_at');
            })
            ->groupBy('wp_sites.id', 'wp_sites.site_name', 'wp_sites.domain', 'wp_sites.rest_path', 
                     'wp_sites.username', 'wp_sites.password', 'wp_sites.jwt_token', 'wp_sites.jwt_expires_at',
                     'wp_sites.status', 'wp_sites.connection_error', 'wp_sites.last_connected_at', 
                     'wp_sites.auto_refresh', 'wp_sites.created_at', 'wp_sites.updated_at')
            ->selectRaw('MAX(wp_posts.published_at) as last_published_at');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('wp_sites.site_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('wp_sites.domain', 'LIKE', '%' . $search . '%');
            });
        }

        // Apply sorting
        if ($sortBy === 'last_published_at') {
            $query->orderByRaw("MAX(wp_posts.published_at) $sortOrder");
        } else {
            $query->orderBy('wp_sites.' . $sortBy, $sortOrder);
        }

        $wordpressSites = $query->paginate(20)->appends([
            'search' => $search,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ]);

        return view('wordpress.managesites')->with([
            'wordpressSites' => $wordpressSites,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder
        ]);
    }

    public function getWordpressPostsView($siteId)
    {
        $wpSite = WpSites::find($siteId);
        if (!$wpSite) {
            return redirect()->route('wordpress.manage')->with('error', 'Wordpress Site not found');
        }

        return view('wordpress.manageposts')->with('wpSite', $wpSite);
    }

    public function getWordpressPostEditor(Request $request)
    {
        $postId = $request->input('post_id');
        $siteId = $request->input('site_id');
        $post = null;

        if ($postId) {
            $post = WpPost::with(['wpSite'])->find($postId);
            if (!$post) {
                return redirect()->route('wordpress.sites.list')->with('error', 'Post not found');
            }
            $siteId = $post->wp_site_id;
        }

        if (!$siteId) {
            return redirect()->route('wordpress.sites.list')->with('error', 'Site ID is required');
        }

        $wpSite = WpSites::find($siteId);
        if (!$wpSite) {
            return redirect()->route('wordpress.sites.list')->with('error', 'WordPress Site not found');
        }

        return view('wordpress.posteditor', compact('wpSite', 'post', 'siteId'));
    }

    public function getWordpressMediaView($siteId)
    {
        $wpSite = WpSites::find($siteId);
        if (!$wpSite) {
            return redirect()->route('wordpress.manage')->with('error', 'Wordpress Site not found');
        }

        return view('wordpress.managemedia', compact('wpSite', 'siteId'));
    }

    public function createWordpressSite(Request $request)
    {
        $rules = [
            'site_name' => 'required|string',
            'domain' => 'required|url',
            'rest_path' => 'nullable|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'auto_refresh' => 'nullable|boolean'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $validated = $validator->validated();

        try {
            $exists = WpSites::where('domain', $validated['domain'])->first();
            if ($exists) {
                return response()->json(['success' => false, 'message' => 'Domain already exists in Wordpress Sites']);
            }
            $wpSite = Wpsites::create($validated);
            $response = $this->checkSiteStatus($wpSite);
            if ($response) {
                return response()->json(['success' => true, 'message' => 'Wordpress Site Created Successfully']);
            } else {
                return response()->json(['success' => false, 'message' => 'Wordpress Site Created But Not Connected']);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something Went Wrong :' . $e->getMessage()
            ], 500);
        }
    }

    public function checkSiteStatus(WpSites $site)
    {
        try {
            $jwtToken = null;

            if ($site->jwt_token && $site->jwt_expires_at) {
                $expiresAt = \Carbon\Carbon::parse($site->jwt_expires_at);

                if ($expiresAt->isFuture()) {
                    Log::info("Found existing JWT token for site {$site->id}, validating...");

                    $validateEndpoint = $site->domain . "/wp-json/jwt-auth/v1/token/validate";

                    $validateResponse = Http::timeout(30)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $site->jwt_token,
                            'Content-Type' => 'application/json'
                        ])
                        ->post($validateEndpoint);

                    if ($validateResponse->successful()) {
                        $validateResult = $validateResponse->json();

                        if (isset($validateResult['code']) && $validateResult['code'] === 'jwt_auth_valid_token') {
                            Log::info("Existing JWT token is valid for site {$site->id}");
                            $jwtToken = $site->jwt_token;

                            $site->status = 'active';
                            $site->last_connected_at = now();
                            $site->connection_error = null;
                            $site->save();

                            return true;
                        } else {
                            Log::info("Existing JWT token is invalid for site {$site->id}, requesting new token...");
                        }
                    } else {
                        Log::info("Existing JWT token is invalid for site {$site->id}, requesting new token...");
                    }
                }
            }

            $tokenEndpoint = $site->domain . "/wp-json/jwt-auth/v1/token";

            $response = Http::timeout(30)
                ->asForm()
                ->post($tokenEndpoint, [
                    'username' => $site->username,
                    'password' => $site->password
                ]);

            if ($response->failed()) {
                $errorMessage = $response->json('message') ?? 'Connection failed';
                Log::error("WordPress connection failed for site {$site->id}: " . $errorMessage);
                $this->updateSiteStatus($site, 'inactive', 'Connection failed: ' . $errorMessage);
                return false;
            }

            $tokenResponse = $response->json();

            if (!isset($tokenResponse['token'])) {
                $errorMessage = $tokenResponse['message'] ?? 'Invalid credentials or JWT plugin not configured';
                Log::error("WordPress token failed for site {$site->id}: " . $errorMessage);
                $this->updateSiteStatus($site, 'inactive', $errorMessage);
                return false;
            }

            $jwtToken = $tokenResponse['token'];

            $validateEndpoint = $site->domain . "/wp-json/jwt-auth/v1/token/validate";

            $validateResponse = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $jwtToken,
                    'Content-Type' => 'application/json'
                ])
                ->post($validateEndpoint);

            if ($validateResponse->failed()) {
                Log::error("WordPress token validation failed for site {$site->id}");
                $this->updateSiteStatus($site, 'inactive', 'Token validation failed');
                return false;
            }

            $validateResult = $validateResponse->json();

            if (!isset($validateResult['code']) || $validateResult['code'] !== 'jwt_auth_valid_token') {
                Log::error("WordPress token invalid for site {$site->id}: " . json_encode($validateResult));
                $this->updateSiteStatus($site, 'inactive', 'Invalid token');
                return false;
            }

            $testEndpoint = $site->domain . $site->rest_path . "users/me";

            $testResponse = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $jwtToken,
                    'Content-Type' => 'application/json'
                ])
                ->get($testEndpoint);

            if ($testResponse->failed()) {
                Log::error("WordPress API test failed for site {$site->id}: HTTP {$testResponse->status()}");
                $this->updateSiteStatus($site, 'inactive', 'API access failed');
                return false;
            }

            $site->jwt_token = $jwtToken;
            $site->jwt_expires_at = now()->addHours(6);
            $site->status = 'active';
            $site->last_connected_at = now();
            $site->connection_error = null;
            $site->save();

            Log::info("WordPress site {$site->id} successfully connected and validated");
            return true;
        } catch (\Exception $e) {
            Log::error("WordPress connection exception for site {$site->id}: " . $e->getMessage());
            $this->updateSiteStatus($site, 'inactive', 'Connection exception: ' . $e->getMessage());
            return false;
        }
    }

    private function updateSiteStatus(Wpsites $site, $status, $error = null)
    {
        $site->status = $status;
        $site->connection_error = $error;
        $site->jwt_token = null;
        $site->jwt_expires_at = null;
        if ($status === 'active') {
            $site->last_connected_at = now();
        }
        $site->save();
    }

    public function updateWordpressSite(Request $request, $id)
    {
        $rules = [
            'site_name' => 'required|string',
            'domain' => 'required|url',
            'rest_path' => 'nullable|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'auto_refresh' => 'nullable|boolean'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $validated = $validator->validated();

        try {
            $wpSite = WpSites::find($id);
            if (!$wpSite) {
                return response()->json(['success' => false, 'message' => 'WordPress Site not found'], 404);
            }
            $wpSite->jwt_token = null;
            $wpSite->jwt_expires_at = null;
            $wpSite->status = 'inactive';
            $wpSite->connection_error = null;
            $wpSite->last_connected_at = null;
            $wpSite->update($validated);
            $response = $this->checkSiteStatus($wpSite);
            if ($response) {
                return response()->json(['success' => true, 'message' => 'Wordpress Site Updated Successfully']);
            } else {
                return response()->json(['success' => false, 'message' => 'Wordpress Site Updated But Not Connected']);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something Went Wrong :' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteWordpressSite($id)
    {
        try {
            $wpSite = WpSites::find($id);
            if (!$wpSite) {
                return response()->json(['success' => false, 'message' => 'WordPress Site not found'], 404);
            }

            $wpSite->delete();
            return response()->json(['success' => true, 'message' => 'WordPress Site deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something Went Wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function testWordpressConnection(Request $request)
    {
        try {
            $siteId = $request->input('site_id');
            $wpSite = WpSites::find($siteId);

            if (!$wpSite) {
                return response()->json(['success' => false, 'message' => 'WordPress Site not found'], 404);
            }

            $connectionResult = $this->checkSiteStatus($wpSite);

            if ($connectionResult) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful! Token validated and API access confirmed.',
                    'status' => 'active',
                    'last_connected' => $wpSite->last_connected_at->format('Y-m-d H:i:s')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection failed: ' . ($wpSite->connection_error ?? 'Unknown error'),
                    'status' => 'inactive'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getWordpressSiteDetails($id)
    {
        try {
            $wpSite = WpSites::find($id);
            if (!$wpSite) {
                return response()->json(['success' => false, 'message' => 'WordPress Site not found'], 404);
            }

            return response()->json(['success' => true, 'data' => $wpSite]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something Went Wrong: ' . $e->getMessage()
            ], 500);
        }
    }


    // ============ POST MANAGEMENT METHODS ============

    public function getWordpressPosts(Request $request, $siteId)
    {
        try {
            $wpSite = WpSites::find($siteId);
            if (!$wpSite) {
                return response()->json(['success' => false, 'message' => 'WordPress Site not found'], 404);
            }

            $status = $request->input('status');
            $search = $request->input('search');
            $sortBy = $request->input('sort_by', 'published_at'); // Default sort by published_at
            $sortOrder = $request->input('sort_order', 'desc'); // Default desc (newest first)

            // Validate sort column
            $allowedSorts = ['id', 'title', 'status', 'wp_status', 'published_at', 'updated_at', 'created_at'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'published_at';
            }

            // Validate sort order
            if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }

            $query = WpPost::where('wp_site_id', $siteId)
                ->with(['user', 'featuredImage']);

            if ($status) {
                $query->where('status', $status);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->orWhere('content', 'LIKE', '%' . $search . '%');
                });
            }

            // Apply sorting with NULL values last
            if ($sortBy === 'published_at') {
                $query->orderByRaw("published_at IS NULL, published_at $sortOrder");
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            $posts = $query->paginate(20)->appends([
                'status' => $status,
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder
            ]);

            return response()->json(['success' => true, 'data' => $posts]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something Went Wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getWordpressPost($id)
    {
        try {
            $post = WpPost::with(['wpSite', 'user', 'featuredImage', 'history.user'])->find($id);
            if (!$post) {
                return response()->json(['success' => false, 'message' => 'Post not found'], 404);
            }

            return response()->json(['success' => true, 'data' => $post]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something Went Wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createWordpressPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wp_site_id' => 'required|exists:wordpress_sites,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'featured_image_id' => 'nullable|exists:wp_media,id',
            'categories' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $post = WpPost::create([
                'wp_site_id' => $request->wp_site_id,
                'user_id' => Auth::id(),
                'title' => $request->title,
                'content' => $request->content,
                'excerpt' => $request->excerpt,
                'featured_image_id' => $request->featured_image_id,
                'categories' => $request->categories,
                'tags' => $request->tags,
                'status' => 'local_draft',
                'wp_status' => 'draft',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Post created locally',
                'data' => $post
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateWordpressPost(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'featured_image_id' => 'nullable|exists:wp_media,id',
            'categories' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $post = WpPost::find($id);
            if (!$post) {
                return response()->json(['success' => false, 'message' => 'Post not found'], 404);
            }

            $post->update([
                'title' => $request->title,
                'content' => $request->content,
                'excerpt' => $request->excerpt,
                'featured_image_id' => $request->featured_image_id,
                'categories' => $request->categories,
                'tags' => $request->tags,
            ]);

            if ($post->isSynced()) {
                $post->status = 'out_of_sync';
                $post->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Post updated locally',
                'data' => $post
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pushPostToWordPress($id)
    {
        try {
            $post = WpPost::find($id);
            if (!$post) {
                return response()->json(['success' => false, 'message' => 'Post not found'], 404);
            }

            $wpSite = WpSites::find($post->wp_site_id);
            if (!$wpSite) {
                return response()->json(['success' => false, 'message' => 'WordPress Site not found'], 404);
            }

            $service = new WordpressPostService();
            $result = $service->pushDraftToWordPress($post, $wpSite);

            if ($result) {
                $previewUrl = $service->getPreviewUrl($post, $wpSite);
                return response()->json([
                    'success' => true,
                    'message' => 'Post pushed to WordPress successfully',
                    'data' => $post->fresh(),
                    'preview_url' => $previewUrl
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $post->error_message ?? 'Failed to push post to WordPress'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }

    public function publishWordpressPost($id)
    {
        try {
            $post = WpPost::find($id);
            if (!$post) {
                return response()->json(['success' => false, 'message' => 'Post not found'], 404);
            }

            if (!$post->wp_post_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post must be pushed to WordPress before publishing'
                ], 400);
            }

            $wpSite = WpSites::find($post->wp_site_id);
            if (!$wpSite) {
                return response()->json(['success' => false, 'message' => 'WordPress Site not found'], 404);
            }

            $service = new WordpressPostService();
            $result = $service->publishPost($post, $wpSite);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Post published successfully!',
                    'data' => $post->fresh(),
                    'live_url' => $result['link'] ?? null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteWordpressPost($id)
    {
        try {
            $post = WpPost::find($id);
            if (!$post) {
                return response()->json(['success' => false, 'message' => 'Post not found'], 404);
            }

            $wpSite = WpSites::find($post->wp_site_id);

            if ($post->wp_post_id && $wpSite) {
                $service = new WordpressPostService();
                $service->deletePostFromWordPress($post, $wpSite);
            }

            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post: ' . $e->getMessage()
            ], 500);
        }
    }

    // ============ MEDIA MANAGEMENT METHODS ============

    public function uploadWordpressMedia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wp_site_id' => 'required|exists:wordpress_sites,id',
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('file');
            $wpSiteId = $request->wp_site_id;

            $path = $file->store('wp-media/' . $wpSiteId);

            $media = WpMedia::create([
                'wp_site_id' => $wpSiteId,
                'user_id' => Auth::id(),
                'original_filename' => $file->getClientOriginalName(),
                'local_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'upload_status' => 'pending',
            ]);

            $wpSite = WpSites::find($wpSiteId);
            $service = new WordpressPostService();
            $uploaded = $service->uploadMedia($media, $wpSite);

            if ($uploaded) {
                return response()->json([
                    'success' => true,
                    'message' => 'Media uploaded successfully',
                    'data' => $media->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $media->error_message ?? 'Failed to upload media to WordPress',
                    'data' => $media->fresh()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload media: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getWordpressMedia($siteId)
    {
        try {
            $media = WpMedia::where('wp_site_id', $siteId)
                ->where('upload_status', 'uploaded')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $media]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch media: ' . $e->getMessage()
            ], 500);
        }
    }

    // ============ CATEGORY & TAG MANAGEMENT METHODS ============

    public function syncWordpressCategories($siteId)
    {
        try {
            $wpSite = WpSites::find($siteId);
            if (!$wpSite) {
                return response()->json(['success' => false, 'message' => 'WordPress Site not found'], 404);
            }

            $service = new WordpressPostService();
            $result = $service->syncCategories($wpSite);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync categories: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getWordpressCategories($siteId)
    {
        try {
            $categories = WpCategory::where('wp_site_id', $siteId)
                ->orderBy('name')
                ->get();

            return response()->json(['success' => true, 'data' => $categories]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncWordpressTags($siteId)
    {
        try {
            $wpSite = WpSites::find($siteId);
            if (!$wpSite) {
                return response()->json(['success' => false, 'message' => 'WordPress Site not found'], 404);
            }

            $service = new WordpressPostService();
            $result = $service->syncTags($wpSite);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync tags: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getWordpressTags($siteId)
    {
        try {
            $tags = WpTag::where('wp_site_id', $siteId)
                ->orderBy('name')
                ->get();

            return response()->json(['success' => true, 'data' => $tags]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tags: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncAllWordpressData($siteId)
    {
        try {
            $wpSite = WpSites::find($siteId);
            if (!$wpSite) {
                return response()->json(['success' => false, 'message' => 'WordPress Site not found'], 404);
            }

            $service = new WordpressPostService();
            $result = $service->syncAllData($wpSite);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync data: ' . $e->getMessage()
            ], 500);
        }
    }
}
