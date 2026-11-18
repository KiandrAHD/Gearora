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

## Dokumen Phase 6

- [PHASE_6_TECHNICAL_DESIGN.md](./PHASE_6_TECHNICAL_DESIGN.md) — desain teknis Trip Planner, aturan, CMS panduan, keamanan konten.
- [TRIP_RECOMMENDATION_ENGINE.md](./TRIP_RECOMMENDATION_ENGINE.md) — pipeline rekomendasi deterministik dan kontraknya.
- [TRIP_RECOMMENDATION_RULES.md](./TRIP_RECOMMENDATION_RULES.md) — panduan penyusunan aturan, prioritas, dan contoh.
- [EQUIPMENT_GUIDES_CMS.md](./EQUIPMENT_GUIDES_CMS.md) — lifecycle panduan, kategori, sumber, upload, XSS.
- [PHASE_6_TEST_PLAN.md](./PHASE_6_TEST_PLAN.md) — test plan Phase 6.

## Dokumen Phase 7

- [PHASE_7_TECHNICAL_DESIGN.md](./PHASE_7_TECHNICAL_DESIGN.md) — desain retail sales dan laporan expanded.
- [ADR_SHARED_PAYMENT_FOR_RETAIL.md](./ADR_SHARED_PAYMENT_FOR_RETAIL.md) — keputusan gateway pembayaran bersama.
- [RETAIL_SALES_WORKFLOW.md](./RETAIL_SALES_WORKFLOW.md) — alur customer dan admin retail.
- [SALES_STOCK_RESERVATION.md](./SALES_STOCK_RESERVATION.md) — reservasi stok, konsumsi, anti-overselling.
- [REPORT_DEFINITIONS.md](./REPORT_DEFINITIONS.md) — definisi metrik laporan dan CSV.
- [PHASE_7_TEST_PLAN.md](./PHASE_7_TEST_PLAN.md) — test plan Phase 7 dan blocker verifikasi.
- [PHASE_6_7_COMPLETION_REPORT.md](./PHASE_6_7_COMPLETION_REPORT.md) — laporan penyelesaian Phase 6/7 (18 November 2026).

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
