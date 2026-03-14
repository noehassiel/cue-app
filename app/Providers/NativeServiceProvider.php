<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Native\Mobile\Events\PushNotification\TokenGenerated;

class NativeServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        /**
         * When the device generates a push notification token (APNS on iOS,
         * FCM on Android), store it in the session so the app can register
         * it with the production API for server-sent push notifications.
         */
        Event::listen(TokenGenerated::class, function (TokenGenerated $event) {
            session(['push_token' => $event->token]);
        });
    }

    /**
     * The NativePHP plugins to enable.
     *
     * Only plugins listed here will be compiled into your native builds.
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    public function plugins(): array
    {
        return [];
    }
}
