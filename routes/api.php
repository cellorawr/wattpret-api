<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\CoinController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReadingListController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIK (token opsional: kalau dikirim, respons memuat status voted/in_library dst.)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/stories', [StoryController::class, 'index']);
Route::get('/stories/{story}', [StoryController::class, 'show']);
Route::get('/ranking', [StoryController::class, 'ranking']);
Route::get('/categories', [StoryController::class, 'categories']);
Route::get('/search', [SearchController::class, 'index']);

Route::get('/chapters/{chapter}', [ChapterController::class, 'show']);
Route::get('/chapters/{chapter}/comments', [ChapterController::class, 'comments']);
Route::get('/chapters/{chapter}/comment-counts', [ChapterController::class, 'commentCounts']);
Route::get('/media/{media}/file', [ChapterController::class, 'showMedia']);

// Harus sebelum /users/{username} agar "suggestions" tidak dianggap username.
Route::get('/users/suggestions', [FollowController::class, 'suggestions'])->middleware('auth:sanctum');
Route::get('/users/{username}', [FollowController::class, 'profile']);
Route::get('/users/{username}/followers', [FollowController::class, 'followersOf']);
Route::get('/users/{username}/following', [FollowController::class, 'followingOf']);
Route::get('/users/{username}/messages', [MessageController::class, 'index']);
Route::get('/users/{username}/reading-lists', [ReadingListController::class, 'ofUser']);
Route::get('/reading-lists/{readingList}', [ReadingListController::class, 'show']);

/*
|--------------------------------------------------------------------------
| BUTUH LOGIN (Authorization: Bearer <token>)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Akun & profil
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::delete('/me', [AuthController::class, 'deleteAccount']);
    Route::put('/me/password', [AuthController::class, 'changePassword']);
    Route::get('/me/avatar', [MediaController::class, 'showAvatar']);
    Route::get('/me/cover', [MediaController::class, 'showCover']);
    Route::post('/me/avatar', [MediaController::class, 'avatar']);
    Route::delete('/me/avatar', [MediaController::class, 'deleteAvatar']);
    Route::post('/me/cover', [MediaController::class, 'cover']);
    Route::delete('/me/cover', [MediaController::class, 'deleteCover']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/verify-email/resend', [AuthController::class, 'resendVerification']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/settings', [SettingsController::class, 'show']);
    Route::put('/settings', [SettingsController::class, 'update']);

    // Karya (penulis)
    Route::get('/my-stories', [StoryController::class, 'mine']);
    Route::post('/stories', [StoryController::class, 'store']);
    Route::put('/stories/{story}', [StoryController::class, 'update']);
    Route::delete('/stories/{story}', [StoryController::class, 'destroy']);
    Route::post('/stories/{story}/cover', [MediaController::class, 'storyCover']);
    Route::post('/stories/{story}/chapters', [StoryController::class, 'storeChapter']);
    Route::put('/stories/{story}/chapters/order', [StoryController::class, 'reorderChapters']);
    Route::post('/stories/{story}/vote', [StoryController::class, 'vote']);
    Route::put('/stories/{story}/progress', [LibraryController::class, 'setProgress']);

    Route::put('/chapters/{chapter}', [ChapterController::class, 'update']);
    Route::delete('/chapters/{chapter}', [ChapterController::class, 'destroy']);
    Route::get('/chapters/{chapter}/revisions', [ChapterController::class, 'revisions']);
    Route::post('/chapters/{chapter}/vote', [ChapterController::class, 'vote']);
    Route::post('/chapters/{chapter}/comments', [ChapterController::class, 'storeComment']);
    Route::post('/chapters/{chapter}/media', [ChapterController::class, 'storeMedia']);
    Route::delete('/comments/{comment}', [ChapterController::class, 'destroyComment']);
    Route::delete('/media/{media}', [ChapterController::class, 'destroyMedia']);

    // Perpustakaan & progres
    Route::get('/library', [LibraryController::class, 'index']);
    Route::post('/library/{story}', [LibraryController::class, 'toggle']);
    Route::post('/library/{story}/archive', [LibraryController::class, 'archive']);
    Route::post('/library/{story}/offline', [LibraryController::class, 'offline']);
    Route::get('/progress', [LibraryController::class, 'progress']);

    // Daftar bacaan
    Route::get('/reading-lists', [ReadingListController::class, 'index']);
    Route::post('/reading-lists', [ReadingListController::class, 'store']);
    Route::put('/reading-lists/{readingList}', [ReadingListController::class, 'update']);
    Route::delete('/reading-lists/{readingList}', [ReadingListController::class, 'destroy']);
    Route::post('/reading-lists/{readingList}/stories/{story}', [ReadingListController::class, 'toggleStory']);
    Route::post('/reading-lists/{readingList}/like', [ReadingListController::class, 'like']);
    Route::get('/liked-lists', [ReadingListController::class, 'liked']);

    // Sosial
    Route::get('/feed', [FeedController::class, 'index']);
    Route::get('/following', [FollowController::class, 'following']);
    Route::post('/users/{username}/follow', [FollowController::class, 'toggle']);
    Route::post('/users/{username}/messages', [MessageController::class, 'store']);
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);

    // Notifikasi & pembaruan
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read']);
    Route::get('/updates', [NotificationController::class, 'updates']);

    // Koin
    Route::get('/coins', [CoinController::class, 'show']);
    Route::post('/coins/claim-daily', [CoinController::class, 'claimDaily']);
    Route::post('/coins/purchase', [CoinController::class, 'purchase']);
    Route::post('/coins/spend', [CoinController::class, 'spend']);
});
