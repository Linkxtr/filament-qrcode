<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Linkxtr\FilamentQrCode\Concerns\HasQrInteractions;
use Linkxtr\FilamentQrCode\Infolists\Entries\QrCodeEntry;
use Linkxtr\FilamentQrCode\Tests\Fixtures\Models\Record;
use Linkxtr\QrCode\Enums\EyeStyle;
use Linkxtr\QrCode\Enums\GradientType;
use Linkxtr\QrCode\Enums\Style;

covers(QrCodeEntry::class, HasQrInteractions::class);

it('initializes qr code infolist entry and supports record() and payload() setters', function (): void {
    $record = Record::create([
        'name' => 'Demo Infolist Record',
        'qr' => 'https://linkxtr.io/demo',
    ]);

    $qrCodeEntry = QrCodeEntry::make('qr');
    $returnedRecord = $qrCodeEntry->record($record);
    expect($returnedRecord)->toBe($qrCodeEntry)
        ->and($qrCodeEntry->getRecord())->toBe($record);

    $returnedPayload = $qrCodeEntry->payload(fn (): string => 'https://custom.example.com');
    expect($returnedPayload)->toBe($qrCodeEntry)
        ->and($qrCodeEntry->getQrPayload())->toBe('https://custom.example.com');

    $returnedStaticPayload = $qrCodeEntry->payload('https://static.example.com');
    expect($returnedStaticPayload)->toBe($qrCodeEntry)
        ->and($qrCodeEntry->getQrPayload())->toBe('https://static.example.com');
});

it('handles state resolution and catches Throwable when getState fails', function (): void {
    $qrCodeEntry = QrCodeEntry::make('qr')->state('https://state.example.com');
    expect($qrCodeEntry->getQrPayload())->toBe('https://state.example.com');

    $entryThrowing = QrCodeEntry::make('qr')
        ->getStateUsing(fn (): never => throw new RuntimeException('State failure'));

    expect($entryThrowing->getQrPayload())->toBeNull()
        ->and($entryThrowing->renderQrCode())->toBeNull()
        ->and($entryThrowing->renderPreviewQrCode())->toBeNull();
});

