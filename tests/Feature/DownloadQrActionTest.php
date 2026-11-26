<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Linkxtr\FilamentQrCode\Actions\DownloadQrCodeAction;
use Linkxtr\FilamentQrCode\Tests\Fixtures\Models\Record;
use Linkxtr\QrCode\Enums\EyeStyle;
use Linkxtr\QrCode\Enums\GradientType;
use Linkxtr\QrCode\Enums\Style;
use Linkxtr\QrCode\Generator;
use Symfony\Component\HttpFoundation\StreamedResponse;

covers(DownloadQrCodeAction::class);

it('initializes download qr action with defaults, label, icon, and fluent setters', function (): void {
    $DownloadQrCodeAction = DownloadQrCodeAction::make();

    expect(DownloadQrCodeAction::getDefaultName())->toBe('downloadQr')
        ->and($DownloadQrCodeAction->getName())->toBe('downloadQr')
        ->and($DownloadQrCodeAction->getView())->toBe('filament::components.button.index')
        ->and($DownloadQrCodeAction->getLabel())->toBe('Download QR')
        ->and($DownloadQrCodeAction->getIcon())->toBe('heroicon-o-arrow-down-tray')
        ->and($DownloadQrCodeAction->getFilename())->toBe('qrcode')
        ->and($DownloadQrCodeAction->getQrFormat())->toBe('svg');

    $fluent = $DownloadQrCodeAction->filename('custom-code')
        ->qrSize(240)
        ->qrFormat('png')
        ->qrColor('#123456');

    expect($fluent)->toBe($DownloadQrCodeAction)
        ->and($DownloadQrCodeAction->getFilename())->toBe('custom-code')
        ->and($DownloadQrCodeAction->getQrSize())->toBe(240)
        ->and($DownloadQrCodeAction->getQrFormat())->toBe('png')
        ->and($DownloadQrCodeAction->getQrColor())->toBe('#123456');
});

it('resolves filename from string, scalar closure, Stringable closure, and falls back to qrcode', function (): void {
    $DownloadQrCodeAction = DownloadQrCodeAction::make()->filename(fn (): int => 123);
    expect($DownloadQrCodeAction->getFilename())->toBe('123');

    $stringable = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'stringable-qr';
        }
    };
    $DownloadQrCodeAction->filename(fn (): Stringable => $stringable);
    expect($DownloadQrCodeAction->getFilename())->toBe('stringable-qr');

    $DownloadQrCodeAction->filename(fn (): array => ['invalid']);
    expect($DownloadQrCodeAction->getFilename())->toBe('qrcode');
});

it('resolves payload from closure, Eloquent model attributes, array keys, stdClass, scalar, and null', function (): void {
    $action = DownloadQrCodeAction::make();

    // Closure
    $DownloadQrCodeAction = $action->payload(fn ($record): string => "custom-{$record['id']}");
    expect($DownloadQrCodeAction)->toBe($action)
        ->and($action->resolvePayloadForRecord(['id' => 7]))->toBe('custom-7');

    // Fresh action without closure
    $action = DownloadQrCodeAction::make();

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
        ->and($action->resolvePayloadForRecord((object) ['other' => 'val']))->toBeNull();

    // Scalar and null
    expect($action->resolvePayloadForRecord('direct-string'))->toBe('direct-string')
        ->and($action->resolvePayloadForRecord(null))->toBeNull();

    // Static string and Stringable payload()
    $staticAction = DownloadQrCodeAction::make()->payload('https://linkx.tr');
    expect($staticAction->resolvePayloadForRecord(['id' => 1]))->toBe('https://linkx.tr');
});

it('returns null when payload is empty or null on action execution', function (): void {
    $DownloadQrCodeAction = DownloadQrCodeAction::make()
        ->record([])
        ->payload(fn (): ?string => null);

    expect($DownloadQrCodeAction->call())->toBeNull();

    $actionEmpty = DownloadQrCodeAction::make()
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
        $action = DownloadQrCodeAction::make()
            ->record(['qr' => 'https://example.com'])
            ->filename('export-qr')
            ->qrFormat($format)
            ->qrSize(200)
            ->qrMargin(2)
            ->qrErrorCorrection('H')
            ->qrColor('#112233')
            ->qrBackgroundColor('#ffffff')
            ->qrGradient('#112233', '#445566', GradientType::VERTICAL)
            ->qrEyeColor(0, '#112233', '#445566')
            ->qrEyeStyle(EyeStyle::SQUARE)
            ->qrInternalEyeStyle(EyeStyle::SQUARE)
            ->qrStyle(Style::SQUARE, 0.5)
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

it('forwards configureQrUsing with record injection on single download action', function (): void {
    $action = DownloadQrCodeAction::make()
        ->record(['qr' => 'https://linkx.tr/action', 'custom_size' => 275])
        ->configureQrUsing(fn (Generator $qr, array $record): Generator => $qr->size((int) $record['custom_size']));

    /** @var StreamedResponse $response */
    $response = $action->call();

    ob_start();
    $response->sendContent();
    $body = (string) ob_get_clean();

    expect($body)->toContain('<svg')
        ->and($body)->toContain('width="275"');
});
