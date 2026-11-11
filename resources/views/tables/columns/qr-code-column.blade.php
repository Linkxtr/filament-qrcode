<div
    x-on:click.stop
    class="fi-ta-qr-code inline-flex items-center gap-2"
>
    @php
        $payload = is_callable($getQrPayload ?? null) ? $getQrPayload() : null;
        $component = $column ?? null;
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
            $statePath = (isset($column) && method_exists($column, 'getStatePath')) ? $column->getStatePath() : null;
        } catch (\Throwable) {
            $statePath = null;
        }
        $statePath ??= (is_callable($getName ?? null) ? $getName() : ($name ?? 'qr'));
        $modalId = 'fi-qr-preview-' . str_replace(['.', '$', '#', '[', ']'], '-', (string) $statePath);
        $isPreviewable = is_callable($isPreviewable ?? null) ? $isPreviewable() : true;
        $isDownloadable = is_callable($isDownloadable ?? null) ? $isDownloadable() : false;
        $previewHeading = (is_callable($getPreviewHeading ?? null) ? $getPreviewHeading() : null)
            ?? (is_callable($getLabel ?? null) ? $getLabel() : ($label ?? null))
            ?? __('QR Code Preview');
        $recordKey = (isset($column) && method_exists($column, 'getRecordKey')) ? $column->getRecordKey() : null;
        $renderedRaw = is_callable($renderQrCode ?? null) ? $renderQrCode() : ($component?->renderQrCode());
        $downloadHref = $format === 'svg'
            ? 'data:image/svg+xml;base64,' . base64_encode($renderedRaw ?? '')
            : ($renderedRaw ?? '');
        $resolvedFilename = is_callable($getDownloadFilename ?? null)
            ? $getDownloadFilename()
            : (($component && method_exists($component, 'getDownloadFilename')) ? $component->getDownloadFilename() : ('qr-' . ($recordKey ?? 'code')));
        $downloadFilename = str_ends_with(strtolower((string) $resolvedFilename), '.' . strtolower((string) $format))
            ? (string) $resolvedFilename
            : ((string) $resolvedFilename . '.' . $format);
    @endphp

    @if ($payload)
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
                        title="{{ __('Click to zoom preview') }}"
                        class="inline-flex items-center justify-center p-1 rounded-md bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 hover:scale-105 hover:shadow transition-all cursor-zoom-in focus:outline-none"
                    >
                        <div class="w-12 h-12 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full">
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
                                class="w-full h-full object-contain"
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
            <div class="inline-flex items-center justify-center p-1 rounded-md bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="w-12 h-12 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full" title="{{ $payload }}">
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
                        class="w-full h-full object-contain"
                    />
                </div>
            </div>
        @endif

        @if ($isDownloadable && ! $isPreviewable)
            <a
                x-on:click.stop
                href="{{ $downloadHref }}"
                download="{{ $downloadFilename }}"
                class="fi-ta-qr-download inline-flex items-center justify-center p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                title="{{ __('Download QR') }}"
            >
                <x-filament::icon icon="heroicon-o-arrow-down-tray" class="w-4 h-4" />
            </a>
        @endif
    @else
        <span class="text-sm text-gray-400 italic">No QR Code Available</span>
    @endif
</div>
