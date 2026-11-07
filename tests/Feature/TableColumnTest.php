<?php

declare(strict_types=1);

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Linkxtr\FilamentQr\Concerns\HasQrInteractions;
use Linkxtr\FilamentQr\Tables\Columns\QrCodeColumn;
use Linkxtr\QrCode\Enums\ErrorCorrectionLevel;
use Linkxtr\QrCode\Enums\EyeStyle;
use Linkxtr\QrCode\Enums\Format;
use Linkxtr\QrCode\Enums\GradientType;
use Linkxtr\QrCode\Enums\Style;
use Livewire\Component;

covers(QrCodeColumn::class, HasQrInteractions::class);

it('can initialize qr code column with styling and payload resolution', function (): void {
    $qrCodeColumn = QrCodeColumn::make('qr')
        ->qrSize(200)
        ->qrMargin(2)
        ->qrColor('#112233')
        ->qrBackgroundColor('#ffffff')
        ->qrFormat(Format::PNG)
        ->qrErrorCorrection(ErrorCorrectionLevel::H);

    expect($qrCodeColumn->getQrSize())->toBe(200)
        ->and($qrCodeColumn->getQrMargin())->toBe(2)
        ->and($qrCodeColumn->getQrColor())->toBe('#112233')
        ->and($qrCodeColumn->getQrBackgroundColor())->toBe('#ffffff')
        ->and($qrCodeColumn->getQrFormat())->toBe('png')
        ->and($qrCodeColumn->getQrErrorCorrection())->toBe('H');
});

it('resolves payload from closure, state, getStateUsing fallback, and returns null when empty', function (): void {
    $qrCodeColumn = QrCodeColumn::make('qr');
    $returned = $qrCodeColumn->payload(fn (): string => 'from-closure');
    expect($returned)->toBe($qrCodeColumn)
        ->and($qrCodeColumn->getQrPayload())->toBe('from-closure');

    $colState = QrCodeColumn::make('qr')->state('from-state');
    expect($colState->getQrPayload())->toBe('from-state');

    $colGetStateUsing = QrCodeColumn::make('qr')->getStateUsing(fn (): string => 'from-get-state-using');
    expect($colGetStateUsing->getQrPayload())->toBe('from-get-state-using');

    $livewire = new class extends Component implements HasTable
    {
        use InteractsWithActions;
        use InteractsWithSchemas;
        use InteractsWithTable;

        public function getTableRecordKey(Model|array $record): string
        {
            return '1';
        }
    };
    $table = Table::make($livewire);
    $colRecord = QrCodeColumn::make('qr')->table($table)->record(['qr' => 'from-record']);
    expect($colRecord->getQrPayload())->toBe('from-record');

    $colEmpty = QrCodeColumn::make('qr')->state(null);
    expect($colEmpty->getQrPayload())->toBeNull()
        ->and($colEmpty->renderQrCode())->toBeNull()
        ->and($colEmpty->renderPreviewQrCode())->toBeNull();
});

it('renders qr code with all styling options and blade view with modal preview', function (): void {
    $qrCodeColumn = QrCodeColumn::make('qr')
        ->payload(fn (): string => 'https://filamentphp.com')
        ->qrSize(190)
        ->qrMargin(2)
        ->qrFormat('svg')
        ->qrErrorCorrection('H')
        ->qrColor('#112233')
        ->qrBackgroundColor('#ffffff')
        ->qrGradient('#112233', '#445566', GradientType::VERTICAL)
        ->qrEyeColor(0, '#112233', '#445566')
        ->qrEyeStyle(EyeStyle::CIRCLE)
        ->qrInternalEyeStyle(EyeStyle::SQUARE)
        ->qrStyle(Style::ROUND, 0.7)
        ->qrEncoding('UTF-8')
        ->previewable(true)
        ->downloadable(true);

    $rendered = $qrCodeColumn->renderQrCode();
    expect($rendered)->toBeString()
        ->and($rendered)->toContain('<svg')
        ->and($rendered)->toContain('width="190"');

    $html = $qrCodeColumn->toHtml();

    expect($html)->toBeString()
        ->and($html)->toContain('fi-ta-qr-code')
        ->and($html)->toContain('fi-modal')
        ->and($html)->toContain('https://filamentphp.com')
        ->and($html)->toContain('Download QR')
        ->and($html)->toContain('x-on:click.stop.prevent="$dispatch(\'open-modal\'')
        ->and($html)->toContain('x-on:click.stop');
});

