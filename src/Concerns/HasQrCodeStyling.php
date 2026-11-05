<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQr\Concerns;

use Closure;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Linkxtr\FilamentQr\Enums\ErrorCorrectionLevel;
use Linkxtr\FilamentQr\Enums\EyeStyle;
use Linkxtr\FilamentQr\Enums\GradientType;
use Linkxtr\FilamentQr\Enums\QrFormat;
use Linkxtr\FilamentQr\Enums\QrStyle;
use Stringable;
use Throwable;

trait HasQrCodeStyling
{
    protected int|Closure|null $qrSize = null;

    protected int|Closure|null $qrMargin = null;

    protected QrFormat|string|Closure|null $qrFormat = null;

    protected ErrorCorrectionLevel|string|Closure|null $qrErrorCorrection = null;

    protected string|Closure|null $qrColor = null;

    protected string|Closure|null $qrBackgroundColor = null;

    /** @var array{from: string|Closure, to: string|Closure, type: string|GradientType|Closure}|null */
    protected ?array $qrGradient = null;

    /** @var array<int, mixed>|null */
    protected ?array $qrEyeColors = null;

    protected EyeStyle|string|Closure|null $qrEyeStyle = null;

    protected EyeStyle|string|Closure|null $qrInternalEyeStyle = null;

    protected QrStyle|string|Closure|null $qrStyle = null;

    protected float|Closure|null $qrStyleSize = null;

    protected string|Closure|null $qrLogoPath = null;

    protected float|Closure|null $qrLogoSize = null;

    protected int|Closure|null $qrDpi = null;

    protected int|Closure|null $qrResolution = null;

    protected string|Closure|null $qrEncoding = null;

    public function qrSize(int|Closure $size): static
    {
        $this->qrSize = $size;

        return $this;
    }

    public function getQrSize(): int
    {
        $size = $this->evaluateStylingValue($this->qrSize);
        if (is_numeric($size)) {
            return (int) $size;
        }

        $configSize = config('filament-qr.defaults.size');

        return is_numeric($configSize) ? (int) $configSize : 160;
    }

    public function qrMargin(int|Closure $margin): static
    {
        $this->qrMargin = $margin;

        return $this;
    }

    public function getQrMargin(): int
    {
        $margin = $this->evaluateStylingValue($this->qrMargin);
        if (is_numeric($margin)) {
            return (int) $margin;
        }

        $configMargin = config('filament-qr.defaults.margin');

        return is_numeric($configMargin) ? (int) $configMargin : 1;
    }

    public function qrFormat(QrFormat|string|Closure $format): static
    {
        $this->qrFormat = $format;

        return $this;
    }

    public function getQrFormat(): string
    {
        $format = $this->evaluateStylingValue($this->qrFormat);

        if ($format instanceof QrFormat) {
            return $format->value;
        }

        if (is_scalar($format) || $format instanceof Stringable) {
            return (string) $format;
        }

        $configFormat = config('filament-qr.defaults.format');

        return is_string($configFormat) ? $configFormat : 'svg';
    }

    public function svg(): static
    {
        return $this->qrFormat(QrFormat::Svg);
    }

    public function png(): static
    {
        return $this->qrFormat(QrFormat::Png);
    }

    public function webp(): static
    {
        return $this->qrFormat(QrFormat::Webp);
    }

    public function eps(): static
    {
        return $this->qrFormat(QrFormat::Eps);
    }

    public function qrErrorCorrection(ErrorCorrectionLevel|string|Closure $level): static
    {
        $this->qrErrorCorrection = $level;

        return $this;
    }

    public function getQrErrorCorrection(): string
    {
        $level = $this->evaluateStylingValue($this->qrErrorCorrection);

        if ($level instanceof ErrorCorrectionLevel) {
            return $level->value;
        }

        if (is_scalar($level) || $level instanceof Stringable) {
            return (string) $level;
        }

        $configLevel = config('filament-qr.defaults.error_correction');

        return is_string($configLevel) ? $configLevel : 'M';
    }

    public function qrColor(string|Closure $color): static
    {
        $this->qrColor = $color;

        return $this;
    }

