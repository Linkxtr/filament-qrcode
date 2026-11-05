<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Linkxtr\FilamentQr\Actions\DownloadQrAction;
use Linkxtr\FilamentQr\Enums\EyeStyle;
use Linkxtr\FilamentQr\Enums\GradientType;
use Linkxtr\FilamentQr\Enums\QrStyle;
use Linkxtr\FilamentQr\Tests\Fixtures\Models\Record;
use Symfony\Component\HttpFoundation\StreamedResponse;

covers(DownloadQrAction::class);

it('initializes download qr action with defaults, label, icon, and fluent setters', function (): void {
    $downloadQrAction = DownloadQrAction::make();

    expect(DownloadQrAction::getDefaultName())->toBe('downloadQr')
        ->and($downloadQrAction->getName())->toBe('downloadQr')
        ->and($downloadQrAction->getView())->toBe('filament::components.button.index')
        ->and($downloadQrAction->getLabel())->toBe('Download QR')
        ->and($downloadQrAction->getIcon())->toBe('heroicon-o-arrow-down-tray')
        ->and($downloadQrAction->getFilename())->toBe('qrcode')
        ->and($downloadQrAction->getQrFormat())->toBe('svg');

    $fluent = $downloadQrAction->filename('custom-code')
        ->qrSize(240)
        ->qrFormat('png')
        ->qrColor('#123456');

    expect($fluent)->toBe($downloadQrAction)
        ->and($downloadQrAction->getFilename())->toBe('custom-code')
        ->and($downloadQrAction->getQrSize())->toBe(240)
        ->and($downloadQrAction->getQrFormat())->toBe('png')
        ->and($downloadQrAction->getQrColor())->toBe('#123456');
});

it('resolves filename from string, scalar closure, Stringable closure, and falls back to qrcode', function (): void {
    $downloadQrAction = DownloadQrAction::make()->filename(fn (): int => 123);
    expect($downloadQrAction->getFilename())->toBe('123');

    $stringable = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'stringable-qr';
        }
    };
    $downloadQrAction->filename(fn (): Stringable => $stringable);
    expect($downloadQrAction->getFilename())->toBe('stringable-qr');

    $downloadQrAction->filename(fn (): array => ['invalid']);
    expect($downloadQrAction->getFilename())->toBe('qrcode');
});

it('resolves payload from closure, Eloquent model attributes, array keys, stdClass, scalar, and null', function (): void {
    $action = DownloadQrAction::make();

    // Closure
    $downloadQrAction = $action->payload(fn ($record): string => "custom-{$record['id']}");
    expect($downloadQrAction)->toBe($action)
        ->and($action->resolvePayloadForRecord(['id' => 7]))->toBe('custom-7');

    // Fresh action without closure
    $action = DownloadQrAction::make();

    // Model with qr
    $m1 = new Record(['qr' => 'model-qr', 'url' => 'model-url']);
    expect($action->resolvePayloadForRecord($m1))->toBe('model-qr');

    // Model with qr_code
    $m2 = new class extends Model
    {
        protected $guarded = [];
    };
    $m2->fill(['qr_code' => 'model-qr-code', 'url' => 'model-url']);

    expect($action->resolvePayloadForRecord($m2))->toBe('model-qr-code');

    // Model with url
    $m3 = new Record(['url' => 'https://model.example.com']);
    expect($action->resolvePayloadForRecord($m3))->toBe('https://model.example.com');

    // Model with key fallback
    $m4 = Record::create(['name' => 'Key Only']);
    expect($action->resolvePayloadForRecord($m4))->toBe((string) $m4->getKey());

    // Array with qr, qr_code, url, id, and empty
    expect($action->resolvePayloadForRecord(['qr' => 'arr-qr', 'qr_code' => 'arr-code']))->toBe('arr-qr')
        ->and($action->resolvePayloadForRecord(['qr_code' => 'arr-code', 'url' => 'arr-url']))->toBe('arr-code')
        ->and($action->resolvePayloadForRecord(['url' => 'arr-url', 'id' => 5]))->toBe('arr-url')
        ->and($action->resolvePayloadForRecord(['id' => 55]))->toBe('55')
        ->and($action->resolvePayloadForRecord([]))->toBeNull();

    // Object with qr, object with url, object with neither
    expect($action->resolvePayloadForRecord((object) ['qr' => 'obj-qr', 'url' => 'obj-url']))->toBe('obj-qr')
        ->and($action->resolvePayloadForRecord((object) ['url' => 'obj-url']))->toBe('obj-url')
        ->and($action->resolvePayloadForRecord((object) ['other' => 'val']))->toBe('');

    // Scalar and null
    expect($action->resolvePayloadForRecord('direct-string'))->toBe('direct-string')
        ->and($action->resolvePayloadForRecord(null))->toBeNull();
});

it('returns null when payload is empty or null on action execution', function (): void {
    $downloadQrAction = DownloadQrAction::make()
        ->record([])
        ->payload(fn (): ?string => null);

    expect($downloadQrAction->call())->toBeNull();

    $actionEmpty = DownloadQrAction::make()
        ->record([])
        ->payload(fn (): string => '');

    expect($actionEmpty->call())->toBeNull();
});

it('streams download for svg, png, webp, and eps formats with full styling options', function (): void {
    $formats = [
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'eps' => 'application/postscript',
    ];

    foreach ($formats as $format => $expectedMime) {
        $action = DownloadQrAction::make()
            ->record(['qr' => 'https://example.com'])
            ->filename('export-qr')
            ->qrFormat($format)
            ->qrSize(200)
            ->qrMargin(2)
            ->qrErrorCorrection('H')
            ->qrColor('#112233')
            ->qrBackgroundColor('#ffffff')
            ->qrGradient('#112233', '#445566', GradientType::Vertical)
            ->qrEyeColor(0, '#112233', '#445566')
            ->qrEyeStyle(EyeStyle::Square)
            ->qrInternalEyeStyle(EyeStyle::Square)
            ->qrStyle(QrStyle::Square, 0.5)
            ->qrEncoding('UTF-8');

        /** @var StreamedResponse $response */
        $response = $action->call();

        expect($response)->toBeInstanceOf(StreamedResponse::class)
            ->and($response->headers->get('content-type'))->toBe($expectedMime)
            ->and($response->headers->get('content-disposition'))->toContain("export-qr.{$format}");

        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        expect($body)->toBeString()->not->toBeEmpty();
    }
});