it('covers all HasQrInteractions methods, defaults, closures, Stringable, and fallbacks', function (): void {
    $qrCodeEntry = QrCodeEntry::make('qr')->record(Record::class);

    // Defaults
    expect($qrCodeEntry->isDownloadable())->toBeFalse()
        ->and($qrCodeEntry->isPreviewable())->toBeTrue()
        ->and($qrCodeEntry->isCopyable())->toBeTrue()
        ->and($qrCodeEntry->getPreviewSize())->toBe(320)
        ->and($qrCodeEntry->getPreviewHeading())->toBe('QR Code Preview')
        ->and($qrCodeEntry->getCopyMessage())->toBe('Copied to clipboard!')
        ->and($qrCodeEntry->getDownloadFilename())->toBe('qr-code');

    // Default parameter values on downloadable(), previewable(), copyable()
    $qrCodeEntry->downloadable(false)->downloadable();
    expect($qrCodeEntry->isDownloadable())->toBeTrue();

    $qrCodeEntry->previewable(false);
    expect($qrCodeEntry->isPreviewable())->toBeFalse();
    $qrCodeEntry->previewable();
    expect($qrCodeEntry->isPreviewable())->toBeTrue();

    $qrCodeEntry->copyable(false);
    expect($qrCodeEntry->isCopyable())->toBeFalse();
    $qrCodeEntry->copyable();
    expect($qrCodeEntry->isCopyable())->toBeTrue();

    $qrCodeEntry->downloadable(false);
    expect($qrCodeEntry->isDownloadable())->toBeFalse();
    $qrCodeEntry->downloadable(true);
    expect($qrCodeEntry->isDownloadable())->toBeTrue();

    $qrCodeEntry->downloadable(fn (): int => 1)
        ->previewable(fn (): int => 1)
        ->copyable(fn (): int => 1);
    expect($qrCodeEntry->isDownloadable())->toBeTrue()
        ->and($qrCodeEntry->isPreviewable())->toBeTrue()
        ->and($qrCodeEntry->isCopyable())->toBeTrue();

    $qrCodeEntry->downloadable(fn (): bool => false)
        ->previewable(fn (): bool => false)
        ->copyable(fn (): bool => false);
    expect($qrCodeEntry->isDownloadable())->toBeFalse()
        ->and($qrCodeEntry->isPreviewable())->toBeFalse()
        ->and($qrCodeEntry->isCopyable())->toBeFalse();

    $stringable = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'stringable-val';
        }
    };

    // getDownloadFilename with explicit downloadFilename
    expect($qrCodeEntry->downloadFilename('explicit-name'))->toBe($qrCodeEntry)
        ->and($qrCodeEntry->getDownloadFilename())->toBe('explicit-name');

    $qrCodeEntry->downloadFilename(fn (): int => 77);
    expect($qrCodeEntry->getDownloadFilename())->toBe('77');

    $qrCodeEntry->downloadFilename(fn (): Stringable => $stringable);
    expect($qrCodeEntry->getDownloadFilename())->toBe('stringable-val');

    $qrCodeEntry->downloadFilename(fn (): array => ['bad']);
    expect($qrCodeEntry->getDownloadFilename())->toBe('qr-code');

    // getDownloadFilename from record (Model, array with id/key/empty/non-scalar, null)
    $entry2 = QrCodeEntry::make('qr');
    $savedModel = Record::create(['name' => 'R1']);
    $entry2->record($savedModel);
    expect($entry2->getDownloadFilename())->toBe('qr-'.$savedModel->getKey());

    $unsavedModel = new Record;
    $entry2->record($unsavedModel);
    expect($entry2->getDownloadFilename())->toBe('qr-code');

    $stringableModel = new class extends Model
    {
        public function getKey(): mixed
        {
            return new class implements Stringable
            {
                public function __toString(): string
                {
                    return 'str-key';
                }
            };
        }
    };
    $entry2->record($stringableModel);
    expect($entry2->getDownloadFilename())->toBe('qr-str-key');

    $entry2->record(['id' => 42, 'key' => 'ignored']);
    expect($entry2->getDownloadFilename())->toBe('qr-42');

    $entry2->record(['key' => 'my-key']);
    expect($entry2->getDownloadFilename())->toBe('qr-my-key');

    $entry2->record(['other' => 'val']);
    expect($entry2->getDownloadFilename())->toBe('qr-code');

    $entry2->record(['id' => ['non-scalar']]);
    expect($entry2->getDownloadFilename())->toBe('qr-code');

    // getPreviewSize
    expect($qrCodeEntry->previewSize(420))->toBe($qrCodeEntry)
        ->and($qrCodeEntry->getPreviewSize())->toBe(420);

    $qrCodeEntry->previewSize(fn (): string => '380');
    expect($qrCodeEntry->getPreviewSize())->toBe(380);

    $qrCodeEntry->previewSize(fn (): string => 'not-a-number');
    config()->set('filament-qrcode.preview.render_size', '450');
    expect($qrCodeEntry->getPreviewSize())->toBe(450);

    config()->set('filament-qrcode.preview.render_size', 'invalid');
    expect($qrCodeEntry->getPreviewSize())->toBe(320);
    config()->set('filament-qrcode.preview.render_size', 320);

    // getPreviewHeading
    expect($qrCodeEntry->previewHeading('Custom Title'))->toBe($qrCodeEntry)
        ->and($qrCodeEntry->getPreviewHeading())->toBe('Custom Title');

    $qrCodeEntry->previewHeading(fn (): int => 900);
    expect($qrCodeEntry->getPreviewHeading())->toBe('900');

    $qrCodeEntry->previewHeading(fn (): Stringable => $stringable);
    expect($qrCodeEntry->getPreviewHeading())->toBe('stringable-val');

    $qrCodeEntry->previewHeading(fn (): array => ['invalid']);
    expect($qrCodeEntry->getPreviewHeading())->toBe('QR Code Preview');

    // getCopyMessage
    expect($qrCodeEntry->copyMessage('Done copying'))->toBe($qrCodeEntry)
        ->and($qrCodeEntry->getCopyMessage())->toBe('Done copying');

    $qrCodeEntry->copyMessage(fn (): int => 101);
    expect($qrCodeEntry->getCopyMessage())->toBe('101');

    $qrCodeEntry->copyMessage(fn (): Stringable => $stringable);
    expect($qrCodeEntry->getCopyMessage())->toBe('stringable-val');

    $qrCodeEntry->copyMessage(fn (): array => ['invalid']);
    expect($qrCodeEntry->getCopyMessage())->toBe('Copied to clipboard!');
});

