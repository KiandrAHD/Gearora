# PHASE 9 — Release Checklist

## Code and tests

- [x] Phase 8 tests retained; no tests skipped or deleted.
- [x] SQLite suite green (331 tests, 1020 assertions).
- [x] MySQL InnoDB integration green on `gearora_test` (7 tests, 71 assertions).
- [x] Vite production build green.
- [x] Changed PHP formatted with Pint.
- [x] Diff whitespace check green.
- [x] Browser smoke on disposable SQLite: home, wizard, recommendation results, guides.
- [ ] Full customer checkout/payment/browser walkthrough with authenticated account.
- [ ] Production deployment smoke and rollback verification.

## Data/payment safety

- [x] No `migrate:fresh`, `db:wipe`, or `migrate:reset` against `gearora`.
- [x] Automated DB testing uses SQLite memory or `gearora_test` only.
- [x] Browser E2E uses disposable `storage/phase9-e2e.sqlite`.
- [x] No production Midtrans transaction.
- [x] Payment status remains server-owned; no client-supplied totals.
- [x] Rental/sales stock services remain system of record.

## Git

- [x] Branch: `feat/phase-9-gearora-experience`.
- [x] Milestone commits pushed; no force push.
- [ ] Merge to main requires owner decision.
