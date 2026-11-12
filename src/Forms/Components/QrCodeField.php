<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQr\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Linkxtr\FilamentQr\Concerns\HasPayloadFormatters;
use Linkxtr\FilamentQr\Concerns\HasQrCodeStyling;
use Linkxtr\FilamentQr\Concerns\HasQrInteractions;
use Linkxtr\FilamentQr\Services\QrCodeRenderer;
use Linkxtr\QrCode\Enums\ErrorCorrectionLevel;
use Linkxtr\QrCode\Enums\EyeStyle;
use Linkxtr\QrCode\Enums\Format;
use Linkxtr\QrCode\Enums\GradientType;
use Linkxtr\QrCode\Enums\Style;
use Throwable;

final class QrCodeField extends Field
{
    use HasPayloadFormatters;
    use HasQrCodeStyling;
    use HasQrInteractions;

    protected string $view = 'filament-qr::forms.components.qr-code-field';

    private Closure|string|null $payloadResolver = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
    }

    /**
     * Set a custom payload resolver closure or static string.
     */
    public function payload(string|Closure $callback): static
    {
        $this->payloadResolver = $callback;

        return $this;
    }

    /**
     * Set QR size in pixels.
     */
    public function size(int|Closure $size): static
    {
        return $this->qrSize($size);
    }

    /**
     * Set quiet-zone margin in modules.
     */
    public function margin(int|Closure $margin): static
    {
        return $this->qrMargin($margin);
    }

    /**
     * Set QR output format (svg, png, webp, eps).
     */
    public function format(Format|string|Closure $format): static
    {
        return $this->qrFormat($format);
    }

    /**
     * Set error correction level (L, M, Q, H).
     */
    public function errorCorrection(ErrorCorrectionLevel|string|Closure $level): static
    {
        return $this->qrErrorCorrection($level);
    }

    /**
     * Set foreground / module color (hex or Filament theme token).
     */
    public function color(string|Closure $color): static
    {
        return $this->qrColor($color);
    }

    /**
     * Set background color (hex or Filament theme token).
     */
    public function backgroundColor(string|Closure $color): static
    {
        return $this->qrBackgroundColor($color);
    }

    /**
     * Set linear gradient for modules.
     */
    public function gradient(string|Closure $from, string|Closure $to, GradientType|string|Closure $type = 'vertical'): static
    {
        return $this->qrGradient($from, $to, $type);
    }

    /**
     * Set finder eye shape style.
     */
    public function eye(EyeStyle|string|Closure $style): static
    {
        return $this->qrEyeStyle($style);
    }

    /**
     * Set module pattern style.
     */
    public function style(Style|string|Closure $style, float|Closure|null $size = null): static
    {
        return $this->qrStyle($style, $size);
    }

    /**
     * Set embedded logo image path and scaling.
     */
    public function logo(string|Closure $path, float|Closure $size = 0.2): static
    {
        return $this->qrLogo($path, $size);
    }

    /**
     * Set output DPI.
     */
    public function dpi(int|Closure $dpi): static
    {
        return $this->qrDpi($dpi);
    }

    /**
     * Set output resolution.
     */
    public function resolution(int|Closure $resolution): static
    {
        return $this->qrResolution($resolution);
    }

    /**
     * Set character encoding.
     */
    public function encoding(string|Closure $encoding): static
    {
        return $this->qrEncoding($encoding);
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraViewData(): array
    {
        if (! $this->hasContainer()) {
            return [
                'record' => $this->getRecord(withContainerRecord: false),
            ];
        }

        return parent::getExtraViewData();
    }

    /**
     * Resolve the string payload for this field.
     */
    public function getQrPayload(): ?string
    {
        if ($this->payloadResolver instanceof Closure) {
            $raw = $this->evaluate($this->payloadResolver);
        } elseif (is_string($this->payloadResolver)) {
            $raw = $this->payloadResolver;
        } else {
            try {
                $raw = $this->hasContainer() ? $this->getState() : null;
            } catch (Throwable) {
                $raw = null;
            }
        }

        return $this->resolveFormattedPayload($raw);
    }

    /**
     * Render the QR code using QrCodeRenderer and all styling options.
     */
    public function renderQrCode(): ?string
    {
        $payload = $this->getQrPayload();

        if (empty($payload)) {
            return null;
        }

        /** @var QrCodeRenderer $qrCodeRenderer */
        $qrCodeRenderer = app(QrCodeRenderer::class);

        return $qrCodeRenderer->render(
            payload: $payload,
            size: $this->getQrSize(),
            margin: $this->getQrMargin(),
            format: $this->getQrFormat(),
            errorCorrection: $this->getQrErrorCorrection(),
            color: $this->getQrColor(),
            backgroundColor: $this->getQrBackgroundColor(),
            gradient: $this->getQrGradient(),
            eyeColors: $this->getQrEyeColors(),
            eyeStyle: $this->getQrEyeStyle(),
            internalEyeStyle: $this->getQrInternalEyeStyle(),
            style: $this->getQrStyle(),
            styleSize: $this->getQrStyleSize(),
            logoPath: $this->getQrLogoPath(),
            logoSize: $this->getQrLogoSize(),
            dpi: $this->getQrDpi(),
            resolution: $this->getQrResolution(),
            encoding: $this->getQrEncoding(),
        );
    }
}
