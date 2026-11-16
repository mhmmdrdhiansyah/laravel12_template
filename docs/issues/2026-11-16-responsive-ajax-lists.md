# Responsive + AJAX Live Search Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Rules source:** `AGENTS.md` (root). Markup reference: `docs/issues/superpowers/metronic-notes.md` — baca dulu sebelum mulai.

**Goal:** Semua halaman list admin (Users, Roles, Permissions) memenuhi AGENTS.md: responsif di mobile dan search tanpa full page reload.

**Architecture:** Tidak ada dependency baru. Search tetap server-side (sudah ada di controller). Tambahan: partial view berisi kartu tabel, dirender ulang via `fetch` dengan debounce 300ms, pagination ikut, state disinkron ke query string via `history.replaceState`. Halaman tanpa server-side search (Permissions) tidak diubah perilakunya — hanya fix responsive.

**Tech Stack:** Laravel 12 blade, jQuery (sudah ada di `plugins.bundle.js`), Metronic 8.3 / Bootstrap 5 dist. Bukan Axios, bukan Alpine, bukan DataTable tambahan.

**Spec (dari analisa 2026-11-16):**

| Temuan | Task |
|---|---|
| Tabel tanpa `table-responsive` (users, roles) | Task 1 |
| Input search `w-250px` fixed, card-header tidak flex-stack | Task 1 |
| Search full page reload (users, roles) | Task 2, 3, 4 |
| Tidak ada filter loading state / abort request | Task 4 |
| Tidak ada reset pagination + query-string sync | Task 4 |
| Permissions index: DataTable client-side, cek responsive saja | Task 5 |
| Form create/edit mobile | Task 6 |
| Verifikasi viewport + test | Task 7 |

**Untuk implementer (junior / model murah):** ikuti langkah PERSIS seperti tertulis. Jangan menambah file, library, abstraksi, atau fitur di luar daftar ini. Jangan mengubah controller lain. Jika ada langkah yang tidak bisa dijalankan persis seperti tertulis, STOP dan laporkan — jangan improvisasi.

---

## Task 1 — Responsive fix statis (users + roles index)

**Target files:**
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/roles/index.blade.php`

**Change (lakukan sama persis di kedua file):**

1. Bungkus `<table>` yang ada dengan wrapper:
   ```blade
   <div class="table-responsive">
       <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
   ```
   dan tutup `</div>` setelah `</table>`. JANGAN mengubah atribut id atau class table.

2. Ganti class input search:
   - Dari: `class="form-control form-control-solid w-250px ps-14"`
   - Menjadi: `class="form-control form-control-solid w-100 w-md-250px ps-14"`

3. Ganti struktur card header:
   - Dari: `<div class="card-header border-0 pt-6">` berisi `<div class="card-title">…</div>`
   - Menjadi:
   ```blade
   <div class="card-header border-0 pt-6 d-flex flex-stack flex-wrap gap-2">
       <div class="card-title m-0"> …form search… </div>
       <div class="card-toolbar m-0"> (kosong untuk sekarang) </div>
   </div>
   ```

**Acceptance:** input search penuh di 375px, tabel bisa discroll horizontal, tidak ada layout break. Verifikasi manual di Task 7.

- [ ] users/index: table-responsive + w-md input + flex-stack header
- [ ] roles/index: idem

## Task 2 — Ekstrak partial tabel users

**Target files:**
- `resources/views/admin/users/_table.blade.php` (BARU — satu-satunya file baru yang diizinkan)
- `resources/views/admin/users/index.blade.php`

**Change:**

1. Pindahkan seluruh blok `<div class="card-body pt-0"> … </div>` (tabel + wrapper table-responsive + pagination) ke `resources/views/admin/users/_table.blade.php`. Partial ini tidak `@extends` apa pun — murni fragmen HTML.

2. Di `index.blade.php`, ganti blok yang dipindah dengan:
   ```blade
   <div id="kt_users_table_container">
       @include('admin.users._table', ['users' => $users])
   </div>
   ```

3. Di DALAM `_table.blade.php`, pastikan pagination dirender dengan:
   ```blade
   {{ $users->withQueryString()->links() }}
   ```
   (kalau pagination belum ada di index, tambahkan baris itu tepat setelah `</table>` — masih di dalam wrapper `table-responsive` tidak boleh; letakkan setelah wrapper, sebelum penutup `card-body`).

**Acceptance:** halaman users render identik seperti sebelum refactor (diff visual: tidak ada), dan `curl` halaman tetap 200.

- [ ] Partial dibuat, index include, output HTML tidak berubah

## Task 3 — Dukungan partial di controller

**Target files:**
- `app/Http/Controllers/Admin/UserController.php`
- `app/Http/Controllers/Admin/RoleController.php`

**Change (pola sama untuk dua controller, hanya view-nya beda):**

Di method `index`, ganti `return view(...)` menjadi:

```php
if ($request->boolean('partial')) {
    return view('admin.users._table', ['users' => $users]);
}

