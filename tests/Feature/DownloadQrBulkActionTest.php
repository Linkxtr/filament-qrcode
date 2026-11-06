<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Linkxtr\FilamentQr\Actions\DownloadQrBulkAction;
use Linkxtr\FilamentQr\Tests\Fixtures\Models\Record;
use Linkxtr\QrCode\Enums\EyeStyle;
use Linkxtr\QrCode\Enums\GradientType;
use Linkxtr\QrCode\Enums\Style;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

covers(DownloadQrBulkAction::class);

it('initializes download qr bulk action with defaults, label, icon, and deselect configuration', function (): void {
    $downloadQrBulkAction = DownloadQrBulkAction::make();

    expect(DownloadQrBulkAction::getDefaultName())->toBe('downloadQrBulk')
        ->and($downloadQrBulkAction->getName())->toBe('downloadQrBulk')
        ->and($downloadQrBulkAction->getLabel())->toBe('Download QR Codes')
        ->and($downloadQrBulkAction->getIcon())->toBe('heroicon-o-arrow-down-tray')
        ->and($downloadQrBulkAction->getFilename())->toBe('qrcodes.zip')
        ->and($downloadQrBulkAction->getZipFilename())->toBe('qrcodes.zip')
        ->and($downloadQrBulkAction->getArchiveFilename())->toBe('qrcodes.zip')
        ->and($downloadQrBulkAction->getQrFormat())->toBe('svg')
        ->and($downloadQrBulkAction->shouldDeselectRecordsAfterCompletion())->toBeFalse();
});

it('fetches selected records from custom collection or parent livewire table and checks deselect behavior', function (): void {
    $customRecords = new Collection([['id' => 1, 'qr' => 'a']]);
    $downloadQrBulkAction = DownloadQrBulkAction::make()->records($customRecords);
    expect($downloadQrBulkAction->getSelectedRecords())->toBe($customRecords);

    $livewire = new class extends Component
    {
        public function getSelectedTableRecords(bool $shouldFetchSelectedRecords = true, ?int $chunkSize = null): Collection
        {
            return new Collection([['id' => 10, 'qr' => 'from-livewire']]);
        }

        public function render(): string
        {
            return '<div></div>';
        }
    };

    $actionWithLivewire = DownloadQrBulkAction::make()->livewire($livewire);
    expect($actionWithLivewire->shouldDeselectRecordsAfterCompletion())->toBeTrue()
        ->and($actionWithLivewire->getSelectedRecords()->all())->toBe([['id' => 10, 'qr' => 'from-livewire']]);

    $actionWithLivewire->deselectRecordsAfterCompletion(false);
    expect($actionWithLivewire->shouldDeselectRecordsAfterCompletion())->toBeFalse();
});

it('resolves zip filename via filename, zipFilename, and archiveFilename with fallbacks', function (): void {
    $downloadQrBulkAction = DownloadQrBulkAction::make();

    expect($downloadQrBulkAction->filename('my-archive'))->toBe($downloadQrBulkAction)
        ->and($downloadQrBulkAction->getFilename())->toBe('my-archive.zip')
        ->and($downloadQrBulkAction->zipFilename('zip-name.zip'))->toBe($downloadQrBulkAction)
        ->and($downloadQrBulkAction->getZipFilename())->toBe('zip-name.zip')
        ->and($downloadQrBulkAction->archiveFilename('UPPER.ZIP'))->toBe($downloadQrBulkAction)
        ->and($downloadQrBulkAction->getArchiveFilename())->toBe('UPPER.ZIP');

    $downloadQrBulkAction->filename('');
    expect($downloadQrBulkAction->getFilename())->toBe('qrcodes.zip');

    $downloadQrBulkAction->filename(fn (): int => 2026);
    expect($downloadQrBulkAction->getFilename())->toBe('2026.zip');

    $stringable = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'str-archive';
        }
    };
    $downloadQrBulkAction->filename(fn (): Stringable => $stringable);
    expect($downloadQrBulkAction->getFilename())->toBe('str-archive.zip');

    $downloadQrBulkAction->filename(fn (): array => ['bad']);
    expect($downloadQrBulkAction->getFilename())->toBe('qrcodes.zip');
});

