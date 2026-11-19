# Admin Navigation Architecture

> **Produk:** GEARORA — Outdoor Supply
> **Fase:** Phase 7.5
> **Tanggal:** 19 November 2026

Dokumen ini menjelaskan arsitektur sidebar admin: sumber konfigurasi, filter permission, penentuan item aktif, badge pending, dan kontrak aksesibilitas.

---

## 1. Komponen

| Berkas | Peran |
| --- | --- |
| `app/Support/Navigation/AdminNavigation.php` | Sumber tunggal definisi grup & child menu, filter permission, penentuan aktif. |
| `app/Support/Navigation/PendingIndicators.php` | Hitung angka pending untuk badge (memoized per request). |
| `resources/views/components/admin/nav-icon.blade.php` | Ikon inline SVG per `name`. |
| `resources/views/components/layouts/admin.blade.php` | Render sidebar bertingkat (Alpine) + shell halaman admin. |

---

## 2. Struktur Data

`AdminNavigation::groups()` mengembalikan array grup. Dua bentuk:

**Standalone** (tanpa submenu, mis. Dashboard):

```php
[
    'id' => 'dashboard',
    'label' => 'Dashboard',
    'icon' => 'dashboard',
    'standalone' => true,
    'route' => 'admin.dashboard',
    'match' => 'admin.dashboard',
    'permission' => 'dashboard.view',
    'children' => [],
]
```

**Grup dengan child:**

```php
[
    'id' => 'catalog',
    'label' => 'Katalog & Inventaris',
    'icon' => 'catalog',
    'children' => [
        ['label' => 'Inventaris', 'route' => 'admin.inventory.index', 'match' => 'admin.inventory.*', 'permission' => 'inventory.view'],
        // ...
    ],
]
```

Field child:

| Field | Wajib | Keterangan |
| --- | --- | --- |
| `label` | ya | Teks menu. |
| `route` | ya | Nama route Laravel. |
| `match` | ya | Pola `routeIs()` untuk deteksi aktif. |
| `permission` | salah satu | Permission tunggal. |
| `any` | salah satu | Daftar permission; lolos bila salah satu dimiliki. |
| `report` | tidak | Nilai `report` pada query, untuk membedakan child laporan. |
| `badge` | tidak | Kunci badge di `PendingIndicators`. |

`permission` dan `any` saling eksklusif per child; `allowsChild()` mendahulukan `any`.

---

## 3. Filter Permission

`AdminNavigation::allows(?User $user, ?string $permission)`:

- `false` bila `$user` null atau `$permission` null.
- `true` bila Owner (bypass).
- selain itu `$user->hasPermission($permission)`.

`AdminNavigation::forUser(?User $user)`:

1. Standalone: tampilkan hanya bila `allows()`.
2. Grup: filter child lewat `allowsChild()`; bila tidak ada child yang lolos, grup disembunyikan.

Sifat yang dijamin: **tidak ada elemen menu setengah-tampil**; grup kosong hilang otomatis.

---

## 4. Penentuan Item Aktif

- `isActive($item)`: cek `request()->routeIs($item['match'])`. Bila item punya `report`, aktif hanya bila `activeReport()` sama.
- `activeReport()`: baca `?report=` (default `summary`), validasi ke `reportPermissionsByName()`, fallback `summary`.
- `isGroupActive($group)`: `true` bila salah satu child aktif; standalone memakai `isActive()` langsung.

`forUser()` dipanggil di layout; layout lalu menyusun `$activeGroups` (map id → bool) dan meneruskannya ke Alpine sebagai `openGroups`, sehingga grup aktif otomatis terbuka.

---

## 5. Badge Pending

`PendingIndicators::forUser($user)` menghitung count per kunci. Cache statis di-key `userId|owner|staff` agar hanya sekali per request. Badge hanya dirender bila `> 0` dan child memang boleh diakses pengguna (karena item yang disembunyikan tidak dirender).

`PendingIndicators::flush()` tersedia untuk pemakaian pada test.

---

## 6. Kontrak Aksesibilitas & Perilaku

Dipertahankan di layout dan diverifikasi test:

| Kontrak | Atribut/Perilaku |
| --- | --- |
| Toggle grup | `@click="openGroups[id] = !openGroups[id]"`, `:aria-expanded`, `aria-controls="admin-nav-<id>"` |
| Panel submenu | `id="admin-nav-<id>"`, `x-show`, `x-cloak` |
| Item aktif | `aria-current="page"` |
| Grup aktif | `data-group-active="true"` pada toggle |
| Sidebar mobile | `id="admin-sidebar"`, tombol `aria-controls="admin-sidebar"` |
| Escape | `@keydown.escape.window="sidebarOpen = false"` |
| Scroll lock | `x-effect="document.body.classList.toggle('nav-open', sidebarOpen)"` |
| Skip link | `#admin-content` |

---

## 7. Menambah Menu Baru

1. Tambah entri di `AdminNavigation::groups()` dengan `route` dan `match` yang benar.
2. Pastikan route terdaftar (test `test_every_navigation_route_exists` akan gagal bila belum).
3. Bila ingin badge, tambah kunci di `PendingIndicators::forUser()` dan set `badge` pada child.
4. Tambah ikon baru di `nav-icon.blade.php` bila `icon` belum dikenal.
5. Jalankan `php artisan test --filter=AdminNavigationTest`.
