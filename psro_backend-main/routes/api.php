<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaptchaController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\PageSettingsController;
use App\Http\Controllers\Api\PostCategoryController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\SiteSettingsController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AdminSecurityController;
use App\Http\Controllers\Api\BlockedIpController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
// --- Public API (no auth) ---
Route::get('/home', [PublicController::class, 'index'])->name('api.home');
Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'submit'])->name('api.contact.submit');

Route::get('/storage/{path}', function (string $path) {

    // جلوگیری path traversal attack
    if (str_contains($path, '..')) {
        abort(403);
    }

    $filePath = storage_path('app/public/' . $path);

    if (!file_exists($filePath) || !is_file($filePath)) {
        abort(404);
    }

    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    $mimeTypes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'pdf'  => 'application/pdf',
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'mp3'  => 'audio/mpeg',
        'zip'  => 'application/zip',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    $mime = $mimeTypes[$extension] ?? 'application/octet-stream';

    return response()->stream(function () use ($filePath) {
        $handle = fopen($filePath, 'rb');
        fpassthru($handle);
        fclose($handle);
    }, 200, [
        'Content-Type' => $mime,
        'Content-Length' => filesize($filePath),
        'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
    ]);

})->where('path', '.*')->name('api.storage.serve');
Route::get('/posts', [PostController::class, 'index'])->name('api.posts.index');
Route::get('/posts/categories', [PostController::class, 'categories'])->name('api.posts.categories');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('api.posts.show');

Route::get('/captcha', [CaptchaController::class, 'show'])->middleware('throttle:10,1')->name('api.captcha');
Route::post('/captcha', [CaptchaController::class, 'show'])->middleware('throttle:10,1')->name('api.captcha.generate');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('api.login');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('api.register');
Route::post('/two-factor-challenge', [\App\Http\Controllers\Api\TwoFactorController::class, 'challenge'])->name('api.two-factor.challenge');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.password.email');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('api.password.store');

// --- Protected API (auth:sanctum) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');

    Route::post('/unlock-screen', [ProfileController::class, 'verifyUnlockPassword'])->name('api.unlock-screen');

    Route::get('/profile', [ProfileController::class, 'show'])->name('api.profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('api.profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('api.profile.avatar.upload');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('api.profile.avatar.remove');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('api.password.update');

    Route::post('/two-factor/setup', [\App\Http\Controllers\Api\TwoFactorController::class, 'setup'])->name('api.two-factor.setup');
    Route::post('/two-factor/confirm', [\App\Http\Controllers\Api\TwoFactorController::class, 'confirm'])->name('api.two-factor.confirm');
    Route::post('/two-factor/disable', [\App\Http\Controllers\Api\TwoFactorController::class, 'disable'])->name('api.two-factor.disable');

    Route::get('/page-settings', [PageSettingsController::class, 'show'])->name('api.page-settings.show');
    Route::put('/page-settings', [PageSettingsController::class, 'update'])->name('api.page-settings.update');
    Route::post('/page-settings/upload-activity-image', [PageSettingsController::class, 'uploadActivityImage'])->name('api.page-settings.upload-activity-image');
    Route::post('/page-settings/upload-header-logo', [PageSettingsController::class, 'uploadHeaderLogo'])->name('api.page-settings.upload-header-logo');
    Route::post('/page-settings/upload-hero-slider-image', [PageSettingsController::class, 'uploadHeroSliderImage'])->name('api.page-settings.upload-hero-slider-image');

    Route::get('/site-settings', [SiteSettingsController::class, 'show'])->name('api.site-settings.show');
    Route::put('/site-settings', [SiteSettingsController::class, 'update'])->name('api.site-settings.update');
    Route::put('/site-settings/activity-section-layout', [SiteSettingsController::class, 'updateActivitySectionLayout'])->name('api.site-settings.activity-section-layout');
    Route::put('/site-settings/activity-section', [SiteSettingsController::class, 'updateActivitySection'])->name('api.site-settings.activity-section');
    Route::put('/site-settings/post-visibility', [SiteSettingsController::class, 'updatePostVisibility'])->name('api.site-settings.post-visibility');

    // Post management routes (admin only)
    Route::post('/posts', [PostController::class, 'store'])->name('api.posts.store');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('api.posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('api.posts.destroy');
    Route::post('/posts/bulk-delete', [PostController::class, 'bulkDelete'])->name('api.posts.bulk-delete');
    Route::post('/posts/reorder-slider', [PostController::class, 'reorderSlider'])->name('api.posts.reorder-slider');

    // Post category management
    Route::get('/post-categories', [PostCategoryController::class, 'index'])->name('api.post-categories.index');
    Route::post('/post-categories', [PostCategoryController::class, 'store'])->name('api.post-categories.store');
    Route::put('/post-categories/{postCategory}', [PostCategoryController::class, 'update'])->name('api.post-categories.update');
    Route::delete('/post-categories/{postCategory}', [PostCategoryController::class, 'destroy'])->name('api.post-categories.destroy');
    Route::post('/post-categories/reorder', [PostCategoryController::class, 'reorder'])->name('api.post-categories.reorder');

    Route::get('/backup', [BackupController::class, 'show'])->name('api.backup.show');
    Route::post('/backup/export', [BackupController::class, 'export'])->name('api.backup.export');
    Route::post('/backup/import', [BackupController::class, 'import'])->name('api.backup.import');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('api.audit-logs.index');

    Route::get('/admin/security', [AdminSecurityController::class, 'show'])->name('api.admin.security.show');
    Route::put('/admin/security', [AdminSecurityController::class, 'update'])->name('api.admin.security.update');
    Route::get('/admin/blocked-ips', [BlockedIpController::class, 'index'])->name('api.admin.blocked-ips.index');
    Route::post('/admin/blocked-ips', [BlockedIpController::class, 'store'])->name('api.admin.blocked-ips.store');
    Route::delete('/admin/blocked-ips/{blockedIp}', [BlockedIpController::class, 'destroy'])->name('api.admin.blocked-ips.destroy');
});
});
