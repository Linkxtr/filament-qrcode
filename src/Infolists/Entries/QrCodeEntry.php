<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQrCode\Infolists\Entries;

use Closure;
use Filament\Infolists\Components\Entry;
use Illuminate\Database\Eloquent\Model;
use Linkxtr\FilamentQrCode\Concerns\HasPayload;
use Linkxtr\FilamentQrCode\Concerns\HasQrCodeStyling;
use Linkxtr\FilamentQrCode\Concerns\HasQrInteractions;
use Throwable;

final class QrCodeEntry extends Entry
{
    use HasPayload;
    use HasQrCodeStyling;
    use HasQrInteractions;

    protected string $view = 'filament-qrcode::infolists.entries.qr-code-entry';

    /**
     * @param  array<string, mixed>|class-string<Model>|Closure|Model|null  $record
     */
    public function record(Model|array|Closure|string|null $record): static
    {
        $this->model($record);

        return $this;
    }

    public function getQrPayload(): ?string
    {
        if ($this->payloadResolver instanceof Closure) {
            $raw = $this->evaluate($this->payloadResolver);
        } elseif ($this->payloadResolver !== null) {
            $raw = $this->payloadResolver;
        } else {
            try {
                $raw = $this->getState();
            } catch (Throwable) {
                $raw = null;
            }
        }

        return $this->resolveFormattedPayload($raw);
    }
}
