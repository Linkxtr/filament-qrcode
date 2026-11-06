<?php

declare(strict_types=1);

use Filament\Support\Facades\FilamentColor;
use Linkxtr\FilamentQr\Concerns\HasQrCodeStyling;
use Linkxtr\FilamentQr\Services\QrCodeRenderer;
use Linkxtr\FilamentQr\Tables\Columns\QrCodeColumn;
use Linkxtr\QrCode\Enums\ErrorCorrectionLevel;
use Linkxtr\QrCode\Enums\EyeStyle;
use Linkxtr\QrCode\Enums\Format;
use Linkxtr\QrCode\Enums\GradientType;
use Linkxtr\QrCode\Enums\Style;

covers(
    HasQrCodeStyling::class,
    QrCodeRenderer::class,
);

beforeEach(function (): void {
    $this->renderer = app(QrCodeRenderer::class);
});

it('supports fluent styling methods and format shortcuts on filament qr column', function (): void {
    $qrCodeColumn = QrCodeColumn::make('test_qr')
        ->qrSize(250)
        ->qrMargin(3)
        ->qrFormat(Format::WEBP)
        ->qrErrorCorrection(ErrorCorrectionLevel::H)
        ->qrColor('#112233')
        ->qrBackgroundColor('#ffffff')
        ->qrGradient('#ff0000', '#0000ff', GradientType::DIAGONAL)
        ->qrEyeColor(0, '#ff0000', '#00ff00')
        ->qrEyeStyle(EyeStyle::CIRCLE)
        ->qrInternalEyeStyle(EyeStyle::SQUARE)
        ->qrStyle(Style::ROUND, 0.7)
        ->qrLogo('/path/to/logo.png')
        ->qrDpi(300)
        ->qrResolution(600)
        ->qrEncoding('ISO-8859-1');

    expect($qrCodeColumn->getQrSize())->toBe(250)
        ->and($qrCodeColumn->getQrMargin())->toBe(3)
        ->and($qrCodeColumn->getQrFormat())->toBe('webp')
        ->and($qrCodeColumn->getQrErrorCorrection())->toBe('H')
        ->and($qrCodeColumn->getQrColor())->toBe('#112233')
        ->and($qrCodeColumn->getQrBackgroundColor())->toBe('#ffffff')
        ->and($qrCodeColumn->getQrGradient())->toBe([
            'from' => '#ff0000',
            'to' => '#0000ff',
            'type' => 'diagonal',
        ])
        ->and($qrCodeColumn->getQrEyeColors())->toBe([
            0 => [
                'inner' => '#ff0000',
                'outer' => '#00ff00',
            ],
        ])
        ->and($qrCodeColumn->getQrEyeStyle())->toBe('circle')
        ->and($qrCodeColumn->getQrInternalEyeStyle())->toBe('square')
        ->and($qrCodeColumn->getQrStyle())->toBe('round')
        ->and($qrCodeColumn->getQrStyleSize())->toBe(0.7)
        ->and($qrCodeColumn->getQrLogoPath())->toBe('/path/to/logo.png')
        ->and($qrCodeColumn->getQrLogoSize())->toBe(0.2)
        ->and($qrCodeColumn->getQrDpi())->toBe(300)
        ->and($qrCodeColumn->getQrResolution())->toBe(600)
        ->and($qrCodeColumn->getQrEncoding())->toBe('ISO-8859-1');

    // Calling qrStyle without size preserves existing styleSize
    $qrCodeColumn->qrStyle(Style::DOT);
    expect($qrCodeColumn->getQrStyle())->toBe('dot')
        ->and($qrCodeColumn->getQrStyleSize())->toBe(0.7);

    // Format shortcuts
    expect(QrCodeColumn::make('c1')->svg()->getQrFormat())->toBe('svg')
        ->and(QrCodeColumn::make('c2')->png()->getQrFormat())->toBe('png')
        ->and(QrCodeColumn::make('c3')->webp()->getQrFormat())->toBe('webp')
        ->and(QrCodeColumn::make('c4')->eps()->getQrFormat())->toBe('eps');
});

