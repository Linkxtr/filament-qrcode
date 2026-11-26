# Architecture & Standards Review: linkxtr/filament-qrcode

**Reviewer:** Omar Fathy, Engineering Lead & Maintainer (`linkxtr/laravel-qrcode`, `linkxtr/docs`)  
**Package:** `linkxtr/filament-qrcode`  
**Status:** Approved with Architecture & Quality Recommendations  
**Date:** October 2026

---

## 1. Executive Summary

This architecture and standards review establishes the architectural foundations, dependency contracts, Filament integration conventions, and quality bar for `linkxtr/filament-qrcode`. 

The package is designed as a companion to `linkxtr/laravel-qrcode` (v3.x), bringing native QR code generation, styling, modal previews, and exports into the Filament admin panel ecosystem.

---

## 2. Repository Structure & Component Inventory

The codebase adheres to standard PSR-4 conventions under namespace `Linkxtr\FilamentQrCode`:

```text
linkxtr/filament-qrcode/
├── config/
│   └── filament-qrcode.php                  # Publishable default styling and preview configuration
├── resources/
│   └── views/
│       ├── forms/components/
│       │   ├── qr-code-field.blade.php      # Form field Blade view
│       │   └── qr-code-field-card.blade.php # Form field preview card partial
│       ├── infolists/entries/
│       │   └── qr-code-entry.blade.php      # Infolist entry Blade view
│       └── tables/columns/
│           └── qr-code-column.blade.php     # Table column Blade view
├── src/
│   ├── Actions/
│   │   ├── DownloadQrCodeAction.php         # Single record QR download action
│   │   └── DownloadQrCodeBulkAction.php     # Bulk selection QR archive download action
│   ├── Concerns/
│   │   ├── HasPayload.php                   # Payload resolution & Stringable DataType delegation (`payload`, `formatPayloadUsing`)
│   │   ├── HasQrCodeStyling.php             # Fluent styling setters, Config DTO builder, and `configureQrUsing()` escape hatch
│   │   └── HasQrInteractions.php            # Preview, copy, download, and shared `renderQrCode()`/`renderPreviewQrCode()` helpers
│   ├── Forms/Components/
│   │   └── QrCodeField.php                  # Filament Form Field (non-dehydrated live QR preview)
│   ├── Infolists/Entries/
│   │   └── QrCodeEntry.php                  # Filament Infolist Entry
│   ├── Services/
│   │   └── QrCodeRenderer.php               # Thin rendering service bridging Config + optional Generator callback to linkxtr/laravel-qrcode
│   ├── Tables/Columns/
│   │   └── QrCodeColumn.php                 # Filament Table Column
│   ├── FilamentQrCodePlugin.php             # Filament\Contracts\Plugin implementation
│   └── FilamentQrCodeServiceProvider.php    # Laravel Service Provider
└── tests/
    ├── Feature/                             # Full Pest feature & mutation test suite
    ├── Pest.php                             # Pest configuration and custom helpers
    └── TestCase.php                         # Orchestra Testbench setup
```

### Architectural Strengths
- **Separation of Concerns:** Business logic for generating QR codes remains in `QrCodeRenderer` and the underlying `linkxtr/laravel-qrcode` package; Filament classes act solely as thin presentation and action wrappers.
- **Zero API Duplication via `configureQrUsing()`:** Common styling options live in `HasQrCodeStyling`, while `configureQrUsing(fn (Generator $qr) => ...)` exposes the live `Linkxtr\QrCode\Generator` instance directly at render time for any advanced `laravel-qrcode` feature (`cmyk()`, `gray()`, `cache()`, macros) without duplicating upstream APIs.
- **Trait Reusability:** `HasPayload`, `HasQrCodeStyling`, and `HasQrInteractions` are shared cleanly across `QrCodeColumn`, `QrCodeEntry`, and `QrCodeField`, while actions compose `HasPayload` and `HasQrCodeStyling`.
- **Decoupled Fallback:** Component defaults read from `config('filament-qrcode.defaults')` with 4-tier fallback to `config('qrcode.*')`, allowing components to function reliably even outside a Filament Panel context.

---

## 3. Dependency Matrix & Composer Constraints

### Validated Constraints (`composer.json`)

| Package / Runtime | Constraint | Status | Notes |
|---|---|---|---|
| `php` | `^8.4` | Validated | Supports PHP 8.4 and 8.5 |
| `filament/filament` | `^5.0` | Validated | Targets Filament v5 on Laravel 12 & 13 |
| `linkxtr/laravel-qrcode` | `^3.0` | Validated | Leverages core QR engine v3.x (Enums, DataTypes, Config DTO, Generator) |

---

## 4. Filament Plugin Contract Validation

`Linkxtr\FilamentQrCode\FilamentQrCodePlugin` implements `Filament\Contracts\Plugin`:

- `getId(): string`: Returns `'filament-qrcode'`, providing a stable identifier within `FilamentManager`.
- `register(Panel $panel): void`: Available for panel-level asset and component registration.
- `boot(Panel $panel): void`: Available for lifecycle boot hooks.
- `make(): static`: Factory instantiation adhering to Filament plugin conventions.
- `get(): static`: Resolves the current plugin instance from the active panel via `filament('filament-qrcode')`.

---

## 5. Service Provider & Resource Namespaces

`Linkxtr\FilamentQrCode\FilamentQrCodeServiceProvider` establishes proper container bindings and asset publishing:

1. **Configuration:**
   - Merges default configuration from `config/filament-qrcode.php` under the `'filament-qrcode'` key.
   - Publishes configuration via `--tag="filament-qrcode-config"`.

2. **Singleton Binding:**
   - Registers `Linkxtr\FilamentQrCode\Services\QrCodeRenderer` as a singleton in the Laravel container.

3. **View Loading & Publishing:**
   - Registers view namespace `'filament-qrcode'` pointing to `resources/views`.
   - Views follow Filament standard naming conventions:
     - `filament-qrcode::tables.columns.qr-code-column`
     - `filament-qrcode::infolists.entries.qr-code-entry`
   - Publishes Blade views via `--tag="filament-qrcode-views"`.
   - Publishes all assets combined via `--tag="filament-qrcode"`.

---

## 6. ApricotCake Quality Bar for Downstream PRs

Every pull request submitted to `linkxtr/filament-qrcode` must satisfy the following strict quality gates before merge approval:

1. **Code Style (Laravel Pint):**
   - Must pass `vendor/bin/pint --test` with 0 violations.
   - Clean imports with `no_unused_imports`.

2. **Static Analysis (PHPStan):**
   - PHPStan Level 8 / Max with `vendor/bin/phpstan analyse --ansi`.
   - Zero errors policy without suppressions unless documented and architecturally justified.

3. **Test Suite & Coverage (Pest):**
   - Must achieve **100% Unit Coverage** (`--coverage --min=100`).
   - Must achieve **100% Type Coverage** (`--type-coverage --min=100`).
   - Test suites must cover:
     - Column rendering, `Stringable` DataType payload resolution (`payload`, `formatPayloadUsing`), and download flags.
     - Infolist entries and state resolution.
     - Single and bulk download actions (`DownloadQrCodeAction`, `DownloadQrCodeBulkAction`).
     - Renderer error handling and color conversions.
     - Plugin registration and panel boot lifecycles.
     - Service provider config merging and publishable tags.

4. **Mutation Testing:**
   - Run `pest --mutate --covered-only` to ensure assertions catch mutated conditions with zero surviving mutants on core domain methods.

5. **Refactoring & Clean Code (Rector):**
   - Automated code refactoring to modern PHP 8.2+ constructor property promotion, match expressions, and typed properties.
