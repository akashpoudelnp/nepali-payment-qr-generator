<?php

namespace Akashpoudelnp\NepaliPaymentQrGenerator;

use Akashpoudelnp\NepaliPaymentQrGenerator\Contracts\QrGeneratorInterface;
use Akashpoudelnp\NepaliPaymentQrGenerator\DTO\PaymentQRData;
use Akashpoudelnp\NepaliPaymentQrGenerator\DTO\PaymentQROutput;
use Akashpoudelnp\NepaliPaymentQrGenerator\Enums\PaymentQRType;
use Akashpoudelnp\NepaliPaymentQrGenerator\Generators\FonepayQrGenerator;
use Akashpoudelnp\NepaliPaymentQrGenerator\Parser\PaymentQrParser;

class PaymentQRGenerator
{
    private readonly QrGeneratorInterface $generator;

    /**
     * @param array<string, mixed> $config
     */
    private function __construct(
        private readonly PaymentQRType $type,
        private readonly array $config = [],
    ) {
        $this->generator = match ($type) {
            PaymentQRType::Fonepay => new FonepayQrGenerator($config),
        };
    }

    public function getType(): PaymentQRType
    {
        return $this->type;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function for(PaymentQRType $type, array $config = []): self
    {
        return new self($type, $config);
    }

    public function generate(float|int|PaymentQRData $amount, string $remarks = 'Payment'): PaymentQROutput
    {
        if ($amount instanceof PaymentQRData) {
            $data = $amount;
        } else {
            $data = PaymentQRData::new()
                ->fill($this->config)
                ->setAmount((float) $amount)
                ->setRemarks($remarks);
        }

        return $this->generator->generate($data);
    }

    public static function parse(string $qrString): PaymentQROutput
    {
        return PaymentQrParser::parse($qrString);
    }

    public static function parseFromImage(string $imagePath): PaymentQROutput
    {
        return PaymentQrParser::parseFromImage($imagePath);
    }

    public static function parseFromBlob(string $imageData): PaymentQROutput
    {
        return PaymentQrParser::parseFromBlob($imageData);
    }
}
