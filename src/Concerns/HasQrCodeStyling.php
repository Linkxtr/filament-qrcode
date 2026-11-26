<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQrCode\Concerns;

use Closure;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Linkxtr\QrCode\DTOs\Config;
use Linkxtr\QrCode\Enums\ErrorCorrectionLevel;
use Linkxtr\QrCode\Enums\EyeStyle;
use Linkxtr\QrCode\Enums\Format;
use Linkxtr\QrCode\Enums\GradientType;
use Linkxtr\QrCode\Enums\Style;
use Linkxtr\QrCode\Generator;
use Linkxtr\QrCode\ValueObjects\Colors\Rgb;
use Stringable;
use Throwable;

trait HasQrCodeStyling
{
    protected int|Closure|null $qrSize = null;

    protected int|Closure|null $qrMargin = null;

    protected Format|string|Closure|null $qrFormat = null;

    protected ErrorCorrectionLevel|string|Closure|null $qrErrorCorrection = null;

    protected string|Closure|null $qrColor = null;

    protected string|Closure|null $qrBackgroundColor = null;

    /** @var array{from: string|Closure, to: string|Closure, type: string|GradientType|Closure}|null */
    protected ?array $qrGradient = null;

    /** @var array<mixed>|Closure|null */
    protected array|Closure|null $qrEyeColors = null;

    protected EyeStyle|string|Closure|null $qrEyeStyle = null;

    protected EyeStyle|string|Closure|null $qrInternalEyeStyle = null;

    protected Style|string|Closure|null $qrStyle = null;

    protected float|Closure|null $qrStyleSize = null;

    protected string|Closure|null $qrLogoPath = null;

    protected float|Closure|null $qrLogoSize = null;

    protected string|Closure|null $qrEncoding = null;

    protected ?Closure $configureQrUsing = null;

    public function qrSize(int|Closure $size): static
    {
        $this->qrSize = $size;

        return $this;
    }

    public function getQrSize(): int
    {
        $size = $this->evaluate($this->qrSize);
        if (is_numeric($size)) {
            return (int) $size;
        }

        $filamentSize = config('filament-qrcode.defaults.size');
        if (is_numeric($filamentSize)) {
            return (int) $filamentSize;
        }

        $qrcodeSize = config('qrcode.size');
        if (is_numeric($qrcodeSize)) {
            return (int) $qrcodeSize;
        }

        return 160;
    }

    public function qrMargin(int|Closure $margin): static
    {
        $this->qrMargin = $margin;

        return $this;
    }

    public function getQrMargin(): int
    {
        $margin = $this->evaluate($this->qrMargin);
        if (is_numeric($margin)) {
            return (int) $margin;
        }

        $filamentMargin = config('filament-qrcode.defaults.margin');
        if (is_numeric($filamentMargin)) {
            return (int) $filamentMargin;
        }

        $qrcodeMargin = config('qrcode.margin');
        if (is_numeric($qrcodeMargin)) {
            return (int) $qrcodeMargin;
        }

        return 1;
    }

    public function qrFormat(Format|string|Closure $format): static
    {
        $this->qrFormat = $format;

        return $this;
    }

    public function getQrFormat(): string
    {
        $format = $this->evaluate($this->qrFormat);

        if ($format instanceof Format) {
            return $format->value;
        }

        if (is_scalar($format) || $format instanceof Stringable) {
            return (string) $format;
        }

        $filamentFormat = config('filament-qrcode.defaults.format');
        if (is_string($filamentFormat) && $filamentFormat !== '') {
            return $filamentFormat;
        }

        $qrcodeFormat = config('qrcode.format');
        if (is_string($qrcodeFormat) && $qrcodeFormat !== '') {
            return $qrcodeFormat;
        }

        return 'svg';
    }

    public function svg(): static
    {
        return $this->qrFormat(Format::SVG);
    }

    public function png(): static
    {
        return $this->qrFormat(Format::PNG);
    }

    public function webp(): static
    {
        return $this->qrFormat(Format::WEBP);
    }

    public function eps(): static
    {
        return $this->qrFormat(Format::EPS);
    }

