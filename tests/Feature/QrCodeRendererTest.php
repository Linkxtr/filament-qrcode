<?php

declare(strict_types=1);

use Linkxtr\FilamentQr\Services\QrCodeRenderer;
use Linkxtr\QrCode\Enums\ErrorCorrectionLevel;
use Linkxtr\QrCode\Enums\EyeStyle;
use Linkxtr\QrCode\Enums\Format;
use Linkxtr\QrCode\Enums\GradientType;
use Linkxtr\QrCode\Enums\Style;
use Linkxtr\QrCode\Facades\QrCode;

covers(QrCodeRenderer::class);

beforeEach(function (): void {
    $this->renderer = app(QrCodeRenderer::class);
});

it('renders inline svg by default with exact default size, margin, and colors', function (): void {
    $svg = $this->renderer->render('https://example.com');

    expect($svg)->toBeString()
        ->and($svg)->toContain('<svg')
        ->and($svg)->toContain('width="160"')
        ->and($svg)->toContain('height="160"')
        ->and($svg)->toContain('</svg>');
});

it('renders raw svg string identical to render', function (): void {
    $raw = $this->renderer->renderRaw('https://example.com');
    $rendered = $this->renderer->render('https://example.com');

    expect($raw)->toBe($rendered)
        ->and($raw)->toContain('width="160"');
});

