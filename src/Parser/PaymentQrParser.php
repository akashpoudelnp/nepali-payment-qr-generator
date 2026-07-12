<?php

namespace Akashpoudelnp\NepaliPaymentQrGenerator\Parser;

use Akashpoudelnp\NepaliPaymentQrGenerator\DTO\PaymentQRData;
use Akashpoudelnp\NepaliPaymentQrGenerator\DTO\PaymentQROutput;
use Akashpoudelnp\NepaliPaymentQrGenerator\Enums\PaymentQRType;
use Akashpoudelnp\NepaliPaymentQrGenerator\Exceptions\PaymentQRException;
use chillerlan\QRCode\QRCode;

class PaymentQrParser
{
    public static function parse(string $qrString): PaymentQROutput
    {
        $result = self::parseToData($qrString);

        return new PaymentQROutput(
            qrString: $qrString,
            txnId: $result['txn_id'],
            type: PaymentQRType::Fonepay,
            data: $result['data'],
        );
    }

    public static function parseFromImage(string $imagePath): PaymentQROutput
    {
        if (!file_exists($imagePath)) {
            throw new PaymentQRException("Image file not found: {$imagePath}");
        }

        $qrCode = new QRCode;
        $decoded = $qrCode->readFromFile($imagePath);

        return self::parse($decoded);
    }

    public static function parseFromBlob(string $imageData): PaymentQROutput
    {
        $qrCode = new QRCode;
        $decoded = $qrCode->readFromBlob($imageData);

        return self::parse($decoded);
    }

    /**
     * @return array{data: PaymentQRData, txn_id: string}
     */
    private static function parseToData(string $qrString): array
    {
        $data = PaymentQRData::new();
        $txnId = '';
        $pos = 0;
        $len = strlen($qrString);

        while ($pos + 4 <= $len) {
            $tag = substr($qrString, $pos, 2);
            $valueLen = (int) substr($qrString, $pos + 2, 2);
            $pos += 4;

            if ($pos + $valueLen > $len) {
                break;
            }

            $value = substr($qrString, $pos, $valueLen);
            $pos += $valueLen;

            switch ($tag) {
                case '26':
                    self::parseFonepayData($data, $value);
                    break;
                case '37':
                    $data->setMerchantPan($value);
                    break;
                case '52':
                    $data->setMcc($value);
                    break;
                case '54':
                    $data->setAmount((float) $value);
                    break;
                case '58':
                    $data->setCountryCode($value);
                    break;
                case '59':
                    $data->setMerchantName($value);
                    break;
                case '60':
                    $data->setMerchantCity($value);
                    break;
                case '62':
                    $txnId = self::parseAdditionalData($data, $value);
                    break;
            }
        }

        return ['data' => $data, 'txn_id' => $txnId];
    }

    private static function parseFonepayData(PaymentQRData $data, string $value): void
    {
        $pos = 0;
        $len = strlen($value);

        while ($pos + 4 <= $len) {
            $tag = substr($value, $pos, 2);
            $valueLen = (int) substr($value, $pos + 2, 2);
            $pos += 4;

            if ($pos + $valueLen > $len) {
                break;
            }

            $subValue = substr($value, $pos, $valueLen);
            $pos += $valueLen;

            match ($tag) {
                '01' => $data->setFonepayId($subValue)->setTag26Format('01_02'),
                '02' => $data->setTerminalId($subValue),
                '07' => $data->setTag26Identifier($subValue)->setTag26Format('07'),
                default => null,
            };
        }
    }

    private static function parseAdditionalData(PaymentQRData $data, string $value): string
    {
        $txnId = '';
        $pos = 0;
        $len = strlen($value);

        while ($pos + 4 <= $len) {
            $tag = substr($value, $pos, 2);
            $valueLen = (int) substr($value, $pos + 2, 2);
            $pos += 4;

            if ($pos + $valueLen > $len) {
                break;
            }

            $subValue = substr($value, $pos, $valueLen);
            $pos += $valueLen;

            match ($tag) {
                '02' => $txnId = $subValue,
                '07' => $data->setTerminalId($subValue),
                '08' => $data->setRemarks($subValue),
                default => null,
            };
        }

        return $txnId;
    }
}
