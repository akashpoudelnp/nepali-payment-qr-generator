<?php

namespace Akashpoudelnp\NepaliPaymentQrGenerator\Tests\Unit;

use Akashpoudelnp\NepaliPaymentQrGenerator\PaymentQRGenerator;
use Akashpoudelnp\NepaliPaymentQrGenerator\Enums\PaymentQRType;
use Akashpoudelnp\NepaliPaymentQrGenerator\DTO\PaymentQRData;
use Akashpoudelnp\NepaliPaymentQrGenerator\Generators\FonepayQrGenerator;
use PHPUnit\Framework\TestCase;

class FonepayQrGeneratorTest extends TestCase
{
    public function test_generates_qr_string_with_default_format(): void
    {
        $output = PaymentQRGenerator::for(PaymentQRType::Fonepay)->generate(100, 'Test');

        $this->assertStringStartsWith('000201', $output->getQrString());
        $this->assertStringContainsString('fonepay.com', $output->getQrString());
        $this->assertStringContainsString('6304', $output->getQrString());
        $this->assertNotEmpty($output->getTxnId());
    }

    public function test_generates_qr_string_with_tag26_07_format(): void
    {
        $output = PaymentQRGenerator::for(PaymentQRType::Fonepay, [
            'tag26_format'     => '07',
            'tag26_identifier' => '2222020003108681',
            'terminal_id'      => '310871',
        ])->generate(50);

        $this->assertStringContainsString('07162222020003108681', $output->getQrString());
    }

    public function test_generates_png_image(): void
    {
        $output = PaymentQRGenerator::for(PaymentQRType::Fonepay)->generate(100);

        $this->assertNotNull($output->getQrImage());
        $this->assertStringStartsWith("\x89PNG", $output->getQrImage());
        $this->assertNotNull($output->getQrImageAsBase64());
        $this->assertStringStartsWith('data:image/png;base64,', $output->getQrImageAsDataUri());
    }

    public function test_generates_with_dto(): void
    {
        $data = PaymentQRData::new()
            ->setAmount(99.99)
            ->setRemarks('DTO Test')
            ->setMerchantName('Test Merchant');

        $output = PaymentQRGenerator::for(PaymentQRType::Fonepay)->generate($data);

        $this->assertSame(99.99, $output->getData()->getAmount());
        $this->assertSame('DTO Test', $output->getData()->getRemarks());
        $this->assertSame('Test Merchant', $output->getData()->getMerchantName());
    }

    public function test_crc16_validates(): void
    {
        $output = PaymentQRGenerator::for(PaymentQRType::Fonepay)->generate(100);
        $qr = $output->getQrString();

        $crcFromStr = substr($qr, -4);
        $body = substr($qr, 0, -4);
        $expectedCrc = FonepayQrGenerator::crc16($body);

        $this->assertSame($expectedCrc, $crcFromStr);
    }

    public function test_get_type(): void
    {
        $generator = PaymentQRGenerator::for(PaymentQRType::Fonepay);
        $this->assertSame(PaymentQRType::Fonepay, $generator->getType());
    }

    public function test_config_merges_as_defaults(): void
    {
        $output = PaymentQRGenerator::for(PaymentQRType::Fonepay, [
            'merchant_name' => 'Config Store',
            'merchant_city' => 'Config City',
        ])->generate(250, 'Config Test');

        $data = $output->getData();
        $this->assertSame('Config Store', $data->getMerchantName());
        $this->assertSame('Config City', $data->getMerchantCity());
        $this->assertSame(250.0, $data->getAmount());
        $this->assertSame('Config Test', $data->getRemarks());
    }
}
