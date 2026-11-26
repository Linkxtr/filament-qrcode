<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQrCode;

use Filament\Contracts\Plugin;
use Filament\Panel;

final class FilamentQrCodePlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(self::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-qrcode';
    }

    public function register(Panel $panel): void
    {
        // Panel-level registrations
    }

    public function boot(Panel $panel): void
    {
        // Panel boot hooks
    }
}
