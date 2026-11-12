@php
    $field = $field ?? null;
    $component = $field;

    $payload = is_callable($getQrPayload ?? null) ? $getQrPayload() : ($component?->getQrPayload());
    $format = is_callable($getQrFormat ?? null) ? $getQrFormat() : ($component?->getQrFormat() ?? 'svg');
    $size = is_callable($getQrSize ?? null) ? $getQrSize() : ($component?->getQrSize() ?? 160);
    $previewSize = is_callable($getPreviewSize ?? null) ? $getPreviewSize() : ($component?->getPreviewSize() ?? 320);
    $margin = is_callable($getQrMargin ?? null) ? $getQrMargin() : ($component?->getQrMargin() ?? 1);
    $color = is_callable($getQrColor ?? null) ? $getQrColor() : ($component?->getQrColor() ?? '#000000');
    $backgroundColor = is_callable($getQrBackgroundColor ?? null) ? $getQrBackgroundColor() : ($component?->getQrBackgroundColor() ?? '#ffffff');
    $errorCorrection = is_callable($getQrErrorCorrection ?? null) ? $getQrErrorCorrection() : ($component?->getQrErrorCorrection() ?? 'M');
    $style = is_callable($getQrStyle ?? null) ? $getQrStyle() : $component?->getQrStyle();
    $eye = is_callable($getQrEyeStyle ?? null) ? $getQrEyeStyle() : $component?->getQrEyeStyle();
    $gradient = is_callable($getQrGradient ?? null) ? $getQrGradient() : $component?->getQrGradient();
    $gradientString = $gradient ? ($gradient['from'] . ';' . $gradient['to']) : null;
    $gradientType = $gradient['type'] ?? null;
    $eyeColors = is_callable($getQrEyeColors ?? null) ? $getQrEyeColors() : $component?->getQrEyeColors();
    $eyeColor0 = isset($eyeColors[0]) ? ($eyeColors[0]['inner'] . (isset($eyeColors[0]['outer']) ? ';' . $eyeColors[0]['outer'] : '')) : null;
    $eyeColor1 = isset($eyeColors[1]) ? ($eyeColors[1]['inner'] . (isset($eyeColors[1]['outer']) ? ';' . $eyeColors[1]['outer'] : '')) : null;
    $eyeColor2 = isset($eyeColors[2]) ? ($eyeColors[2]['inner'] . (isset($eyeColors[2]['outer']) ? ';' . $eyeColors[2]['outer'] : '')) : null;
    $logoPath = is_callable($getQrLogoPath ?? null) ? $getQrLogoPath() : $component?->getQrLogoPath();
    $logoSize = is_callable($getQrLogoSize ?? null) ? $getQrLogoSize() : ($component?->getQrLogoSize() ?? 0.2);
    $encoding = is_callable($getQrEncoding ?? null) ? $getQrEncoding() : ($component?->getQrEncoding() ?? 'UTF-8');

    $statePath = null;
    try {
        if ($field && method_exists($field, 'hasContainer') && $field->hasContainer()) {
            $statePath = $field->getStatePath();
        }
    } catch (\Throwable) {
        $statePath = null;
    }
    if ($statePath === null) {
        $nameVal = is_callable($getName ?? null) ? $getName() : null;
        $statePath = (is_scalar($nameVal) || $nameVal instanceof \Stringable) ? (string) $nameVal : 'qr';
    }
    $statePath = (string) $statePath;
    $modalId = 'fi-qr-preview-' . str_replace(['.', '$', '#', '[', ']'], '-', $statePath);

    $isPreviewable = is_callable($isPreviewable ?? null) ? (bool) $isPreviewable() : ($component?->isPreviewable() ?? true);
    $isDownloadable = is_callable($isDownloadable ?? null) ? (bool) $isDownloadable() : ($component?->isDownloadable() ?? false);
    $isCopyable = is_callable($isCopyable ?? null) ? (bool) $isCopyable() : ($component?->isCopyable() ?? true);

    $copyMessage = null;
    if (is_callable($getCopyMessage ?? null)) {
        $copyMessage = $getCopyMessage();
    } elseif ($component && method_exists($component, 'getCopyMessage')) {
        $copyMessage = $component->getCopyMessage();
    }
    if (! is_string($copyMessage) && ! $copyMessage instanceof \Stringable) {
        $copyMessage = __('Copied to clipboard!');
    }
    $copyMessage = (string) $copyMessage;

    $previewHeading = null;
    if (is_callable($getPreviewHeading ?? null)) {
        $previewHeading = $getPreviewHeading();
    } elseif ($component && method_exists($component, 'getPreviewHeading')) {
        $previewHeading = $component->getPreviewHeading();
    }
    if ($previewHeading === null && is_callable($getLabel ?? null)) {
        $previewHeading = $getLabel();
    } elseif ($previewHeading === null && $component && method_exists($component, 'getLabel')) {
        $previewHeading = $component->getLabel();
    }
    if (! is_string($previewHeading) && ! $previewHeading instanceof \Stringable) {
        $previewHeading = __('QR Code Preview');
    }
    $previewHeading = (string) $previewHeading;

    $recordKey = null;
    try {
        $recordKey = (isset($field) && method_exists($field, 'getRecord') && $field->getRecord()) ? $field->getRecord()->getKey() : null;
    } catch (\Throwable) {
        $recordKey = null;
    }

    $renderedRaw = is_callable($renderQrCode ?? null) ? $renderQrCode() : ($component?->renderQrCode());
    $downloadHref = $format === 'svg'
        ? 'data:image/svg+xml;base64,' . base64_encode($renderedRaw ?? '')
        : ($renderedRaw ?? '');

    $resolvedFilename = null;
    if (is_callable($getDownloadFilename ?? null)) {
        $resolvedFilename = $getDownloadFilename();
    } elseif ($component && method_exists($component, 'getDownloadFilename')) {
        $resolvedFilename = $component->getDownloadFilename();
    }
    if (! is_string($resolvedFilename) && ! $resolvedFilename instanceof \Stringable) {
        $resolvedFilename = 'qr-' . ($recordKey ?? 'code');
    }
    $resolvedFilename = (string) $resolvedFilename;

    $downloadFilename = str_ends_with(strtolower($resolvedFilename), '.' . strtolower((string) $format))
        ? $resolvedFilename
        : ($resolvedFilename . '.' . $format);
