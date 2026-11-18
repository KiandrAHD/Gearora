# GEARORA — Project Documentation Pack

Dokumen baseline untuk pengembangan **GEARORA — Outdoor Supply**.

| Dokumen | Tujuan |
|---|---|
| [PRD_GEARORA.md](./PRD_GEARORA.md) | Visi, persona, cakupan fitur, kebutuhan fungsional/nonfungsional, business rules, dan acceptance criteria |
| [ARCHITECTURE_GEARORA.md](./ARCHITECTURE_GEARORA.md) | Arsitektur aplikasi, modul, layer, domain model, alur transaksi, keamanan, dan deployment topology |
| [TECH_STACK_GEARORA.md](./TECH_STACK_GEARORA.md) | Pemilihan teknologi, kompatibilitas versi Laravel/PHP, tooling, database, dan batasan AeonFree |
| [ROADMAP_GEARORA.md](./ROADMAP_GEARORA.md) | Fase implementasi, deliverables, exit criteria, dependency, dan Definition of Done |
| [RENTAL_POLICY.md](./RENTAL_POLICY.md) | Kebijakan rental baseline MVP yang mengikat implementasi Phase 3–7 |
| [PHASE_0_1_FINAL_REPORT.md](./PHASE_0_1_FINAL_REPORT.md) | Laporan penyelesaian Phase 0 & 1 (17 November 2026) |

## Phase 5

- [PHASE_5_COMPLETION_REPORT.md](./PHASE_5_COMPLETION_REPORT.md) — hasil Phase 5 dan blocker verifikasi sandbox.

## Dokumen Phase 5

- [PHASE_5_TECHNICAL_DESIGN.md](./PHASE_5_TECHNICAL_DESIGN.md) — desain teknis, requirement map, dan ADR Phase 5.
- [MIDTRANS_INTEGRATION.md](./MIDTRANS_INTEGRATION.md) — konfigurasi gateway, webhook, keamanan, sandbox setup.
- [PAYMENT_WORKFLOW.md](./PAYMENT_WORKFLOW.md) — alur pembayaran manual dan gateway.
- [DEPOSIT_MANAGEMENT.md](./DEPOSIT_MANAGEMENT.md) — ledger, saldo, deduction, refund.
- [REFUND_POLICY_IMPLEMENTATION.md](./REFUND_POLICY_IMPLEMENTATION.md) — kebijakan kalkulasi refund.
- [HANDOVER_RETURN_INSPECTION.md](./HANDOVER_RETURN_INSPECTION.md) — pickup, return, inspeksi, pelepasan unit.
- [RENTAL_OPERATIONS.md](./RENTAL_OPERATIONS.md) — overdue dan transisi order.
- [ADDITIONAL_CHARGES.md](./ADDITIONAL_CHARGES.md) — biaya tambahan dan persetujuan.
- [PHASE_5_TEST_PLAN.md](./PHASE_5_TEST_PLAN.md) — test plan dan hasil aktual.

## Sumber utama

- Proposal konsep: <https://github.com/KiandrAHD/Gearora/blob/main/.Gearora/GEARORA.md>
- Master Homepage design reference: <https://github.com/KiandrAHD/Gearora/blob/main/.docs/Desain_Gearora.md>

## Catatan baseline

Repository yang diperiksa berisi proposal konsep dan master design reference Homepage. Belum ditemukan source aplikasi Laravel, migration, atau implementasi Admin Dashboard di root repository pada saat dokumen ini disusun. Karena itu, empat dokumen ini mendefinisikan target produk dan rancangan teknis; bukan dokumentasi dari kode backend yang sudah berjalan.

Spesifikasi publik AeonFree menampilkan PHP 8.2 dan MySQL 8.0. Versi PHP aktual pada akun, ekstensi, Composer/SSH, cron, permission, serta pemisahan direktori privat dan `public_html` masih harus diverifikasi di control panel sebelum deployment.

## Cara menggunakan

1. Review PRD dan putuskan seluruh kebijakan bisnis yang masih terbuka.
2. Validasi hosting melalui checklist Phase 0 di Roadmap.
3. Finalisasi ERD dan keputusan versi Laravel berdasarkan runtime PHP aktual.
4. Implementasikan fase satu per satu; jangan melewati quality gate inventory dan anti-overbooking.
