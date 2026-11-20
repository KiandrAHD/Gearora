# PHASE 9 — MySQL Integration Report

Tanggal: 20 November 2026

## Scope

Opt-in MySQL 8 tests run only against `gearora_test`:

```
vendor/bin/phpunit -c phpunit.mysql.xml tests/MySql
```

Both MySQL test classes call `beforeRefreshingDatabase()` and abort unless driver is `mysql` and database is exactly `gearora_test`. Dev database `gearora` was not refreshed or modified by this run.

## Result

| Class | Test | Result |
|---|---|---|
| `MySqlConcurrencyTest` | InnoDB row lock blocks second connection | PASS |
| `MySqlCrudIntegrationTest` | InnoDB schema + foreign keys | PASS |
| `MySqlCrudIntegrationTest` | Payment/operations schema | PASS |
| `MySqlCrudIntegrationTest` | Product/variant CRUD persistence | PASS |
| `MySqlCrudIntegrationTest` | Safe delete/deactivation rules | PASS |
| `MySqlTripPackageIntegrationTest` | `trip_packages` InnoDB + FKs + JSON snapshot | PASS |
| `MySqlTripPackageIntegrationTest` | Mixed checkout commits both orders via savepoints | PASS |

Totals: **7 tests / 71 assertions**, direct `vendor/bin/phpunit` exit code **0**.

Note: `php artisan test --configuration=phpunit.mysql.xml` on this Laravel/PHPUnit combination emits a harmless "Option --configuration cannot be used more than once" warning and returns exit 1 even though every test passes. Use `vendor/bin/phpunit -c phpunit.mysql.xml tests/MySql` for a trustworthy exit code.

## Notes

- `trip_packages` is InnoDB with foreign keys to `users`, `rental_orders`, and `sales_orders`.
- `plan_snapshot` stores JSON.
- Mixed package checkout proves the nested `DB::transaction` calls behave as savepoints on InnoDB: rental and sales orders plus the stock reservation all commit together.