<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQrCode\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;
use Linkxtr\FilamentQrCode\Concerns\HasPayload;
use Linkxtr\FilamentQrCode\Concerns\HasQrCodeStyling;
use Linkxtr\FilamentQrCode\Concerns\HasQrInteractions;

final class QrCodeColumn extends Column
{
    use HasPayload;
    use HasQrCodeStyling;
    use HasQrInteractions;

    protected string $view = 'filament-qrcode::tables.columns.qr-code-column';

    protected function setUp(): void
    {
        $this->disabledClick();
    }

    public function getQrPayload(): ?string
    {
        $raw = $this->payloadResolver instanceof Closure
            ? $this->evaluate($this->payloadResolver)
            : ($this->payloadResolver ?? $this->getState() ?? $this->evaluate($this->getStateUsing));

        return $this->resolveFormattedPayload($raw);
    }
}
