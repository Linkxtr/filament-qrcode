<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQrCode\Actions;

use Closure;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Linkxtr\FilamentQrCode\Concerns\HasPayload;
use Linkxtr\FilamentQrCode\Concerns\HasQrCodeStyling;
use Linkxtr\FilamentQrCode\Services\QrCodeRenderer;
use Stringable;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadQrCodeAction extends Action
{
    use HasPayload;
    use HasQrCodeStyling;

    private string|Closure $filename = 'qrcode';

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Download QR');
        $this->icon('heroicon-o-arrow-down-tray');

        $this->action(function (): ?StreamedResponse {
            $record = $this->getRecord();
            $payload = $this->resolvePayloadForRecord($record);

            if (empty($payload)) {
                return null;
            }

            $config = $this->toQrCodeConfig();
            $format = $config->getFormat()->value;

            /** @var QrCodeRenderer $qrCodeRenderer */
            $qrCodeRenderer = app(QrCodeRenderer::class);

            $output = $qrCodeRenderer->renderRaw($payload, $config, $this->getQrConfigurator($record));

            $contentType = match ($format) {
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
                'eps' => 'application/postscript',
                default => 'image/png',
            };

            $filename = $this->getFilename();

            return response()->streamDownload(function () use ($output): void {
                echo $output;
            }, "{$filename}.{$format}", ['Content-Type' => $contentType]);
        });
    }

    public static function getDefaultName(): string
    {
        return 'downloadQr';
    }

    public function resolvePayloadForRecord(mixed $record): ?string
    {
        if ($this->payloadResolver instanceof Closure) {
            $raw = $this->evaluate($this->payloadResolver, ['record' => $record]);
        } elseif ($this->payloadResolver !== null) {
            $raw = $this->payloadResolver;
        } elseif ($record instanceof Model) {
            $raw = $record->getAttribute('qr') ?? $record->getAttribute('qr_code') ?? $record->getAttribute('url') ?? $record->getKey();
        } elseif (is_array($record)) {
            $raw = $record['qr'] ?? $record['qr_code'] ?? $record['url'] ?? $record['id'] ?? null;
        } elseif (is_object($record) && isset($record->qr)) {
            $raw = $record->qr;
        } elseif (is_object($record) && isset($record->url)) {
            $raw = $record->url;
        } else {
            $raw = $record;
        }

        return $this->resolveFormattedPayload($raw);
    }

    public function filename(string|Closure $name): static
    {
        $this->filename = $name;

        return $this;
    }

    public function getFilename(): string
    {
        $resolved = $this->evaluate($this->filename);

        return is_scalar($resolved) || $resolved instanceof Stringable ? (string) $resolved : 'qrcode';
    }
}