it('disables cell click by default on QrCodeColumn to prevent table row navigation', function (): void {
    $column = QrCodeColumn::make('qr');

    expect($column->isClickDisabled())->toBeTrue();

    $column->disabledClick(false);
    expect($column->isClickDisabled())->toBeFalse();

    $column->disabledClick(fn (): bool => true);
    expect($column->isClickDisabled())->toBeTrue();

    $column->disabledClick(fn (): bool => false);
    expect($column->isClickDisabled())->toBeFalse();
});

it('has correct default interaction properties on QrCodeColumn', function (): void {
    $column = QrCodeColumn::make('qr');

    expect($column->isDownloadable())->toBeFalse()
        ->and($column->isPreviewable())->toBeTrue()
        ->and($column->isClickableToPreview())->toBeTrue()
        ->and($column->isCopyable())->toBeTrue()
        ->and($column->getPreviewSize())->toBe(320)
        ->and($column->getPreviewHeading())->toBe('QR Code Preview')
        ->and($column->getCopyMessage())->toBe('Copied to clipboard!')
        ->and($column->getDownloadFilename())->toBe('qr-code');

    $column->downloadable()->previewable(false)->copyable(false);
    expect($column->isDownloadable())->toBeTrue()
        ->and($column->isClickableToPreview())->toBeFalse()
        ->and($column->isPreviewable())->toBeFalse()
        ->and($column->isCopyable())->toBeFalse();

    $column->downloadable(false)->previewable(true)->copyable(true);
    expect($column->isDownloadable())->toBeFalse()
        ->and($column->isClickableToPreview())->toBeTrue()
        ->and($column->isPreviewable())->toBeTrue()
        ->and($column->isCopyable())->toBeTrue();

    $column->clickableToPreview(false);
    expect($column->isClickableToPreview())->toBeFalse()
        ->and($column->isPreviewable())->toBeFalse();

    $column->clickableToPreview(true);
    expect($column->isClickableToPreview())->toBeTrue()
        ->and($column->isPreviewable())->toBeTrue();

    $column->downloadable(fn (): bool => true)
        ->previewable(fn (): bool => true)
        ->copyable(fn (): bool => true);
    expect($column->isDownloadable())->toBeTrue()
        ->and($column->isPreviewable())->toBeTrue()
        ->and($column->isClickableToPreview())->toBeTrue()
        ->and($column->isCopyable())->toBeTrue();

    $column->downloadable(fn (): bool => false)
        ->previewable(fn (): bool => false)
        ->copyable(fn (): bool => false);
    expect($column->isDownloadable())->toBeFalse()
        ->and($column->isPreviewable())->toBeFalse()
        ->and($column->isClickableToPreview())->toBeFalse()
        ->and($column->isCopyable())->toBeFalse();

    $column->clickableToPreview(fn (): bool => true);
    expect($column->isClickableToPreview())->toBeTrue()
        ->and($column->isPreviewable())->toBeTrue();

    $column->clickableToPreview(fn (): bool => false);
    expect($column->isClickableToPreview())->toBeFalse()
        ->and($column->isPreviewable())->toBeFalse();

    $column->downloadFilename('custom-report');
    expect($column->getDownloadFilename())->toBe('custom-report');
});

it('renders standalone download button when downloadable is true and previewable is false', function (): void {
    $column = QrCodeColumn::make('qr')
        ->payload(fn (): string => 'https://filamentphp.com')
        ->downloadable(true)
        ->previewable(false)
        ->downloadFilename('qr-standalone');

    $html = $column->toHtml();

    expect($html)->toContain('fi-ta-qr-download')
        ->and($html)->toContain('download="qr-standalone.svg"')
        ->and($html)->toContain('x-on:click.stop');
});
