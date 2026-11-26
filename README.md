# Filament QR Code

[![Latest Version on Packagist](https://img.shields.io/packagist/v/linkxtr/filament-qrcode.svg?style=flat-square)](https://packagist.org/packages/linkxtr/filament-qrcode)
[![Total Downloads](https://img.shields.io/packagist/dt/linkxtr/filament-qrcode.svg?style=flat-square)](https://packagist.org/packages/linkxtr/filament-qrcode)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/linkxtr/filament-qrcode/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/linkxtr/filament-qrcode/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/PHP-8.4%20%7C%208.5-blue.svg?style=flat-square)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12%20%7C%2013-red.svg?style=flat-square)](https://laravel.com)
[![Filament Version](https://img.shields.io/badge/Filament-5-orange.svg?style=flat-square)](https://filamentphp.com)

A clean, modern Filament plugin to generate, render, style, and export QR codes powered by [`linkxtr/laravel-qrcode`](https://github.com/Linkxtr/laravel-qrcode).

Seamlessly embed dynamic QR codes inside **Filament Table Columns**, display rich codes in **Infolist Entries**, and provide one-click exports using single and bulk **Table Actions**.

---

## Highlights

- **Table Column (`QrCodeColumn`)**: Inline preview, responsive sizing, dark mode support, title tooltip, and instant click-to-preview or download.
- **Infolist Entry (`QrCodeEntry`)**: High-fidelity QR code display in view pages, infolist schemas, and slide-overs with state readout.
- **Form Preview Field (`QrCodeField`)**: Reactive live preview cards in Filament form schemas, dark mode compatible layout, dynamic live updates, and built-in copy and download buttons.
- **Table Actions (`DownloadQrCodeAction` & `DownloadQrCodeBulkAction`)**: Single-record streaming downloads and multi-record ZIP archives in SVG, PNG, or WebP.
- **Fluent Styling**: Custom dimensions, quiet-zone margins, error correction levels (L, M, Q, H), and brand color palette tokens (`primary`, `success`, `danger`, `warning`, `info`, `gray`, or hex).
- **Advanced Aesthetics**: Linear gradients, custom finder eye inner/outer colors, and embedded logo images.
- **Direct `laravel-qrcode` Escape Hatch (`configureQrUsing()`)**: Access the live `Linkxtr\QrCode\Generator` instance at render time for any advanced feature (`cmyk()`, `gray()`, `cache()`, `withoutCache()`, custom macros) without API duplication.
- **First-Class `laravel-qrcode` DataTypes**: Pass any `Stringable` DataType from `linkxtr/laravel-qrcode` (`WiFi`, `VCard`, `MeCard`, `Email`, `PhoneNumber`, `SMS`, `WhatsApp`, `Telegram`, `Geo`, `CalendarEvent`, `BTC`, `Ethereum`) directly into `payload()` or `formatPayloadUsing()`.
- **Multi-Version Compatibility**: Supports **PHP 8.4 and 8.5**, **Laravel 12 and 13**, and **Filament 5**.

---

## Installation

Install the package via Composer:

```bash
composer require linkxtr/filament-qrcode
```

### 1. Panel Plugin Registration

Register the `FilamentQrCodePlugin` in your Filament Panel configuration provider (usually `app/Providers/Filament/AdminPanelProvider.php`):

```php
namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Linkxtr\FilamentQrCode\FilamentQrCodePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugin(FilamentQrCodePlugin::make());
    }
}
```

### 2. Publish Configuration (Optional)

Publish the configuration file to customize package-wide defaults:

```bash
php artisan vendor:publish --tag="filament-qrcode-config"
```

This creates `config/filament-qrcode.php`:

```php
return [
    'defaults' => [
        'size' => 160,
        'margin' => 1,
        'format' => 'svg', // svg, png, webp
        'error_correction' => 'M', // L, M, Q, H
        'color' => '#000000',
        'background_color' => '#ffffff',
    ],
    'preview' => [
        'modal_width' => 'sm',
        'render_size' => 320,
    ],
];
```

### 3. Publish Blade Views (Optional)

If you need to customize the HTML or Tailwind layout of columns or infolists, publish the views:

```bash
php artisan vendor:publish --tag="filament-qrcode-views"
```

---

## Usage Guide & Recipes

### 1. Table Columns (`QrCodeColumn`)

Add a QR code column directly to any Filament Resource table schema:

```php
namespace App\Filament\Resources\OrderResource\Pages;

use Filament\Tables\Table;
use Linkxtr\FilamentQrCode\Tables\Columns\QrCodeColumn;

public function table(Table $table): Table
{
    return $table
        ->columns([
            QrCodeColumn::make('order_number')
                ->label('Tracking QR')
                ->qrSize(100)
                ->qrColor('primary')
                ->downloadable(),
        ]);
}
```

#### Custom Sizing, Margins & Error Correction

```php
use Linkxtr\FilamentQrCode\Tables\Columns\QrCodeColumn;
use Linkxtr\QrCode\Enums\ErrorCorrectionLevel;
use Linkxtr\QrCode\Enums\Format;

QrCodeColumn::make('ticket_uuid')
    ->qrSize(140)
    ->qrMargin(2)
    ->qrFormat(Format::SVG) // or 'png', 'webp', 'eps'
    ->qrErrorCorrection(ErrorCorrectionLevel::H); // 'L', 'M', 'Q', 'H'
```

#### Color Tokens, Hex Values & Gradients

You can pass Filament theme color tokens (`primary`, `danger`, `warning`, `success`, `info`, `gray`) or standard HEX colors:

```php
QrCodeColumn::make('access_token')
    ->qrColor('success')
    ->qrBackgroundColor('#f8fafc')
    ->qrGradient(from: '#0ea5e9', to: '#6366f1', type: 'vertical');
```

#### Eye Customization & Embedded Logos

```php
QrCodeColumn::make('company_uuid')
    ->qrEyeColor(eyeIndex: 0, inner: '#ef4444', outer: '#b91c1c')
    ->qrEyeColor(eyeIndex: 1, inner: '#3b82f6', outer: '#1d4ed8')
    ->qrLogo(path: public_path('images/logo-icon.png'), size: 0.22);
```

#### Custom Payload Resolution

```php
QrCodeColumn::make('custom_url')
    ->payload(fn ($record) => route('orders.show', $record));
```

#### Advanced Generator Customization (`configureQrUsing`)

Every component and action (`QrCodeColumn`, `QrCodeEntry`, `QrCodeField`, `DownloadQrCodeAction`, `DownloadQrCodeBulkAction`) provides `configureQrUsing(Closure $callback)` to configure the live `Linkxtr\QrCode\Generator` directly at render time. Use it to invoke any `laravel-qrcode` feature (`cmyk()`, `gray()`, `cache()`, `withoutCache()`, custom macros, etc.) without duplicating the generator API:

```php
use Linkxtr\FilamentQrCode\Tables\Columns\QrCodeColumn;
use Linkxtr\QrCode\Generator;

QrCodeColumn::make('invoice_url')
    ->configureQrUsing(fn (Generator $qr, $record): Generator => $qr
        ->gray(20, 95)
        ->cache(3600));
```

> **Note:** `Linkxtr\QrCode\Generator` is immutable — return the modified `Generator` instance from your closure. Filament closure injection supports `$qr`, `$generator`, typed `Generator`, and `$record`.

---

### 2. Infolist Entries (`QrCodeEntry`)

Display QR codes in View/Edit Resource pages or slide-over modals:

```php
namespace App\Filament\Resources\DeviceResource\Pages;

use Filament\Infolists\Infolist;
use Linkxtr\FilamentQrCode\Infolists\Entries\QrCodeEntry;
use Linkxtr\QrCode\DataTypes\WiFi;

public function infolist(Infolist $infolist): Infolist
{
    return $infolist
        ->schema([
            QrCodeEntry::make('device_serial')
                ->label('Device Provisioning QR')
                ->qrSize(260)
                ->qrColor('#1e293b')
                ->payload(fn ($record): WiFi => new WiFi(
                    ssid: $record->ssid,
                    password: $record->wpa_key,
                )),
        ]);
}
```

The `QrCodeEntry` component inherits the exact same fluent styling and payload traits (`HasQrCodeStyling`, `HasPayload`) as `QrCodeColumn`.

---

### 3. Form Preview Fields (`QrCodeField`)

Embed live, reactive QR codes directly into Filament Form schemas (such as Create, Edit, or modal schemas):

```php
namespace App\Filament\Resources\CampaignResource\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Linkxtr\FilamentQrCode\Forms\Components\QrCodeField;

public function form(Form $form): Form
{
    return $form
        ->schema([
            TextInput::make('target_url')
                ->label('Landing Page URL')
                ->live(onBlur: true)
                ->default('https://linkxtr.io'),

            QrCodeField::make('qr_preview')
                ->label('Dynamic QR Preview')
                ->payload(fn (Get $get): string => (string) ($get('target_url') ?: 'https://linkxtr.io'))
                ->qrSize(200)
                ->qrMargin(2)
                ->qrColor('primary')
                ->downloadable()
                ->copyable(),
        ]);
}
```

#### Fluent Styling & Reactive Helpers

`QrCodeField` uses the same canonical `HasQrCodeStyling` methods as `QrCodeColumn` and `QrCodeEntry`:

```php
QrCodeField::make('qr')
    ->payload('https://linkxtr.io')
    ->qrSize(220)
    ->qrMargin(2)
    ->qrFormat('svg') // svg, png, webp, eps
    ->qrColor('#0f172a')
    ->qrBackgroundColor('#f8fafc')
    ->qrGradient(from: '#0284c7', to: '#4f46e5', type: 'vertical')
    ->qrEyeStyle(\Linkxtr\QrCode\Enums\EyeStyle::CIRCLE)
    ->qrStyle(\Linkxtr\QrCode\Enums\Style::ROUND, 0.75)
    ->downloadable()
    ->copyable();
```

---

### 4. Table Actions

#### Single-Record Action (`DownloadQrCodeAction`)

Add a row action to your table allowing users to instantly download an SVG, PNG, or WebP QR code:

```php
use Filament\Tables\Table;
use Linkxtr\FilamentQrCode\Actions\DownloadQrCodeAction;

public function table(Table $table): Table
{
    return $table
        ->actions([
            DownloadQrCodeAction::make()
                ->filename(fn ($record) => "qr-order-{$record->id}")
                ->qrFormat('svg')
                ->qrSize(400)
                ->qrColor('primary'),
        ]);
}
```

#### Bulk Action (`DownloadQrCodeBulkAction`)

Allow administrators to select multiple records in a table and download a combined `.zip` file containing individual QR codes:

```php
use Filament\Tables\Table;
use Linkxtr\FilamentQrCode\Actions\DownloadQrCodeBulkAction;

public function table(Table $table): Table
{
    return $table
        ->bulkActions([
            DownloadQrCodeBulkAction::make()
                ->filename('exported-tickets')
                ->qrFormat('png')
                ->qrSize(500)
                ->qrColor('#000000'),
        ]);
}
```

---

## Payload & `laravel-qrcode` DataTypes

All components and actions (`QrCodeColumn`, `QrCodeEntry`, `QrCodeField`, `DownloadQrCodeAction`, and `DownloadQrCodeBulkAction`) share the `HasPayload` trait, accepting any `string`, `Stringable` (including every DataType from [`linkxtr/laravel-qrcode`](https://github.com/Linkxtr/laravel-qrcode)), or `Closure`:

| Method | Signature | Description |
|---|---|---|
| `payload()` | `string \| Stringable \| Closure \| null $payload` | Resolves the raw payload string or `Stringable` DataType. |
| `formatPayloadUsing()` | `Closure $callback` | Transforms the resolved state or payload into a `string` or `Stringable` DataType. |

### Using `linkxtr/laravel-qrcode` DataTypes Directly

```php
use Linkxtr\FilamentQrCode\Tables\Columns\QrCodeColumn;
use Linkxtr\QrCode\DataTypes\Email;
use Linkxtr\QrCode\DataTypes\PhoneNumber;
use Linkxtr\QrCode\DataTypes\VCard;
use Linkxtr\QrCode\DataTypes\WhatsApp;
use Linkxtr\QrCode\DataTypes\WiFi;

// Pass a DataType via payload()
QrCodeColumn::make('wifi_qr')
    ->payload(fn (Router $record): WiFi => new WiFi(
        ssid: $record->ssid,
        password: $record->password,
    ));

// Or transform an existing column state via formatPayloadUsing()
QrCodeColumn::make('phone')
    ->formatPayloadUsing(fn (string $state): PhoneNumber => new PhoneNumber($state));

// Works with VCard, Email, WhatsApp, SMS, Geo, CalendarEvent, BTC, Ethereum, Telegram, MeCard, etc.
QrCodeColumn::make('contact_qr')
    ->payload(fn (Contact $record): VCard => new VCard(
        name: $record->name,
        phone: $record->phone,
        email: $record->email,
    ));
```

---

## Fluent Method API Reference

Every component implementing `HasQrCodeStyling` supports the following fluent methods:

| Method | Signature | Description |
|---|---|---|
| `qrSize()` | `int \| Closure $size` | Size in pixels (default: `160`). |
| `qrMargin()` | `int \| Closure $margin` | Quiet-zone margin in modules (default: `1`). |
| `qrFormat()` | `Format \| string \| Closure $format` | `svg`, `png`, `webp`, or `eps` (default: `svg`). |
| `qrErrorCorrection()` | `ErrorCorrectionLevel \| string \| Closure $level` | `L` (7%), `M` (15%), `Q` (25%), or `H` (30%). |
| `qrColor()` | `string \| Closure $color` | Foreground color (HEX string or Filament token). |
| `qrBackgroundColor()` | `string \| Closure $color` | Background color (HEX string or Filament token). |
| `qrGradient()` | `string $from, string $to, GradientType \| string $type = 'vertical'` | Linear gradient with starting/ending colors. |
| `qrEyeColor()` | `int $eyeIndex, string $inner, ?string $outer = null` | Custom colors for finder eyes (`0`, `1`, or `2`). |
| `qrLogo()` | `string \| Closure $path, float \| Closure $size = 0.2` | Overlay logo image path and relative scaling. |
| `configureQrUsing()` | `Closure $callback` | Receives the live `Linkxtr\QrCode\Generator` (`$qr`, `$generator`, `$record`) for any advanced `laravel-qrcode` feature (`cmyk()`, `gray()`, `cache()`, macros). |

### Component-Specific Methods

- **`QrCodeColumn`**:
  - `downloadable(bool | Closure $condition = true)`: Enables instant file download on click.
  - `previewable(bool | Closure $condition = true)`: Enables click-to-preview behavior.
  - `payload(Closure $callback)`: Overrides the record field resolver for the raw payload.
- **`DownloadQrCodeAction`**:
  - `filename(string $name)`: Base filename without extension for single download.
- **`DownloadQrCodeBulkAction`**:
  - `filename(string | Closure $name)`: Base name for the generated `.zip` file.
  - `recordFilename(string | Closure $callback)`: Custom filename per record entry inside the `.zip` archive.

---

## v1.0.0 Release Notes & Packagist Checklist

### Release Highlights (v1.0.0)

- Initial stable release of `linkxtr/filament-qrcode`.
- First-class support for **Filament 5** on **Laravel 12 and 13** (**PHP 8.4 and 8.5**).
- Powered by `linkxtr/laravel-qrcode` v3.x.
- Zero-configuration table column (`QrCodeColumn`) with dark mode support.
- Rich infolist entry (`QrCodeEntry`) with mono-font payload inspector.
- Reactive form preview field (`QrCodeField`) with live updates, copy, and download buttons.
- Single and bulk download actions (`DownloadQrCodeAction` and `DownloadQrCodeBulkAction`).
- Full `Stringable` DataType delegation to `linkxtr/laravel-qrcode` via `payload()` and `formatPayloadUsing()`.
- Full SVG, PNG, and WebP rendering pipeline with gradients, eye styling, and logos.

### Packagist Submission Checklist

- [x] Canonical package name `linkxtr/filament-qrcode` registered in `composer.json`.
- [x] Semantic versioning Git tag `v1.0.0` ready.
- [x] Auto-discovery service provider configured under `extra.laravel.providers`.
- [x] PHP compatibility matrix validated (`^8.4`).
- [x] Filament 5 and `linkxtr/laravel-qrcode: ^3.0` dependencies configured.
- [x] Packagist GitHub webhook configured for automated release publishing.

---

## Strategic Roadmap

`linkxtr/filament-qrcode` is actively developed across four strategic waves:

- **Wave 1 (v1.1) - Form Live QR Preview Component**: `QrCodeField` with reactive schema updating (`live()`), card presentation, zoom modal preview, and one-click download/copy buttons. *(Released)*
- **Wave 2 (v1.2) - Interactive QR Customizer**: In-form visual design studio for real-time module styles, eye shapes, gradient presets, and logo uploads. *(Planned)*
- **Wave 3 (v1.3) - Print Sheet Action & Grid Template**: Batch label sheets with standard stationery layouts (Avery, A4, US Letter), cut lines, and print-ready PDF/HTML. *(Planned)*
- **Wave 4 (v1.4) - Dashboard Widgets**: `QuickQrGeneratorWidget` for ad-hoc generation and `QrCodeStatsWidget` for scan telemetry and campaign metrics. *(Planned)*

📖 *For full technical specifications, architecture diagrams, and milestone details, see the [Strategic Roadmap](docs/ROADMAP.md).*

---

## Testing

Run tests with Pest:

```bash
composer test
```

---

## Contributing

Please review [CONTRIBUTING.md](.github/CONTRIBUTING.md) for details on submitting pull requests and running our quality test suite.

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
