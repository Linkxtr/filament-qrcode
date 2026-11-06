<?php

declare(strict_types=1);

namespace Linkxtr\FilamentQr\Concerns;

use Closure;
use Linkxtr\QrCode\DataTypes\BTC;
use Linkxtr\QrCode\DataTypes\Email;
use Linkxtr\QrCode\DataTypes\Ethereum;
use Linkxtr\QrCode\DataTypes\Geo;
use Linkxtr\QrCode\DataTypes\MeCard;
use Linkxtr\QrCode\DataTypes\PhoneNumber;
use Linkxtr\QrCode\DataTypes\SMS;
use Linkxtr\QrCode\DataTypes\VCard;
use Linkxtr\QrCode\DataTypes\WiFi;
use Stringable;

trait HasPayloadFormatters
{
    protected ?Closure $payloadFormatter = null;

    public function formatPayloadUsing(Closure $callback): static
    {
        $this->payloadFormatter = $callback;

        return $this;
    }

    public function asWifi(
        string|Closure|null $ssid = null,
        string|Closure|null $password = null,
        string|Closure|null $encryption = 'WPA',
        bool|Closure $hidden = false
    ): static {
        return $this->formatPayloadUsing(function ($raw) use ($ssid, $password, $encryption, $hidden): string {
            $sVal = $this->evaluateFormattedValue($ssid, $raw) ?? $raw;
            $pVal = $this->evaluateFormattedValue($password, $raw);
            $tVal = $this->evaluateFormattedValue($encryption, $raw);
            $hVal = (bool) $this->evaluateFormattedValue($hidden, $raw);

            $s = $this->formatString($sVal);
            $p = $this->formatNullableString($pVal);
            $t = $this->formatNullableString($tVal);
            if ($t !== null && strtoupper($t) === 'NONE') {
                $t = 'NOPASS';
            }

            return (string) new WiFi(
                ssid: $s,
                encryption: $t,
                password: $p,
                hidden: $hVal,
            );
        });
    }

    public function asUrl(string|Closure|null $url = null): static
    {
        return $this->formatPayloadUsing(fn ($raw): string => $this->formatString($this->evaluateFormattedValue($url, $raw) ?? $raw));
    }

    public function asEmail(
        string|Closure|null $email = null,
        string|Closure|null $subject = null,
        string|Closure|null $body = null,
        string|Closure|null $cc = null,
        string|Closure|null $bcc = null
    ): static {
        return $this->formatPayloadUsing(function ($raw) use ($email, $subject, $body, $cc, $bcc): string {
            $e = $this->formatString($this->evaluateFormattedValue($email, $raw) ?? $raw);
            $s = $this->formatNullableString($this->evaluateFormattedValue($subject, $raw));
            $b = $this->formatNullableString($this->evaluateFormattedValue($body, $raw));
            $c = $this->formatNullableString($this->evaluateFormattedValue($cc, $raw));
            $bc = $this->formatNullableString($this->evaluateFormattedValue($bcc, $raw));

            return (string) new Email(
                address: $e,
                subject: $s,
                body: $b,
                cc: $c,
                bcc: $bc,
            );
        });
    }

    public function asSms(string|Closure|null $phone = null, string|Closure|null $message = null): static
    {
        return $this->formatPayloadUsing(function ($raw) use ($phone, $message): string {
            $p = $this->formatString($this->evaluateFormattedValue($phone, $raw) ?? $raw);
            $m = $this->formatNullableString($this->evaluateFormattedValue($message, $raw));

            return (string) new SMS(phoneNumber: $p, message: $m);
        });
    }

    public function asPhoneNumber(string|Closure|null $phone = null): static
    {
        return $this->formatPayloadUsing(function ($raw) use ($phone): string {
            $p = $this->formatString($this->evaluateFormattedValue($phone, $raw) ?? $raw);

            return (string) new PhoneNumber($p);
        });
    }