it('resolves qr payload for closure, Eloquent model, array, stdClass, scalar, and null', function (): void {
    $action = DownloadQrBulkAction::make();

    $downloadQrBulkAction = $action->payload(fn ($record): string => "bulk-{$record['id']}");
    expect($downloadQrBulkAction)->toBe($action)
        ->and($action->getQrPayload(['id' => 12]))->toBe('bulk-12');

    $action = DownloadQrBulkAction::make();

    $m1 = new Record(['qr' => 'model-qr']);
    expect($action->getQrPayload($m1))->toBe('model-qr');

    $m2 = new class extends Model
    {
        protected $guarded = [];
    };
    $m2->fill(['qr_code' => 'model-qr-code']);

    expect($action->getQrPayload($m2))->toBe('model-qr-code');

    $m3 = Record::create(['name' => 'Model Key']);
    expect($action->getQrPayload($m3))->toBe((string) $m3->getKey())
        ->and($action->getQrPayload(['qr' => 'a-qr', 'qr_code' => 'a-code']))
        ->toBe('a-qr')
        ->and($action->getQrPayload(['qr_code' => 'a-code', 'url' => 'a-url']))
        ->toBe('a-code')
        ->and($action->getQrPayload(['url' => 'a-url', 'id' => 9]))
        ->toBe('a-url')
        ->and($action->getQrPayload(['id' => 9]))
        ->toBe('9')
        ->and($action->getQrPayload([]))
        ->toBeNull()
        ->and($action->getQrPayload((object) ['qr' => 'o-qr', 'id' => 4]))
        ->toBe('o-qr')
        ->and($action->getQrPayload((object) ['id' => 44]))
        ->toBe('44')
        ->and($action->getQrPayload((object) ['other' => 'x']))
        ->toBe('')
        ->and($action->getQrPayload('raw-scalar'))
        ->toBe('raw-scalar')
        ->and($action->getQrPayload(null))
        ->toBeNull();
});

it('resolves record filename and entry filename across all branches', function (): void {
    $action = DownloadQrBulkAction::make();

    $stringable = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'str-id';
        }
    };

    // Custom recordFilename / entryFilename closure
    expect($action->recordFilename(fn (array $record, int $index, string $format): string => "rec-{$record['id']}-{$index}-{$format}"))->toBe($action)
        ->and($action->getRecordFilename(['id' => 1]))->toBe('rec-1-1-svg');

    expect($action->entryFilename(fn (): int => 505))->toBe($action)
        ->and($action->getRecordFilename(['id' => 1], 2))->toBe('505');

    $action->recordFilename(fn (): Stringable => $stringable);
    expect($action->getRecordFilename(['id' => 1], 2))->toBe('str-id');

    $action->recordFilename(fn (): array => ['bad']);
    expect($action->getRecordFilename(['id' => 1], 3))->toBe('qrcode_3');

    // Without custom callback
    $action = DownloadQrBulkAction::make();

    // Model with key and Model without key
    $savedModel = Record::create(['name' => 'Saved']);
    $unsavedModel = new Record(['name' => 'Unsaved']);
    $stringableKeyModel = new class extends Model
    {
        public function getKey(): mixed
        {
            return new class implements Stringable
            {
                public function __toString(): string
                {
                    return 'model-str-key';
                }
            };
        }
    };
    expect($action->getRecordFilename($savedModel, 4))->toBe((string) $savedModel->getKey())
        ->and($action->getRecordFilename($stringableKeyModel, 4))->toBe('model-str-key')
        ->and($action->getRecordFilename($unsavedModel, 4))->toBe('qrcode_4');

    // Array with scalar id, Stringable id, non-scalar id, and no id
    expect($action->getRecordFilename(['id' => 88], 5))->toBe('88')
        ->and($action->getRecordFilename(['id' => $stringable], 5))->toBe('str-id')
        ->and($action->getRecordFilename(['id' => ['bad']], 5))->toBe('qrcode_5')
        ->and($action->getRecordFilename(['name' => 'no-id'], 5))->toBe('qrcode_5');

    // Object with scalar id, Stringable id, non-scalar id, and no id
    expect($action->getRecordFilename((object) ['id' => 77], 6))->toBe('77')
        ->and($action->getRecordFilename((object) ['id' => $stringable], 6))->toBe('str-id')
        ->and($action->getRecordFilename((object) ['id' => ['bad']], 6))->toBe('qrcode_6')
        ->and($action->getRecordFilename((object) ['name' => 'no-id'], 6))->toBe('qrcode_6')
        ->and($action->getRecordFilename('plain-string'))->toBe('qrcode_1');

    // resolveEntryFilename with default index/format and case-insensitive extension matching
    expect($action->resolveEntryFilename(null))->toBe('qrcode_1.svg')
        ->and($action->resolveEntryFilename(['id' => 'already.svg'], 0, 'svg'))->toBe('already.svg')
        ->and($action->resolveEntryFilename(['id' => 'already.svg'], 0, 'SVG'))->toBe('already.svg')
        ->and($action->resolveEntryFilename(['id' => 'ends_with_svg'], 0, 'svg'))->toBe('ends_with_svg.svg')
        ->and($action->resolveEntryFilename(['id' => 'ALREADY.SVG'], 0, 'svg'))->toBe('ALREADY.SVG')
        ->and($action->resolveEntryFilename(['id' => 'item'], 1, 'PNG'))->toBe('item.PNG');
});

