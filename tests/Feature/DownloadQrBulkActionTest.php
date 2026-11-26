<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Linkxtr\FilamentQrCode\Actions\DownloadQrCodeBulkAction;
use Linkxtr\FilamentQrCode\Tests\Fixtures\Models\Record;
use Linkxtr\QrCode\Enums\EyeStyle;
use Linkxtr\QrCode\Enums\GradientType;
use Linkxtr\QrCode\Enums\Style;
use Linkxtr\QrCode\Generator;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

covers(DownloadQrCodeBulkAction::class);

it('initializes download qr bulk action with defaults, label, icon, and deselect configuration', function (): void {
    $DownloadQrCodeBulkAction = DownloadQrCodeBulkAction::make();

    expect(DownloadQrCodeBulkAction::getDefaultName())->toBe('downloadQrBulk')
        ->and($DownloadQrCodeBulkAction->getName())->toBe('downloadQrBulk')
        ->and($DownloadQrCodeBulkAction->getLabel())->toBe('Download QR Codes')
        ->and($DownloadQrCodeBulkAction->getIcon())->toBe('heroicon-o-arrow-down-tray')
        ->and($DownloadQrCodeBulkAction->getFilename())->toBe('qrcodes.zip')
        ->and($DownloadQrCodeBulkAction->getQrFormat())->toBe('svg')
        ->and($DownloadQrCodeBulkAction->shouldDeselectRecordsAfterCompletion())->toBeFalse();
});

it('fetches selected records from custom collection or parent livewire table and checks deselect behavior', function (): void {
    $customRecords = new Collection([['id' => 1, 'qr' => 'a']]);
    $DownloadQrCodeBulkAction = DownloadQrCodeBulkAction::make()->records($customRecords);
    expect($DownloadQrCodeBulkAction->getSelectedRecords())->toBe($customRecords);

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

    $actionWithLivewire = DownloadQrCodeBulkAction::make()->livewire($livewire);
    expect($actionWithLivewire->shouldDeselectRecordsAfterCompletion())->toBeTrue()
        ->and($actionWithLivewire->getSelectedRecords()->all())->toBe([['id' => 10, 'qr' => 'from-livewire']]);

    $actionWithLivewire->deselectRecordsAfterCompletion(false);
    expect($actionWithLivewire->shouldDeselectRecordsAfterCompletion())->toBeFalse();
});

it('resolves zip filename via filename with fallbacks', function (): void {
    $DownloadQrCodeBulkAction = DownloadQrCodeBulkAction::make();

    expect($DownloadQrCodeBulkAction->filename('my-archive'))->toBe($DownloadQrCodeBulkAction)
        ->and($DownloadQrCodeBulkAction->getFilename())->toBe('my-archive.zip')
        ->and($DownloadQrCodeBulkAction->filename('zip-name.zip'))->toBe($DownloadQrCodeBulkAction)
        ->and($DownloadQrCodeBulkAction->getFilename())->toBe('zip-name.zip')
        ->and($DownloadQrCodeBulkAction->filename('UPPER.ZIP'))->toBe($DownloadQrCodeBulkAction)
        ->and($DownloadQrCodeBulkAction->getFilename())->toBe('UPPER.ZIP');

    $DownloadQrCodeBulkAction->filename('');
    expect($DownloadQrCodeBulkAction->getFilename())->toBe('qrcodes.zip');

    $DownloadQrCodeBulkAction->filename(fn (): int => 2026);
    expect($DownloadQrCodeBulkAction->getFilename())->toBe('2026.zip');

    $stringable = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'str-archive';
        }
    };
    $DownloadQrCodeBulkAction->filename(fn (): Stringable => $stringable);
    expect($DownloadQrCodeBulkAction->getFilename())->toBe('str-archive.zip');

    $DownloadQrCodeBulkAction->filename(fn (): array => ['bad']);
    expect($DownloadQrCodeBulkAction->getFilename())->toBe('qrcodes.zip');
});

it('resolves qr payload for closure, Eloquent model, array, stdClass, scalar, and null', function (): void {
    $action = DownloadQrCodeBulkAction::make();

    $DownloadQrCodeBulkAction = $action->payload(fn ($record): string => "bulk-{$record['id']}");
    expect($DownloadQrCodeBulkAction)->toBe($action)
        ->and($action->getQrPayload(['id' => 12]))->toBe('bulk-12');

    $action = DownloadQrCodeBulkAction::make();

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
        ->toBeNull()
        ->and($action->getQrPayload('raw-scalar'))
        ->toBe('raw-scalar')
        ->and($action->getQrPayload(null))
        ->toBeNull();

    // Static string payload()
    $staticAction = DownloadQrCodeBulkAction::make()->payload('https://linkx.tr');
    expect($staticAction->getQrPayload(['id' => 1]))->toBe('https://linkx.tr');
});

