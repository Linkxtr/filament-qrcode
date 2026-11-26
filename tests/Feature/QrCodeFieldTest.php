<?php

declare(strict_types=1);

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Linkxtr\FilamentQrCode\Concerns\HasQrInteractions;
use Linkxtr\FilamentQrCode\Forms\Components\QrCodeField;
use Linkxtr\QrCode\DataTypes\Email;
use Linkxtr\QrCode\DataTypes\SMS;
use Linkxtr\QrCode\DataTypes\WhatsApp;
use Linkxtr\QrCode\DataTypes\WiFi;
use Linkxtr\QrCode\Enums\ErrorCorrectionLevel;
use Linkxtr\QrCode\Enums\EyeStyle;
use Linkxtr\QrCode\Enums\Format;
use Linkxtr\QrCode\Enums\GradientType;
use Linkxtr\QrCode\Enums\Style;
use Linkxtr\QrCode\Generator;
use Livewire\Component;

covers(QrCodeField::class, HasQrInteractions::class);

it('initializes qr code form field with defaults and sets dehydrated to false', function (): void {
    $field = QrCodeField::make('qr_preview');

    expect($field)->toBeInstanceOf(QrCodeField::class)
        ->and($field->getName())->toBe('qr_preview')
        ->and($field->isDehydrated())->toBeFalse()
        ->and($field->getExtraViewData())->toBe(['record' => null])
        ->and($field->isDownloadable())->toBeFalse()
        ->and($field->isPreviewable())->toBeTrue()
        ->and($field->isCopyable())->toBeTrue()
        ->and($field->getQrSize())->toBe(400)
        ->and($field->getQrMargin())->toBe(4)
        ->and($field->getQrFormat())->toBe('svg')
        ->and($field->getQrErrorCorrection())->toBe('M')
        ->and($field->getQrColor())->toBe('0,0,0')
        ->and($field->getQrBackgroundColor())->toBe('255,255,255');
});

it('resolves payload from static string, closure, and state via schema container', function (): void {
    $fieldString = QrCodeField::make('qr')->payload('https://static.example.com');
    expect($fieldString->getQrPayload())->toBe('https://static.example.com');

    $fieldClosure = QrCodeField::make('qr')->payload(fn (): string => 'https://closure.example.com');
    expect($fieldClosure->getQrPayload())->toBe('https://closure.example.com');

    $livewire = new class extends Component implements HasSchemas
    {
        use InteractsWithActions;
        use InteractsWithSchemas;

        /** @var array<string, mixed>|null */
        public ?array $data = [
            'qr' => 'https://state.example.com',
        ];
    };

    $fieldState = QrCodeField::make('qr')->hint('Scan this QR');
    $schema = Schema::make($livewire)->statePath('data');
    $fieldState->container($schema);

    expect($fieldState->getQrPayload())->toBe('https://state.example.com')
        ->and($fieldState->getExtraViewData())->toHaveKeys(['get', 'operation', 'record'])
        ->and($fieldState->getChildComponents(QrCodeField::AFTER_LABEL_SCHEMA_KEY))->not->toBeEmpty();

    $fieldThrowing = QrCodeField::make('qr');
    $fieldThrowing->container(Schema::make());

    expect($fieldThrowing->getQrPayload())->toBeNull()
        ->and($fieldThrowing->renderQrCode())->toBeNull();

    $fieldEmpty = QrCodeField::make('qr');
    expect($fieldEmpty->getQrPayload())->toBeNull()
        ->and($fieldEmpty->renderQrCode())->toBeNull();
});

it('supports canonical qr* styling methods from HasQrCodeStyling', function (): void {
    $field = QrCodeField::make('qr')
        ->qrSize(220)
        ->qrMargin(3)
        ->qrFormat(Format::PNG)
        ->qrErrorCorrection(ErrorCorrectionLevel::H)
        ->qrColor('#224466')
        ->qrBackgroundColor('#f0f4f8')
        ->qrGradient('#112233', '#445566', GradientType::VERTICAL)
        ->qrEyeStyle(EyeStyle::CIRCLE)
        ->qrStyle(Style::ROUND, 0.75)
        ->qrLogo('images/brand.png', 0.22)
        ->qrEncoding('UTF-8');

    expect($field->getQrSize())->toBe(220)
        ->and($field->getQrMargin())->toBe(3)
        ->and($field->getQrFormat())->toBe('png')
        ->and($field->getQrErrorCorrection())->toBe('H')
        ->and($field->getQrColor())->toBe('#224466')
        ->and($field->getQrBackgroundColor())->toBe('#f0f4f8')
        ->and($field->getQrGradient())->toBe([
            'from' => '#112233',
            'to' => '#445566',
            'type' => 'vertical',
        ])
        ->and($field->getQrEyeStyle())->toBe('circle')
        ->and($field->getQrStyle())->toBe('round')
        ->and($field->getQrStyleSize())->toBe(0.75)
        ->and($field->getQrLogoPath())->toBe('images/brand.png')
        ->and($field->getQrLogoSize())->toBe(0.22)
        ->and($field->getQrEncoding())->toBe('UTF-8');

    $fieldDefaultLogo = QrCodeField::make('qr')->qrLogo('images/default-brand.png');
    expect($fieldDefaultLogo->getQrLogoPath())->toBe('images/default-brand.png')
        ->and($fieldDefaultLogo->getQrLogoSize())->toBe(0.2);
});

