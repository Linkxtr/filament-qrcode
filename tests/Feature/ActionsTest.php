<?php

declare(strict_types=1);

use Linkxtr\FilamentQrCode\Actions\DownloadQrCodeAction;
use Linkxtr\FilamentQrCode\Actions\DownloadQrCodeBulkAction;

covers(DownloadQrCodeAction::class, DownloadQrCodeBulkAction::class);

it('can initialize download qr action with defaults and fluent setters', function (): void {
    $DownloadQrCodeAction = DownloadQrCodeAction::make('downloadQr')
        ->filename('custom-code')
        ->qrSize(240)
        ->qrFormat('png')
        ->qrColor('#123456');

    expect($DownloadQrCodeAction->getName())->toBe('downloadQr')
        ->and($DownloadQrCodeAction->getQrSize())->toBe(240)
        ->and($DownloadQrCodeAction->getQrFormat())->toBe('png')
        ->and($DownloadQrCodeAction->getQrColor())->toBe('#123456');
});

it('can initialize download qr bulk action with filename and styling', function (): void {
    $DownloadQrCodeBulkAction = DownloadQrCodeBulkAction::make('downloadQrBulk')
        ->filename('batch-qr')
        ->qrSize(180)
        ->qrFormat('svg')
        ->qrColor('#333333');

    expect($DownloadQrCodeBulkAction->getName())->toBe('downloadQrBulk')
        ->and($DownloadQrCodeBulkAction->getFilename())->toBe('batch-qr.zip')
        ->and($DownloadQrCodeBulkAction->getQrSize())->toBe(180)
        ->and($DownloadQrCodeBulkAction->getQrFormat())->toBe('svg')
        ->and($DownloadQrCodeBulkAction->getQrColor())->toBe('#333333');
});