it('executes download qr bulk action, skips empty payloads while incrementing index, and cleans up temp file', function (): void {
    $records = new EloquentCollection([
        ['qr' => ''],
        ['qr' => 'https://example.com/second'],
        ['qr' => 'https://example.com/third'],
    ]);

    $downloadQrBulkAction = DownloadQrBulkAction::make()
        ->records($records)
        ->archiveFilename(function (): string {
            clearstatcache(true, (string) $GLOBALS['lastTempnamFile']);
            expect(filesize((string) $GLOBALS['lastTempnamFile']))->toBeGreaterThan(0);

            return 'batch-qrs';
        })
        ->qrSize(180)
        ->qrMargin(2)
        ->qrFormat('svg')
        ->qrErrorCorrection('M')
        ->qrColor('#112233')
        ->qrBackgroundColor('#ffffff')
        ->qrGradient('#112233', '#445566', GradientType::VERTICAL)
        ->qrEyeColor(0, '#112233', '#445566')
        ->qrEyeStyle(EyeStyle::SQUARE)
        ->qrInternalEyeStyle(EyeStyle::SQUARE)
        ->qrStyle(Style::SQUARE, 0.5)
        ->qrEncoding('UTF-8');

    /** @var StreamedResponse $response */
    $response = $downloadQrBulkAction->call();

    expect($response)->toBeInstanceOf(StreamedResponse::class)
        ->and($response->headers->get('content-type'))->toBe('application/zip')
        ->and($response->headers->get('content-disposition'))->toContain('batch-qrs.zip');

    ob_start();
    $response->sendContent();
    $binaryContent = (string) ob_get_clean();

    expect($GLOBALS['lastUnlinkedFile'])->toBeString()
        ->and(file_exists((string) $GLOBALS['lastUnlinkedFile']))->toBeFalse();

    $tempZip = tempnam(sys_get_temp_dir(), 'verify_zip_');
    file_put_contents($tempZip, $binaryContent);

    $zip = new ZipArchive;
    expect($zip->open($tempZip))->toBeTrue()
        ->and($zip->numFiles)->toBe(2);

    $names = [$zip->getNameIndex(0), $zip->getNameIndex(1)];
    expect($names)->toBe(['qrcode_2.svg', 'qrcode_3.svg'])
        ->and($zip->getFromName('qrcode_2.svg'))->toContain('<svg')
        ->and($zip->getFromName('qrcode_3.svg'))->toContain('<svg');

    $zip->close();
    @unlink($tempZip);
});

it('unlinks temporary file and returns null when all records have empty payloads', function (): void {
    $records = new Collection([
        ['id' => 1, 'qr' => null],
        ['id' => 2, 'qr' => ''],
    ]);

    $downloadQrBulkAction = DownloadQrBulkAction::make()
        ->records($records)
        ->payload(fn ($record) => $record['qr']);

    $response = $downloadQrBulkAction->call();

    expect($response)->toBeNull()
        ->and($GLOBALS['lastUnlinkedFile'])->toBeString()
        ->and($GLOBALS['lastUnlinkedFile'])->toContain('qr_zip_')
        ->and(file_exists((string) $GLOBALS['lastUnlinkedFile']))->toBeFalse();
});

it('throws RuntimeException when tempnam fails', function (): void {
    $GLOBALS['mockTempnamFalse'] = true;

    $downloadQrBulkAction = DownloadQrBulkAction::make();

    expect(fn (): ?StreamedResponse => $downloadQrBulkAction->downloadZip(new Collection([['qr' => 'test']])))
        ->toThrow(RuntimeException::class, 'Failed to create temporary file for ZIP archive.');
});

it('throws RuntimeException when ZipArchive open fails', function (): void {
    $GLOBALS['mockTempnamPath'] = sys_get_temp_dir();

    $downloadQrBulkAction = DownloadQrBulkAction::make();

    expect(fn (): ?StreamedResponse => $downloadQrBulkAction->downloadZip(new Collection([['qr' => 'test']])))
        ->toThrow(RuntimeException::class, 'Failed to initialize ZIP archive.');
});
it('initializes zip archive with overwrite flag when temp file already contains bytes', function (): void {
    $temp = tempnam(sys_get_temp_dir(), 'test_dirty_zip_');
    file_put_contents($temp, 'pre-existing-non-zip-data');
    $GLOBALS['mockTempnamPath'] = $temp;

    $downloadQrBulkAction = DownloadQrBulkAction::make();
    $response = $downloadQrBulkAction->downloadZip(new Collection([['qr' => 'https://example.com']]));

    expect($response)->toBeInstanceOf(StreamedResponse::class);

    ob_start();
    $response->sendContent();
    $content = (string) ob_get_clean();

    expect(substr($content, 0, 4))->toBe("PK\x03\x04");
});
