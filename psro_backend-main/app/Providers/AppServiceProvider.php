<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Post;
use App\Policies\AuditLogPolicy;
use App\Policies\PostPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use PragmaRX\Google2FA\Google2FA;
use League\MimeTypeDetection\MimeTypeDetector;
use App\Support\ExtensionMimeTypeDetector;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MimeTypeDetector::class, ExtensionMimeTypeDetector::class);
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            if ($request->user()) {
                return Limit::perMinute(100)->by((string) $request->user()->id);
            }

            return Limit::perMinute(60)->by($request->ip());
        });

        Vite::prefetch(concurrency: 3);

        // Register policies
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        // Password reset link must point to the frontend (Vue app)
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $email = $notifiable->getEmailForPasswordReset();

            return config('app.frontend_url')
                .'/reset-password/'.$token
                .'?email='.urlencode($email);
        });
    }
}