    public function qrErrorCorrection(ErrorCorrectionLevel|string|Closure $level): static
    {
        $this->qrErrorCorrection = $level;

        return $this;
    }

    public function getQrErrorCorrection(): string
    {
        $level = $this->evaluate($this->qrErrorCorrection);

        if ($level instanceof ErrorCorrectionLevel) {
            return $level->value;
        }

        if (is_scalar($level) || $level instanceof Stringable) {
            return (string) $level;
        }

        $filamentLevel = config('filament-qrcode.defaults.error_correction');
        if (is_string($filamentLevel) && $filamentLevel !== '') {
            return $filamentLevel;
        }

        $qrcodeLevel = config('qrcode.error_correction');
        if (is_string($qrcodeLevel) && $qrcodeLevel !== '') {
            return $qrcodeLevel;
        }

        return 'M';
    }

    public function qrColor(string|Closure $color): static
    {
        $this->qrColor = $color;

        return $this;
    }

    public function getQrColor(): string
    {
        $color = $this->evaluate($this->qrColor);
        $colorString = is_scalar($color) || $color instanceof Stringable ? (string) $color : null;

        if ($colorString === null || trim($colorString) === '') {
            $filamentColor = config('filament-qrcode.defaults.color');
            if (is_string($filamentColor) && trim($filamentColor) !== '') {
                $colorString = $filamentColor;
            } else {
                $qrcodeColor = config('qrcode.color');
                if (is_string($qrcodeColor) && trim($qrcodeColor) !== '') {
                    $colorString = $qrcodeColor;
                } else {
                    $colorString = '#000000';
                }
            }
        }

        return $this->resolveColorToken($colorString);
    }

    public function qrBackgroundColor(string|Closure $color): static
    {
        $this->qrBackgroundColor = $color;

        return $this;
    }

    public function getQrBackgroundColor(): string
    {
        $color = $this->evaluate($this->qrBackgroundColor);
        $colorString = is_scalar($color) || $color instanceof Stringable ? (string) $color : null;

        if ($colorString === null || trim($colorString) === '') {
            $filamentBg = config('filament-qrcode.defaults.background_color');
            if (is_string($filamentBg) && trim($filamentBg) !== '') {
                $colorString = $filamentBg;
            } else {
                $qrcodeBg = config('qrcode.background_color');
                if (is_string($qrcodeBg) && trim($qrcodeBg) !== '') {
                    $colorString = $qrcodeBg;
                } else {
                    $colorString = '#ffffff';
                }
            }
        }

        return $this->resolveColorToken($colorString);
    }

    public function qrGradient(string|Closure $from, string|Closure $to, GradientType|string|Closure $type = 'vertical'): static
    {
        $this->qrGradient = [
            'from' => $from,
            'to' => $to,
            'type' => $type,
        ];

        return $this;
    }

    /**
     * @return array{from: string, to: string, type?: string}|null
     */
    public function getQrGradient(): ?array
    {
        if ($this->qrGradient === null) {
            return null;
        }

        $from = $this->evaluate($this->qrGradient['from']);
        $to = $this->evaluate($this->qrGradient['to']);
        $fromString = is_scalar($from) || $from instanceof Stringable ? (string) $from : '';
        $toString = is_scalar($to) || $to instanceof Stringable ? (string) $to : '';

        if ($fromString === '' || $toString === '') {
            return null;
        }

        $type = $this->evaluate($this->qrGradient['type']);

        if ($type instanceof GradientType) {
            $type = $type->value;
        }

        $typeString = is_scalar($type) || $type instanceof Stringable ? (string) $type : 'vertical';

        return [
            'from' => $this->resolveColorToken($fromString),
            'to' => $this->resolveColorToken($toString),
            'type' => $typeString,
        ];
    }

    public function qrEyeColor(int $eyeIndex, string|Closure $inner, string|Closure|null $outer = null): static
    {
        if (! is_array($this->qrEyeColors)) {
            $this->qrEyeColors = [];
        }

        $this->qrEyeColors[$eyeIndex] = [
            'inner' => $inner,
            'outer' => $outer,
        ];

        return $this;
    }

