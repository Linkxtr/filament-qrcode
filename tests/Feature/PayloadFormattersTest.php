<?php

declare(strict_types=1);

use Linkxtr\FilamentQr\Concerns\HasPayloadFormatters;
use Linkxtr\FilamentQr\Tables\Columns\QrCodeColumn;

covers(HasPayloadFormatters::class);

it('formats wifi payload with WPA, WEP, NOPASS, NONE, hidden flag, empty password, closures, and special char escaping', function (): void {
    $qrCodeColumn = QrCodeColumn::make('wifi1')
        ->asWifi('MyNetwork;Test\\1:2,3', 'Pass;word\\1:2,3', 'wpa', true);
    expect($qrCodeColumn->getQrPayload())
        ->toBe('WIFI:S:MyNetwork\\;Test\\\\1\\:2\\,3;T:WPA;P:Pass\\;word\\\\1\\:2\\,3;H:true;;');

    $colNopass = QrCodeColumn::make('wifi2')
        ->asWifi('OpenCafe', null, 'NOPASS', false);
    expect($colNopass->getQrPayload())
        ->toBe('WIFI:S:OpenCafe;T:NOPASS;;');

    $colNone = QrCodeColumn::make('wifi3')
        ->asWifi('GuestNet', '', 'none');
    expect($colNone->getQrPayload())
        ->toBe('WIFI:S:GuestNet;T:NOPASS;;');

    $colEmptyPassWpa = QrCodeColumn::make('wifi4')
        ->state('StateSSID')
        ->asWifi(null, '', fn (): array => ['invalid-encryption']);
    expect($colEmptyPassWpa->getQrPayload())
        ->toBe('WIFI:S:StateSSID;T:NOPASS;;');

    $colHiddenInt = QrCodeColumn::make('wifi5')
        ->asWifi('IntHiddenNet', 'pass', 'WPA', fn (): int => 1);
    expect($colHiddenInt->getQrPayload())
        ->toBe('WIFI:S:IntHiddenNet;T:WPA;P:pass;H:true;;');
});

it('formats url, email, sms, and phone number payloads across all branches', function (): void {
    // URL
    expect(QrCodeColumn::make('u1')->asUrl('https://laravel.com')->getQrPayload())->toBe('https://laravel.com')
        ->and(QrCodeColumn::make('u2')->state('https://state.dev')->asUrl()->getQrPayload())->toBe('https://state.dev');

    // Email with all fields, empty string fields, and state fallback
    $qrCodeColumn = QrCodeColumn::make('e1')->asEmail(
        email: 'support@example.com',
        subject: 'Hello',
        body: 'Body text',
        cc: 'cc@example.com',
        bcc: 'bcc@example.com'
    );
    expect($qrCodeColumn->getQrPayload())
        ->toBe('mailto:support@example.com?subject=Hello&body=Body%20text&cc=cc%40example.com&bcc=bcc%40example.com');

    $emailEmptyOptionals = QrCodeColumn::make('e2')
        ->state('only@example.com')
        ->asEmail(null, '', '', '', '');
    expect($emailEmptyOptionals->getQrPayload())->toBe('mailto:only@example.com');

    // SMS with message, empty message, and state fallback
    expect(QrCodeColumn::make('s1')->asSms('+15550001', 'Code 123')->getQrPayload())->toBe('sms:+15550001?body=Code%20123')
        ->and(QrCodeColumn::make('s2')->state('+15550002')->asSms(null, '')->getQrPayload())->toBe('sms:+15550002');

    // Phone number
    expect(QrCodeColumn::make('p1')->asPhoneNumber('+1234567890')->getQrPayload())->toBe('tel:+1234567890')
        ->and(QrCodeColumn::make('p2')->state('+9876543210')->asPhoneNumber()->getQrPayload())->toBe('tel:+9876543210');
});