    public function asBitcoin(
        string|Closure|null $address = null,
        float|string|Closure|null $amount = null,
        string|Closure|null $label = null,
        string|Closure|null $message = null,
        string|Closure|null $returnAddress = null
    ): static {
        return $this->formatPayloadUsing(function ($raw) use ($address, $amount, $label, $message, $returnAddress): string {
            $addr = trim($this->formatString($this->evaluateFormattedValue($address, $raw) ?? $raw));
            $amt = $this->evaluateFormattedValue($amount, $raw);
            $amountVal = $this->formatNullableString($amt);
            $labelVal = $this->formatNullableString($this->evaluateFormattedValue($label, $raw));
            $messageVal = $this->formatNullableString($this->evaluateFormattedValue($message, $raw));
            $returnAddressVal = $this->formatNullableString($this->evaluateFormattedValue($returnAddress, $raw));

            if ($amountVal !== null) {
                return (string) new BTC(
                    address: $addr,
                    amount: $amountVal,
                    label: $labelVal,
                    message: $messageVal,
                    returnAddress: $returnAddressVal,
                );
            }

            $params = [];
            if ($labelVal !== null) {
                $params['label'] = $labelVal;
            }

            if ($messageVal !== null) {
                $params['message'] = $messageVal;
            }

            if ($returnAddressVal !== null) {
                $params['r'] = $returnAddressVal;
            }

            return 'bitcoin:'.$addr.($params !== [] ? '?'.http_build_query($params, encoding_type: PHP_QUERY_RFC3986) : '');
        });
    }

    public function asBtc(
        string|Closure|null $address = null,
        float|string|Closure|null $amount = null,
        string|Closure|null $label = null,
        string|Closure|null $message = null,
        string|Closure|null $returnAddress = null
    ): static {
        return $this->asBitcoin($address, $amount, $label, $message, $returnAddress);
    }

    public function asEthereum(
        string|Closure|null $address = null,
        float|string|Closure|null $amount = null
    ): static {
        return $this->formatPayloadUsing(function ($raw) use ($address, $amount): string {
            $addr = $this->formatString($this->evaluateFormattedValue($address, $raw) ?? $raw);
            $amt = $this->evaluateFormattedValue($amount, $raw);
            $amountVal = $this->formatNullableString($amt);

            return (string) new Ethereum(address: $addr, amount: $amountVal);
        });
    }

    public function asEth(
        string|Closure|null $address = null,
        float|string|Closure|null $amount = null
    ): static {
        return $this->asEthereum($address, $amount);
    }

    /**
     * @param  array<string, mixed>|Closure  $extra
     */
    public function asCrypto(
        string|Closure $type,
        string|Closure|null $address = null,
        float|string|Closure|null $amount = null,
        array|Closure $extra = []
    ): static {
        return $this->formatPayloadUsing(function ($raw) use ($type, $address, $amount, $extra): string {
            $scheme = strtolower($this->formatString($this->evaluateFormattedValue($type, $raw)));
            $addr = trim($this->formatString($this->evaluateFormattedValue($address, $raw) ?? $raw));
            $amt = $this->evaluateFormattedValue($amount, $raw);
            $evaluatedExtra = $this->evaluateFormattedValue($extra, $raw);
            $extraParams = is_array($evaluatedExtra) ? $evaluatedExtra : [];

            $params = [];
            $formattedAmt = $this->formatNullableString($amt);
            if ($formattedAmt !== null) {
                $params['amount'] = $formattedAmt;
            }

            foreach ($extraParams as $k => $v) {
                $formattedVal = $this->formatNullableString($v);
                if ($formattedVal !== null) {
                    $params[$k] = $formattedVal;
                }
            }

            $query = $params !== [] ? '?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986) : '';

            return $scheme.':'.$addr.$query;
        });
    }

