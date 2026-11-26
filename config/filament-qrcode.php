<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default QR Code Configuration
    |--------------------------------------------------------------------------
    |
    | Default values applied across Filament table columns, infolist entries,
    | and actions when not overridden via fluent component setters.
    |
    | Configuration resolution follows a strict 4-tier inheritance model:
    |   Tier 1: Explicit Component Setter (e.g. ->qrSize(200))
    |   Tier 2: config('filament-qrcode.defaults.*') (configured below or via .env)
    |   Tier 3: config('qrcode.*') (inherited from linkxtr/laravel-qrcode)
    |   Tier 4: Hardcoded Fallbacks (size: 160, margin: 1, format: 'svg',
    |                               error_correction: 'M', color: '#000000',
    |                               background_color: '#ffffff')
    |
    | Setting any key to null allows it to inherit from `config('qrcode.*')`.
    |
    */
    'defaults' => [
        /*
        | Output format for generated QR codes.
        | Supported: "svg", "png", "webp"
        */
        'format' => env('FILAMENT_QR_CODE_FORMAT', 'svg'),

        /*
        | Size of the generated QR code in pixels.
        | Supported: integer (e.g. 160, 200, 400)
        */
        'size' => (int) (env('FILAMENT_QR_CODE_SIZE') ?? 400),

        /*
        | Margin / quiet zone around the QR code in blocks.
        | Supported: integer (e.g. 0, 1, 2, 4)
        */
        'margin' => (int) (env('FILAMENT_QR_CODE_MARGIN') ?? 4),

        /*
        | Foreground / module color. Accepts Hex ('#000000'), RGB/RGBA CSV ('0,0,0'),
        | or Filament semantic color token ('primary', 'primary-600', 'danger', etc.).
        */
        'color' => env('FILAMENT_QR_CODE_COLOR', '0,0,0'),

        /*
        | Background color. Accepts Hex ('#ffffff'), RGB/RGBA CSV ('255,255,255'),
        | or Filament semantic color token.
        */
        'background_color' => env('FILAMENT_QR_CODE_BACKGROUND_COLOR', '255,255,255'),

        /*
        | Error correction level.
        | Supported: "L" (7%), "M" (15%), "Q" (25%), "H" (30%)
        */
        'error_correction' => env('FILAMENT_QR_CODE_ERROR_CORRECTION', 'M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Preview Modal Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for the zoom/preview modal opened on click.
    |
    */
    'preview' => [
        /*
        | Filament modal width class. Supported: "xs", "sm", "md", "lg", "xl", "2xl", etc.
        */
        'modal_width' => env('FILAMENT_QR_CODE_PREVIEW_MODAL_WIDTH', 'sm'),

        /*
        | Rendered size in pixels for the enlarged preview inside the modal.
        */
        'render_size' => (int) (env('FILAMENT_QR_CODE_PREVIEW_SIZE') ?? 320),
    ],
];
