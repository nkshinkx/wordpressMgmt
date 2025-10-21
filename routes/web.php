<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\WordpressController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CronController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'checkRole:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users/create', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::post('/users/{id}/edit', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{id}/delete', [UserController::class, 'delete'])->name('users.delete');
});

Route::middleware(['auth'])->group(function () {
    Route::get('wordpress-sites-list', [WordpressController::class, 'getWordpressSites'])->name('wordpress.sites.list');
    Route::post('wordpress-sites', [WordpressController::class, 'createWordpressSite'])->name('wordpress.sites.create')->middleware('checkRole:admin,manager');
    Route::put('wordpress-sites/{id}', [WordpressController::class, 'updateWordpressSite'])->name('wordpress.sites.update')->middleware('checkRole:admin,manager');
    Route::delete('wordpress-sites/{id}', [WordpressController::class, 'deleteWordpressSite'])->name('wordpress.sites.delete')->middleware('checkRole:admin');
    Route::get('wordpress-sites/{id}', [WordpressController::class, 'getWordpressSiteDetails'])->name('wordpress.sites.details');


    Route::post('test-wordpress-connection', [WordpressController::class, 'testWordpressConnection'])->name('wordpress.connection.test');

    Route::get('wordpress-posts/{siteId}', [WordpressController::class, 'getWordpressPostsView'])->name('wordpress.posts.view');
    Route::get('wordpress-post-editor', [WordpressController::class, 'getWordpressPostEditor'])->name('wordpress.post.editor');
    Route::get('wordpress-media/{siteId}', [WordpressController::class, 'getWordpressMediaView'])->name('wordpress.media.view');


    Route::post('api/wordpress-data/{siteId}/sync', [WordpressController::class, 'syncAllWordpressData'])->name('wordpress.data.sync');
    Route::get('api/wordpress-posts/{siteId}', [WordpressController::class, 'getWordpressPosts'])->name('wordpress.posts.list');
    Route::get('api/wordpress-post/{id}', [WordpressController::class, 'getWordpressPost'])->name('wordpress.post.get');
    Route::post('api/wordpress-posts', [WordpressController::class, 'createWordpressPost'])->name('wordpress.post.create');
    Route::put('api/wordpress-posts/{id}', [WordpressController::class, 'updateWordpressPost'])->name('wordpress.post.update');
    Route::post('api/wordpress-posts/{id}/push', [WordpressController::class, 'pushPostToWordPress'])->name('wordpress.post.push');
    Route::post('api/wordpress-posts/{id}/publish', [WordpressController::class, 'publishWordpressPost'])->name('wordpress.post.publish');
    Route::delete('api/wordpress-posts/{id}', [WordpressController::class, 'deleteWordpressPost'])->name('wordpress.post.delete')->middleware('checkRole:admin,manager');

    Route::post('api/wordpress-media', [WordpressController::class, 'uploadWordpressMedia'])->name('wordpress.media.upload');
    Route::get('api/wordpress-media/{siteId}', [WordpressController::class, 'getWordpressMedia'])->name('wordpress.media.list');

    Route::post('api/wordpress-categories/{siteId}/sync', [WordpressController::class, 'syncWordpressCategories'])->name('wordpress.categories.sync');
    Route::get('api/wordpress-categories/{siteId}', [WordpressController::class, 'getWordpressCategories'])->name('wordpress.categories.list');
    Route::post('api/wordpress-tags/{siteId}/sync', [WordpressController::class, 'syncWordpressTags'])->name('wordpress.tags.sync');
    Route::get('api/wordpress-tags/{siteId}', [WordpressController::class, 'getWordpressTags'])->name('wordpress.tags.list');
});