return view('admin.users.index', ['users' => $users]);
```

(Roles: `admin.roles._table` — ekstrak partial roles dengan pola Task 2 dulu, id `$roles`.)

DILARANG: menambah parameter, route baru, atau response type lain. Query string `partial=1` adalah satu-satunya sinyal.

- [ ] UserController: branch partial
- [ ] RoleController: partial roles diekstrak + branch partial

## Task 4 — Live search JS (debounce + abort + URL sync)

**Target files:**
- `resources/views/admin/users/index.blade.php` (tambah `@push('scripts')` di akhir file)
- `resources/views/admin/roles/index.blade.php` (idem, ganti selector)

**Change — tulis kode ini di kedua index (yang berbeda hanya nilai `URL` dan selector; users dicontoh):**

```blade
@push('scripts')
<script>
(function () {
    var form = document.querySelector('.card-title form');
    var input = form.querySelector('input[name="search"]');
    var container = document.getElementById('kt_users_table_container');
    var controller = null;
    var timer = null;

    function refresh() {
        if (controller) controller.abort();
        controller = new AbortController();
        var params = new URLSearchParams(window.location.search);
        if (input.value) { params.set('search', input.value); } else { params.delete('search'); }
        params.set('partial', '1');

        container.style.opacity = '0.5';
        fetch(form.action + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal
        })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
        .then(function (html) {
            container.innerHTML = html;
            container.style.opacity = '';
            var url = new URL(window.location);
            if (input.value) { url.searchParams.set('search', input.value); } else { url.searchParams.delete('search'); }
            url.searchParams.delete('partial');
            url.searchParams.delete('page');
            window.history.replaceState({}, '', url);
        })
        .catch(function (e) { if (e.name !== 'AbortError') { container.style.opacity = ''; } });
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(refresh, 300);
    });
})();
</script>
@endpush
```

**Poin yang TIDAK boleh diubah:** debounce 300ms; `AbortController` membatalkan request sebelumnya; `history.replaceState` (bukan pushState); reset `page` saat search berubah; opacity 0.5 sebagai loading state. Tidak boleh menambah library, spinner animasi, atau toast.

**Acceptance:** ketik di search → tbody berubah tanpa reload halaman (cek: console tidak ada navigasi, nilai input tidak hilang); URL berisi `?search=…`; refresh browser mempertahankan hasil; request lama dibatalkan saat mengetik cepat.

- [ ] users: live search jalan sesuai acceptance
- [ ] roles: idem

## Task 5 — Permissions: responsive only

**Target file:** `resources/views/admin/permissions/index.blade.php`

**Change:**

1. Bungkus `<table>` dengan `<div class="table-responsive">`.
2. Periksa init DataTable yang sudah ada (baris ±49, `$(document.getElementById('kt_table_permissions')).DataTable({…})`). JANGAN mengubah konfigurasinya. DataTable punya search box bawaan — biarkan.
3. Jika card-header-nya belum `d-flex flex-stack flex-wrap gap-2`, samakan dengan Task 1 langkah 3.

DILARANG mengubah Permissions menjadi server-side search — di luar scope.

- [ ] permissions: table-responsive + header flex, DataTable config utuh

## Task 6 — Form create/edit users & roles: cek mobile

**Target files:**
- `resources/views/admin/users/create.blade.php`, `resources/views/admin/users/edit.blade.php`
- `resources/views/admin/roles/create.blade.php`, `resources/views/admin/roles/edit.blade.php`

**Change — hanya perbaiki jika menemukan salah satu dari ini:**

- Grid bukan `row` + `col-*` → ubah ke `row` dan setiap field `col-12 col-md-6` (field panjang seperti password note boleh `col-12` penuh).
- Ada lebar fixed (`w-250px`, `width:` inline) pada input/select → ganti `w-100 w-md-*`.
- Tombol submit di card-footer: pastikan `d-flex flex-wrap` (bukan absolute/fixed).

DILARANG mengubah validasi, nama field, atau struktur controller.

- [ ] 4 form dicek, temuan difix, tidak ada lebar fixed di mobile

## Task 7 — Verifikasi (WAJIB sebelum selesai)

Jalankan semua, urut:

1. `docker compose exec -u www-data app php artisan test` — suite harus hijau; jika ada test yang gagal karena perubahanmu, perbaiki dulu.
2. Buka `http://localhost:8088/admin/users` login `superadmin@example.com` / `password` (sesuai README).
3. Cek 3 viewport (DevTools device toolbar): **375px, 768px, 1440px** — untuk users, roles, permissions, 1 form create/edit, **dashboard, profile, dan login**:
   - Tidak ada horizontal scroll di level halaman (tabel boleh scroll di dalam wrapper-nya sendiri).
   - Sidebar jadi drawer dengan tombol hamburger.
   - Halaman-halaman ini (dashboard, profile, login) sudah responsif secara desain — TIDAK BOLEH diedit. Jika ada yang terlihat rusak di viewport manapun, STOP dan laporkan temuan; jangan memperbaiki sendiri di luar scope task.
4. Users: ketik "super" → tabel terfilter tanpa reload; buka DevTools Network → lihat request `partial=1` dengan status 200 dan request sebelumnya `(canceled)` saat mengetik cepat; URL jadi `?search=super`; refresh → hasil tetap.
5. Roles: ulangi langkah 4.
6. Hapus semua file eksperimen/temporer yang kamu buat (tidak seharusnya ada).

**Definition of done:** semua checkbox terisi, suite hijau, langkah 2–5 terverifikasi di browser. Laporan harus menyebut hasil viewport yang benar-benar dites, bukan "seharusnya responsif".

- [ ] Test suite hijau
- [ ] Viewport 375/768/1440 dicek di 3 halaman list + 1 form + dashboard, profile, login
- [ ] Live search users & roles terverifikasi di Network tab
- [ ] Tidak ada file temporer tersisa
