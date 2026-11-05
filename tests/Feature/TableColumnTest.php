<?php

declare(strict_types=1);

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Linkxtr\FilamentQr\Concerns\HasQrInteractions;
use Linkxtr\FilamentQr\Enums\ErrorCorrectionLevel;
use Linkxtr\FilamentQr\Enums\EyeStyle;
use Linkxtr\FilamentQr\Enums\GradientType;
use Linkxtr\FilamentQr\Enums\QrFormat;
use Linkxtr\FilamentQr\Enums\QrStyle;
use Linkxtr\FilamentQr\Tables\Columns\QrCodeColumn;
use Livewire\Component;

covers(QrCodeColumn::class, HasQrInteractions::class);

it('can initialize qr code column with styling and payload resolution', function (): void {
    $qrCodeColumn = QrCodeColumn::make('qr')
        ->qrSize(200)
        ->qrMargin(2)
        ->qrColor('#112233')
        ->qrBackgroundColor('#ffffff')
        ->qrFormat(QrFormat::Png)
        ->qrErrorCorrection(ErrorCorrectionLevel::High);

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
        ->qrGradient('#112233', '#445566', GradientType::Vertical)
        ->qrEyeColor(0, '#112233', '#445566')
        ->qrEyeStyle(EyeStyle::Circle)
        ->qrInternalEyeStyle(EyeStyle::Square)
        ->qrStyle(QrStyle::Round, 0.7)
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
        ->and($html)->toContain('Download QR');
});
