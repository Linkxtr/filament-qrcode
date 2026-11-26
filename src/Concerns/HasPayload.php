<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQrCode\Concerns;

use Closure;
use Stringable;

trait HasPayload
{
    protected Closure|Stringable|string|null $payloadResolver = null;

    protected ?Closure $payloadFormatter = null;

    public function payload(string|Stringable|Closure|null $payload): static
    {
        $this->payloadResolver = $payload;

        return $this;
    }

    public function formatPayloadUsing(Closure $callback): static
    {
        $this->payloadFormatter = $callback;

        return $this;
    }

    public function resolveFormattedPayload(mixed $raw): ?string
    {
        $value = $this->payloadFormatter instanceof Closure
            ? ($this->payloadFormatter)($raw)
            : $raw;

        return is_scalar($value) || $value instanceof Stringable
            ? (string) $value
            : null;
    }
}