it('formats bitcoin, btc alias, ethereum, eth alias, and arbitrary crypto URI payloads', function (): void {
    // Bitcoin with all 5 parameters and whitespace trimming
    $qrCodeColumn = QrCodeColumn::make('btc1')->asBitcoin(
        address: '  bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdq  ',
        amount: 0.05,
        label: 'Store Order',
        message: 'Order #1234',
        returnAddress: 'https://merchant.example.com/pay'
    );
    expect($qrCodeColumn->getQrPayload())
        ->toBe('bitcoin:bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdq?amount=0.05&label=Store%20Order&message=Order%20%231234&r=https%3A%2F%2Fmerchant.example.com%2Fpay');

    // Bitcoin with empty optional strings and state fallback
    $btcEmptyOpts = QrCodeColumn::make('btc2')
        ->state('1BoatSLRHtKNngkdXEeobR76b53lettpyT')
        ->asBitcoin(null, '', '', '', '');
    expect($btcEmptyOpts->getQrPayload())->toBe('bitcoin:1BoatSLRHtKNngkdXEeobR76b53lettpyT');

    // Bitcoin whitespace trim when amount is null
    expect(QrCodeColumn::make('btc_trim')->asBitcoin('  1BoatSLRHtKNngkdXEeobR76b53lettpyT  ')->getQrPayload())
        ->toBe('bitcoin:1BoatSLRHtKNngkdXEeobR76b53lettpyT');

    // Bitcoin without amount but with label, message, and returnAddress
    $btcNoAmount = QrCodeColumn::make('btc_no_amount')
        ->asBitcoin(
            address: '1BoatSLRHtKNngkdXEeobR76b53lettpyT',
            label: 'Donation',
            message: 'Support Us',
            returnAddress: 'https://example.com/return'
        );
    expect($btcNoAmount->getQrPayload())
        ->toBe('bitcoin:1BoatSLRHtKNngkdXEeobR76b53lettpyT?label=Donation&message=Support%20Us&r=https%3A%2F%2Fexample.com%2Freturn');

    // asBtc alias forwards all 5 parameters
    $btcAlias = QrCodeColumn::make('btc3')->asBtc(
        address: 'bc1qxyz',
        amount: '1.25',
        label: 'Donate',
        message: 'Thanks',
        returnAddress: 'https://return.example.com'
    );
    expect($btcAlias->getQrPayload())
        ->toBe('bitcoin:bc1qxyz?amount=1.25&label=Donate&message=Thanks&r=https%3A%2F%2Freturn.example.com');

    // Ethereum & asEth alias
    expect(QrCodeColumn::make('eth1')->state('  0xabc  ')->asEthereum(null, '')->getQrPayload())
        ->toBe('ethereum:0xabc')
        ->and(QrCodeColumn::make('eth2')->asEth('  0xdef  ', 2.5)->getQrPayload())
        ->toBe('ethereum:0xdef?amount=2.5')
        ->and(QrCodeColumn::make('eth3')->asEthereum('0x123', '   ')->getQrPayload())
        ->toBe('ethereum:0x123');

    // asCrypto with uppercase type, whitespace address, empty/valid extra params, and non-array extra closure
    $sol = QrCodeColumn::make('sol')->asCrypto(
        type: 'SOLANA',
        address: '  7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU  ',
        amount: 10.5,
        extra: [
            'memo' => 'INV-99',
            'empty_str' => '',
            'null_val' => null,
            0 => 'indexed',
        ]
    );
    expect($sol->getQrPayload())
        ->toBe('solana:7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU?amount=10.5&memo=INV-99&0=indexed');

    $cryptoFallback = QrCodeColumn::make('xmr')
        ->state('44Affq5kSiGBoZ')
        ->asCrypto('Monero', null, '', fn (): ?array => null);
    expect($cryptoFallback->getQrPayload())->toBe('monero:44Affq5kSiGBoZ');
});

