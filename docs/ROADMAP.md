# Strategic Roadmap: linkxtr/filament-qr

Welcome to the strategic product and engineering roadmap for **`linkxtr/filament-qr`**. 

This document outlines the four strategic development waves expanding `filament-qr` from a high-quality display and action package into a complete, enterprise-grade QR operations suite natively embedded within Filament Admin Panels.

---

## Strategic Vision & Product Philosophy

The mission of `linkxtr/filament-qr` is to provide the most fluid, aesthetically refined, and developer-friendly QR code tooling in the Laravel and Filament ecosystem. By delegating low-level vector rendering and SVG manipulation to [`linkxtr/laravel-qrcode`](https://github.com/Linkxtr/laravel-qrcode), `filament-qr` focuses on delivering:

1. **Native Filament UX**: Zero-friction integration with Table Schemas, Infolist Schemas, Form Schemas, Actions, and Widgets.
2. **Defensive Aesthetics**: Cohesive light and dark mode styling, defensive centering, responsive container bounding, and accessible preview modals.
3. **Reactive Interactivity**: Instant real-time feedback as administrators type, toggle, or customize codes.
4. **Physical & Digital Utility**: Bridging digital records with physical print layouts, label sheets, and analytics.

---

## 4 Strategic Feature Waves

```text
┌──────────────────────────────────────────────────────────────────────────────────┐
│                               FILAMENT QR ROADMAP                                │
├─────────────────┬─────────────────┬───────────────────┬──────────────────────────┤
│  Wave 1 (v1.1)  │  Wave 2 (v1.2)  │   Wave 3 (v1.3)   │      Wave 4 (v1.4)       │
├─────────────────┼─────────────────┼───────────────────┼──────────────────────────┤
│ Form Live QR    │ Interactive QR  │ Print Sheet Action│ Dashboard Widgets        │
│ Preview Field   │ Customizer      │ & Grid Layouts    │ & Quick Generators       │
│                 │                 │                   │                          │
│ • QrCodeField   │ • Style Pickers │ • PrintQrSheet    │ • QrCodeStatsWidget      │
│ • Reactive live │ • Eye & Dot UI  │ • Avery templates │ • QuickQrGeneratorWidget │
│ • Download/Copy │ • Logo Uploader │ • PDF / SVG Sheet │ • Realtime generation    │
└─────────────────┴─────────────────┴───────────────────┴──────────────────────────┘
```

---

### Wave 1 (v1.1): Form Live QR Preview Component (`QrCodeField`)

**Status:** Completed & Released in v1.1.0  
**Target:** `Linkxtr\FilamentQr\Forms\Components\QrCodeField`

#### Objectives & Capabilities
- **Form Schema Integration**: Embed live, interactive QR code cards directly into Filament Form schemas (`Create`, `Edit`, and custom modal forms).
- **Reactive State Binding**: Deep integration with Filament's reactive cycle (`->live()`). As administrators update input fields (such as `url`, `slug`, `sku`, or `phone`), the QR preview re-renders dynamically without page reloads.
- **Fluent Styling API**: First-class support for `size()`, `color()`, `backgroundColor()`, `margin()`, `format()`, `errorCorrection()`, `style()`, `eye()`, `gradient()`, and `logo()`.
- **Integrated Actions**: Built-in one-click download (`->downloadable()`) and clipboard copy (`->copyable()`) buttons located directly on the form card.
- **Defensive Card UI**: Consistent dark mode compatibility, centered preview containment, and fallback state handling when payload data is blank.

---

### Wave 2 (v1.2): In-Form Interactive QR Customizer

**Status:** Planned (Next Milestone)  
**Target:** `Linkxtr\FilamentQr\Forms\Components\QrCodeCustomizer`

#### Objectives & Capabilities
- **Visual Design Studio**: Provide a turnkey, interactive customizer component that can be placed in forms or opened via a builder modal.
- **Dynamic Pattern & Shape Selectors**:
  - Module styles: Square, Dots, Rounded, Radial, Classy.
  - Finder eye shapes: Circle, Square, Pointy, Custom rounded outer/inner.
- **Palette & Gradient Controls**:
  - Live color pickers for foreground, background, and individual finder eyes.
  - Linear and radial gradient direction controls with instant canvas preview.
- **Embedded Brand Logo Manager**:
  - Drag-and-drop logo upload with automatic alpha channel handling.
  - Live scale slider (10% to 35% bounding) with automatic error-correction level boosting (`H` level) to guarantee scannability.
- **Preset Library**: Ability to save and load pre-configured brand QR style presets across models.

---

### Wave 3 (v1.3): Print Sheet Action & Grid Template

**Status:** Planned  
**Target:** `Linkxtr\FilamentQr\Actions\PrintQrSheetAction` & `PrintQrSheetBulkAction`

#### Objectives & Capabilities
- **Physical Label Sheets**: Allow administrators to export and print formatted label sheets for inventory items, conference badges, restaurant tables, or asset tags.
- **Standard Label Paper Presets**: Built-in layout grids for standard stationery:
  - Avery 5160 / 5163 / L7160 / L7163.
  - Standard A4 and US Letter grid layouts (2x4, 3x7, 4x10).
- **Customizable Card Metadata**:
  - Include record titles, subtitles, barcodes, prices, or serial numbers beneath or beside each QR code.
  - Configurable cut lines, margins, and page bleed guides.
- **Direct Browser Print & Vector PDF Export**:
  - CSS `@media print` optimized HTML sheet view for instant printer dialog triggering.
  - High-resolution vector output ensuring crisp optical scans even on thermal label printers.

---

### Wave 4 (v1.4): Filament Dashboard Widgets

**Status:** Planned  
**Target:** `Linkxtr\FilamentQr\Widgets\QrCodeStatsWidget` & `QuickQrGeneratorWidget`

#### Objectives & Capabilities
- **`QuickQrGeneratorWidget`**:
  - Compact dashboard widget allowing administrators to generate, test, and download a one-off QR code on the fly without creating database records.
  - Quick type switch: URL, Wi-Fi login, vCard, Plain Text.
- **`QrCodeStatsWidget`**:
  - Overview widget tracking dynamic QR scan counts, unique scanners, and device categories (when coupled with URL redirection tracking models).
  - Filament chart integration displaying daily scan trends and top-performing campaigns.

---

## Quality & Compatibility Commitments

Across all four waves, `linkxtr/filament-qr` adheres to strict engineering benchmarks:

- **Strict Typing & PHPStan Level Max**: 100% strict types (`declare(strict_types=1);`), zero PHPStan errors at level `max`.
- **Formatting Standards**: 100% Laravel Pint compliance.
- **Testing Coverage**: Comprehensive Pest test suite covering unit contracts, Livewire reactive state, and HTML DOM assertions.
- **Matrix Support**: PHP 8.2 through 8.4+, Laravel 10 through 12+, Filament v3, v4, and v5.