it('renders png as base64 data uri and raw png binary', function (): void {
    $png = $this->renderer->render('https://example.com', format: 'png');
    $rawPng = $this->renderer->renderRaw('https://example.com', format: 'png');

    expect($png)->toBe('data:image/png;base64,'.base64_encode($rawPng))
        ->and(substr($rawPng, 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});

it('renders data uri for both svg and raster formats', function (): void {
    $svgRaw = $this->renderer->renderRaw('https://example.com', format: 'svg');
    $svgDataUri = $this->renderer->renderDataUri('https://example.com');

    expect($svgDataUri)->toBe('data:image/svg+xml;base64,'.base64_encode($svgRaw));

    $pngRaw = $this->renderer->renderRaw('https://example.com', format: 'png');
    $pngDataUri = $this->renderer->renderDataUri('https://example.com', format: 'png');

    expect($pngDataUri)->toBe('data:image/png;base64,'.base64_encode($pngRaw));
});

it('applies custom size, margin, foreground and background colors', function (): void {
    $svg = $this->renderer->render(
        payload: 'https://example.com',
        size: 300,
        margin: 4,
        color: '#112233',
        backgroundColor: '#aabbcc'
    );

    expect($svg)->toContain('width="300"')
        ->and($svg)->toContain('height="300"')
        ->and($svg)->toMatch('/(#112233|rgb\(17,\s*34,\s*51\))/i')
        ->and($svg)->toMatch('/(#aabbcc|rgb\(170,\s*187,\s*204\))/i');
});

it('applies gradients with explicit and default vertical type', function (): void {
    $svgDefault = $this->renderer->render(
        payload: 'https://example.com',
        gradient: [
            'from' => '#ff0000',
            'to' => '#0000ff',
        ]
    );

    $svgVertical = $this->renderer->render(
        payload: 'https://example.com',
        gradient: [
            'from' => '#ff0000',
            'to' => '#0000ff',
            'type' => 'vertical',
        ]
    );

    $svgHorizontal = $this->renderer->render(
        payload: 'https://example.com',
        gradient: [
            'from' => '#ff0000',
            'to' => '#0000ff',
            'type' => 'horizontal',
        ]
    );

    expect($svgDefault)->toBe($svgVertical)
        ->and($svgDefault)->toContain('linearGradient')
        ->and($svgDefault)->not->toBe($svgHorizontal);
});

it('applies eye colors, eye styles, internal eye styles, module styles, encoding, and logo', function (): void {
    $logoFile = tempnam(sys_get_temp_dir(), 'qr_logo_').'.png';
    $img = imagecreatetruecolor(16, 16);
    imagepng($img, $logoFile);
    imagedestroy($img);

    try {
        $mockBuilder = Mockery::mock();
        $mockBuilder->shouldReceive('format')->once()->with('png')->andReturnSelf();
        QrCode::swap($mockBuilder);
        $mockBuilder->shouldReceive('size')->once()->with(240)->andReturnSelf();
        $mockBuilder->shouldReceive('margin')->once()->with(2)->andReturnSelf();
        $mockBuilder->shouldReceive('errorCorrection')->once()->with('H')->andReturnSelf();
        $mockBuilder->shouldReceive('color')->once()->with(17, 34, 51)->andReturnSelf();
        $mockBuilder->shouldReceive('backgroundColor')->once()->with(250, 251, 252)->andReturnSelf();
        $mockBuilder->shouldReceive('gradient')->once()->with('#111111', '#222222', 'diagonal')->andReturnSelf();
        $mockBuilder->shouldReceive('eyeColor')->once()->with(0, '#ff0000', '#00ff00')->andReturnSelf();
        $mockBuilder->shouldReceive('eyeColor')->once()->with(1, '#0000ff', null)->andReturnSelf();
        $mockBuilder->shouldReceive('eye')->once()->with('circle')->andReturnSelf();
        $mockBuilder->shouldReceive('internalEye')->once()->with('pointy')->andReturnSelf();
        $mockBuilder->shouldReceive('style')->once()->with('dot', 0.65)->andReturnSelf();
        $mockBuilder->shouldReceive('encoding')->once()->with('ISO-8859-1')->andReturnSelf();
        $mockBuilder->shouldReceive('merge')->once()->with($logoFile, 0.25)->andReturnSelf();
        $mockBuilder->shouldReceive('generate')->once()->with('payload-123')->andReturn('raw-png-bytes');

        $result = $this->renderer->renderDataUri(
            payload: 'payload-123',
            size: 240,
            margin: 2,
            format: 'png',
            errorCorrection: 'H',
            color: '#112233',
            backgroundColor: '#fafbfc',
            gradient: ['from' => '#111111', 'to' => '#222222', 'type' => 'diagonal'],
            eyeColors: [
                0 => ['inner' => '#ff0000', 'outer' => '#00ff00'],
                '1' => ['inner' => '#0000ff'],
            ],
            eyeStyle: EyeStyle::CIRCLE,
            internalEyeStyle: EyeStyle::POINTY,
            style: Style::DOT,
            styleSize: 0.65,
            logoPath: $logoFile,
            logoSize: 0.25,
            dpi: 150,
            resolution: 300,
            encoding: 'ISO-8859-1',
        );

        expect($result)->toBe('data:image/png;base64,'.base64_encode('raw-png-bytes'));
    } finally {
        @unlink($logoFile);
    }
});

it('forwards all arguments through render() and uses default logoSize and string styles', function (): void {
    $mockBuilder = Mockery::mock();
    $mockBuilder->shouldReceive('format')->once()->with('webp')->andReturnSelf();
    QrCode::swap($mockBuilder);
    $mockBuilder->shouldReceive('size')->once()->with(160)->andReturnSelf();
    $mockBuilder->shouldReceive('margin')->once()->with(1)->andReturnSelf();
    $mockBuilder->shouldReceive('errorCorrection')->once()->with('M')->andReturnSelf();
    $mockBuilder->shouldReceive('color')->once()->with(0, 0, 0)->andReturnSelf();
    $mockBuilder->shouldReceive('backgroundColor')->once()->with(255, 255, 255)->andReturnSelf();
    $mockBuilder->shouldReceive('eye')->once()->with('square')->andReturnSelf();
    $mockBuilder->shouldReceive('internalEye')->once()->with('circle')->andReturnSelf();
    $mockBuilder->shouldReceive('style')->once()->with('round', null)->andReturnSelf();
    $mockBuilder->shouldReceive('merge')->once()->with('/tmp/logo.png', 0.2)->andReturnSelf();
    $mockBuilder->shouldReceive('encoding')->never();
    $mockBuilder->shouldReceive('generate')->once()->with('hello')->andReturn('webp-bytes');

    $rendered = $this->renderer->render(
        payload: 'hello',
        format: 'webp',
        eyeStyle: 'square',
        internalEyeStyle: 'circle',
        style: 'round',
        logoPath: '/tmp/logo.png',
    );

    expect($rendered)->toBe('data:image/webp;base64,'.base64_encode('webp-bytes'));
});

it('embeds dpi and resolution fallback into png and webp images using imagick', function (): void {
    $rawPngDpi = $this->renderer->renderRaw('https://example.com', format: 'png', dpi: 300);
    $rawPngDpiEnum = $this->renderer->renderRaw('https://example.com', format: Format::PNG, dpi: 300);
    $imagick1 = new Imagick;
    $imagick1->readImageBlob($rawPngDpi);

    $imagickEnum = new Imagick;
    $imagickEnum->readImageBlob($rawPngDpiEnum);

    expect($imagickEnum->getImageResolution()['x'])->toBeGreaterThan(100);

    $res1 = $imagick1->getImageResolution();

    expect($res1['x'])->toBeGreaterThan(100)
        ->and($res1['y'])->toBeGreaterThan(100)
        ->and($imagick1->getImageUnits())->toBe(Imagick::RESOLUTION_PIXELSPERCENTIMETER);

    $rawPngRes = $this->renderer->renderRaw('https://example.com', format: 'png', resolution: 240);
    $imagick2 = new Imagick;
    $imagick2->readImageBlob($rawPngRes);

    $res2 = $imagick2->getImageResolution();

    expect($res2['x'])->toBeGreaterThan(90)
        ->and($res2['y'])->toBeGreaterThan(90);

    $rawWebpNoRes = $this->renderer->renderRaw('https://example.com', format: 'webp');
    $rawWebpRes = $this->renderer->renderRaw('https://example.com', format: 'webp', resolution: 240);
    expect(substr($rawWebpRes, 0, 4))->toBe('RIFF')
        ->and($rawWebpRes)->not->toBe($rawWebpNoRes);

    $rawSvgNoDpi = $this->renderer->renderRaw('https://example.com', format: 'svg');
    $rawSvgDpi = $this->renderer->renderRaw('https://example.com', format: 'svg', dpi: 300);
    expect($rawSvgDpi)->toBe($rawSvgNoDpi);
});

it('safely retains raw output if imagick throws an exception during resolution embedding', function (): void {
    $mockBuilder = Mockery::mock();
    $mockBuilder->shouldReceive('format')->once()->with('png')->andReturnSelf();
    QrCode::swap($mockBuilder);
    $mockBuilder->shouldReceive('size')->once()->with(160)->andReturnSelf();
    $mockBuilder->shouldReceive('margin')->once()->with(1)->andReturnSelf();
    $mockBuilder->shouldReceive('errorCorrection')->once()->with('M')->andReturnSelf();
    $mockBuilder->shouldReceive('color')->once()->with(0, 0, 0)->andReturnSelf();
    $mockBuilder->shouldReceive('backgroundColor')->once()->with(255, 255, 255)->andReturnSelf();
    $mockBuilder->shouldReceive('generate')->once()->with('test')->andReturn('corrupt-image-data');

    $result = $this->renderer->renderRaw('test', format: 'png', dpi: 300);

    expect($result)->toBe('corrupt-image-data');
});

it('returns the unmodified raster output when imagick is not loaded', function (): void {
    $baseline = $this->renderer->renderRaw('https://example.com', format: 'png');

    $GLOBALS['mockImagickLoaded'] = false;

    expect($this->renderer->renderRaw('https://example.com', format: 'png', dpi: 300))->toBe($baseline)
        ->and($this->renderer->renderRaw('https://example.com', format: 'webp', resolution: 240))->toStartWith('RIFF');
});

it('skips resolution embedding when dpi and resolution are zero or null', function (): void {
    $baseline = $this->renderer->renderRaw('https://example.com', format: 'png');

    expect($this->renderer->renderRaw('https://example.com', format: 'png', dpi: 0))->toBe($baseline)
        ->and($this->renderer->renderRaw('https://example.com', format: 'png', resolution: 0))->toBe($baseline)
        ->and($this->renderer->renderRaw('https://example.com', format: 'png', dpi: 0, resolution: 0))->toBe($baseline)
        ->and($this->renderer->renderRaw('https://example.com', format: 'png', dpi: null, resolution: null))->toBe($baseline);
});

it('prefers dpi over resolution and falls back to resolution when dpi is null', function (): void {
    $raw = $this->renderer->renderRaw('https://example.com', format: 'png', dpi: 300, resolution: 72);
    $imagick = new Imagick;
    $imagick->readImageBlob($raw);

    expect($imagick->getImageResolution()['x'])->toBeGreaterThan(100);
});

it('converts 3-character and 6-character hex strings to rgb arrays', function (): void {
    expect($this->renderer->hexToRgb('#fff'))->toBe([255, 255, 255])
        ->and($this->renderer->hexToRgb('#000'))->toBe([0, 0, 0])
        ->and($this->renderer->hexToRgb('#112233'))->toBe([17, 34, 51])
        ->and($this->renderer->hexToRgb('ffffff'))->toBe([255, 255, 255]);
});

it('accepts Format, ErrorCorrectionLevel, and GradientType enums in render and renderDataUri', function (): void {
    $svg = $this->renderer->render('https://example.com', format: Format::SVG, errorCorrection: ErrorCorrectionLevel::H);
    expect($svg)->toBeString()->toContain('<svg');

    $svgDataUri = $this->renderer->renderDataUri('https://example.com', format: Format::SVG);
    expect($svgDataUri)->toStartWith('data:image/svg+xml;base64,');

    $pngRender = $this->renderer->render('https://example.com', format: Format::PNG);
    expect($pngRender)->toStartWith('data:image/png;base64,');

    $pngDataUri = $this->renderer->renderDataUri(
        payload: 'https://example.com',
        format: Format::PNG,
        errorCorrection: ErrorCorrectionLevel::L,
        gradient: [
            'from' => '#ff0000',
            'to' => '#0000ff',
            'type' => GradientType::HORIZONTAL,
        ]
    );
    expect($pngDataUri)->toStartWith('data:image/png;base64,');
});

it('uses default logoSize of 0.2 when logoPath is provided in renderRaw, render, and renderDataUri', function (): void {
    $logoFile = tempnam(sys_get_temp_dir(), 'qr_logo_').'.png';
    $img = imagecreatetruecolor(16, 16);
    imagepng($img, $logoFile);
    imagedestroy($img);

    try {
        // 1. renderRaw with logoPath and default logoSize (0.2)
        $mockBuilderRaw = Mockery::mock();
        $mockBuilderRaw->shouldReceive('format')->once()->with('svg')->andReturnSelf();
        $mockBuilderRaw->shouldReceive('size')->once()->with(160)->andReturnSelf();
        $mockBuilderRaw->shouldReceive('margin')->once()->with(1)->andReturnSelf();
        $mockBuilderRaw->shouldReceive('errorCorrection')->once()->with('M')->andReturnSelf();
        $mockBuilderRaw->shouldReceive('color')->once()->with(0, 0, 0)->andReturnSelf();
        $mockBuilderRaw->shouldReceive('backgroundColor')->once()->with(255, 255, 255)->andReturnSelf();
        $mockBuilderRaw->shouldReceive('merge')->once()->with($logoFile, 0.2)->andReturnSelf();
        $mockBuilderRaw->shouldReceive('generate')->once()->with('test-raw-default')->andReturn('<svg>raw-default</svg>');
        QrCode::swap($mockBuilderRaw);

        $rawOutput = $this->renderer->renderRaw(
            payload: 'test-raw-default',
            logoPath: $logoFile,
        );
        expect($rawOutput)->toBe('<svg>raw-default</svg>');

        // 2. render with logoPath and default logoSize (0.2)
        $mockBuilderRender = Mockery::mock();
        $mockBuilderRender->shouldReceive('format')->once()->with('svg')->andReturnSelf();
        $mockBuilderRender->shouldReceive('size')->once()->with(160)->andReturnSelf();
        $mockBuilderRender->shouldReceive('margin')->once()->with(1)->andReturnSelf();
        $mockBuilderRender->shouldReceive('errorCorrection')->once()->with('M')->andReturnSelf();
        $mockBuilderRender->shouldReceive('color')->once()->with(0, 0, 0)->andReturnSelf();
        $mockBuilderRender->shouldReceive('backgroundColor')->once()->with(255, 255, 255)->andReturnSelf();
        $mockBuilderRender->shouldReceive('merge')->once()->with($logoFile, 0.2)->andReturnSelf();
        $mockBuilderRender->shouldReceive('generate')->once()->with('test-render-default')->andReturn('<svg>render-default</svg>');
        QrCode::swap($mockBuilderRender);

        $renderOutput = $this->renderer->render(
            payload: 'test-render-default',
            logoPath: $logoFile,
        );
        expect($renderOutput)->toBe('<svg>render-default</svg>');

        // 3. renderDataUri with logoPath and default logoSize (0.2)
        $mockBuilderDataUri = Mockery::mock();
        $mockBuilderDataUri->shouldReceive('format')->once()->with('png')->andReturnSelf();
        $mockBuilderDataUri->shouldReceive('size')->once()->with(160)->andReturnSelf();
        $mockBuilderDataUri->shouldReceive('margin')->once()->with(1)->andReturnSelf();
        $mockBuilderDataUri->shouldReceive('errorCorrection')->once()->with('M')->andReturnSelf();
        $mockBuilderDataUri->shouldReceive('color')->once()->with(0, 0, 0)->andReturnSelf();
        $mockBuilderDataUri->shouldReceive('backgroundColor')->once()->with(255, 255, 255)->andReturnSelf();
        $mockBuilderDataUri->shouldReceive('merge')->once()->with($logoFile, 0.2)->andReturnSelf();
        $mockBuilderDataUri->shouldReceive('generate')->once()->with('test-datauri-default')->andReturn('png-raw-bytes');
        QrCode::swap($mockBuilderDataUri);

        $dataUriOutput = $this->renderer->renderDataUri(
            payload: 'test-datauri-default',
            format: 'png',
            logoPath: $logoFile,
        );
        expect($dataUriOutput)->toBe('data:image/png;base64,'.base64_encode('png-raw-bytes'));
    } finally {
        @unlink($logoFile);
    }
});