@endphp

<div
    x-data="{ copied: false }"
    class="fi-fo-qr-code flex flex-col items-center justify-center w-full"
    wire:key="{{ $statePath }}-{{ md5($payload ?? '') }}"
>
    @if ($payload)
        <div
            class="fi-fo-qr-card w-full max-w-sm rounded-xl p-4 bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center text-center transition-all"
            style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;"
        >
            @if ($isPreviewable)
                <x-filament::modal
                    :id="$modalId"
                    width="md"
                    alignment="center"
                >
                    <x-slot name="trigger">
                        <button
                            type="button"
                            x-on:click.stop.prevent="$dispatch('open-modal', { id: @js($modalId) })"
                            class="p-3 rounded-lg bg-white shadow-inner flex items-center justify-center cursor-zoom-in hover:scale-105 transition-all focus:outline-none border border-gray-100 dark:border-gray-700"
                            title="{{ __('Click to zoom preview') }}"
                        >
                            <div class="max-w-full [&>svg]:w-full [&>svg]:h-auto flex items-center justify-center">
                                <x-qrcode
                                    :data="$payload"
                                    :size="$size"
                                    :format="$format"
                                    :color="$color"
                                    :background-color="$backgroundColor"
                                    :margin="$margin"
                                    :style="$style"
                                    :error-correction="$errorCorrection"
                                    :encoding="$encoding"
                                    :eye="$eye"
                                    :eye-color0="$eyeColor0"
                                    :eye-color1="$eyeColor1"
                                    :eye-color2="$eyeColor2"
                                    :gradient="$gradientString"
                                    :gradient-type="$gradientType"
                                    :merge="$logoPath"
                                    :merge-percentage="$logoSize"
                                    class="max-w-full h-auto object-contain"
                                />
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="heading">
                        {{ $previewHeading }}
                    </x-slot>

                    <div
                        class="fi-qr-modal-content flex flex-col items-center justify-center p-4 text-center"
                        style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; text-align: center; padding: 1rem;"
                        x-on:click.stop
                    >
                        <div
                            class="p-4 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center justify-center mx-auto"
                            style="display: flex; justify-content: center; align-items: center; margin: 0 auto; max-width: 280px; width: 100%; aspect-ratio: 1 / 1; border-radius: 0.75rem; padding: 1rem; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); border: 1px solid rgba(0, 0, 0, 0.05);"
                        >
                            <div
                                class="w-full h-full flex items-center justify-center [&>svg]:w-full [&>svg]:h-full [&>img]:w-full [&>img]:h-full"
                                style="display: flex; justify-content: center; align-items: center; width: 100%; height: 100%;"
                            >
                                <x-qrcode
                                    :data="$payload"
                                    :size="$previewSize"
                                    :format="$format"
                                    :color="$color"
                                    :background-color="$backgroundColor"
                                    :margin="$margin"
                                    :style="$style"
                                    :error-correction="$errorCorrection"
                                    :encoding="$encoding"
                                    :eye="$eye"
                                    :eye-color0="$eyeColor0"
                                    :eye-color1="$eyeColor1"
                                    :eye-color2="$eyeColor2"
                                    :gradient="$gradientString"
                                    :gradient-type="$gradientType"
                                    :merge="$logoPath"
                                    :merge-percentage="$logoSize"
                                    class="w-full h-full object-contain"
                                />
                            </div>
                        </div>

                        <div
                            class="w-full flex justify-center text-center mt-4 px-2"
                            style="display: flex; justify-content: center; text-align: center; width: 100%; margin-top: 1rem;"
                        >
                            <span
                                class="inline-block font-mono text-xs text-gray-600 dark:text-gray-400 break-all select-all bg-gray-50 dark:bg-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700"
                                style="display: inline-block; font-family: monospace; font-size: 0.75rem; word-break: break-all; padding: 0.375rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(156, 163, 175, 0.25); background-color: rgba(156, 163, 175, 0.08);"
                            >
                                {{ $payload }}
                            </span>
                        </div>

                        @if ($isDownloadable)
                            <div
                                class="w-full flex justify-center mt-4"
                                style="display: flex; justify-content: center; width: 100%; margin-top: 1rem;"
                            >
                                <x-filament::button
                                    tag="a"
                                    :href="$downloadHref"
                                    :download="$downloadFilename"
                                    icon="heroicon-o-arrow-down-tray"
                                    size="sm"
                                    color="primary"
                                    x-on:click.stop
                                >
                                    {{ __('Download QR') }}
                                </x-filament::button>
                            </div>
                        @endif
                    </div>
                </x-filament::modal>
            @else
                <div class="p-3 rounded-lg bg-white shadow-inner flex items-center justify-center border border-gray-100 dark:border-gray-700">
                    <div class="max-w-full [&>svg]:w-full [&>svg]:h-auto flex items-center justify-center">
                        <x-qrcode
                            :data="$payload"
                            :size="$size"
                            :format="$format"
                            :color="$color"
                            :background-color="$backgroundColor"
                            :margin="$margin"
                            :style="$style"
                            :error-correction="$errorCorrection"
                            :encoding="$encoding"
                            :eye="$eye"
                            :eye-color0="$eyeColor0"
                            :eye-color1="$eyeColor1"
                            :eye-color2="$eyeColor2"
                            :gradient="$gradientString"
                            :gradient-type="$gradientType"
                            :merge="$logoPath"
                            :merge-percentage="$logoSize"
                            class="max-w-full h-auto object-contain"
                        />
                    </div>
                </div>
            @endif

            <div
                class="w-full flex justify-center text-center mt-3 px-2"
                style="display: flex; justify-content: center; text-align: center; width: 100%; margin-top: 0.75rem;"
            >
                <span
                    class="inline-block font-mono text-xs text-gray-600 dark:text-gray-300 break-all select-all bg-gray-50 dark:bg-gray-900/60 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700"
                    style="display: inline-block; font-family: monospace; font-size: 0.75rem; word-break: break-all; padding: 0.375rem 0.75rem; border-radius: 0.5rem;"
                >
                    {{ $payload }}
                </span>
            </div>

            @if ($isDownloadable || $isCopyable)
                <div
                    class="mt-4 flex flex-wrap items-center justify-center gap-2"
                    style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 1rem;"
                >
                    @if ($isCopyable)
                        <x-filament::button
                            type="button"
                            color="gray"
                            size="sm"
                            icon="heroicon-o-clipboard-document"
                            x-on:click="window.navigator.clipboard.writeText(@js($payload)); copied = true; setTimeout(() => copied = false, 2000)"
                        >
                            <span x-text="copied ? @js($copyMessage) : @js(__('Copy'))"></span>
                        </x-filament::button>
                    @endif

                    @if ($isDownloadable)
                        <x-filament::button
                            tag="a"
                            :href="$downloadHref"
                            :download="$downloadFilename"
                            icon="heroicon-o-arrow-down-tray"
                            size="sm"
                            color="primary"
                            x-on:click.stop
                        >
                            {{ __('Download QR') }}
                        </x-filament::button>
                    @endif
                </div>
            @endif
        </div>
    @else
        <div
            class="fi-fo-qr-empty w-full max-w-sm rounded-xl p-6 bg-gray-50 dark:bg-gray-900/50 border border-dashed border-gray-200 dark:border-gray-700 text-center text-sm text-gray-400 dark:text-gray-500 italic"
            style="display: flex; justify-content: center; align-items: center; width: 100%; padding: 1.5rem; border-radius: 0.75rem; text-align: center;"
        >
            {{ __('No QR Code Available') }}
        </div>
    @endif
</div>
