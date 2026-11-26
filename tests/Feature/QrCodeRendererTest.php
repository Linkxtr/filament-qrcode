<?php

declare(strict_types=1);

use Linkxtr\FilamentQrCode\Services\QrCodeRenderer;
use Linkxtr\QrCode\DTOs\Config;
use Linkxtr\QrCode\Enums\ErrorCorrectionLevel;
use Linkxtr\QrCode\Enums\EyeStyle;
use Linkxtr\QrCode\Enums\Format;
use Linkxtr\QrCode\Enums\GradientType;
use Linkxtr\QrCode\Enums\Style;
use Linkxtr\QrCode\Facades\QrCode;
use Linkxtr\QrCode\Generator;
use Linkxtr\QrCode\ValueObjects\Colors\Rgb;

covers(QrCodeRenderer::class);

beforeEach(function (): void {
    $this->renderer = app(QrCodeRenderer::class);
});

it('renders inline svg by default with Config defaults', function (): void {
    $svg = $this->renderer->render('https://example.com');

    expect($svg)->toBeString()
        ->and($svg)->toContain('<svg')
        ->and($svg)->toContain('width="400"')
        ->and($svg)->toContain('height="400"')
        ->and($svg)->toContain('</svg>');
});

it('renders raw svg string identical to render', function (): void {
    $raw = $this->renderer->renderRaw('https://example.com');
    $rendered = $this->renderer->render('https://example.com');

    expect($raw)->toBe($rendered)
        ->and($raw)->toContain('width="400"');
});