it('implements environment-aware 4-tier configuration inheritance', function (): void {
    $col = QrCodeColumn::make('tiers');

    // Tier 4: Hardcoded Fallbacks (when Tier 1, Tier 2, and Tier 3 are unset or invalid)
    config()->set('filament-qr.defaults.size');
    config()->set('filament-qr.defaults.margin');
    config()->set('filament-qr.defaults.format');
    config()->set('filament-qr.defaults.error_correction');
    config()->set('filament-qr.defaults.color');
    config()->set('filament-qr.defaults.background_color');

    config()->set('qrcode.size');
    config()->set('qrcode.margin');
    config()->set('qrcode.format');
    config()->set('qrcode.error_correction');
    config()->set('qrcode.color');
    config()->set('qrcode.background_color');

    expect($col->getQrSize())->toBe(160)
        ->and($col->getQrMargin())->toBe(1)
        ->and($col->getQrFormat())->toBe('svg')
        ->and($col->getQrErrorCorrection())->toBe('M')
        ->and($col->getQrColor())->toBe('#000000')
        ->and($col->getQrBackgroundColor())->toBe('#ffffff');

    // Tier 4 also holds when Tier 2 and Tier 3 contain invalid non-numeric/empty values
    config()->set('filament-qr.defaults.size', 'not-a-number');
    config()->set('filament-qr.defaults.margin', 'not-a-number');
    config()->set('filament-qr.defaults.format', 123);
    config()->set('filament-qr.defaults.error_correction', 123);
    config()->set('filament-qr.defaults.color', 123);
    config()->set('filament-qr.defaults.background_color', 123);

    config()->set('qrcode.size', 'not-a-number');
    config()->set('qrcode.margin', 'not-a-number');
    config()->set('qrcode.format', 123);
    config()->set('qrcode.error_correction', 123);
    config()->set('qrcode.color', 123);
    config()->set('qrcode.background_color', 123);

    expect($col->getQrSize())->toBe(160)
        ->and($col->getQrMargin())->toBe(1)
        ->and($col->getQrFormat())->toBe('svg')
        ->and($col->getQrErrorCorrection())->toBe('M')
        ->and($col->getQrColor())->toBe('#000000')
        ->and($col->getQrBackgroundColor())->toBe('#ffffff');

    // Tier 3: config('qrcode.*') inherited when filament-qr is unset (with string numbers and whitespace fallbacks)
    config()->set('filament-qr.defaults.size', '');
    config()->set('filament-qr.defaults.margin', '');
    config()->set('filament-qr.defaults.format', '');
    config()->set('filament-qr.defaults.error_correction', '');
    config()->set('filament-qr.defaults.color', '   ');
    config()->set('filament-qr.defaults.background_color', '   ');

    config()->set('qrcode.size', '400');
    config()->set('qrcode.margin', '4');
    config()->set('qrcode.format', 'webp');
    config()->set('qrcode.error_correction', 'Q');
    config()->set('qrcode.color', '10,20,30');
    config()->set('qrcode.background_color', '240,240,240');

    expect($col->getQrSize())->toBe(400)
        ->and($col->getQrMargin())->toBe(4)
        ->and($col->getQrFormat())->toBe('webp')
        ->and($col->getQrErrorCorrection())->toBe('Q')
        ->and($col->getQrColor())->toBe('10,20,30')
        ->and($col->getQrBackgroundColor())->toBe('240,240,240');

    // Tier 3 with empty format and error_correction falls back to Tier 4
    config()->set('qrcode.format', '');
    config()->set('qrcode.error_correction', '');
    config()->set('qrcode.color', '   ');
    config()->set('qrcode.background_color', '   ');
    expect($col->getQrFormat())->toBe('svg')
        ->and($col->getQrErrorCorrection())->toBe('M')
        ->and($col->getQrColor())->toBe('#000000')
        ->and($col->getQrBackgroundColor())->toBe('#ffffff');

    // Tier 2: config('filament-qr.defaults.*') overrides config('qrcode.*')
    config()->set('qrcode.format', 'eps');
    config()->set('qrcode.error_correction', 'L');
    config()->set('filament-qr.defaults.size', '220');
    config()->set('filament-qr.defaults.margin', '2');
    config()->set('filament-qr.defaults.format', 'png');
    config()->set('filament-qr.defaults.error_correction', 'H');
    config()->set('filament-qr.defaults.color', '#123456');
    config()->set('filament-qr.defaults.background_color', '#abcdef');

    expect($col->getQrSize())->toBe(220)
        ->and($col->getQrMargin())->toBe(2)
        ->and($col->getQrFormat())->toBe('png')
        ->and($col->getQrErrorCorrection())->toBe('H')
        ->and($col->getQrColor())->toBe('#123456')
        ->and($col->getQrBackgroundColor())->toBe('#abcdef');

    // Explicit whitespace strings fall back to config
    $col->qrColor('   ')->qrBackgroundColor('   ');
    expect($col->getQrColor())->toBe('#123456')
        ->and($col->getQrBackgroundColor())->toBe('#abcdef');

    // Tier 1: Explicit component setters override everything
    $col->qrSize(350)
        ->qrMargin(6)
        ->qrFormat(Format::SVG)
        ->qrErrorCorrection(ErrorCorrectionLevel::L)
        ->qrColor('#998877')
        ->qrBackgroundColor('#112233');

    expect($col->getQrSize())->toBe(350)
        ->and($col->getQrMargin())->toBe(6)
        ->and($col->getQrFormat())->toBe('svg')
        ->and($col->getQrErrorCorrection())->toBe('L')
        ->and($col->getQrColor())->toBe('#998877')
        ->and($col->getQrBackgroundColor())->toBe('#112233');
});