it('formats vCard contacts via individual arguments and associative array keys', function (): void {
    // Full vCard with individual parameters and CRLF/CR/LF/special character escaping
    $qrCodeColumn = QrCodeColumn::make('vc4')->asVCard(
        name: "Jane;Doe,Jr\\Sr\r\nLine2\rLine3",
        phone: '+1-555-0100',
        email: 'jane@example.com',
        company: 'Acme;Corp',
        job: 'Lead,Eng',
        firstName: 'Jane',
        lastName: 'Doe',
        address: '100 Main St',
        url: 'https://janedoe.com',
        note: 'Note line',
        birthday: '1990-05-15',
        version: '4.0',
        emailWork: 'work@example.com',
        emailHome: 'home@example.com',
        phoneWork: '+1-555-0101',
        phoneHome: '+1-555-0102',
        phoneCell: '+1-555-0103',
        role: 'Architecture'
    );

    expect($qrCodeColumn->getQrPayload())->toBe(implode("\r\n", [
        'BEGIN:VCARD',
        'VERSION:3.0',
        'FN:Jane\\;Doe\\,Jr\\\\Sr\\nLine2\\nLine3',
        'N:Doe;Jane;;;',
        'ORG:Acme\\;Corp',
        'TITLE:Lead\\,Eng',
        'ROLE:Architecture',
        'EMAIL;type=INTERNET:jane@example.com',
        'EMAIL;type=INTERNET,WORK:work@example.com',
        'EMAIL;type=INTERNET,HOME:home@example.com',
        'TEL:+1-555-0100',
        'TEL;type=WORK:+1-555-0101',
        'TEL;type=HOME:+1-555-0102',
        'TEL;type=CELL:+1-555-0103',
        'ADR:;;100 Main St;;;;',
        'URL:https://janedoe.com',
        'NOTE:Note line',
        'BDAY:1990-05-15',
        'END:VCARD',
        '',
    ]));

    // vCard with only firstName (lastName null) and empty strings for optional fields
    $vCard3FirstOnly = QrCodeColumn::make('vc3a')
        ->state('Fallback Name')
        ->asVCard(
            phone: '',
            email: 'v3@example.com',
            company: '',
            job: '',
            firstName: 'OnlyFirst',
            address: '',
            url: '',
            note: '',
            birthday: '',
            version: '3.0',
            emailWork: '',
            emailHome: '',
            phoneWork: '',
            phoneHome: '',
            phoneCell: '',
            role: ''
        );

    expect($vCard3FirstOnly->getQrPayload())->toBe(implode("\r\n", [
        'BEGIN:VCARD',
        'VERSION:3.0',
        'FN:Fallback Name',
        'N:;OnlyFirst;;;',
        'EMAIL;type=INTERNET:v3@example.com',
        'END:VCARD',
        '',
    ]));

    // vCard with only lastName (firstName null)
    $vCardLastOnly = QrCodeColumn::make('vc3b')->asVCard(
        name: 'Last Only',
        email: '',
        lastName: 'OnlyLast',
        version: null
    );
    expect($vCardLastOnly->getQrPayload())->toBe("BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Last Only\r\nN:OnlyLast;;;;\r\nEND:VCARD\r\n");

    // Associative array mode: primary keys
    $vCardArrPrimary = QrCodeColumn::make('vc_arr1')->asVCard([
        'name' => 'John Primary',
        'firstName' => 'John',
        'lastName' => 'Primary',
        'company' => 'Primary Co',
        'job' => 'CTO',
        'role' => 'Exec',
        'email' => 'john@primary.com',
        'emailWork' => 'jw@primary.com',
        'emailHome' => 'jh@primary.com',
        'phone' => '111',
        'phoneWork' => '222',
        'phoneHome' => '333',
        'phoneCell' => '444',
        'address' => '1 Primary Way',
        'url' => 'https://primary.com',
        'note' => 'Primary Note',
        'birthday' => '1985-01-01',
        'version' => '3.0',
    ]);
    expect($vCardArrPrimary->getQrPayload())->toBe(implode("\r\n", [
        'BEGIN:VCARD',
        'VERSION:3.0',
        'FN:John Primary',
        'N:Primary;John;;;',
        'ORG:Primary Co',
        'TITLE:CTO',
        'ROLE:Exec',
        'EMAIL;type=INTERNET:john@primary.com',
        'EMAIL;type=INTERNET,WORK:jw@primary.com',
        'EMAIL;type=INTERNET,HOME:jh@primary.com',
        'TEL:111',
        'TEL;type=WORK:222',
        'TEL;type=HOME:333',
        'TEL;type=CELL:444',
        'ADR:;;1 Primary Way;;;;',
        'URL:https://primary.com',
        'NOTE:Primary Note',
        'BDAY:1985-01-01',
        'END:VCARD',
        '',
    ]));

    // Associative array mode: alternate keys (fn, first_name, last_name, org, title, email_work, email_home, phone_work, phone_home, phone_cell, adr, bday)
    $vCardArrAlt = QrCodeColumn::make('vc_arr2')->asVCard([
        'fn' => 'Alt Name',
        'first_name' => 'AltFirst',
        'last_name' => 'AltLast',
        'org' => 'Alt Org',
        'title' => 'Alt Title',
        'email_work' => 'aw@alt.com',
        'email_home' => 'ah@alt.com',
        'phone_work' => '555',
        'phone_home' => '666',
        'phone_cell' => '777',
        'adr' => '2 Alt St',
        'bday' => '2000-12-31',
    ]);
    expect($vCardArrAlt->getQrPayload())->toBe(implode("\r\n", [
        'BEGIN:VCARD',
        'VERSION:3.0',
        'FN:Alt Name',
        'N:AltLast;AltFirst;;;',
        'ORG:Alt Org',
        'TITLE:Alt Title',
        'EMAIL;type=INTERNET,WORK:aw@alt.com',
        'EMAIL;type=INTERNET,HOME:ah@alt.com',
        'TEL;type=WORK:555',
        'TEL;type=HOME:666',
        'TEL;type=CELL:777',
        'ADR:;;2 Alt St;;;;',
        'BDAY:2000-12-31',
        'END:VCARD',
        '',
    ]));

    // Associative array mode: fallback to $raw when neither name nor fn is present
    $vCardArrRawFallback = QrCodeColumn::make('vc_arr3')
        ->state('Raw Fallback Name')
        ->asVCard([]);
    expect($vCardArrRawFallback->getQrPayload())->toBe("BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Raw Fallback Name\r\nEND:VCARD\r\n");
});