    /**
     * @param  string|array<mixed>|Closure  $innerOrConfig
     */
    public function qrEyeColors(string|array|Closure $innerOrConfig, string|Closure|null $outer = null): static
    {
        if (is_array($innerOrConfig)) {
            $this->qrEyeColors = $innerOrConfig;

            return $this;
        }

        if ($outer === null && $innerOrConfig instanceof Closure) {
            $this->qrEyeColors = $innerOrConfig;

            return $this;
        }

        $eye = [
            'inner' => $innerOrConfig,
            'outer' => $outer,
        ];

        $this->qrEyeColors = [
            0 => $eye,
            1 => $eye,
            2 => $eye,
        ];

        return $this;
    }

    /**
     * @return array<int, array{inner: string, outer?: string|null}>|null
     */
    public function getQrEyeColors(): ?array
    {
        $raw = $this->evaluate($this->qrEyeColors);

        if (! is_array($raw)) {
            return null;
        }

        $resolved = [];
        foreach ($raw as $index => $colors) {
            if (! is_array($colors) || ! isset($colors['inner'])) {
                continue;
            }

            $inner = $this->evaluate($colors['inner']);
            $outer = isset($colors['outer']) ? $this->evaluate($colors['outer']) : null;

            $innerStr = is_scalar($inner) || $inner instanceof Stringable ? (string) $inner : '';
            $outerStr = is_scalar($outer) || $outer instanceof Stringable ? (string) $outer : null;

            $resolved[(int) $index] = [
                'inner' => $this->resolveColorToken($innerStr),
                'outer' => $outerStr !== null ? $this->resolveColorToken($outerStr) : null,
            ];
        }

        return $resolved !== [] ? $resolved : null;
    }

    public function qrEyeStyle(EyeStyle|string|Closure $style): static
    {
        $this->qrEyeStyle = $style;

        return $this;
    }

    public function getQrEyeStyle(): ?string
    {
        $style = $this->evaluate($this->qrEyeStyle);

        if ($style instanceof EyeStyle) {
            return $style->value;
        }

        return is_scalar($style) || $style instanceof Stringable ? (string) $style : null;
    }

    public function qrInternalEyeStyle(EyeStyle|string|Closure $style): static
    {
        $this->qrInternalEyeStyle = $style;

        return $this;
    }

    public function getQrInternalEyeStyle(): ?string
    {
        $style = $this->evaluate($this->qrInternalEyeStyle);

        if ($style instanceof EyeStyle) {
            return $style->value;
        }

        return is_scalar($style) || $style instanceof Stringable ? (string) $style : null;
    }

    public function qrStyle(Style|string|Closure $style, float|Closure|null $size = null): static
    {
        $this->qrStyle = $style;
        if ($size !== null) {
            $this->qrStyleSize = $size;
        }

        return $this;
    }

    public function getQrStyle(): ?string
    {
        $style = $this->evaluate($this->qrStyle);

        if ($style instanceof Style) {
            return $style->value;
        }

        return is_scalar($style) || $style instanceof Stringable ? (string) $style : null;
    }

    public function getQrStyleSize(): ?float
    {
        $size = $this->evaluate($this->qrStyleSize);

        return is_numeric($size) ? (float) $size : null;
    }

    public function qrLogo(string|Closure $path, float|Closure $size = 0.2): static
    {
        $this->qrLogoPath = $path;
        $this->qrLogoSize = $size;

        return $this;
    }

    public function getQrLogoPath(): ?string
    {
        $path = $this->evaluate($this->qrLogoPath);

        return is_scalar($path) || $path instanceof Stringable ? (string) $path : null;
    }

    public function getQrLogoSize(): float
    {
        $size = $this->evaluate($this->qrLogoSize);

        return is_numeric($size) ? (float) $size : 0.2;
    }

    public function qrEncoding(string|Closure $encoding): static
    {
        $this->qrEncoding = $encoding;

        return $this;
    }

    public function getQrEncoding(): string
    {
        $encoding = $this->evaluate($this->qrEncoding);

        return is_scalar($encoding) || $encoding instanceof Stringable ? (string) $encoding : 'UTF-8';
    }