it('handles closures, Stringable, numeric strings, and config fallbacks across all styling getters', function (): void {
    $stringable = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'stringable-token';
        }
    };

    $qrCodeColumn = QrCodeColumn::make('defaults');

    // Unset defaults (uses package hardcoded fallbacks when configs are null)
    config()->set('filament-qr.defaults.size');
    config()->set('filament-qr.defaults.margin');
    config()->set('filament-qr.defaults.format');
    config()->set('filament-qr.defaults.error_correction');
    config()->set('filament-qr.defaults.color');
    config()->set('filament-qr.defaults.background_color');

    config()->set('qrcode.size');
    config()->set('qrcode.margin');
    config()->set('qrcode.format');
    config()->set('qrcode.error_correction');
    config()->set('qrcode.color');
    config()->set('qrcode.background_color');

    expect($qrCodeColumn->getQrSize())->toBe(160)
        ->and($qrCodeColumn->getQrMargin())->toBe(1)
        ->and($qrCodeColumn->getQrFormat())->toBe('svg')
        ->and($qrCodeColumn->getQrErrorCorrection())->toBe('M')
        ->and($qrCodeColumn->getQrColor())->toBe('#000000')
        ->and($qrCodeColumn->getQrBackgroundColor())->toBe('#ffffff')
        ->and($qrCodeColumn->getQrGradient())->toBeNull()
        ->and($qrCodeColumn->getQrEyeColors())->toBeNull()
        ->and($qrCodeColumn->getQrEyeStyle())->toBeNull()
        ->and($qrCodeColumn->getQrInternalEyeStyle())->toBeNull()
        ->and($qrCodeColumn->getQrStyle())->toBeNull()
        ->and($qrCodeColumn->getQrStyleSize())->toBeNull()
        ->and($qrCodeColumn->getQrLogoPath())->toBeNull()
        ->and($qrCodeColumn->getQrLogoSize())->toBe(0.2)
        ->and($qrCodeColumn->getQrDpi())->toBeNull()
        ->and($qrCodeColumn->getQrResolution())->toBeNull()
        ->and($qrCodeColumn->getQrEncoding())->toBe('UTF-8');

    // Numeric string & Stringable closures
    $qrCodeColumn->qrSize(fn (): string => '195')
        ->qrMargin(fn (): string => '5')
        ->qrFormat(fn (): Stringable => $stringable)
        ->qrErrorCorrection(fn (): Stringable => $stringable)
        ->qrColor(fn (): Stringable => $stringable)
        ->qrBackgroundColor(fn (): Stringable => $stringable)
        ->qrEyeStyle(fn (): Stringable => $stringable)
        ->qrInternalEyeStyle(fn (): Stringable => $stringable)
        ->qrStyle(fn (): Stringable => $stringable, fn (): string => '0.85')
        ->qrLogo(fn (): Stringable => $stringable, fn (): string => '0.35')
        ->qrDpi(fn (): string => '180')
        ->qrResolution(fn (): string => '360')
        ->qrEncoding(fn (): Stringable => $stringable);

    expect($qrCodeColumn->getQrSize())->toBe(195)
        ->and($qrCodeColumn->getQrMargin())->toBe(5)
        ->and($qrCodeColumn->getQrFormat())->toBe('stringable-token')
        ->and($qrCodeColumn->getQrErrorCorrection())->toBe('stringable-token')
        ->and($qrCodeColumn->getQrColor())->toBe('stringable-token')
        ->and($qrCodeColumn->getQrBackgroundColor())->toBe('stringable-token')
        ->and($qrCodeColumn->getQrEyeStyle())->toBe('stringable-token')
        ->and($qrCodeColumn->getQrInternalEyeStyle())->toBe('stringable-token')
        ->and($qrCodeColumn->getQrStyle())->toBe('stringable-token')
        ->and($qrCodeColumn->getQrStyleSize())->toBe(0.85)
        ->and($qrCodeColumn->getQrLogoPath())->toBe('stringable-token')
        ->and($qrCodeColumn->getQrLogoSize())->toBe(0.35)
        ->and($qrCodeColumn->getQrDpi())->toBe(180)
        ->and($qrCodeColumn->getQrResolution())->toBe(360)
        ->and($qrCodeColumn->getQrEncoding())->toBe('stringable-token');

    // Non-scalar closures falling back to null / defaults
    $qrCodeColumn->qrFormat(fn (): array => [])
        ->qrErrorCorrection(fn (): array => [])
        ->qrColor(fn (): array => [])
        ->qrBackgroundColor(fn (): array => [])
        ->qrEyeStyle(fn (): array => [])
        ->qrInternalEyeStyle(fn (): array => [])
        ->qrStyle(fn (): array => [], fn (): string => 'not-float')
        ->qrLogo(fn (): array => [], fn (): string => 'not-float')
        ->qrDpi(fn (): string => 'not-int')
        ->qrResolution(fn (): string => 'not-int')
        ->qrEncoding(fn (): array => []);

    expect($qrCodeColumn->getQrFormat())->toBe('svg')
        ->and($qrCodeColumn->getQrErrorCorrection())->toBe('M')
        ->and($qrCodeColumn->getQrColor())->toBe('#000000')
        ->and($qrCodeColumn->getQrBackgroundColor())->toBe('#ffffff')
        ->and($qrCodeColumn->getQrEyeStyle())->toBeNull()
        ->and($qrCodeColumn->getQrInternalEyeStyle())->toBeNull()
        ->and($qrCodeColumn->getQrStyle())->toBeNull()
        ->and($qrCodeColumn->getQrStyleSize())->toBeNull()
        ->and($qrCodeColumn->getQrLogoPath())->toBeNull()
        ->and($qrCodeColumn->getQrLogoSize())->toBe(0.2)
        ->and($qrCodeColumn->getQrDpi())->toBeNull()
        ->and($qrCodeColumn->getQrResolution())->toBeNull()
        ->and($qrCodeColumn->getQrEncoding())->toBe('UTF-8');
});