it('formats mecard and geo payloads across individual parameters, array input, and closures', function (): void {
    // MeCard with all individual arguments and escaping
    $qrCodeColumn = QrCodeColumn::make('mc1')->asMeCard(
        name: 'Alex;Green:Jr,1\\2',
        phone: '+100200300',
        email: 'alex@example.com',
        url: 'https://alex.example.com',
        address: '123 Street;Apt 4',
        note: 'VIP:Client,1'
    );
    expect($qrCodeColumn->getQrPayload())
        ->toBe('MECARD:N:Alex\\;Green\\:Jr,1\\\\2;TEL:+100200300;EMAIL:alex@example.com;NOTE:VIP\\:Client\\,1;ADR:123 Street\\;Apt 4;URL:https://alex.example.com;;');

    // MeCard with state fallback and empty optional strings
    $meCardEmptyOpts = QrCodeColumn::make('mc2')
        ->state('State Person')
        ->asMeCard(null, '', '', '', '', '');
    expect($meCardEmptyOpts->getQrPayload())->toBe('MECARD:N:State Person;;');

    // MeCard with associative array
    $meCardArray = QrCodeColumn::make('mc3')->asMeCard([
        'name' => 'Array User',
        'phone' => '999',
        'email' => 'arr@example.com',
        'url' => 'https://arr.dev',
        'address' => 'Cairo',
        'note' => 'Array note',
    ]);
    expect($meCardArray->getQrPayload())
        ->toBe('MECARD:N:Array User;TEL:999;EMAIL:arr@example.com;NOTE:Array note;ADR:Cairo;URL:https://arr.dev;;');

    // MeCard with associative array using alternate keys (n, tel, adr)
    $meCardAltKeys = QrCodeColumn::make('mc3_alt')->asMeCard([
        'n' => 'Alt User',
        'tel' => '888',
        'adr' => 'Giza',
    ]);
    expect($meCardAltKeys->getQrPayload())
        ->toBe('MECARD:N:Alt User;TEL:888;ADR:Giza;;');

    // MeCard with associative array without name (falls back to raw state)
    $meCardArrayFallback = QrCodeColumn::make('mc4')
        ->state('Raw Array User')
        ->asMeCard([]);
    expect($meCardArrayFallback->getQrPayload())->toBe('MECARD:N:Raw Array User;;');

    // Geo with name, without name, and with empty string name
    expect(QrCodeColumn::make('g1')->asGeo(30.0444, 31.2357, 'Cairo Tower')->getQrPayload())
        ->toBe('geo:30.0444,31.2357?name=Cairo%20Tower')
        ->and(QrCodeColumn::make('g2')->asGeo('30.0444', '31.2357')->getQrPayload())
        ->toBe('geo:30.0444,31.2357')
        ->and(QrCodeColumn::make('g3')->asGeo(30.0444, 31.2357, '')->getQrPayload())
        ->toBe('geo:30.0444,31.2357');
});

it('evaluates closures with state, raw, and record injections and formats Stringable and non-scalar values', function (): void {
    $stringable = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'from-stringable';
        }
    };

    $qrCodeColumn = QrCodeColumn::make('c1')
        ->state('injected-val')
        ->asUrl(fn (string $state, string $raw, string $record): string => "{$state}|{$raw}|{$record}");
    expect($qrCodeColumn->getQrPayload())->toBe('injected-val|injected-val|injected-val');

    $col2 = QrCodeColumn::make('c2')
        ->state($stringable);
    expect($col2->getQrPayload())->toBe('from-stringable');

    $col3 = QrCodeColumn::make('c3')
        ->formatPayloadUsing(fn (): array => ['non-scalar']);
    expect($col3->getQrPayload())->toBe('');
});
