<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQrCode\Services;

use BaconQrCode\Renderer\RendererStyle\Gradient;
use Closure;
use Linkxtr\QrCode\DTOs\Config;
use Linkxtr\QrCode\Enums\EyeStyle;
use Linkxtr\QrCode\Facades\QrCode;
use Linkxtr\QrCode\Generator;

final class QrCodeRenderer
{
    /**
     * Render the given payload as raw output (raw SVG string or binary image).
     */
    public function renderRaw(string $payload, ?Config $config = null, ?Closure $configureUsing = null): string
    {
        $config ??= new Config;

        $color = $config->getColorValue()->toRgb();
        $bg = $config->getBackgroundColorValue()->toRgb();

        $builder = QrCode::format($config->getFormat())
            ->size($config->getSize())
            ->margin($config->getMargin())
            ->errorCorrection($config->getErrorCorrectionLevel())
            ->color($color->red, $color->green, $color->blue, $color->alpha)
            ->backgroundColor($bg->red, $bg->green, $bg->blue, $bg->alpha)
            ->style($config->getStyle(), $config->getStyleSize());

        $gradient = $config->getGradient();
        if ($gradient instanceof Gradient) {
            $start = $gradient->getStartColor()->toRgb();
            $end = $gradient->getEndColor()->toRgb();
            $builder = $builder->gradient(
                [$start->getRed(), $start->getGreen(), $start->getBlue()],
                [$end->getRed(), $end->getGreen(), $end->getBlue()],
                strtolower($gradient->getType()->name()),
            );
        }

        foreach ($config->getEyeColors() as $index => $eyeFill) {
            $inner = $eyeFill->getExternalColor()->toRgb();
            $outer = $eyeFill->inheritsInternalColor() ? null : $eyeFill->getInternalColor()->toRgb();
            $builder = $builder->eyeColor(
                $index,
                [$inner->getRed(), $inner->getGreen(), $inner->getBlue()],
                $outer !== null ? [$outer->getRed(), $outer->getGreen(), $outer->getBlue()] : null,
            );
        }

        if ($config->getEyeStyle() instanceof EyeStyle) {
            $builder = $builder->eye($config->getEyeStyle());
        }

        if ($config->getInternalEyeStyle() instanceof EyeStyle) {
            $builder = $builder->internalEye($config->getInternalEyeStyle());
        }

        if ($config->getEncoding() !== 'UTF-8') {
            $builder = $builder->encoding($config->getEncoding());
        }

        if ($config->getImageMerge() !== '') {
            $builder = $builder->mergeString($config->getImageMerge(), $config->getImagePercentage());
        }

        if ($configureUsing instanceof Closure) {
            $configured = $configureUsing($builder);

            if ($configured instanceof Generator) {
                $builder = $configured;
            }
        }

        return (string) $builder->generate($payload);
    }

    /**
     * Render the given payload as an inline SVG string or base64 data URI.
     */
    public function render(string $payload, ?Config $config = null, ?Closure $configureUsing = null): string
    {
        $config ??= new Config;
        $raw = $this->renderRaw($payload, $config, $configureUsing);
        $format = $config->getFormat()->value;

        if ($format === 'svg') {
            return $raw;
        }

        return 'data:image/'.$format.';base64,'.base64_encode($raw);
    }

    /**
     * Render as a data URI regardless of format.
     */
    public function renderDataUri(string $payload, ?Config $config = null, ?Closure $configureUsing = null): string
    {
        $config ??= new Config;
        $raw = $this->renderRaw($payload, $config, $configureUsing);
        $format = $config->getFormat()->value;
        $mime = $format === 'svg' ? 'image/svg+xml' : 'image/'.$format;

        return 'data:'.$mime.';base64,'.base64_encode($raw);
    }
}