it('supports laravel-qrcode Stringable DataTypes via payload() and formatPayloadUsing() on form field', function (): void {
    $wifiField = QrCodeField::make('wifi')
        ->payload(new WiFi(ssid: 'HomeNetwork', encryption: 'WPA', password: 'secretpassword'));
    expect($wifiField->getQrPayload())->toBe('WIFI:S:HomeNetwork;T:WPA;P:secretpassword;;');

    $emailField = QrCodeField::make('email')
        ->payload(fn (): Email => new Email(address: 'dev@linkx.tr', subject: 'Inquiry'));
    expect($emailField->getQrPayload())->toBe('mailto:dev@linkx.tr?subject=Inquiry');

    $smsField = QrCodeField::make('sms')
        ->payload('+1234567890')
        ->formatPayloadUsing(fn (mixed $state): SMS => new SMS(phoneNumber: is_string($state) ? $state : '', message: 'Hello'));
    expect($smsField->getQrPayload())->toBe('sms:+1234567890?body=Hello');

    $whatsappField = QrCodeField::make('whatsapp')
        ->payload(new WhatsApp(phoneNumber: '+1234567890', message: 'Hi'));
    expect($whatsappField->getQrPayload())->toBe('https://wa.me/1234567890?text=Hi');
});

it('renders blade view with rendered svg, dark mode classes, and empty state fallback', function (): void {
    $field = QrCodeField::make('qr_code')
        ->payload('https://apricotcake.linkxtr.io/preview')
        ->downloadable(true)
        ->copyable(true)
        ->copyMessage('Copied link!')
        ->downloadFilename('custom-qr-preview');

    $html = view('filament-qrcode::forms.components.qr-code-field', [
        'field' => $field,
        'renderQrCode' => $field->renderQrCode(...),
        'getQrPayload' => $field->getQrPayload(...),
        'getQrFormat' => $field->getQrFormat(...),
        'getQrSize' => $field->getQrSize(...),
        'getPreviewSize' => $field->getPreviewSize(...),
        'getDownloadFilename' => $field->getDownloadFilename(...),
        'isDownloadable' => $field->isDownloadable(...),
        'isPreviewable' => $field->isPreviewable(...),
        'isCopyable' => $field->isCopyable(...),
        'getCopyMessage' => $field->getCopyMessage(...),
    ])->render();

    expect($html)->toBeString()
        ->and($html)->toContain('fi-fo-qr-code')
        ->and($html)->toContain('fi-fo-qr-card')
        ->and($html)->toContain('<svg')
        ->and($html)->toContain('https://apricotcake.linkxtr.io/preview')
        ->and($html)->toContain('dark:bg-gray-800')
        ->and($html)->toContain('dark:border-gray-700')
        ->and($html)->toContain('download="custom-qr-preview.svg"')
        ->and($html)->toContain('Copied link!');

    // Empty state test
    $emptyField = QrCodeField::make('empty_qr');
    $emptyHtml = view('filament-qrcode::forms.components.qr-code-field', [
        'field' => $emptyField,
        'getQrPayload' => fn (): ?string => null,
    ])->render();

    expect($emptyHtml)->toContain('fi-fo-qr-empty')
        ->and($emptyHtml)->toContain('No QR Code Available');
});

it('renders standalone inline card when previewable is false', function (): void {
    $field = QrCodeField::make('non_modal_qr')
        ->payload('https://example.com/direct')
        ->previewable(false)
        ->downloadable(true)
        ->copyable(true);

    $html = view('filament-qrcode::forms.components.qr-code-field', [
        'field' => $field,
        'renderQrCode' => $field->renderQrCode(...),
        'getQrPayload' => $field->getQrPayload(...),
        'getQrFormat' => $field->getQrFormat(...),
        'isDownloadable' => fn (): bool => true,
        'isPreviewable' => fn (): bool => false,
        'isCopyable' => fn (): bool => true,
    ])->render();

    expect($html)->toContain('fi-fo-qr-card')
        ->and($html)->not->toContain('x-slot name="heading"')
        ->and($html)->toContain('<svg')
        ->and($html)->toContain('https://example.com/direct');
});

it('renders via toHtml() seamlessly', function (): void {
    $field = QrCodeField::make('qr_live')
        ->payload('https://filamentphp.com/forms');

    $html = $field->toHtml();

    expect($html)->toBeString()
        ->and($html)->toContain('fi-fo-qr-code')
        ->and($html)->toContain('<svg')
        ->and($html)->toContain('https://filamentphp.com/forms');
});

it('forwards all styling parameters on renderQrCode to produce valid SVG', function (): void {
    $field = QrCodeField::make('styled_qr')
        ->payload('https://linkxtr.io/full-style')
        ->qrSize(180)
        ->qrMargin(2)
        ->qrFormat('svg')
        ->qrErrorCorrection('Q')
        ->qrColor('#0a2540')
        ->qrBackgroundColor('#ffffff')
        ->qrGradient('#0a2540', '#635bff', GradientType::VERTICAL)
        ->qrEyeStyle(EyeStyle::CIRCLE)
        ->qrStyle(Style::ROUND, 0.8)
        ->qrEncoding('UTF-8');

    $rendered = $field->renderQrCode();

    expect($rendered)->toBeString()
        ->and($rendered)->toContain('<svg')
        ->and($rendered)->toContain('width="180"');
});

it('applies configureQrUsing on QrCodeField for advanced laravel-qrcode features', function (): void {
    $field = QrCodeField::make('advanced_qr')
        ->payload('https://linkx.tr/custom')
        ->configureQrUsing(fn (Generator $qr): Generator => $qr->size(245)->gray(20, 95));

    $rendered = $field->renderQrCode();

    expect($rendered)->toBeString()
        ->and($rendered)->toContain('<svg')
        ->and($rendered)->toContain('width="245"');
});