it('covers all gradient and eye color edge cases', function (): void {
    $stringable = new class implements Stringable
    {
        public function __toString(): string
        {
            return '#abcdef';
        }
    };

    $qrCodeColumn = QrCodeColumn::make('grad');

    // Default type parameter ('vertical') and Stringable / int / non-scalar type fallback
    $qrCodeColumn->qrGradient(fn (): Stringable => $stringable, fn (): Stringable => $stringable);

    expect($qrCodeColumn->getQrGradient())->toBe([
        'from' => '#abcdef',
        'to' => '#abcdef',
        'type' => 'vertical',
    ]);

    $qrCodeColumn->qrGradient(fn (): int => 123, fn (): int => 456);
    expect($qrCodeColumn->getQrGradient())->toBe([
        'from' => '123',
        'to' => '456',
        'type' => 'vertical',
    ]);

    $qrCodeColumn->qrGradient(fn (): array => ['bad'], '#123456');
    expect($qrCodeColumn->getQrGradient())->toBeNull();

    $qrCodeColumn->qrGradient('#123456', fn (): array => ['bad']);
    expect($qrCodeColumn->getQrGradient())->toBeNull();

    $qrCodeColumn->qrGradient('#111111', '#222222', fn (): array => ['bad']);
    expect($qrCodeColumn->getQrGradient())->toBe([
        'from' => '#111111',
        'to' => '#222222',
        'type' => 'vertical',
    ]);

    $qrCodeColumn->qrGradient('#111111', '#222222', fn (): Stringable => new class implements Stringable
    {
        public function __toString(): string
        {
            return 'radial';
        }
    });
    expect($qrCodeColumn->getQrGradient())->toBe([
        'from' => '#111111',
        'to' => '#222222',
        'type' => 'radial',
    ]);

    // Empty from or to returns null
    $qrCodeColumn->qrGradient('', '#222222');
    expect($qrCodeColumn->getQrGradient())->toBeNull();

    $qrCodeColumn->qrGradient('#111111', '');
    expect($qrCodeColumn->getQrGradient())->toBeNull();

    // qrEyeColors uniform shorthand (populates 3 eyes: 0, 1, 2)
    $qrCodeColumn->qrEyeColors('#112233', '#445566');
    expect($qrCodeColumn->getQrEyeColors())->toBe([
        0 => ['inner' => '#112233', 'outer' => '#445566'],
        1 => ['inner' => '#112233', 'outer' => '#445566'],
        2 => ['inner' => '#112233', 'outer' => '#445566'],
    ]);

    // qrEyeColors uniform shorthand with closures
    $qrCodeColumn->qrEyeColors(fn (): string => '#112233', fn (): string => '#445566');
    expect($qrCodeColumn->getQrEyeColors())->toBe([
        0 => ['inner' => '#112233', 'outer' => '#445566'],
        1 => ['inner' => '#112233', 'outer' => '#445566'],
        2 => ['inner' => '#112233', 'outer' => '#445566'],
    ]);

    // qrEyeColors uniform shorthand with single inner string and outer null
    $qrCodeColumn->qrEyeColors('#112233');
    expect($qrCodeColumn->getQrEyeColors())->toBe([
        0 => ['inner' => '#112233', 'outer' => null],
        1 => ['inner' => '#112233', 'outer' => null],
        2 => ['inner' => '#112233', 'outer' => null],
    ]);

    // qrEyeColors with array ignores second argument
    $qrCodeColumn->qrEyeColors([0 => ['inner' => '#aabbcc', 'outer' => null]], '#ignored');
    expect($qrCodeColumn->getQrEyeColors())->toBe([
        0 => ['inner' => '#aabbcc', 'outer' => null],
    ]);

    // qrEyeColors with closure returning full array
    $qrCodeColumn->qrEyeColors(fn (): array => [0 => ['inner' => '#334455', 'outer' => '#667788']]);
    expect($qrCodeColumn->getQrEyeColors())->toBe([
        0 => ['inner' => '#334455', 'outer' => '#667788'],
    ]);

    // qrEyeColors with non-numeric string key casts to int index 0
    $qrCodeColumn->qrEyeColors(['eye_zero' => ['inner' => '#556677', 'outer' => null]]);
    $resolvedEyes = $qrCodeColumn->getQrEyeColors();
    expect($resolvedEyes)->toBeArray()->toHaveKey(0);

    // qrEyeColors with Closure returning non-array -> null, then qrEyeColor resets to array
    $qrCodeColumn->qrEyeColors(fn (): ?array => null);
    expect($qrCodeColumn->getQrEyeColors())->toBeNull();

    $qrCodeColumn->qrEyeColor(1, fn (): Stringable => $stringable)
        ->qrEyeColor(2, '#112233');
    expect($qrCodeColumn->getQrEyeColors())->toBe([
        1 => ['inner' => '#abcdef', 'outer' => null],
        2 => ['inner' => '#112233', 'outer' => null],
    ]);

    // qrEyeColors with array containing invalid entries, Stringable outer, and non-scalar inner/outer
    $qrCodeColumn->qrEyeColors([
        0 => 'not-an-array',
        1 => ['outer' => '#111111'], // missing inner
        2 => ['inner' => ['non-scalar'], 'outer' => $stringable],
        '3' => ['inner' => '#000000', 'outer' => ['non-scalar']],
    ]);
    expect($qrCodeColumn->getQrEyeColors())->toBe([
        2 => ['inner' => '', 'outer' => '#abcdef'],
        3 => ['inner' => '#000000', 'outer' => null],
    ]);

    // qrEyeColors where all entries are skipped returns null
    $qrCodeColumn->qrEyeColors([
        0 => ['outer' => '#111111'],
    ]);
    expect($qrCodeColumn->getQrEyeColors())->toBeNull();
});

