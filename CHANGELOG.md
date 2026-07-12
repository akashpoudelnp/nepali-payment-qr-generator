# Changelog

## v1.0.0 — 2026-07-12

- Initial release
- Fonepay EMVCo QR generation (tag26 `01_02` and `07` formats)
- QR code image rendering (PNG) via `chillerlan/php-qrcode`
- EMVCo string parser — reconstructs `PaymentQRData` from QR strings
- QR image parser — decode PNG images back to `PaymentQRData`
- `PaymentQROutput` DTO with `data:` URI support
- `PaymentQRGenerator` facade with fluent builder API
