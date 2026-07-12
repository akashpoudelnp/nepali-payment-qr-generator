<?php

namespace Akashpoudelnp\NepaliPaymentQrGenerator\Tests\Unit;

use Akashpoudelnp\NepaliPaymentQrGenerator\PaymentQRGenerator;
use Akashpoudelnp\NepaliPaymentQrGenerator\Enums\PaymentQRType;
use PHPUnit\Framework\TestCase;

class PaymentQrParserTest extends TestCase
{
    public function test_parses_generated_qr_string(): void
    {
        $original = PaymentQRGenerator::for(PaymentQRType::Fonepay, [
            'merchant_name' => 'Test Store',
            'merchant_city' => 'Kathmandu',
        ])->generate(150.75, 'Parse Test');

        $parsed = PaymentQRGenerator::parse($original->getQrString());

        $this->assertSame(150.75, $parsed->getData()->getAmount());
        $this->assertSame('Test Store', $parsed->getData()->getMerchantName());
        $this->assertSame('Kathmandu', $parsed->getData()->getMerchantCity());
        $this->assertSame('Parse Test', $parsed->getData()->getRemarks());
        $this->assertSame($original->getTxnId(), $parsed->getTxnId());
    }

    public function test_parses_qr_image_file(): void
    {
        $original = PaymentQRGenerator::for(PaymentQRType::Fonepay)->generate(75, 'Image Test');

        $tmp = tempnam(sys_get_temp_dir(), 'qr') . '.png';
        file_put_contents($tmp, $original->getQrImage());

        $parsed = PaymentQRGenerator::parseFromImage($tmp);

        $this->assertSame(75.0, $parsed->getData()->getAmount());
        $this->assertSame('Image Test', $parsed->getData()->getRemarks());

        unlink($tmp);
    }

    public function test_parses_tag26_07_format(): void
    {
        $original = PaymentQRGenerator::for(PaymentQRType::Fonepay, [
            'tag26_format'     => '07',
            'tag26_identifier' => 'TEST_ID_123',
            'terminal_id'      => '310871',
        ])->generate(200);

        $parsed = PaymentQRGenerator::parse($original->getQrString());

        $this->assertSame(200.0, $parsed->getData()->getAmount());
        $this->assertSame('07', $parsed->getData()->getTag26Format());
        $this->assertSame('TEST_ID_123', $parsed->getData()->getTag26Identifier());
        $this->assertSame('310871', $parsed->getData()->getTerminalId());
    }

    public function test_parsed_output_has_same_qr_string(): void
    {
        $original = PaymentQRGenerator::for(PaymentQRType::Fonepay)->generate(100);

        $parsed = PaymentQRGenerator::parse($original->getQrString());

        $this->assertSame($original->getQrString(), $parsed->getQrString());
    }

    public function test_parse_from_blob(): void
    {
        $original = PaymentQRGenerator::for(PaymentQRType::Fonepay)->generate(50);

        $parsed = PaymentQRGenerator::parseFromBlob($original->getQrImage());

        $this->assertSame(50.0, $parsed->getData()->getAmount());
    }
}