it('resolves record filename and entry filename across all branches', function (): void {
    $action = DownloadQrCodeBulkAction::make();

    $stringable = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'str-id';
        }
    };

    // Custom recordFilename closure
    expect($action->recordFilename(fn (array $record, int $index, string $format): string => "rec-{$record['id']}-{$index}-{$format}"))->toBe($action)
        ->and($action->getRecordFilename(['id' => 1]))->toBe('rec-1-1-svg');

    expect($action->recordFilename(fn (): int => 505))->toBe($action)
        ->and($action->getRecordFilename(['id' => 1], 2))->toBe('505');

    $action->recordFilename(fn (): Stringable => $stringable);
    expect($action->getRecordFilename(['id' => 1], 2))->toBe('str-id');

    $action->recordFilename(fn (): array => ['bad']);
    expect($action->getRecordFilename(['id' => 1], 3))->toBe('qrcode_3');

    // Without custom callback
    $action = DownloadQrCodeBulkAction::make();

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

    $DownloadQrCodeBulkAction = DownloadQrCodeBulkAction::make()
        ->records($records)
        ->filename(function (): string {
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
    $response = $DownloadQrCodeBulkAction->call();

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

    $DownloadQrCodeBulkAction = DownloadQrCodeBulkAction::make()
        ->records($records)
        ->payload(fn ($record) => $record['qr']);

    $response = $DownloadQrCodeBulkAction->call();

    expect($response)->toBeNull()
        ->and($GLOBALS['lastUnlinkedFile'])->toBeString()
        ->and($GLOBALS['lastUnlinkedFile'])->toContain('qr_zip_')
        ->and(file_exists((string) $GLOBALS['lastUnlinkedFile']))->toBeFalse();
});

it('throws RuntimeException when tempnam fails', function (): void {
    $GLOBALS['mockTempnamFalse'] = true;

    $DownloadQrCodeBulkAction = DownloadQrCodeBulkAction::make();

    expect(fn (): ?StreamedResponse => $DownloadQrCodeBulkAction->downloadZip(new Collection([['qr' => 'test']])))
        ->toThrow(RuntimeException::class, 'Failed to create temporary file for ZIP archive.');
});

it('throws RuntimeException when ZipArchive open fails', function (): void {
    $GLOBALS['mockTempnamPath'] = sys_get_temp_dir();

    $DownloadQrCodeBulkAction = DownloadQrCodeBulkAction::make();

    expect(fn (): ?StreamedResponse => $DownloadQrCodeBulkAction->downloadZip(new Collection([['qr' => 'test']])))
        ->toThrow(RuntimeException::class, 'Failed to initialize ZIP archive.');
});
it('initializes zip archive with overwrite flag when temp file already contains bytes', function (): void {
    $temp = tempnam(sys_get_temp_dir(), 'test_dirty_zip_');
    file_put_contents($temp, 'pre-existing-non-zip-data');
    $GLOBALS['mockTempnamPath'] = $temp;

    $DownloadQrCodeBulkAction = DownloadQrCodeBulkAction::make();
    $response = $DownloadQrCodeBulkAction->downloadZip(new Collection([['qr' => 'https://example.com']]));

    expect($response)->toBeInstanceOf(StreamedResponse::class);

    ob_start();
    $response->sendContent();
    $content = (string) ob_get_clean();

    expect(substr($content, 0, 4))->toBe("PK\x03\x04");
});

it('forwards configureQrUsing with per-record injection on bulk download action', function (): void {
    $records = new Collection([
        ['id' => 1, 'qr' => 'https://linkx.tr/one', 'custom_size' => 210],
        ['id' => 2, 'qr' => 'https://linkx.tr/two', 'custom_size' => 260],
    ]);

    $action = DownloadQrCodeBulkAction::make()
        ->records($records)
        ->configureQrUsing(fn (Generator $qr, array $record): Generator => $qr->size((int) $record['custom_size']));

    /** @var StreamedResponse $response */
    $response = $action->call();

    ob_start();
    $response->sendContent();
    $binaryContent = (string) ob_get_clean();

    $tempZip = tempnam(sys_get_temp_dir(), 'verify_config_zip_');
    file_put_contents($tempZip, $binaryContent);

    $zip = new ZipArchive;
    expect($zip->open($tempZip))->toBeTrue()
        ->and($zip->getFromName('1.svg'))->toContain('width="210"')
        ->and($zip->getFromName('2.svg'))->toContain('width="260"');

    $zip->close();
    @unlink($tempZip);
});