it('resolves hex, rgb, FilamentColor palettes, shade notations, and fallback map tokens', function (): void {
    $qrCodeColumn = QrCodeColumn::make('colors');

    // Direct hex and rgb with whitespace trimming
    expect($qrCodeColumn->resolveColorToken('  #123456  '))->toBe('#123456')
        ->and($qrCodeColumn->resolveColorToken('  rgb(10, 20, 30)  '))->toBe('rgb(10, 20, 30)');

    // Register custom palettes in FilamentColor
    FilamentColor::register([
        'brandhex' => [
            100 => '#000000',
            500 => '#AABBCC',
            700 => '#DDEEFF',
        ],
        'brandrgb' => [
            100 => '#000000',
            600 => 'rgb(16, 185, 129)',
        ],
        'brandfirst' => [
            100 => '#102030',
        ],
        'brandbad' => [
            500 => 'invalid-color-that-throws',
        ],
    ]);

    // Shade notations (-, ., :) and case-insensitivity
    expect($qrCodeColumn->resolveColorToken('BRANDHEX'))->toBe('#aabbcc')
        ->and($qrCodeColumn->resolveColorToken('brandhex-700'))->toBe('#ddeeff')
        ->and($qrCodeColumn->resolveColorToken('brandhex.700'))->toBe('#ddeeff')
        ->and($qrCodeColumn->resolveColorToken('BRANDHEX:700'))->toBe('#ddeeff')
        // Falls back to 500 when requested shade 300 is missing
        ->and($qrCodeColumn->resolveColorToken('brandhex-300'))->toBe('#aabbcc')
        // Falls back to 600 and converts rgb(...) via Color::convertToHex
        ->and($qrCodeColumn->resolveColorToken('brandrgb-400'))->toBe('#11b981')
        // Falls back to reset() when 500 and 600 are missing
        ->and($qrCodeColumn->resolveColorToken('brandfirst-900'))->toBe('#102030')
        // Converts unrecognized color string to #000000
        ->and($qrCodeColumn->resolveColorToken('brandbad'))->toBe('#000000')
        // Secondary is not in FilamentColor default colors, so it uses $fallbackMap
        ->and($qrCodeColumn->resolveColorToken('secondary'))->toBe('#64748b')
        ->and($qrCodeColumn->resolveColorToken('UnknownToken'))->toBe('UnknownToken');

    // Catches Throwable when FilamentColor::getColor throws
    FilamentColor::shouldReceive('getColor')->with('throwtoken')->andThrow(new RuntimeException('Color failure'));
    expect($qrCodeColumn->resolveColorToken('throwtoken'))->toBe('throwtoken');

    // Fallback map when FilamentColor returns null for semantic tokens
    FilamentColor::shouldReceive('getColor')->andReturn(null);

    expect($qrCodeColumn->resolveColorToken('primary'))->toBe('#fe9a00')
        ->and($qrCodeColumn->resolveColorToken('danger'))->toBe('#ef4444')
        ->and($qrCodeColumn->resolveColorToken('gray'))->toBe('#6b7280')
        ->and($qrCodeColumn->resolveColorToken('info'))->toBe('#3b82f6')
        ->and($qrCodeColumn->resolveColorToken('success'))->toBe('#10b981')
        ->and($qrCodeColumn->resolveColorToken('warning'))->toBe('#f59e0b')
        ->and($qrCodeColumn->resolveColorToken('secondary'))->toBe('#64748b')
        ->and($qrCodeColumn->resolveColorToken('UnknownToken'))->toBe('UnknownToken');
});
