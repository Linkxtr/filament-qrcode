<?php

declare(strict_types=1);

use Linkxtr\FilamentQr\Actions\DownloadQrAction;
use Linkxtr\FilamentQr\Actions\DownloadQrBulkAction;

covers(DownloadQrAction::class, DownloadQrBulkAction::class);

it('can initialize download qr action with defaults and fluent setters', function (): void {
    $downloadQrAction = DownloadQrAction::make('downloadQr')
        ->filename('custom-code')
        ->qrSize(240)
        ->qrFormat('png')
        ->qrColor('#123456');

    expect($downloadQrAction->getName())->toBe('downloadQr')
        ->and($downloadQrAction->getQrSize())->toBe(240)
        ->and($downloadQrAction->getQrFormat())->toBe('png')
        ->and($downloadQrAction->getQrColor())->toBe('#123456');
});

it('can initialize download qr bulk action with archive filename and styling', function (): void {
    $downloadQrBulkAction = DownloadQrBulkAction::make('downloadQrBulk')
        ->archiveFilename('batch-qr')
        ->qrSize(180)
        ->qrFormat('svg')
        ->qrColor('#333333');

    expect($downloadQrBulkAction->getName())->toBe('downloadQrBulk')
        ->and($downloadQrBulkAction->getQrSize())->toBe(180)
        ->and($downloadQrBulkAction->getQrFormat())->toBe('svg')
        ->and($downloadQrBulkAction->getQrColor())->toBe('#333333');
});
