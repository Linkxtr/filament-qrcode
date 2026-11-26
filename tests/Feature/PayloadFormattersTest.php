<?php

declare(strict_types=1);

use Linkxtr\FilamentQrCode\Actions\DownloadQrCodeAction;
use Linkxtr\FilamentQrCode\Actions\DownloadQrCodeBulkAction;
use Linkxtr\FilamentQrCode\Concerns\HasPayload;
use Linkxtr\FilamentQrCode\Forms\Components\QrCodeField;
use Linkxtr\FilamentQrCode\Infolists\Entries\QrCodeEntry;
use Linkxtr\FilamentQrCode\Tables\Columns\QrCodeColumn;
use Linkxtr\QrCode\DataTypes\Email;
use Linkxtr\QrCode\DataTypes\Geo;
use Linkxtr\QrCode\DataTypes\PhoneNumber;
use Linkxtr\QrCode\DataTypes\SMS;
use Linkxtr\QrCode\DataTypes\VCard;
use Linkxtr\QrCode\DataTypes\WhatsApp;
use Linkxtr\QrCode\DataTypes\WiFi;

covers(HasPayload::class);

it('resolves Stringable laravel-qrcode DataTypes directly via payload() across all components and actions', function (): void {
    $wifi = new WiFi(ssid: 'OfficeWiFi', encryption: 'WPA', password: 'secret-password');
    $vcard = new VCard(name: 'Jane Doe', phone: '+1234567890', email: 'jane@linkx.tr', company: 'Linkxtr');
    $email = new Email(address: 'hello@linkx.tr', subject: 'Hello', body: 'World');
    $whatsapp = new WhatsApp(phoneNumber: '+15551234567', message: 'Hi there');
    $geo = new Geo(latitude: 30.0444, longitude: 31.2357, name: 'Cairo Tower');

    $column = QrCodeColumn::make('wifi')->payload($wifi);
    expect($column->getQrPayload())->toBe((string) $wifi)
        ->and($column->getQrPayload())->toBe('WIFI:S:OfficeWiFi;T:WPA;P:secret-password;;');

    $entry = QrCodeEntry::make('contact')->payload($vcard);
    expect($entry->getQrPayload())->toBe((string) $vcard)
        ->and($entry->getQrPayload())->toContain('BEGIN:VCARD', 'FN:Jane Doe', 'EMAIL;type=INTERNET:jane@linkx.tr');

    $field = QrCodeField::make('support')->payload($email);
    expect($field->getQrPayload())->toBe((string) $email)
        ->and($field->getQrPayload())->toBe('mailto:hello@linkx.tr?subject=Hello&body=World');

    $action = DownloadQrCodeAction::make()->payload($whatsapp);
    expect($action->resolvePayloadForRecord(['id' => 1]))->toBe((string) $whatsapp)
        ->and($action->resolvePayloadForRecord(['id' => 1]))->toBe('https://wa.me/15551234567?text=Hi%20there');

    $bulkAction = DownloadQrCodeBulkAction::make()->payload($geo);
    expect($bulkAction->getQrPayload(['id' => 1]))->toBe((string) $geo)
        ->and($bulkAction->getQrPayload(['id' => 1]))->toContain('geo:30.0444,31.2357');
});

it('resolves Stringable DataTypes returned from payload() closures and formatPayloadUsing()', function (): void {
    $column = QrCodeColumn::make('router')
        ->payload(fn (): WiFi => new WiFi(ssid: 'GuestNet', encryption: 'NOPASS'));

    expect($column->getQrPayload())->toBe('WIFI:S:GuestNet;T:NOPASS;;');

    $entry = QrCodeEntry::make('phone')
        ->state('+15550001111')
        ->formatPayloadUsing(fn (mixed $state): PhoneNumber => new PhoneNumber(is_string($state) ? $state : ''));

    expect($entry->getQrPayload())->toBe('tel:+15550001111');

    $field = QrCodeField::make('sms')
        ->payload('+15550002222')
        ->formatPayloadUsing(fn (mixed $state): SMS => new SMS(phoneNumber: is_string($state) ? $state : '', message: 'OTP 4242'));

    expect($field->getQrPayload())->toBe('sms:+15550002222?body=OTP%204242');

    $action = DownloadQrCodeAction::make()
        ->payload(fn (array $record): Email => new Email(address: is_string($record['email'] ?? null) ? $record['email'] : ''));

    expect($action->resolvePayloadForRecord(['email' => 'ops@linkx.tr']))->toBe('mailto:ops@linkx.tr');

    $bulkAction = DownloadQrCodeBulkAction::make()
        ->payload(fn (array $record): PhoneNumber => new PhoneNumber(is_string($record['phone'] ?? null) ? $record['phone'] : ''));

    expect($bulkAction->getQrPayload(['phone' => '+15559998888']))->toBe('tel:+15559998888');
});

it('handles null, scalar, non-scalar, and formatter chaining cleanly', function (): void {
    $column = QrCodeColumn::make('code')->payload('https://linkx.tr');
    expect($column->getQrPayload())->toBe('https://linkx.tr');

    // Resetting payload to null falls back to state
    $returned = $column->payload(null)->state('fallback-state');
    expect($returned)->toBe($column)
        ->and($column->getQrPayload())->toBe('fallback-state');

    // Scalar numeric values cast to string
    $numeric = QrCodeColumn::make('num')->state(12345);
    expect($numeric->getQrPayload())->toBe('12345');

    // Non-scalar values return null
    $nonScalar = QrCodeColumn::make('arr')
        ->state('raw')
        ->formatPayloadUsing(fn (): array => ['unsupported']);
    expect($nonScalar->getQrPayload())->toBeNull();

    // Formatter returning null returns null
    $nullFormatted = QrCodeColumn::make('nullable')
        ->state('raw')
        ->formatPayloadUsing(fn (): ?string => null);
    expect($nullFormatted->getQrPayload())->toBeNull();

    // Custom Stringable object works identically to DataTypes
    $customStringable = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'custom-stringable-payload';
        }
    };

    $formattedStringable = QrCodeColumn::make('custom')
        ->state('ignored')
        ->formatPayloadUsing(fn (): Stringable => $customStringable);

    expect($formattedStringable->getQrPayload())->toBe('custom-stringable-payload');
});
