# Nepali Payment QR Generator

[![Latest Version](https://img.shields.io/packagist/v/akashpoudelnp/nepali-payment-qr-generator?style=flat-square)](https://packagist.org/packages/akashpoudelnp/nepali-payment-qr-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/akashpoudelnp/nepali-payment-qr-generator?style=flat-square)](https://packagist.org/packages/akashpoudelnp/nepali-payment-qr-generator)
[![PHP Version](https://img.shields.io/packagist/php-v/akashpoudelnp/nepali-payment-qr-generator?style=flat-square)](https://packagist.org/packages/akashpoudelnp/nepali-payment-qr-generator)
[![License](https://img.shields.io/github/license/akashpoudelnp/nepali-payment-qr-generator?style=flat-square)](https://github.com/akashpoudelnp/nepali-payment-qr-generator/blob/main/LICENSE)
[![GitHub Stars](https://img.shields.io/github/stars/akashpoudelnp/nepali-payment-qr-generator?style=flat-square)](https://github.com/akashpoudelnp/nepali-payment-qr-generator)

Generate and parse Nepali payment QR codes (Fonepay, with more providers coming soon).

Produces **EMVCo-compliant QR strings** and renders them as **PNG images**. The parser can decode both EMVCo strings and QR images back into structured data.

## Requirements

- PHP >= 8.1
- `ext-gd` (for PNG image output)
- `ext-mbstring`

## Installation

```bash
composer require akashpoudelnp/nepali-payment-qr-generator
```

---

## Quick Start

```php
use Akashpoudelnp\NepaliPaymentQrGenerator\PaymentQRGenerator;
use Akashpoudelnp\NepaliPaymentQrGenerator\Enums\PaymentQRType;

$output = PaymentQRGenerator::for(PaymentQRType::Fonepay, [
    'merchant_name' => 'Himalaya Tea Shop',
    'merchant_city' => 'Kathmandu',
    'fonepay_id'    => '2109020664',
    'terminal_id'   => '497140',
    'mcc'           => '5499',
    'country_code'  => 'NP',
])->generate(250.00, 'Order INV-2024-001');
```

### Output Methods

```php
$output->getQrString();             // "0002010102122639..."
$output->getTxnId();                // "TXN863412"
$output->getQrImage();              // Raw PNG binary string
$output->getQrImageAsBase64();      // Base64-encoded PNG
$output->getQrImageAsDataUri();     // "data:image/png;base64,..."
$output->getData();                 // PaymentQRData used to generate
$output->getType();                 // PaymentQRType::Fonepay
```

### Save to file

```php
file_put_contents('payment-qr.png', $output->getQrImage());
```

### Embed directly in HTML

```php
echo '<img src="' . $output->getQrImageAsDataUri() . '" alt="Scan to pay">';
```

---

## How EMVCo QR Works

The QR code does **not** contain a URL or image. It stores a **TLV (Tag-Length-Value)** string that payment apps parse to process the transaction.

### Structure

```
00 02  01          → Payload Format Indicator (always "01")
01 02  12          → Point of Initiation Method ("12" = dynamic)
26 39  [data...]   → FonePay-specific data (see below)
52 04  8012        → Merchant Category Code (MCC)
53 03  524         → Currency (524 = NPR)
54 05  250.00      → Amount
58 02  NP          → Country Code
59 14  [name...]   → Merchant Name
60 09  [city...]   → Merchant City
62 24  [data...]   → Additional data (terminal ID, txn ID, remarks)
63 04  A1B2        → CRC16-CCITT checksum
```

Each tag is: **2 chars tag + 2 chars length (decimal) + N chars value**.

---

## Fonepay Tag 26 — Two Formats

The format is **auto-detected** — set `tag26_identifier` for format `07`, or omit it for default format `01_02`.

### Format `01_02` (default) — Separate IDs

Tag 26 contains sub-tags with individual Fonepay ID and Terminal ID.

```php
PaymentQRGenerator::for(PaymentQRType::Fonepay, [
    'fonepay_id'   => '2109020664',
    'terminal_id'  => '497140',
]);
```

**Example generated QR string:**
```
000201010212
26 39  0011fonepay.com 01102109020664 0206497140
5204 5499
5303 524
5407 250.00
5802 NP
5911 Himalaya Tea
6009 Kathmandu
62 24  0706497140 0209TXN863412 0814Order INV-2024
6304 A1B2
```

Broken down:
```
Tag 26 length=39
├── 00 length=11 → "fonepay.com"       (FonePay URL)
├── 01 length=10 → "2109020664"        (FonePay merchant ID)
└── 02 length=06 → "497140"            (Terminal ID)
```

### Format `07` — Combined Identifier

Tag 26 uses a single combined merchant identifier instead of separate sub-tags.

```php
PaymentQRGenerator::for(PaymentQRType::Fonepay, [
    'tag26_identifier' => '2222020003108681',
    'terminal_id'      => '310871',
]);
```

**Example generated QR string:**
```
000201010212
26 35  0011fonepay.com 07162222020003108681
5204 5943
5303 524
5407 200.00
5802 NP
5930 Poudel Radio and Watch Service
6013 Ratnanagar MC
62 24  0706310871 0209TXN863586 0807Payment
6304 62FB
```

Broken down:
```
Tag 26 length=35
├── 00 length=11 → "fonepay.com"              (FonePay URL)
└── 07 length=16 → "2222020003108681"         (Combined identifier)
```

### How to Identify Which Format

| Criterion | `01_02` | `07` |
|---|---|---|
| Sub-tags inside tag 26 | `01` + `02` | `07` only |
| QR string snippet | `2639...0110...0206` | `2635...0011...0716` |
| Typical identifier | 10-digit Fonepay ID + 6-digit terminal | 16-digit combined ID |

---

## Parsing QR Data

The parser reverses the EMVCo string back into structured data via `PaymentQROutput`.

### From an EMVCo String

```php
$parsed = PaymentQRGenerator::parse('0002010102122635...');
```

### From a QR Image File

```php
$parsed = PaymentQRGenerator::parseFromImage('scan-me.png');
```

### From Raw Image Data (e.g., uploaded file)

```php
$parsed = PaymentQRGenerator::parseFromBlob($imageBinary);
```

### Parsed Output Example

Given a QR generated with:
```php
$output = PaymentQRGenerator::for(PaymentQRType::Fonepay, [
    'merchant_name' => 'Himalaya Tea Shop',
    'merchant_city' => 'Kathmandu',
    'fonepay_id'    => '2109020664',
    'terminal_id'   => '497140',
    'mcc'           => '5499',
])->generate(250.00, 'Order INV-2024-001');
```

Parsing it back:
```php
$parsed = PaymentQRGenerator::parse($output->getQrString());

$data = $parsed->getData();

$data->getAmount();                    // 250.0
$data->getRemarks();                   // "Order INV-2024-001"
$data->getMerchantName();             // "Himalaya Tea Shop"
$data->getMerchantCity();             // "Kathmandu"
$data->getFonepayId();                // "2109020664"
$data->getTerminalId();               // "497140"
$data->getMcc();                      // "5499"
$data->getCountryCode();              // "NP"
$data->getTag26Format();              // "01_02"
$data->getTag26Identifier();          // null (only set for "07" format)
$data->getMerchantPan();              // null (only set if present in QR)

$parsed->getTxnId();                  // "TXN863412" (from tag 62 sub-tag 02)
$parsed->getQrString();               // Original EMVCo string
$parsed->getType();                   // PaymentQRType::Fonepay
```

### Parse → Modify → Re-generate

```php
// Parse an existing QR
$parsed = PaymentQRGenerator::parseFromImage('existing-qr.png');
$data = $parsed->getData();

// Change amount and re-generate
$data->setAmount(500.00);
$data->setRemarks('Partial payment');

$newOutput = PaymentQRGenerator::for(PaymentQRType::Fonepay)->generate($data);
file_put_contents('updated-qr.png', $newOutput->getQrImage());
```

---

## Using the DTO Directly

`PaymentQRData` uses a fluent builder pattern. Every field has a sensible default — you only need to override what matters.

```php
use Akashpoudelnp\NepaliPaymentQrGenerator\DTO\PaymentQRData;

$data = PaymentQRData::new()
    ->setAmount(500.00)
    ->setRemarks('Gift')
    ->setMerchantName('Mountain Books')
    ->setMerchantCity('Pokhara')
    ->setFonepayId('2109020664')
    ->setTerminalId('123456')
    ->setMcc('5942');

$output = PaymentQRGenerator::for(PaymentQRType::Fonepay)->generate($data);
```

---

## Full End-to-End Example

```php
<?php

require 'vendor/autoload.php';

use Akashpoudelnp\NepaliPaymentQrGenerator\PaymentQRGenerator;
use Akashpoudelnp\NepaliPaymentQrGenerator\Enums\PaymentQRType;
use Akashpoudelnp\NepaliPaymentQrGenerator\DTO\PaymentQRData;

// 1. Merchant configuration (usually from .env or config)
$config = [
    'merchant_name'    => 'Yeti Electronics',
    'merchant_city'    => 'Bharatpur',
    'tag26_identifier' => '9988776655443322',
    'terminal_id'      => '310871',
    'mcc'              => '5732',
];

// 2. Generate QR for a transaction
$output = PaymentQRGenerator::for(PaymentQRType::Fonepay, $config)
    ->generate(1299.00, 'iPhone 15 back cover');

printf("EMVCo: %s\n", $output->getQrString());
printf("TxnID: %s\n", $output->getTxnId());

// 3. Display in browser
echo '<img src="' . $output->getQrImageAsDataUri() . '">';

// 4. Later, parse the same QR back
$parsed = PaymentQRGenerator::parseFromImage('scan.png');

$parsedData = $parsed->getData();
printf("Paid: NPR %.2f to %s\n",
    $parsedData->getAmount(),
    $parsedData->getMerchantName()
);
// Output: Paid: NPR 1299.00 to Yeti Electronics
```

---

## Configuration Reference

| Key | Description | Default |
|---|---|---|
| `merchant_name` | Merchant display name (tag 59) | `'Merchant'` |
| `merchant_city` | Merchant city (tag 60) | `'City'` |
| `merchant_pan` | Optional merchant PAN (tag 37) | `null` |
| `fonepay_id` | FonePay merchant ID (tag 26/01) | `'2109020664'` |
| `terminal_id` | Terminal ID (tag 26/02 & tag 62/07) | `'497140'` |
| `mcc` | Merchant Category Code (tag 52) | `'8012'` |
| `country_code` | ISO 3166-1 alpha-2 country (tag 58) | `'NP'` |
| `tag26_format` | Auto-detected from `tag26_identifier`. Set automatically when parsing a QR. | `'01_02'` |
| `tag26_identifier` | Combined identifier (tag 26/07). When set, format `07` is used automatically. | `null` |
| `qr_scale` | QR image pixel scale factor | `10` |

### Common MCC Codes

| MCC | Category |
|---|---|
| `5499` | Grocery / Convenience Store |
| `5732` | Electronics |
| `5812` | Restaurant |
| `5942` | Bookstore |
| `5943` | Watch / Jewellery |
| `5999` | General Retail |
| `8012` | Doctor / Clinic |
| `8099` | Health Services |

---

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

### Data Flow

```
                     ┌──────────────────┐
                     │  PaymentQRData   │
                     │  (input DTO)     │
                     └────────┬─────────┘
                              │
              ┌───────────────┴───────────────┐
              │                               │
    ┌─────────▼─────────┐         ┌───────────▼───────────┐
    │  FonepayQrGenerator│         │   PaymentQrParser    │
    │  (generate)        │         │   (parse)            │
    └─────────┬─────────┘         └───────────┬───────────┘
              │                               │
    ┌─────────▼─────────┐         ┌───────────▼───────────┐
    │  PaymentQROutput  │◄────────│  EMVCo string /       │
    │  (qrString,       │         │  QR image             │
    │   txnId,          │         └───────────────────────┘
    │   qrImage,        │
    │   data)           │
    └───────────────────┘
```

## License

MIT
