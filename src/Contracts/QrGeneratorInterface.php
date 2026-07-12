<?php

namespace Akashpoudelnp\NepaliPaymentQrGenerator\Contracts;

use Akashpoudelnp\NepaliPaymentQrGenerator\DTO\PaymentQRData;
use Akashpoudelnp\NepaliPaymentQrGenerator\DTO\PaymentQROutput;

interface QrGeneratorInterface
{
    public function generate(PaymentQRData $data): PaymentQROutput;
}
