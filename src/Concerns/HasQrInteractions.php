<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQrCode\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Linkxtr\FilamentQrCode\Services\QrCodeRenderer;
use Stringable;
use Throwable;

trait HasQrInteractions
{
    protected bool|Closure|null $isDownloadable = null;

    protected string|Closure|null $downloadFilename = null;

    protected bool|Closure|null $isPreviewable = null;

    protected int|Closure|null $previewSize = null;

    protected string|Closure|null $previewHeading = null;

    protected bool|Closure|null $isCopyable = null;

    protected string|Closure $copyMessage = 'Copied to clipboard!';

    public function downloadable(bool|Closure $condition = true): static
    {
        $this->isDownloadable = $condition;

        return $this;
    }

    public function isDownloadable(): bool
    {
        if ($this->isDownloadable !== null) {
            return (bool) $this->evaluate($this->isDownloadable);
        }

        return false;
    }

    public function downloadFilename(string|Closure $filename): static
    {
        $this->downloadFilename = $filename;

        return $this;
    }

    public function getDownloadFilename(): string
    {
        if ($this->downloadFilename !== null) {
            $evaluated = $this->evaluate($this->downloadFilename);

            return is_scalar($evaluated) || $evaluated instanceof Stringable ? (string) $evaluated : 'qr-code';
        }

        try {
            $record = $this->getRecord();
        } catch (Throwable) {
            $record = null;
        }

        if ($record instanceof Model) {
            $key = $record->getKey();
        } elseif (is_array($record)) {
            $key = $record['id'] ?? $record['key'] ?? 'code';
        } else {
            $key = 'code';
        }

        $keyString = is_scalar($key) || $key instanceof Stringable ? $key : 'code';

        return "qr-{$keyString}";
    }

    public function previewable(bool|Closure $condition = true): static
    {
        $this->isPreviewable = $condition;

        return $this;
    }

    public function isPreviewable(): bool
    {
        if ($this->isPreviewable !== null) {
            return (bool) $this->evaluate($this->isPreviewable);
        }

        return true;
    }

    public function previewSize(int|Closure $size): static
    {
        $this->previewSize = $size;

        return $this;
    }

    public function getPreviewSize(): int
    {
        $evaluated = $this->evaluate($this->previewSize);
        if (is_numeric($evaluated)) {
            return (int) $evaluated;
        }

        $configSize = config('filament-qrcode.preview.render_size');

        return is_numeric($configSize) ? (int) $configSize : 320;
    }

    public function previewHeading(string|Closure $heading): static
    {
        $this->previewHeading = $heading;

        return $this;
    }

    public function getPreviewHeading(): string
    {
        $evaluated = $this->evaluate($this->previewHeading);
        if (is_scalar($evaluated) || $evaluated instanceof Stringable) {
            return (string) $evaluated;
        }

        return 'QR Code Preview';
    }

    public function copyable(bool|Closure $condition = true): static
    {
        $this->isCopyable = $condition;

        return $this;
    }

    public function isCopyable(): bool
    {
        if ($this->isCopyable !== null) {
            return (bool) $this->evaluate($this->isCopyable);
        }

        return true;
    }

    public function copyMessage(string|Closure $message): static
    {
        $this->copyMessage = $message;

        return $this;
    }

    public function getCopyMessage(): string
    {
        $evaluated = $this->evaluate($this->copyMessage);
        if (is_scalar($evaluated) || $evaluated instanceof Stringable) {
            return (string) $evaluated;
        }

        return 'Copied to clipboard!';
    }

    public function renderQrCode(): ?string
    {
        return $this->renderQrWithSize();
    }

    public function renderPreviewQrCode(): ?string
    {
        return $this->renderQrWithSize($this->getPreviewSize());
    }

    protected function renderQrWithSize(?int $size = null): ?string
    {
        $payload = $this->getQrPayload();

        if (empty($payload)) {
            return null;
        }

        /** @var QrCodeRenderer $qrCodeRenderer */
        $qrCodeRenderer = app(QrCodeRenderer::class);

        return $qrCodeRenderer->render(
            $payload,
            $this->toQrCodeConfig($size),
            $this->getQrConfigurator(),
        );
    }
}
