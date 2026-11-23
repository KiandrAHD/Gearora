# GEARORA Full QA — Automated Test Results

Tanggal eksekusi: 2026-11-23
Environment: PHP 8.5.9, Laravel 12.72.0, PHPUnit 11.5.58.

## Default suite (SQLite in-memory)

```bash
php artisan test --compact
```

```
Tests:    336 passed (1048 assertions)
Duration: 44.76s
```

Baseline sebelum follow-up: 331 passed (1020 assertions).
Delta: +5 test, +28 assertions (departure time, date derivation, flexible mode, package document payment state).

## MySQL integration suite (`gearora_test` only)

```bash
vendor/bin/phpunit -c phpunit.mysql.xml tests/MySql
```

```
OK (7 tests, 71 assertions)
Time: 00:31.307
```

## Targeted security suites

```bash
php artisan test --filter='CsrfSecurityTest|RateLimitSecurityTest|CustomerOwnershipSecurityTest|GuideContentRendererTest|EquipmentGuideTest|AdminGuideStudioTest|AdminRouteAuthorizationTest|ErrorPageRenderingTest|CsvReportExportTest|RbacAndBookingTest|AdminTripPackageTest' --compact
```

```
Tests:    68 passed (193 assertions)
```

## Asset build

```
vite v6.4.6 building for production...
public/build/assets/app-HgnaKh-y.css   104.00 kB │ gzip: 20.37 kB
public/build/assets/app-DCDA2-qu.js    122.02 kB │ gzip: 43.95 kB
✓ built in 1.44s
```

## Code style

```
vendor/bin/pint --dirty => passed
```
