# PHASE 9 — Security Notes

## Package checkout

- Client sends IDs, quantity, fulfillment only. No client total accepted.
- Server validates variant, fulfillment capability, current price, rental period, rental availability, and retail stock.
- `TripPackageCheckoutService` uses transaction; inner order services preserve their locking/reservation rules.
- No controller stock mutation.
- Package access, document, and PDF are ownership-protected.
- Payment status remains service/webhook-owned. Package status aggregates linked orders only.

## Guides

- Escape-first renderer prevents stored HTML/script execution.
- No raw HTML or arbitrary iframe support.
- YouTube ID pattern limits output iframe origin to `youtube-nocookie.com`.
- Remote image hotlinks rejected from inline Markdown.
- Preview endpoint is authenticated/authorized and returns only safe renderer output.

## Data safety

- New migrations additive and reversible.
- SQLite memory used PHPUnit; MySQL refresh tests hard fail outside `gearora_test`.
- Browser smoke database is disposable `storage/phase9-e2e.sqlite` and excluded from Git.
