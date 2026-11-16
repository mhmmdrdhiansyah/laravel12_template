# Agent Rules — Project Template

Wajib dibaca sebelum menulis/mengubah code. Pelanggaran = pekerjaan belum selesai.

## 1. Responsive — lengkap sejak awal

Setiap tampilan yang dibuat HARUS responsif sejak commit pertama. Dilarang
membuat tampilan desktop-only lalu menunda mobile ke "nanti".

Checklist wajib untuk setiap view (stack: Metronic 8.3 / Bootstrap 5):

- Layout shell: gunakan pola `layouts.admin.blade.php` (aside drawer,
  `data-kt-drawer-*`, toggle mobile). Jangan buat layout baru.
- Setiap `<table>` WAJIB dibungkus `<div class="table-responsive">`.
- Lebar input/select di toolbar: pakai `w-100 w-md-250px w-lg-300px` —
  dilarang `w-250px` fixed di mobile.
- Header card: `card-header` pakai `d-flex flex-stack flex-wrap gap-2`
  (title kiri, toolbar/search/filter kanan, turun rapi di mobile).
- Toolbar halaman (tombol Add, dsb): `d-flex flex-wrap` + spacing
  `mb-0 mb-lg-7` mengikuti pola yang sudah ada.
- Form (create/edit): grid `row` + `col-12 col-md-6` dsb; dilarang kolom
  fixed-width yang overflow di layar < 768px.
- Verifikasi: jalankan halaman dan cek di viewport ≤ 375px, 768px, ≥ 1200px
  sebelum menganggap tugas selesai.

## 2. Search / Filter / Live interaction — wajib AJAX-style

Setiap list page (index) HARUS punya search/filter yang bekerja TANPA full
page reload (ibarat AJAX):

- Backend: controller index menerima `Request`, filter via `when()` +
  `paginate()->withQueryString()`. Untuk AJAX, tambahkan dukungan
  `Accept: application/json` atau partial view (render hanya tbody/pagination)
  — pilih satu pola dan konsisten.
- Frontend: input search dengan debounce (±300ms) via `fetch`/jQuery
  `$.ajax`, update tbody + pagination tanpa reload. Feedback loading state
  (spinner/disabled) saat request berjalan.
- Filter dropdown (status, role, dsb) jika relevan: reset ke halaman 1 saat
  filter berubah.
- Server-side untuk data besar; DataTable client-side hanya boleh untuk
  dataset kecil dan statis.
- URL tetap shareable: sinkronkan state search/filter ke query string
  (`history.replaceState`) — refresh tidak boleh menghilangkan filter.

## 3. Sumber referensi

- Markup Metronic: `docs/issues/superpowers/metronic-notes.md`.
- Jangan pakai asset/widget Metronic yang tidak disalin ke `public/assets/`
  (lihat daftar "Do NOT copy" di notes §1).