it('forwards all 18 styling options to QrCodeRenderer on renderQrCode and renderPreviewQrCode', function (): void {
    $logoFile = tempnam(sys_get_temp_dir(), 'entry_logo_').'.png';
    $img = imagecreatetruecolor(16, 16);
    imagepng($img, $logoFile);
    imagedestroy($img);

    try {
        $entry = QrCodeEntry::make('qr')
            ->state('https://filamentphp.com')
            ->qrSize(210)
            ->previewSize(350)
            ->qrMargin(3)
            ->qrFormat('png')
            ->qrErrorCorrection('Q')
            ->qrColor('#112233')
            ->qrBackgroundColor('#abcdef')
            ->qrGradient('#111111', '#222222', GradientType::HORIZONTAL)
            ->qrEyeColor(0, '#ff0000', '#00ff00')
            ->qrEyeStyle(EyeStyle::CIRCLE)
            ->qrInternalEyeStyle(EyeStyle::POINTY)
            ->qrStyle(Style::DOT, 0.8)
            ->qrLogo($logoFile, 0.25)
            ->qrEncoding('ISO-8859-1');

        $renderedEntry = $entry->renderQrCode();
        $renderedPreview = $entry->renderPreviewQrCode();

        expect($renderedEntry)->toBeString()->toStartWith('data:image/png;base64,')
            ->and($renderedPreview)->toBeString()->toStartWith('data:image/png;base64,')
            ->and($renderedEntry)->not->toBe($renderedPreview);
    } finally {
        @unlink($logoFile);
    }
});

it('renders blade view with rendered svg and fallback text when empty', function (): void {
    $qrCodeEntry = QrCodeEntry::make('qr')
        ->state('https://filamentphp.com/demo');

    $html = view('filament-qrcode::infolists.entries.qr-code-entry', [
        'renderQrCode' => $qrCodeEntry->renderQrCode(...),
        'getQrPayload' => $qrCodeEntry->getQrPayload(...),
        'getQrFormat' => $qrCodeEntry->getQrFormat(...),
        'renderPreviewQrCode' => $qrCodeEntry->renderPreviewQrCode(...),
        'getPreviewHeading' => $qrCodeEntry->getPreviewHeading(...),
        'getDownloadFilename' => $qrCodeEntry->getDownloadFilename(...),
        'isDownloadable' => $qrCodeEntry->isDownloadable(...),
        'isPreviewable' => $qrCodeEntry->isPreviewable(...),
        'isCopyable' => $qrCodeEntry->isCopyable(...),
        'getCopyMessage' => $qrCodeEntry->getCopyMessage(...),
    ])->render();

    expect($html)->toBeString()
        ->and($html)->toContain('fi-in-qr-code')
        ->and($html)->toContain('<svg')
        ->and($html)->toContain('https://filamentphp.com/demo')
        ->and($html)->toContain('x-on:click.stop.prevent="$dispatch(\'open-modal\'');

    $emptyEntry = QrCodeEntry::make('qr')->state(null);

    $emptyHtml = view('filament-qrcode::infolists.entries.qr-code-entry', [
        'renderQrCode' => $emptyEntry->renderQrCode(...),
        'getQrPayload' => $emptyEntry->getQrPayload(...),
        'getQrFormat' => $emptyEntry->getQrFormat(...),
        'renderPreviewQrCode' => $emptyEntry->renderPreviewQrCode(...),
        'getPreviewHeading' => $emptyEntry->getPreviewHeading(...),
        'getDownloadFilename' => $emptyEntry->getDownloadFilename(...),
        'isDownloadable' => $emptyEntry->isDownloadable(...),
        'isPreviewable' => $emptyEntry->isPreviewable(...),
        'isCopyable' => $emptyEntry->isCopyable(...),
        'getCopyMessage' => $emptyEntry->getCopyMessage(...),
    ])->render();

    expect($emptyHtml)->toContain('No QR Code Available');
});

