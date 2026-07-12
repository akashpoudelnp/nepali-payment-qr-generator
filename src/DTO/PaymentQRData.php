<?php

namespace Akashpoudelnp\NepaliPaymentQrGenerator\DTO;

class PaymentQRData
{
    private float $amount = 0.0;
    private string $remarks = 'Payment';
    private ?string $merchantPan = null;
    private string $merchantName = 'Merchant';
    private string $merchantCity = 'City';
    private string $terminalId = '497140';
    private string $fonepayId = '2109020664';
    private string $mcc = '8012';
    private string $countryCode = 'NP';
    private string $tag26Format = '01_02';
    private ?string $tag26Identifier = null;

    private function __construct() {}

    public static function new(): self
    {
        return new self();
    }

    /**
     * @param array<string, mixed> $config
     */
    public function fill(array $config): static
    {
        foreach ($config as $key => $value) {
            $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    public function getAmount(): float { return $this->amount; }
    public function setAmount(float $amount): static { $this->amount = $amount; return $this; }

    public function getRemarks(): string { return $this->remarks; }
    public function setRemarks(string $remarks): static { $this->remarks = $remarks; return $this; }

    public function getMerchantPan(): ?string { return $this->merchantPan; }
    public function setMerchantPan(?string $merchantPan): static { $this->merchantPan = $merchantPan; return $this; }

    public function getMerchantName(): string { return $this->merchantName; }
    public function setMerchantName(string $merchantName): static { $this->merchantName = $merchantName; return $this; }

    public function getMerchantCity(): string { return $this->merchantCity; }
    public function setMerchantCity(string $merchantCity): static { $this->merchantCity = $merchantCity; return $this; }

    public function getTerminalId(): string { return $this->terminalId; }
    public function setTerminalId(string $terminalId): static { $this->terminalId = $terminalId; return $this; }

    public function getFonepayId(): string { return $this->fonepayId; }
    public function setFonepayId(string $fonepayId): static { $this->fonepayId = $fonepayId; return $this; }

    public function getMcc(): string { return $this->mcc; }
    public function setMcc(string $mcc): static { $this->mcc = $mcc; return $this; }

    public function getCountryCode(): string { return $this->countryCode; }
    public function setCountryCode(string $countryCode): static { $this->countryCode = $countryCode; return $this; }

    public function getTag26Format(): string { return $this->tag26Format; }
    public function setTag26Format(string $tag26Format): static { $this->tag26Format = $tag26Format; return $this; }

    public function getTag26Identifier(): ?string { return $this->tag26Identifier; }
    public function setTag26Identifier(?string $tag26Identifier): static { $this->tag26Identifier = $tag26Identifier; return $this; }
}
