<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            $id = $notifiable->getKey();
            $hash = sha1($notifiable->getEmailForVerification());
            $backendUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $id,
                    'hash' => $hash,
                ],
                false,
            );
            $query = parse_url($backendUrl, PHP_URL_QUERY);
            $frontendUrl = rtrim((string) config('app.frontend_url'), '/')."/auth/email/verify/{$id}/{$hash}";

            return $query ? "{$frontendUrl}?{$query}" : $frontendUrl;
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $frontendUrl = rtrim((string) config('app.frontend_url'), '/').'/auth/reset-password/'.rawurlencode($token);
            $query = http_build_query([
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return "{$frontendUrl}?{$query}";
        });

        Scramble::configure()
            ->expose(document: '/docs/openapi.json')
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo): void {
                if ($routeInfo->className() === \App\Http\Controllers\AudioController::class
                    && $routeInfo->methodName() === 'byMode') {
                    $operation->security = [];
                }
            })
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->secure(SecurityScheme::http('bearer'));
            });
    }
}