    public function getQrColor(): string
    {
        $color = $this->evaluateStylingValue($this->qrColor);
        $colorString = is_scalar($color) || $color instanceof Stringable ? (string) $color : null;
        if ($colorString === null) {
            $configColor = config('filament-qr.defaults.color');
            $colorString = is_string($configColor) ? $configColor : '#000000';
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
        $color = $this->evaluateStylingValue($this->qrBackgroundColor);
        $colorString = is_scalar($color) || $color instanceof Stringable ? (string) $color : null;
        if ($colorString === null) {
            $configColor = config('filament-qr.defaults.background_color');
            $colorString = is_string($configColor) ? $configColor : '#ffffff';
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

        $from = $this->evaluateStylingValue($this->qrGradient['from']);
        $to = $this->evaluateStylingValue($this->qrGradient['to']);
        $fromString = is_scalar($from) || $from instanceof Stringable ? (string) $from : '';
        $toString = is_scalar($to) || $to instanceof Stringable ? (string) $to : '';

        if ($fromString === '' || $toString === '') {
            return null;
        }

        $type = $this->evaluateStylingValue($this->qrGradient['type']);

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
        $this->qrEyeColors ??= [];

        $this->qrEyeColors[$eyeIndex] = [
            'inner' => $inner,
            'outer' => $outer,
        ];

        return $this;
    }

    /**
     * @param  array<int, array{inner: string, outer?: string|null}>|string|Closure  $innerOrConfig
     */
    public function qrEyeColors(string|array|Closure $innerOrConfig, string|Closure|null $outer = null): static
    {
        if (is_array($innerOrConfig)) {
            $this->qrEyeColors = $innerOrConfig;

            return $this;
        }

        $eye = [
            'inner' => $innerOrConfig,
            'outer' => $outer,
        ];

        $this->qrEyeColors = [$eye, $eye, $eye];

        return $this;
    }

    /**
     * @return array<int, array{inner: string, outer?: string|null}>|null
     */
    public function getQrEyeColors(): ?array
    {
        if ($this->qrEyeColors === null) {
            return null;
        }

        /** @var array<int, array{inner: string, outer?: string|null}> $resolved */
        $resolved = [];
        foreach ($this->qrEyeColors as $index => $colors) {
            if (! is_array($colors)) {
                continue;
            }

            $inner = isset($colors['inner']) ? $this->evaluateStylingValue($colors['inner']) : null;
            $outer = isset($colors['outer']) ? $this->evaluateStylingValue($colors['outer']) : null;

            if ($inner !== null) {
                $innerString = is_scalar($inner) || $inner instanceof Stringable ? (string) $inner : '';
                $outerString = is_scalar($outer) || $outer instanceof Stringable ? (string) $outer : null;

                $resolved[$index] = [
                    'inner' => $this->resolveColorToken($innerString),
                    'outer' => $outerString !== null ? $this->resolveColorToken($outerString) : null,
                ];
            }
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
        $style = $this->evaluateStylingValue($this->qrEyeStyle);

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
        $style = $this->evaluateStylingValue($this->qrInternalEyeStyle);

        if ($style instanceof EyeStyle) {
            return $style->value;
        }

        return is_scalar($style) || $style instanceof Stringable ? (string) $style : null;
    }

    public function qrStyle(QrStyle|string|Closure $style, float|Closure|null $size = null): static
    {
        $this->qrStyle = $style;
        if ($size !== null) {
            $this->qrStyleSize = $size;
        }

        return $this;
    }

    public function getQrStyle(): ?string
    {
        $style = $this->evaluateStylingValue($this->qrStyle);

        if ($style instanceof QrStyle) {
            return $style->value;
        }

        return is_scalar($style) || $style instanceof Stringable ? (string) $style : null;
    }

    public function getQrStyleSize(): ?float
    {
        $size = $this->evaluateStylingValue($this->qrStyleSize);

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
        $path = $this->evaluateStylingValue($this->qrLogoPath);

        return is_scalar($path) || $path instanceof Stringable ? (string) $path : null;
    }

    public function getQrLogoSize(): float
    {
        $size = $this->evaluateStylingValue($this->qrLogoSize);

        return is_numeric($size) ? (float) $size : 0.2;
    }

    public function qrDpi(int|Closure $dpi): static
    {
        $this->qrDpi = $dpi;

        return $this;
    }

    public function getQrDpi(): ?int
    {
        $dpi = $this->evaluateStylingValue($this->qrDpi);

        return is_numeric($dpi) ? (int) $dpi : null;
    }

    public function qrResolution(int|Closure $resolution): static
    {
        $this->qrResolution = $resolution;

        return $this;
    }

    public function getQrResolution(): ?int
    {
        $res = $this->evaluateStylingValue($this->qrResolution);

        return is_numeric($res) ? (int) $res : null;
    }

    public function qrEncoding(string|Closure $encoding): static
    {
        $this->qrEncoding = $encoding;

        return $this;
    }

    public function getQrEncoding(): string
    {
        $encoding = $this->evaluateStylingValue($this->qrEncoding);

        return is_scalar($encoding) || $encoding instanceof Stringable ? (string) $encoding : 'UTF-8';
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

    /**
     * @param  array<string, mixed>  $namedInjections
     */
    protected function evaluateStylingValue(mixed $value, array $namedInjections = []): mixed
    {
        return $this->evaluate($value, $namedInjections);
    }
}
