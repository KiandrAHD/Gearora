# GEARORA — Project Documentation Pack

Dokumen baseline untuk pengembangan **GEARORA — Outdoor Supply**.

| Dokumen | Tujuan |
|---|---|
| [PRD_GEARORA.md](./PRD_GEARORA.md) | Visi, persona, cakupan fitur, kebutuhan fungsional/nonfungsional, business rules, dan acceptance criteria |
| [ARCHITECTURE_GEARORA.md](./ARCHITECTURE_GEARORA.md) | Arsitektur aplikasi, modul, layer, domain model, alur transaksi, keamanan, dan deployment topology |
| [TECH_STACK_GEARORA.md](./TECH_STACK_GEARORA.md) | Pemilihan teknologi, kompatibilitas versi Laravel/PHP, tooling, database, dan batasan AeonFree |
| [ROADMAP_GEARORA.md](./ROADMAP_GEARORA.md) | Fase implementasi, deliverables, exit criteria, dependency, dan Definition of Done |

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
