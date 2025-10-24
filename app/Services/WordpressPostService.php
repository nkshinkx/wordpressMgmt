<?php

namespace App\Services;

use App\Models\WpSites;
use App\Models\WpMedia;
use App\Models\WpPost;
use App\Models\WpCategory;
use App\Models\WpTag;
use App\Models\WpPostHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class WordpressPostService
{
    /**
     * Get or refresh JWT token for a WordPress site
     */

    public function getValidToken(WpSites $site)
    {
        $expiresAt = $site->jwt_expires_at ? Carbon::createFromFormat('Y-m-d H:i:s', $site->jwt_expires_at) : null;

        if ($site->jwt_token && $expiresAt && now()->lt($expiresAt->subMinutes(5))) {
            return $site->jwt_token;
        }

        return $this->refreshToken($site);
    }

    /**
     * Refresh JWT token
     */
    private function refreshToken(WpSites $site)
    {
        try {
            $tokenEndpoint = $site->domain . "/wp-json/jwt-auth/v1/token";

            $response = Http::timeout(30)
                ->asForm()
                ->post($tokenEndpoint, [
                    'username' => $site->username,
                    'password' => $site->password
                ]);

            if ($response->successful()) {
                $tokenResponse = $response->json();
                if (isset($tokenResponse['token'])) {
                    $site->jwt_token = $tokenResponse['token'];
                    $site->jwt_expires_at = now()->addHours(6);
                    $site->save();
                    return $tokenResponse['token'];
                }
            }

            Log::error("Failed to refresh JWT token for site {$site->id}");
            return null;
        } catch (\Exception $e) {
            Log::error("Exception refreshing JWT token for site {$site->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload media to WordPress
     */
    public function uploadMedia(WpMedia $media, WpSites $site)
    {
        try {
            $token = $this->getValidToken($site);
            if (!$token) {
                $media->upload_status = 'failed';
                $media->error_message = 'Failed to get valid JWT token';
                $media->save();
                return false;
            }

            $filePath = storage_path('app/' . $media->local_path);
            if (!file_exists($filePath)) {
                $media->upload_status = 'failed';
                $media->error_message = 'Local file not found';
                $media->save();
                return false;
            }

            $endpoint = $site->domain . $site->rest_path . "media";

            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->attach('file', file_get_contents($filePath), $media->original_filename)
                ->post($endpoint);

            if ($response->status() === 201) {
                $mediaResponse = $response->json();
                $media->wp_media_id = $mediaResponse['id'];
                $media->wp_url = $mediaResponse['source_url'];
                $media->upload_status = 'uploaded';
                $media->error_message = null;
                $media->save();

                Log::info("Media uploaded successfully to site {$site->id}, WP Media ID: {$mediaResponse['id']}");
                return true;
            } else {
                $errorResponse = $response->json();
                $errorMessage = $errorResponse['message'] ?? 'Unknown error';

                $media->upload_status = 'failed';
                $media->error_message = "Upload failed (HTTP {$response->status()}): {$errorMessage}";
                $media->save();

                Log::error("Media upload failed for site {$site->id}: {$errorMessage}");
                return false;
            }
        } catch (\Exception $e) {
            $media->upload_status = 'failed';
            $media->error_message = 'Exception: ' . $e->getMessage();
            $media->save();

            Log::error("Exception uploading media to site {$site->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync categories from WordPress
     */
    public function syncCategories(WpSites $site)
    {
        try {
            $token = $this->getValidToken($site);
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to get valid JWT token'];
            }

            $endpoint = $site->domain . $site->rest_path . "categories?per_page=100";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get($endpoint);

            if ($response->successful()) {
                $categories = $response->json();
                $syncedCount = 0;

                foreach ($categories as $category) {
                    WpCategory::updateOrCreate(
                        [
                            'wp_site_id' => $site->id,
                            'wp_category_id' => $category['id']
                        ],
                        [
                            'name' => $category['name'],
                            'slug' => $category['slug'],
                            'parent_id' => $category['parent'] ?? null,
                            'count' => $category['count'] ?? 0,
                            'synced_at' => now(),
                        ]
                    );
                    $syncedCount++;
                }

                Log::info("Synced {$syncedCount} categories for site {$site->id}");
                return ['success' => true, 'count' => $syncedCount];
            } else {
                Log::error("Failed to sync categories for site {$site->id}, HTTP {$response->status()}");
                return ['success' => false, 'message' => "Failed to fetch categories (HTTP {$response->status()})"];
            }
        } catch (\Exception $e) {
            Log::error("Exception syncing categories for site {$site->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Sync tags from WordPress
     */
    public function syncTags(WpSites $site)
    {
        try {
            $token = $this->getValidToken($site);
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to get valid JWT token'];
            }

            $endpoint = $site->domain . $site->rest_path . "tags?per_page=100";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get($endpoint);

            if ($response->successful()) {
                $tags = $response->json();
                $syncedCount = 0;

                foreach ($tags as $tag) {
                    WpTag::updateOrCreate(
                        [
                            'wp_site_id' => $site->id,
                            'wp_tag_id' => $tag['id']
                        ],
                        [
                            'name' => $tag['name'],
                            'slug' => $tag['slug'],
                            'count' => $tag['count'] ?? 0,
                            'synced_at' => now(),
                        ]
                    );
                    $syncedCount++;
                }

                Log::info("Synced {$syncedCount} tags for site {$site->id}");
                return ['success' => true, 'count' => $syncedCount];
            } else {
                Log::error("Failed to sync tags for site {$site->id}, HTTP {$response->status()}");
                return ['success' => false, 'message' => "Failed to fetch tags (HTTP {$response->status()})"];
            }
        } catch (\Exception $e) {
            Log::error("Exception syncing tags for site {$site->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Create or update post as draft in WordPress
     */
    public function pushDraftToWordPress(WpPost $post, WpSites $site)
    {
        try {
            $token = $this->getValidToken($site);
            if (!$token) {
                $post->status = 'failed';
                $post->error_message = 'Failed to get valid JWT token';
                $post->save();
                return false;
            }

            $postData = [
                'title' => $post->title,
                'content' => $post->content,
                'excerpt' => $post->excerpt ?? '',
                'status' => $post->wp_status,
            ];

            if (!empty($post->categories)) {
                $postData['categories'] = $post->categories;
            }

            if (!empty($post->tags)) {
                $postData['tags'] = $post->tags;
            }

            if ($post->featured_image_id) {
                $featuredImage = WpMedia::find($post->featured_image_id);
                if ($featuredImage && $featuredImage->wp_media_id) {
                    $postData['featured_media'] = $featuredImage->wp_media_id;
                }
            }

            if ($post->wp_author_id) {
                $postData['author'] = $post->wp_author_id;
            }

            $isUpdate = !is_null($post->wp_post_id);
            $endpoint = $site->domain . $site->rest_path . "posts" . ($isUpdate ? "/{$post->wp_post_id}" : "");

            $httpClient = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ]);

            $response = $isUpdate
                ? $httpClient->put($endpoint, $postData)
                : $httpClient->post($endpoint, $postData);

            if (in_array($response->status(), [200, 201])) {
                $wpResponse = $response->json();
                $post->wp_post_id = $wpResponse['id'];
                $post->status = $post->wp_status === 'publish' ? 'published' : 'pushed_draft';

                if ($post->wp_status === 'publish' && !empty($wpResponse['date'])) {
                    $post->published_at = \Carbon\Carbon::parse($wpResponse['date']);
                }

                $post->last_synced_at = now();
                $post->error_message = null;
                $post->save();

                WpPostHistory::create([
                    'wp_post_id' => $post->id,
                    'user_id' => Auth::id(),
                    'action' => $isUpdate ? 'updated' : 'pushed',
                    'notes' => $isUpdate ? 'Post updated in WordPress' : 'Post pushed to WordPress as draft'
                ]);

                Log::info("Post {$post->id} " . ($isUpdate ? 'updated' : 'created') . " in WordPress site {$site->id}, WP Post ID: {$wpResponse['id']}");
                return true;
            } else {
                $errorResponse = $response->json();
                $errorMessage = $errorResponse['message'] ?? 'Unknown error';

                $post->status = 'failed';
                $post->error_message = "Push failed (HTTP {$response->status()}): {$errorMessage}";
                $post->save();

                WpPostHistory::create([
                    'wp_post_id' => $post->id,
                    'user_id' => Auth::id(),
                    'action' => 'failed',
                    'notes' => $errorMessage
                ]);

                Log::error("Failed to push post {$post->id} to WordPress site {$site->id}: {$errorMessage}");
                return false;
            }
        } catch (\Exception $e) {
            $post->status = 'failed';
            $post->error_message = 'Exception: ' . $e->getMessage();
            $post->save();

            WpPostHistory::create([
                'wp_post_id' => $post->id,
                'user_id' => Auth::id(),
                'action' => 'failed',
                'notes' => 'Exception: ' . $e->getMessage()
            ]);

            Log::error("Exception pushing post {$post->id} to WordPress site {$site->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Publish a draft post
     */
    public function publishPost(WpPost $post, WpSites $site)
    {
        if (!$post->wp_post_id) {
            return ['success' => false, 'message' => 'Post not pushed to WordPress yet'];
        }

        try {
            $token = $this->getValidToken($site);
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to get valid JWT token'];
            }

            $endpoint = $site->domain . $site->rest_path . "posts/{$post->wp_post_id}";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ])
                ->put($endpoint, ['status' => 'publish']);

            if ($response->successful()) {
                $wpResponse = $response->json();
                $post->status = 'published';
                $post->wp_status = 'publish';

                // Store published timestamp from WordPress
                if (!empty($wpResponse['date'])) {
                    $post->published_at = \Carbon\Carbon::parse($wpResponse['date']);
                } else {
                    $post->published_at = now();
                }

                $post->last_synced_at = now();
                $post->save();

                WpPostHistory::create([
                    'wp_post_id' => $post->id,
                    'user_id' => Auth::id(),
                    'action' => 'published',
                    'notes' => 'Post published successfully'
                ]);

                Log::info("Post {$post->id} published on WordPress site {$site->id}");
                return ['success' => true, 'link' => $wpResponse['link'] ?? null];
            } else {
                $errorResponse = $response->json();
                $errorMessage = $errorResponse['message'] ?? 'Unknown error';

                Log::error("Failed to publish post {$post->id} on WordPress site {$site->id}: {$errorMessage}");
                return ['success' => false, 'message' => "Publish failed (HTTP {$response->status()}): {$errorMessage}"];
            }
        } catch (\Exception $e) {
            Log::error("Exception publishing post {$post->id} on WordPress site {$site->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Delete post from WordPress
     */
    public function deletePostFromWordPress(WpPost $post, WpSites $site)
    {
        if (!$post->wp_post_id) {
            return ['success' => true, 'message' => 'Post not on WordPress, only deleted locally'];
        }

        try {
            $token = $this->getValidToken($site);
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to get valid JWT token'];
            }

            $endpoint = $site->domain . $site->rest_path . "posts/{$post->wp_post_id}?force=true";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->delete($endpoint);

            if ($response->successful()) {
                Log::info("Post {$post->id} deleted from WordPress site {$site->id}");
                return ['success' => true];
            } else {
                $errorResponse = $response->json();
                $errorMessage = $errorResponse['message'] ?? 'Unknown error';

                Log::error("Failed to delete post {$post->id} from WordPress site {$site->id}: {$errorMessage}");
                return ['success' => false, 'message' => "Delete failed (HTTP {$response->status()}): {$errorMessage}"];
            }
        } catch (\Exception $e) {
            Log::error("Exception deleting post {$post->id} from WordPress site {$site->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Get WordPress preview URL
     */
    public function getPreviewUrl(WpPost $post, WpSites $site)
    {
        if (!$post->wp_post_id) {
            return null;
        }

        return $site->domain . "/?p={$post->wp_post_id}&preview=true";
    }

    /**
     * Create a new category in WordPress if it doesn't exist
     */
    public function createCategory(WpSites $site, $categoryName)
    {
        try {
            $token = $this->getValidToken($site);
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to get valid JWT token'];
            }

            $endpoint = $site->domain . $site->rest_path . "categories";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, ['name' => $categoryName]);

            if ($response->status() === 201) {
                $categoryResponse = $response->json();

                WpCategory::create([
                    'wp_site_id' => $site->id,
                    'wp_category_id' => $categoryResponse['id'],
                    'name' => $categoryResponse['name'],
                    'slug' => $categoryResponse['slug'],
                    'synced_at' => now(),
                ]);

                return ['success' => true, 'category' => $categoryResponse];
            } else {
                $errorResponse = $response->json();
                $errorMessage = $errorResponse['message'] ?? 'Unknown error';
                return ['success' => false, 'message' => $errorMessage];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Create a new tag in WordPress if it doesn't exist
     */
    public function createTag(WpSites $site, $tagName)
    {
        try {
            $token = $this->getValidToken($site);
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to get valid JWT token'];
            }

            $endpoint = $site->domain . $site->rest_path . "tags";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, ['name' => $tagName]);

            if ($response->status() === 201) {
                $tagResponse = $response->json();

                WpTag::create([
                    'wp_site_id' => $site->id,
                    'wp_tag_id' => $tagResponse['id'],
                    'name' => $tagResponse['name'],
                    'slug' => $tagResponse['slug'],
                    'synced_at' => now(),
                ]);

                return ['success' => true, 'tag' => $tagResponse];
            } else {
                $errorResponse = $response->json();
                $errorMessage = $errorResponse['message'] ?? 'Unknown error';
                return ['success' => false, 'message' => $errorMessage];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Sync all data from WordPress (categories, tags, media, posts)
     */
    public function syncAllData(WpSites $site)
    {
        try {
            $results = [
                'categories' => 0,
                'tags' => 0,
                'media' => 0,
                'posts' => 0,
                'errors' => []
            ];

            $categoriesResult = $this->syncCategories($site);
            if ($categoriesResult['success']) {
                $results['categories'] = $categoriesResult['count'];
            } else {
                $results['errors'][] = 'Categories: ' . $categoriesResult['message'];
            }

            $tagsResult = $this->syncTags($site);
            if ($tagsResult['success']) {
                $results['tags'] = $tagsResult['count'];
            } else {
                $results['errors'][] = 'Tags: ' . $tagsResult['message'];
            }

            $mediaResult = $this->syncMediaFromWordPress($site);
            if ($mediaResult['success']) {
                $results['media'] = $mediaResult['count'];
            } else {
                $results['errors'][] = 'Media: ' . $mediaResult['message'];
            }

            $postsResult = $this->syncExistingPosts($site);
            if ($postsResult['success']) {
                $results['posts'] = $postsResult['count'];
            } else {
                $results['errors'][] = 'Posts: ' . $postsResult['message'];
            }

            Log::info("Complete sync for site {$site->id}: " . json_encode($results));

            return [
                'success' => true,
                'results' => $results,
                'message' => sprintf(
                    'Synced %d categories, %d tags, %d media files, %d posts',
                    $results['categories'],
                    $results['tags'],
                    $results['media'],
                    $results['posts']
                )
            ];
        } catch (\Exception $e) {
            Log::error("Exception in complete sync for site {$site->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Sync media from WordPress to our database
     */
    private function syncMediaFromWordPress(WpSites $site)
    {
        try {
            $token = $this->getValidToken($site);
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to get valid JWT token'];
            }

            $endpoint = $site->domain . $site->rest_path . "media?per_page=100";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get($endpoint);

            if ($response->successful()) {
                $mediaItems = $response->json();
                $syncedCount = 0;

                foreach ($mediaItems as $media) {
                    $existingMedia = WpMedia::where('wp_media_id', $media['id'])
                        ->where('wp_site_id', $site->id)
                        ->first();

                    if (!$existingMedia) {
                        WpMedia::create([
                            'wp_site_id' => $site->id,
                            'user_id' => Auth::id(),
                            'original_filename' => basename($media['source_url']),
                            'local_path' => 'synced/' . $site->id . '/' . basename($media['source_url']),
                            'wp_media_id' => $media['id'],
                            'wp_url' => $media['source_url'],
                            'upload_status' => 'uploaded',
                            'file_size' => $media['media_details']['filesize'] ?? null,
                            'mime_type' => $media['mime_type'] ?? null,
                        ]);
                        $syncedCount++;
                    }
                }

                Log::info("Synced {$syncedCount} media files for site {$site->id}");
                return ['success' => true, 'count' => $syncedCount];
            } else {
                Log::error("Failed to sync media for site {$site->id}, HTTP {$response->status()}");
                return ['success' => false, 'message' => "Failed to fetch media (HTTP {$response->status()})"];
            }
        } catch (\Exception $e) {
            Log::error("Exception syncing media for site {$site->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Sync existing posts from WordPress to our database
     */
    public function syncExistingPosts(WpSites $site)
    {
        try {
            $token = $this->getValidToken($site);
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to get valid JWT token'];
            }

            $endpoint = $site->domain . $site->rest_path . "posts?per_page=100";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get($endpoint);

            if ($response->successful()) {
                $posts = $response->json();
                $syncedCount = 0;

                foreach ($posts as $post) {
                    $existingPost = WpPost::where('wp_post_id', $post['id'])->first();

                    $featuredImageId = null;
                    if (!empty($post['featured_media']) && $post['featured_media'] != 0) {
                        $wpMedia = WpMedia::where('wp_media_id', $post['featured_media'])
                            ->where('wp_site_id', $site->id)
                            ->first();
                        if ($wpMedia) {
                            $featuredImageId = $wpMedia->id;
                        }
                    }

                    if ($existingPost) {
                        $existingPost->title = $post['title']['rendered'];
                        $existingPost->content = $post['content']['rendered'];
                        $existingPost->excerpt = $post['excerpt']['rendered'] ?? null;
                        $existingPost->wp_status = $post['status'];
                        $existingPost->wp_author_id = $post['author'];
                        $existingPost->featured_image_id = $featuredImageId;

                        // Store published timestamp if post is published
                        if ($post['status'] === 'publish' && !empty($post['date'])) {
                            $existingPost->published_at = \Carbon\Carbon::parse($post['date']);
                        }

                        $existingPost->last_synced_at = now();
                        $existingPost->save();
                    } else {
                        $newPost = new WpPost();
                        $newPost->wp_post_id = $post['id'];
                        $newPost->title = $post['title']['rendered'];
                        $newPost->content = $post['content']['rendered'];
                        $newPost->excerpt = $post['excerpt']['rendered'] ?? null;
                        $newPost->wp_status = $post['status'];
                        $newPost->status = $post['status'] === 'publish' ? 'published' : 'pushed_draft';
                        $newPost->wp_author_id = $post['author'];
                        $newPost->wp_site_id = $site->id;
                        $newPost->user_id = Auth::id();
                        $newPost->categories = $post['categories'] ?? [];
                        $newPost->tags = $post['tags'] ?? [];
                        $newPost->featured_image_id = $featuredImageId;

                        // Store published timestamp if post is published
                        if ($post['status'] === 'publish' && !empty($post['date'])) {
                            $newPost->published_at = \Carbon\Carbon::parse($post['date']);
                        }

                        $newPost->last_synced_at = now();
                        $newPost->save();
                    }
                    $syncedCount++;
                }

                Log::info("Synced {$syncedCount} existing posts for site {$site->id}");
                return ['success' => true, 'count' => $syncedCount];
            } else {
                Log::error("Failed to sync existing posts for site {$site->id}, HTTP {$response->status()}");
                return ['success' => false, 'message' => "Failed to fetch existing posts (HTTP {$response->status()})"];
            }
        } catch (\Exception $e) {
            Log::error("Exception syncing existing posts for site {$site->id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }
}
