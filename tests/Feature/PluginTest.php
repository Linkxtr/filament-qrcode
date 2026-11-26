<?php

declare(strict_types=1);

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;
use Linkxtr\FilamentQrCode\FilamentQrCodePlugin;
use Linkxtr\FilamentQrCode\FilamentQrCodeServiceProvider;
use Linkxtr\FilamentQrCode\Services\QrCodeRenderer;

covers(FilamentQrCodePlugin::class, FilamentQrCodeServiceProvider::class);

it('implements filament plugin contract and resolves via get()', function (): void {
    $filamentQrCodePlugin = FilamentQrCodePlugin::make();

    expect($filamentQrCodePlugin)->toBeInstanceOf(Plugin::class)
        ->and($filamentQrCodePlugin->getId())->toBe('filament-qrcode');

    $panel = Panel::make()
        ->id('admin')
        ->path('admin')
        ->plugin($filamentQrCodePlugin);

    Filament::registerPanel($panel);
    Filament::setCurrentPanel($panel);

    $resolvedPlugin = FilamentQrCodePlugin::get();
    expect($resolvedPlugin)->toBeInstanceOf(FilamentQrCodePlugin::class)
        ->and($resolvedPlugin->getId())->toBe('filament-qrcode');
});

it('registers in panel lifecycle without errors', function (): void {
    $filamentQrCodePlugin = FilamentQrCodePlugin::make();
    $panel = Panel::make()->id('test')->path('test');

    $filamentQrCodePlugin->register($panel);
    $filamentQrCodePlugin->boot($panel);

    expect($filamentQrCodePlugin->getId())->toBe('filament-qrcode');
});

it('binds qr code renderer as a singleton in the service container', function (): void {
    $qrCodeRenderer = app(QrCodeRenderer::class);
    $renderer2 = app(QrCodeRenderer::class);

    expect($qrCodeRenderer)->toBeInstanceOf(QrCodeRenderer::class)
        ->and($qrCodeRenderer)->toBe($renderer2);
});

it('merges default package configuration and loads views', function (): void {
    expect(config('filament-qrcode.defaults.size'))->toBe(400)
        ->and(config('filament-qrcode.defaults.margin'))->toBe(4)
        ->and(config('filament-qrcode.defaults.format'))->toBe('svg')
        ->and(config('filament-qrcode.defaults.error_correction'))->toBe('M')
        ->and(config('filament-qrcode.defaults.color'))->toBe('0,0,0')
        ->and(config('filament-qrcode.defaults.background_color'))->toBe('255,255,255')
        ->and(config('filament-qrcode.preview.render_size'))->toBe(320);

    $hints = app('view')->getFinder()->getHints();
    expect($hints)->toHaveKey('filament-qrcode')
        ->and(realpath($hints['filament-qrcode'][0]))->toBe(realpath(__DIR__.'/../../resources/views'));
});

it('registers publishable tags with exact source and destination paths', function (): void {
    $expectedConfigSource = realpath(__DIR__.'/../../config/filament-qrcode.php');
    $expectedViewsSource = realpath(__DIR__.'/../../resources/views');

    $configPublishes = ServiceProvider::pathsToPublish(FilamentQrCodeServiceProvider::class, 'filament-qrcode-config');
    expect($configPublishes)->toHaveCount(1);
    $configSourceKey = array_key_first($configPublishes);
    expect(realpath((string) $configSourceKey))->toBe($expectedConfigSource)
        ->and($configPublishes[$configSourceKey])->toBe(config_path('filament-qrcode.php'));

    $viewsPublishes = ServiceProvider::pathsToPublish(FilamentQrCodeServiceProvider::class, 'filament-qrcode-views');
    expect($viewsPublishes)->toHaveCount(1);
    $viewsSourceKey = array_key_first($viewsPublishes);
    expect(realpath((string) $viewsSourceKey))->toBe($expectedViewsSource)
        ->and($viewsPublishes[$viewsSourceKey])->toBe(resource_path('views/vendor/filament-qrcode'));

    $combinedPublishes = ServiceProvider::pathsToPublish(FilamentQrCodeServiceProvider::class, 'filament-qrcode');
    expect($combinedPublishes)->toHaveCount(2);
    $realCombined = [];
    foreach ($combinedPublishes as $src => $dest) {
        $realCombined[realpath((string) $src)] = $dest;
    }

    expect($realCombined)->toBe([
        $expectedConfigSource => config_path('filament-qrcode.php'),
        $expectedViewsSource => resource_path('views/vendor/filament-qrcode'),
    ]);
});
