<?php

namespace Akashpoudelnp\NepaliPaymentQrGenerator\Generators;

use Akashpoudelnp\NepaliPaymentQrGenerator\Contracts\QrGeneratorInterface;
use Akashpoudelnp\NepaliPaymentQrGenerator\DTO\PaymentQRData;
use Akashpoudelnp\NepaliPaymentQrGenerator\DTO\PaymentQROutput;
use Akashpoudelnp\NepaliPaymentQrGenerator\Enums\PaymentQRType;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
class FonepayQrGenerator implements QrGeneratorInterface
{
    private const FP_URL = 'fonepay.com';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config = [],
    ) {}

    public function generate(PaymentQRData $data): PaymentQROutput
    {
        $result = $this->buildEmvcoString($data);
        $image = $this->renderQrImage($result['qr_string']);

        return new PaymentQROutput(
            qrString: $result['qr_string'],
            txnId: $result['txn_id'],
            type: PaymentQRType::Fonepay,
            qrImage: $image,
            data: $data,
        );
    }

    /**
     * @return array{qr_string: string, txn_id: string}
     */
    private function buildEmvcoString(PaymentQRData $data): array
    {
        $qr = '000201';
        $qr .= '010212';

        if ($data->getMerchantPan() !== null && $data->getMerchantPan() !== '') {
            $pan = $data->getMerchantPan();
            $qr .= sprintf('37%02d%s', strlen($pan), $pan);
        }

        if ($data->getTag26Format() === '07' && $data->getTag26Identifier() !== null) {
            $tag26Id = $data->getTag26Identifier();
            $fpData = sprintf(
                '00%02d%s07%02d%s',
                strlen(self::FP_URL), self::FP_URL,
                strlen($tag26Id), $tag26Id,
            );
        } else {
            $fonepayId = $data->getFonepayId();
            $terminalId = $data->getTerminalId();
            $fpData = sprintf(
                '00%02d%s01%02d%s02%02d%s',
                strlen(self::FP_URL), self::FP_URL,
                strlen($fonepayId), $fonepayId,
                strlen($terminalId), $terminalId,
            );
        }
        $qr .= sprintf('26%02d%s', strlen($fpData), $fpData);

        $mcc = $data->getMcc();
        $qr .= sprintf('5204%s', $mcc);

        $qr .= '5303524';

        $amountStr = number_format($data->getAmount(), 2, '.', '');
        $qr .= sprintf('54%02d%s', strlen($amountStr), $amountStr);

        $countryCode = $data->getCountryCode();
        $qr .= sprintf('5802%s', $countryCode);

        $merchantName = $data->getMerchantName();
        $qr .= sprintf('59%02d%s', strlen($merchantName), $merchantName);

        $merchantCity = $data->getMerchantCity();
        $qr .= sprintf('60%02d%s', strlen($merchantCity), $merchantCity);

        $txnId = 'TXN' . substr((string) time(), -6);
        $terminalId = $data->getTerminalId();
        $remarks = $data->getRemarks();

        $addData = '';
        $addData .= sprintf('07%02d%s', strlen($terminalId), $terminalId);
        $addData .= sprintf('02%02d%s', strlen($txnId), $txnId);
        $addData .= sprintf('08%02d%s', strlen($remarks), $remarks);
        $qr .= sprintf('62%02d%s', strlen($addData), $addData);

        $qr .= '6304';
        $qr .= self::crc16($qr);

        return [
            'qr_string' => $qr,
            'txn_id' => $txnId,
        ];
    }

    private function renderQrImage(string $qrString): string
    {
        $scale = $this->config['qr_scale'] ?? 10;

        $options = new QROptions([
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_L,
            'scale'        => $scale,
            'outputBase64' => false,
        ]);

        $qrCode = new QRCode($options);
        return $qrCode->render($qrString);
    }

    public static function crc16(string $input): string
    {
        $crc = 0xFFFF;
        $len = strlen($input);

        for ($i = 0; $i < $len; $i++) {
            $crc ^= ord($input[$i]) << 8;

            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc <<= 1;
                }

                $crc &= 0xFFFF;
            }
        }

        return strtoupper(dechex($crc));
    }
}