    /**
     * @param  array<string, string>|string|Closure|null  $name
     */
    public function asVCard(
        string|array|Closure|null $name = null,
        string|Closure|null $phone = null,
        string|Closure|null $email = null,
        string|Closure|null $company = null,
        string|Closure|null $job = null,
        string|Closure|null $firstName = null,
        string|Closure|null $lastName = null,
        string|Closure|null $address = null,
        string|Closure|null $url = null,
        string|Closure|null $note = null,
        string|Closure|null $birthday = null,
        string|Closure|null $version = '4.0',
        string|Closure|null $emailWork = null,
        string|Closure|null $emailHome = null,
        string|Closure|null $phoneWork = null,
        string|Closure|null $phoneHome = null,
        string|Closure|null $phoneCell = null,
        string|Closure|null $role = null
    ): static {
        return $this->formatPayloadUsing(function ($raw) use (
            $name, $phone, $email, $company, $job, $firstName, $lastName,
            $address, $url, $note, $birthday, $emailWork, $emailHome,
            $phoneWork, $phoneHome, $phoneCell, $role
        ): string {
            $evaluatedName = $this->evaluateFormattedValue($name, $raw);

            if (is_array($evaluatedName)) {
                $data = $evaluatedName;
                $fn = isset($data['name']) ? $this->formatString($data['name']) : (isset($data['fn']) ? $this->formatString($data['fn']) : $this->formatString($raw));
                $firstNameVal = $data['firstName'] ?? $data['first_name'] ?? null;
                $lastNameVal = $data['lastName'] ?? $data['last_name'] ?? null;
                $companyVal = $data['company'] ?? $data['org'] ?? null;
                $jobVal = $data['job'] ?? $data['title'] ?? null;
                $roleVal = $data['role'] ?? null;
                $emailVal = $data['email'] ?? null;
                $emailWorkVal = $data['emailWork'] ?? $data['email_work'] ?? null;
                $emailHomeVal = $data['emailHome'] ?? $data['email_home'] ?? null;
                $phoneVal = $data['phone'] ?? null;
                $phoneWorkVal = $data['phoneWork'] ?? $data['phone_work'] ?? null;
                $phoneHomeVal = $data['phoneHome'] ?? $data['phone_home'] ?? null;
                $phoneCellVal = $data['phoneCell'] ?? $data['phone_cell'] ?? null;
                $addressVal = $data['address'] ?? $data['adr'] ?? null;
                $urlVal = $data['url'] ?? null;
                $noteVal = $data['note'] ?? null;
                $birthdayVal = $data['birthday'] ?? $data['bday'] ?? null;
            } else {
                $fn = $this->formatString($evaluatedName ?? $raw);
                $firstNameVal = $this->evaluateFormattedValue($firstName, $raw);
                $lastNameVal = $this->evaluateFormattedValue($lastName, $raw);
                $companyVal = $this->evaluateFormattedValue($company, $raw);
                $jobVal = $this->evaluateFormattedValue($job, $raw);
                $roleVal = $this->evaluateFormattedValue($role, $raw);
                $emailVal = $this->evaluateFormattedValue($email, $raw);
                $emailWorkVal = $this->evaluateFormattedValue($emailWork, $raw);
                $emailHomeVal = $this->evaluateFormattedValue($emailHome, $raw);
                $phoneVal = $this->evaluateFormattedValue($phone, $raw);
                $phoneWorkVal = $this->evaluateFormattedValue($phoneWork, $raw);
                $phoneHomeVal = $this->evaluateFormattedValue($phoneHome, $raw);
                $phoneCellVal = $this->evaluateFormattedValue($phoneCell, $raw);
                $addressVal = $this->evaluateFormattedValue($address, $raw);
                $urlVal = $this->evaluateFormattedValue($url, $raw);
                $noteVal = $this->evaluateFormattedValue($note, $raw);
                $birthdayVal = $this->evaluateFormattedValue($birthday, $raw);
            }

            return (string) new VCard(
                name: $fn,
                phone: $this->formatNullableString($phoneVal),
                email: $this->formatNullableString($emailVal),
                company: $this->formatNullableString($companyVal),
                job: $this->formatNullableString($jobVal),
                firstName: $this->formatNullableString($firstNameVal),
                lastName: $this->formatNullableString($lastNameVal),
                emailWork: $this->formatNullableString($emailWorkVal),
                emailHome: $this->formatNullableString($emailHomeVal),
                phoneWork: $this->formatNullableString($phoneWorkVal),
                phoneHome: $this->formatNullableString($phoneHomeVal),
                phoneCell: $this->formatNullableString($phoneCellVal),
                role: $this->formatNullableString($roleVal),
                address: $this->formatNullableString($addressVal),
                url: $this->formatNullableString($urlVal),
                note: $this->formatNullableString($noteVal),
                birthday: $this->formatNullableString($birthdayVal),
            );
        });
    }

