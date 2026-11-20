# PHASE 9 — Test Plan and Results

Tanggal: 20 November 2026

## Automated validation

| Check | Command | Result |
|---|---|---|
| SQLite full suite | `php artisan test --compact` | PASS — 331 tests, 1020 assertions |
| MySQL integration | `vendor/bin/phpunit -c phpunit.mysql.xml tests/MySql` | PASS — 7 tests, 71 assertions, exit 0; target enforced as `gearora_test` |
| Frontend build | `npm run build` | PASS — Vite production build |
| Code style | `vendor/bin/pint` on changed PHP files | PASS |
| Whitespace | `git diff --check` | PASS |

## Browser smoke validation

Target: local Laravel server, disposable SQLite `storage/phase9-e2e.sqlite`. No dev database writes.

| Flow | Outcome |
|---|---|
| Public home | HTTP 200, expected title and navigation visible |
| Trip Planner initial state | Five step labels, three experience choices rendered |
| Wizard interaction | Entered destination, duration, participants, mode and dates; summary updated |
| Recommendation results | POST returned results; three demo gear cards and package form rendered |
| Guides landing | HTTP 200; editorial hero and guide cards rendered |

Screenshots were recorded under `storage/e2e/` (not committed). Browser E2E
extended to authenticated checkout (transfer), package document/PDF, Guide Studio
(publish + inline image + YouTube + XSS payload), and admin trip-package monitor.
Midtrans production was not invoked. Full evidence and the mobile-viewport
limitation are in `.docs/PHASE_9_BROWSER_E2E_RESULTS.md`.

## MySQL safety

`phpunit.mysql.xml` explicitly targets `gearora_test`. Both MySQL test classes abort before refresh if connection/database mismatch. Dev DB `gearora` unchanged by MySQL test run.
