<?php

declare(strict_types=1);

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;
use Linkxtr\FilamentQr\FilamentQrPlugin;
use Linkxtr\FilamentQr\FilamentQrServiceProvider;
use Linkxtr\FilamentQr\Services\QrCodeRenderer;

covers(FilamentQrPlugin::class, FilamentQrServiceProvider::class);

it('implements filament plugin contract and resolves via get()', function (): void {
    $filamentQrPlugin = FilamentQrPlugin::make();

    expect($filamentQrPlugin)->toBeInstanceOf(Plugin::class)
        ->and($filamentQrPlugin->getId())->toBe('filament-qr');

    $panel = Panel::make()
        ->id('admin')
        ->path('admin')
        ->plugin($filamentQrPlugin);

    Filament::registerPanel($panel);
    Filament::setCurrentPanel($panel);

    $resolvedPlugin = FilamentQrPlugin::get();
    expect($resolvedPlugin)->toBeInstanceOf(FilamentQrPlugin::class)
        ->and($resolvedPlugin->getId())->toBe('filament-qr');
});

it('registers in panel lifecycle without errors', function (): void {
    $filamentQrPlugin = FilamentQrPlugin::make();
    $panel = Panel::make()->id('test')->path('test');

    $filamentQrPlugin->register($panel);
    $filamentQrPlugin->boot($panel);

    expect($filamentQrPlugin->getId())->toBe('filament-qr');
});

it('binds qr code renderer as a singleton in the service container', function (): void {
    $qrCodeRenderer = app(QrCodeRenderer::class);
    $renderer2 = app(QrCodeRenderer::class);

    expect($qrCodeRenderer)->toBeInstanceOf(QrCodeRenderer::class)
        ->and($qrCodeRenderer)->toBe($renderer2);
});

it('merges default package configuration and loads views', function (): void {
    expect(config('filament-qr.defaults.size'))->toBeNull()
        ->and(config('filament-qr.defaults.margin'))->toBeNull()
        ->and(config('filament-qr.defaults.format'))->toBeNull()
        ->and(config('filament-qr.defaults.error_correction'))->toBeNull()
        ->and(config('filament-qr.defaults.color'))->toBeNull()
        ->and(config('filament-qr.defaults.background_color'))->toBeNull()
        ->and(config('filament-qr.preview.render_size'))->toBe(320);

    $hints = app('view')->getFinder()->getHints();
    expect($hints)->toHaveKey('filament-qr')
        ->and(realpath($hints['filament-qr'][0]))->toBe(realpath(__DIR__.'/../../resources/views'));
});

it('registers publishable tags with exact source and destination paths', function (): void {
    $expectedConfigSource = realpath(__DIR__.'/../../config/filament-qr.php');
    $expectedViewsSource = realpath(__DIR__.'/../../resources/views');

    $configPublishes = ServiceProvider::pathsToPublish(FilamentQrServiceProvider::class, 'filament-qr-config');
    expect($configPublishes)->toHaveCount(1);
    $configSourceKey = array_key_first($configPublishes);
    expect(realpath((string) $configSourceKey))->toBe($expectedConfigSource)
        ->and($configPublishes[$configSourceKey])->toBe(config_path('filament-qr.php'));

    $viewsPublishes = ServiceProvider::pathsToPublish(FilamentQrServiceProvider::class, 'filament-qr-views');
    expect($viewsPublishes)->toHaveCount(1);
    $viewsSourceKey = array_key_first($viewsPublishes);
    expect(realpath((string) $viewsSourceKey))->toBe($expectedViewsSource)
        ->and($viewsPublishes[$viewsSourceKey])->toBe(resource_path('views/vendor/filament-qr'));

    $combinedPublishes = ServiceProvider::pathsToPublish(FilamentQrServiceProvider::class, 'filament-qr');
    expect($combinedPublishes)->toHaveCount(2);
    $realCombined = [];
    foreach ($combinedPublishes as $src => $dest) {
        $realCombined[realpath((string) $src)] = $dest;
    }

    expect($realCombined)->toBe([
        $expectedConfigSource => config_path('filament-qr.php'),
        $expectedViewsSource => resource_path('views/vendor/filament-qr'),
    ]);
});
