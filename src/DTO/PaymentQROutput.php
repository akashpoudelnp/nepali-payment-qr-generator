<?php

namespace Akashpoudelnp\NepaliPaymentQrGenerator\DTO;

use Akashpoudelnp\NepaliPaymentQrGenerator\Enums\PaymentQRType;

class PaymentQROutput
{
    public function __construct(
        private readonly string     $qrString,
        private readonly string     $txnId,
        private readonly PaymentQRType $type,
        private readonly ?string    $qrImage = null,
        private readonly ?PaymentQRData $data = null,
    ) {}

    public function getQrString(): string
    {
        return $this->qrString;
    }

    public function getTxnId(): string
    {
        return $this->txnId;
    }

    public function getType(): PaymentQRType
    {
        return $this->type;
    }

    public function getQrImage(): ?string
    {
        return $this->qrImage;
    }

    public function getData(): ?PaymentQRData
    {
        return $this->data;
    }

    public function getQrImageAsBase64(): ?string
    {
        if ($this->qrImage === null) {
            return null;
        }
        return base64_encode($this->qrImage);
    }

    public function getQrImageAsDataUri(): ?string
    {
        if ($this->qrImage === null) {
            return null;
        }
        return 'data:image/png;base64,' . base64_encode($this->qrImage);
    }
}
