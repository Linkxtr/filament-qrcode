<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQrCode;

use Illuminate\Support\ServiceProvider;
use Linkxtr\FilamentQrCode\Services\QrCodeRenderer;

final class FilamentQrCodeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-qrcode');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filament-qrcode.php' => config_path('filament-qrcode.php'),
            ], 'filament-qrcode-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/filament-qrcode'),
            ], 'filament-qrcode-views');

            $this->publishes([
                __DIR__.'/../config/filament-qrcode.php' => config_path('filament-qrcode.php'),
                __DIR__.'/../resources/views' => resource_path('views/vendor/filament-qrcode'),
            ], 'filament-qrcode');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-qrcode.php', 'filament-qrcode');

        $this->app->singleton(QrCodeRenderer::class, fn (): QrCodeRenderer => new QrCodeRenderer);
    }
}