    /**
     * @param  array<string, string>|string|Closure|null  $name
     */
    public function asMeCard(
        string|array|Closure|null $name = null,
        string|Closure|null $phone = null,
        string|Closure|null $email = null,
        string|Closure|null $url = null,
        string|Closure|null $address = null,
        string|Closure|null $note = null
    ): static {
        return $this->formatPayloadUsing(function ($raw) use ($name, $phone, $email, $url, $address, $note): string {
            $evaluatedName = $this->evaluateFormattedValue($name, $raw);

            if (is_array($evaluatedName)) {
                $data = $evaluatedName;
                $n = isset($data['name']) ? $this->formatString($data['name']) : (isset($data['n']) ? $this->formatString($data['n']) : $this->formatString($raw));
                $pVal = $data['phone'] ?? $data['tel'] ?? null;
                $eVal = $data['email'] ?? null;
                $uVal = $data['url'] ?? null;
                $aVal = $data['address'] ?? $data['adr'] ?? null;
                $ntVal = $data['note'] ?? null;
            } else {
                $n = $this->formatString($evaluatedName ?? $raw);
                $pVal = $this->evaluateFormattedValue($phone, $raw);
                $eVal = $this->evaluateFormattedValue($email, $raw);
                $uVal = $this->evaluateFormattedValue($url, $raw);
                $aVal = $this->evaluateFormattedValue($address, $raw);
                $ntVal = $this->evaluateFormattedValue($note, $raw);
            }

            return (string) new MeCard(
                name: $n,
                phone: $this->formatNullableString($pVal),
                email: $this->formatNullableString($eVal),
                url: $this->formatNullableString($uVal),
                address: $this->formatNullableString($aVal),
                note: $this->formatNullableString($ntVal),
            );
        });
    }

    public function asGeo(
        float|string|Closure $latitude,
        float|string|Closure $longitude,
        string|Closure|null $name = null
    ): static {
        return $this->formatPayloadUsing(function ($raw) use ($latitude, $longitude, $name): string {
            $lat = (float) $this->formatString($this->evaluateFormattedValue($latitude, $raw));
            $lng = (float) $this->formatString($this->evaluateFormattedValue($longitude, $raw));
            $queryName = $this->evaluateFormattedValue($name, $raw);

            return (string) new Geo($lat, $lng, $this->formatNullableString($queryName));
        });
    }

    public function resolveFormattedPayload(mixed $raw): ?string
    {
        if ($this->payloadFormatter) {
            return $this->formatString(($this->payloadFormatter)($raw));
        }

        return $raw !== null ? $this->formatString($raw) : null;
    }

    protected function formatNullableString(mixed $value): ?string
    {
        $formatted = trim($this->formatString($value));

        return $formatted !== '' ? $formatted : null;
    }

    protected function formatString(mixed $value, string $default = ''): string
    {
        if (is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        return $default;
    }

    protected function evaluateFormattedValue(mixed $value, mixed $raw): mixed
    {
        return $this->evaluate($value, ['state' => $raw, 'raw' => $raw, 'record' => $raw]);
    }
}
