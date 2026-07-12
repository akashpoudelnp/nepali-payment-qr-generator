# Nepali Payment QR Generator

Generate and parse Nepali payment QR codes (Fonepay, with more providers coming soon).

Produces EMVCo-compliant QR strings and renders them as PNG images.

## Requirements

- PHP >= 8.1
- `ext-gd` (for PNG image output)
- `ext-mbstring`

## Installation

```bash
composer require akashpoudelnp/nepali-payment-qr-generator
```

## Quick Start

```php
use Akashpoudelnp\NepaliPaymentQrGenerator\PaymentQRGenerator;
use Akashpoudelnp\NepaliPaymentQrGenerator\Enums\PaymentQRType;

$output = PaymentQRGenerator::for(PaymentQRType::Fonepay, [
    'merchant_name' => 'Your Store Name',
    'merchant_city' => 'Kathmandu',
    'fonepay_id'    => '2109020664',
    'terminal_id'   => '497140',
    'mcc'           => '8012',
    'country_code'  => 'NP',
])->generate(150.75, 'Order #1234');
```

### Output

```php
$output->getQrString();             // EMVCo QR string
$output->getTxnId();                // Auto-generated transaction ID
$output->getQrImage();              // Raw PNG binary
$output->getQrImageAsBase64();      // Base64-encoded PNG
$output->getQrImageAsDataUri();     // data:image/png;base64,...
$output->getData();                 // PaymentQRData used for generation
$output->getType();                 // PaymentQRType enum
```

### Save to file

```php
file_put_contents('qr.png', $output->getQrImage());
```

## Fonepay Tag 26 Format

Two formats are supported via config:

### `01_02` (default) — separate Fonepay ID + Terminal ID

```php
PaymentQRGenerator::for(PaymentQRType::Fonepay, [
    'tag26_format' => '01_02',
    'fonepay_id'   => '2109020664',
    'terminal_id'  => '497140',
    // ...
]);
```

### `07` — single combined identifier

```php
PaymentQRGenerator::for(PaymentQRType::Fonepay, [
    'tag26_format'     => '07',
    'tag26_identifier' => '2222020003108681',
    'terminal_id'      => '310871',
    // ...
]);
```

## Using the DTO Directly

```php
use Akashpoudelnp\NepaliPaymentQrGenerator\DTO\PaymentQRData;

$data = PaymentQRData::new()
    ->setAmount(100.50)
    ->setRemarks('Payment for order')
    ->setMerchantName('My Store')
    ->setMerchantCity('Pokhara')
    ->setFonepayId('2109020664')
    ->setTerminalId('497140')
    ->setMcc('5943');

$output = PaymentQRGenerator::for(PaymentQRType::Fonepay)->generate($data);
```

## Parsing QR Data

Parse an EMVCo string back to a DTO:

```php
$parsed = PaymentQRGenerator::parse($qrString);

echo $parsed->getData()->getAmount();       // 150.75
echo $parsed->getData()->getMerchantName(); // "Your Store Name"
echo $parsed->getTxnId();                   // TXN...
```

Parse from a QR image file:

```php
$parsed = PaymentQRGenerator::parseFromImage('payment-qr.png');
```

Parse from raw image data (e.g. uploaded file):

```php
$parsed = PaymentQRGenerator::parseFromBlob($imageData);
```

## Configuration Reference

| Key | Description | Default |
|---|---|---|
| `merchant_name` | Merchant display name | `'Merchant'` |
| `merchant_city` | Merchant city | `'City'` |
| `merchant_pan` | Optional merchant PAN | `null` |
| `fonepay_id` | Fonepay merchant ID | `'2109020664'` |
| `terminal_id` | Terminal ID | `'497140'` |
| `mcc` | Merchant Category Code | `'8012'` |
| `country_code` | ISO 3166-1 alpha-2 country | `'NP'` |
| `tag26_format` | Tag 26 format (`01_02` or `07`) | `'01_02'` |
| `tag26_identifier` | Combined identifier (for `07` format) | `null` |
| `qr_scale` | QR image scale factor | `10` |

## Architecture

```
PaymentQRGenerator       → Static facade (entry point)
├── PaymentQRType        → Enum of supported providers
├── PaymentQRData        → Input DTO (builder pattern)
└── Generators/
    └── FonepayQrGenerator → EMVCo string + image generation

PaymentQrParser          → Parse EMVCo strings & QR images
└── PaymentQROutput      → Output DTO (qrString, txnId, qrImage, data)
```

## License

MIT
