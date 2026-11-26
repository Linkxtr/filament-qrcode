<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Linkxtr\FilamentQrCode\Concerns\HasPayload;
use Linkxtr\FilamentQrCode\Concerns\HasQrCodeStyling;
use Linkxtr\FilamentQrCode\Concerns\HasQrInteractions;
use Throwable;

final class QrCodeField extends Field
{
    use HasPayload;
    use HasQrCodeStyling;
    use HasQrInteractions;

    protected string $view = 'filament-qrcode::forms.components.qr-code-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
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

    public function getQrPayload(): ?string
    {
        if ($this->payloadResolver instanceof Closure) {
            $raw = $this->evaluate($this->payloadResolver);
        } elseif ($this->payloadResolver !== null) {
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
}