it('renders standalone download button in infolist entry when previewable is false', function (): void {
    $qrCodeEntry = QrCodeEntry::make('qr')
        ->state('https://filamentphp.com/demo')
        ->downloadable(true)
        ->previewable(false)
        ->downloadFilename('entry-download');

    $html = view('filament-qrcode::infolists.entries.qr-code-entry', [
        'entry' => $qrCodeEntry,
        'renderQrCode' => $qrCodeEntry->renderQrCode(...),
        'getQrPayload' => $qrCodeEntry->getQrPayload(...),
        'getQrFormat' => $qrCodeEntry->getQrFormat(...),
        'getDownloadFilename' => $qrCodeEntry->getDownloadFilename(...),
        'isDownloadable' => $qrCodeEntry->isDownloadable(...),
        'isPreviewable' => $qrCodeEntry->isPreviewable(...),
        'isCopyable' => $qrCodeEntry->isCopyable(...),
    ])->render();

    expect($html)->toContain('download="entry-download.svg"')
        ->and($html)->toContain('x-on:click.stop');
});

it('renders preview modal with defensive centered layout, card container, monospace pill, and filament button in infolist entry', function (): void {
    $qrCodeEntry = QrCodeEntry::make('email')
        ->state('admin@test.test')
        ->previewable(true)
        ->downloadable(true);

    $html = view('filament-qrcode::infolists.entries.qr-code-entry', [
        'entry' => $qrCodeEntry,
        'renderQrCode' => $qrCodeEntry->renderQrCode(...),
        'getQrPayload' => $qrCodeEntry->getQrPayload(...),
        'getQrFormat' => $qrCodeEntry->getQrFormat(...),
        'renderPreviewQrCode' => $qrCodeEntry->renderPreviewQrCode(...),
        'getPreviewHeading' => $qrCodeEntry->getPreviewHeading(...),
        'getDownloadFilename' => $qrCodeEntry->getDownloadFilename(...),
        'isDownloadable' => $qrCodeEntry->isDownloadable(...),
        'isPreviewable' => $qrCodeEntry->isPreviewable(...),
        'isCopyable' => $qrCodeEntry->isCopyable(...),
        'getCopyMessage' => $qrCodeEntry->getCopyMessage(...),
    ])->render();

    expect($html)
        ->toContain('fi-qr-modal-content')
        ->toContain('style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; text-align: center; padding: 1rem;"')
        ->toContain('style="display: flex; justify-content: center; align-items: center; margin: 0 auto; max-width: 280px; width: 100%; aspect-ratio: 1 / 1; border-radius: 0.75rem; padding: 1rem; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); border: 1px solid rgba(0, 0, 0, 0.05);"')
        ->toContain('style="display: flex; justify-content: center; text-align: center; width: 100%; margin-top: 1rem;"')
        ->toContain('style="display: inline-block; font-family: monospace; font-size: 0.75rem; word-break: break-all; padding: 0.375rem 0.75rem; border-radius: 0.5rem;')
        ->toContain('admin@test.test')
        ->toContain('style="display: flex; justify-content: center; width: 100%; margin-top: 1rem;"')
        ->toContain('Download QR');
});