    public function configureQrUsing(Closure $callback): static
    {
        $this->configureQrUsing = $callback;

        return $this;
    }

    public function toQrCodeConfig(?int $size = null): Config
    {
        $config = new Config;
        $config->setFormat($this->getQrFormat());
        $config->setSize($size ?? $this->getQrSize());
        $config->setMargin($this->getQrMargin());
        $config->setErrorCorrectionLevel($this->getQrErrorCorrection());
        $config->setEncoding($this->getQrEncoding());

        $color = Rgb::parse($this->getQrColor());
        $config->setupColor($color->red, $color->green, $color->blue, $color->alpha);

        $bg = Rgb::parse($this->getQrBackgroundColor());
        $config->setupBackgroundColor($bg->red, $bg->green, $bg->blue, $bg->alpha);

        $gradient = $this->getQrGradient();
        if ($gradient !== null) {
            $config->setupGradient(
                Rgb::parse($gradient['from']),
                Rgb::parse($gradient['to']),
                $gradient['type'] ?? 'vertical',
            );
        }

        $eyeColors = $this->getQrEyeColors();
        if ($eyeColors !== null) {
            foreach ($eyeColors as $index => $colors) {
                $config->setupEyeColor(
                    $index,
                    Rgb::parse($colors['inner']),
                    isset($colors['outer']) ? Rgb::parse($colors['outer']) : null,
                );
            }
        }

        $eyeStyle = $this->getQrEyeStyle();
        if ($eyeStyle !== null) {
            $config->setEyeStyle($eyeStyle);
        }

        $internalEyeStyle = $this->getQrInternalEyeStyle();
        if ($internalEyeStyle !== null) {
            $config->setInternalEyeStyle($internalEyeStyle);
        }

        $style = $this->getQrStyle();
        if ($style !== null) {
            $config->setupStyle($style, $this->getQrStyleSize());
        }

        $logoPath = $this->getQrLogoPath();
        if ($logoPath !== null) {
            $config->setupMergePath($logoPath);
            $config->setImagePercentage($this->getQrLogoSize());
        }

        return $config;
    }

    public function resolveColorToken(string $color): string
    {
        $color = trim($color);

        if (str_starts_with($color, '#') || str_starts_with($color, 'rgb')) {
            return $color;
        }

        // Check for shade notation, e.g. "primary-600", "primary.600", "primary:600"
        $token = strtolower($color);
        $shade = null;
        if (preg_match('/^([a-z0-9_-]+)[.:-]([0-9]{2,3})$/', $token, $matches)) {
            $token = $matches[1];
            $shade = $matches[2];
        }

        // Resolve dynamically from FilamentColor facade
        try {
            $registeredColor = FilamentColor::getColor($token);

            if (! empty($registeredColor)) {
                $shadeVal = ($shade !== null ? ($registeredColor[$shade] ?? null) : null)
                    ?? $registeredColor[500]
                    ?? $registeredColor[600]
                    ?? reset($registeredColor);
                if (is_string($shadeVal)) {
                    return Color::convertToHex($shadeVal);
                }
            }
        } catch (Throwable) {
            // Ignore and fall back
        }

        $fallbackMap = [
            'primary' => '#fe9a00',
            'danger' => '#ef4444',
            'gray' => '#6b7280',
            'info' => '#3b82f6',
            'success' => '#10b981',
            'warning' => '#f59e0b',
            'secondary' => '#64748b',
        ];

        return $fallbackMap[$token] ?? $color;
    }

    public function getQrConfigurator(mixed $record = null): ?Closure
    {
        if (! $this->configureQrUsing instanceof Closure) {
            return null;
        }

        $callback = $this->configureQrUsing;

        return function (Generator $generator) use ($callback, $record): mixed {
            $namedInjections = [
                'qr' => $generator,
                'generator' => $generator,
            ];

            if ($record !== null) {
                $namedInjections['record'] = $record;
            }

            return $this->evaluate(
                $callback,
                namedInjections: $namedInjections,
                typedInjections: [Generator::class => $generator],
            );
        };
    }
}