it('renders png as base64 data uri and raw png binary', function (): void {
    $config = new Config;
    $config->setFormat(Format::PNG);

    $png = $this->renderer->render('https://example.com', $config);
    $rawPng = $this->renderer->renderRaw('https://example.com', $config);

    expect($png)->toBe('data:image/png;base64,'.base64_encode($rawPng))
        ->and(substr($rawPng, 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});

it('renders data uri for both svg and raster formats', function (): void {
    $svgRaw = $this->renderer->renderRaw('https://example.com');
    $svgDataUri = $this->renderer->renderDataUri('https://example.com');

    expect($svgDataUri)->toBe('data:image/svg+xml;base64,'.base64_encode($svgRaw));

    $pngConfig = new Config;
    $pngConfig->setFormat(Format::PNG);

    $pngRaw = $this->renderer->renderRaw('https://example.com', $pngConfig);
    $pngDataUri = $this->renderer->renderDataUri('https://example.com', $pngConfig);

    expect($pngDataUri)->toBe('data:image/png;base64,'.base64_encode($pngRaw));
});

it('applies custom size, margin, foreground and background colors from Config', function (): void {
    $config = new Config;
    $config->setSize(300);
    $config->setMargin(4);
    $config->setupColor(17, 34, 51);
    $config->setupBackgroundColor(170, 187, 204);

    $svg = $this->renderer->render('https://example.com', $config);

    expect($svg)->toContain('width="300"')
        ->and($svg)->toContain('height="300"')
        ->and($svg)->toMatch('/(#112233|rgb\(17,\s*34,\s*51\))/i')
        ->and($svg)->toMatch('/(#aabbcc|rgb\(170,\s*187,\s*204\))/i');
});

it('applies gradients from Config', function (): void {
    $verticalConfig = new Config;
    $verticalConfig->setupGradient(Rgb::parse('#ff0000'), Rgb::parse('#0000ff'), GradientType::VERTICAL);

    $horizontalConfig = new Config;
    $horizontalConfig->setupGradient(Rgb::parse('#ff0000'), Rgb::parse('#0000ff'), GradientType::HORIZONTAL);

    $svgVertical = $this->renderer->render('https://example.com', $verticalConfig);
    $svgHorizontal = $this->renderer->render('https://example.com', $horizontalConfig);

    expect($svgVertical)->toContain('linearGradient')
        ->and($svgVertical)->not->toBe($svgHorizontal);
});

it('forwards all Config properties onto QrCode facade builder', function (): void {
    $config = new Config;
    $config->setFormat(Format::PNG);
    $config->setSize(240);
    $config->setMargin(2);
    $config->setErrorCorrectionLevel(ErrorCorrectionLevel::H);
    $config->setupColor(17, 34, 51, 90);
    $config->setupBackgroundColor(250, 251, 252, 80);
    $config->setupGradient(Rgb::parse('#111111'), Rgb::parse('#222222'), GradientType::DIAGONAL);
    $config->setupEyeColor(0, Rgb::parse('#ff0000'), Rgb::parse('#00ff00'));
    $config->setupEyeColor(1, Rgb::parse('#0000ff'));
    $config->setEyeStyle(EyeStyle::CIRCLE);
    $config->setInternalEyeStyle(EyeStyle::POINTY);
    $config->setupStyle(Style::DOT, 0.65);
    $config->setEncoding('ISO-8859-1');
    $config->setupMergeString('raw-logo-bytes');
    $config->setImagePercentage(0.25);

    $mockBuilder = Mockery::mock();
    $mockBuilder->shouldReceive('format')->once()->with(Format::PNG)->andReturnSelf();
    QrCode::swap($mockBuilder);
    $mockBuilder->shouldReceive('size')->once()->with(240)->andReturnSelf();
    $mockBuilder->shouldReceive('margin')->once()->with(2)->andReturnSelf();
    $mockBuilder->shouldReceive('errorCorrection')->once()->with(ErrorCorrectionLevel::H)->andReturnSelf();
    $mockBuilder->shouldReceive('color')->once()->with(17, 34, 51, 90)->andReturnSelf();
    $mockBuilder->shouldReceive('backgroundColor')->once()->with(250, 251, 252, 80)->andReturnSelf();
    $mockBuilder->shouldReceive('style')->once()->with(Style::DOT, 0.65)->andReturnSelf();
    $mockBuilder->shouldReceive('gradient')->once()->with([17, 17, 17], [34, 34, 34], 'diagonal')->andReturnSelf();
    $mockBuilder->shouldReceive('eyeColor')->once()->with(0, [255, 0, 0], [0, 255, 0])->andReturnSelf();
    $mockBuilder->shouldReceive('eyeColor')->once()->with(1, [0, 0, 255], null)->andReturnSelf();
    $mockBuilder->shouldReceive('eye')->once()->with(EyeStyle::CIRCLE)->andReturnSelf();
    $mockBuilder->shouldReceive('internalEye')->once()->with(EyeStyle::POINTY)->andReturnSelf();
    $mockBuilder->shouldReceive('encoding')->once()->with('ISO-8859-1')->andReturnSelf();
    $mockBuilder->shouldReceive('mergeString')->once()->with('raw-logo-bytes', 0.25)->andReturnSelf();
    $mockBuilder->shouldReceive('generate')->once()->with('payload-123')->andReturn('raw-png-bytes');

    $result = $this->renderer->renderDataUri('payload-123', $config);

    expect($result)->toBe('data:image/png;base64,'.base64_encode('raw-png-bytes'));
});

it('skips optional builder calls when Config leaves them at defaults', function (): void {
    $config = new Config;
    $config->setFormat(Format::WEBP);

    $mockBuilder = Mockery::mock();
    $mockBuilder->shouldReceive('format')->once()->with(Format::WEBP)->andReturnSelf();
    QrCode::swap($mockBuilder);
    $mockBuilder->shouldReceive('size')->once()->with(400)->andReturnSelf();
    $mockBuilder->shouldReceive('margin')->once()->with(4)->andReturnSelf();
    $mockBuilder->shouldReceive('errorCorrection')->once()->with(ErrorCorrectionLevel::M)->andReturnSelf();
    $mockBuilder->shouldReceive('color')->once()->with(0, 0, 0, 100)->andReturnSelf();
    $mockBuilder->shouldReceive('backgroundColor')->once()->with(255, 255, 255, 100)->andReturnSelf();
    $mockBuilder->shouldReceive('style')->once()->with(Style::SQUARE, 0.5)->andReturnSelf();
    $mockBuilder->shouldReceive('gradient')->never();
    $mockBuilder->shouldReceive('eyeColor')->never();
    $mockBuilder->shouldReceive('eye')->never();
    $mockBuilder->shouldReceive('internalEye')->never();
    $mockBuilder->shouldReceive('encoding')->never();
    $mockBuilder->shouldReceive('mergeString')->never();
    $mockBuilder->shouldReceive('generate')->once()->with('hello')->andReturn('webp-bytes');

    $rendered = $this->renderer->render('hello', $config);

    expect($rendered)->toBe('data:image/webp;base64,'.base64_encode('webp-bytes'));
});

it('applies configureUsing callback to the live Generator across renderRaw, render, and renderDataUri', function (): void {
    $svg = $this->renderer->render(
        'https://example.com',
        null,
        fn (Generator $qr): Generator => $qr->size(235)->gray(20, 95),
    );

    expect($svg)->toContain('<svg')
        ->and($svg)->toContain('width="235"')
        ->and($svg)->toContain('height="235"');

    $raw = $this->renderer->renderRaw(
        'https://example.com',
        null,
        fn (Generator $qr): Generator => $qr->size(215)->cmyk(),
    );

    expect($raw)->toContain('<svg')
        ->and($raw)->toContain('width="215"');

    $dataUri = $this->renderer->renderDataUri(
        'https://example.com',
        null,
        fn (Generator $qr): Generator => $qr->size(205),
    );

    expect($dataUri)->toStartWith('data:image/svg+xml;base64,')
        ->and((string) base64_decode(substr($dataUri, strlen('data:image/svg+xml;base64,')), true))->toContain('width="205"');

    // Non-Generator return value (e.g. void/null) keeps the configured builder intact
    $called = false;
    $unchanged = $this->renderer->render(
        'https://example.com',
        null,
        function (Generator $qr) use (&$called): void {
            $called = true;
            $qr->size(999);
        },
    );

    expect($called)->toBeTrue()
        ->and($unchanged)->toContain('width="400"');
});
