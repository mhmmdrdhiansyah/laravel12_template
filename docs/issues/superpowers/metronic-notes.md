# Metronic 8.3.0 demo1 — View Reference Notes

Source of truth for Tasks 7–9. Every view must derive its markup from the
reference page recorded here. Asset source: `/mnt/share/template/demo1/dist`
(copied to `public/assets/` in Task 5).

## 0. Fundamentals (getting-started.html, file-structure.html)

- Required CSS order: Google font (Poppins) → `assets/plugins/global/plugins.bundle.css` → `assets/css/style.bundle.css`.
- Required JS order: `var hostUrl = "assets/";` inline → `assets/plugins/global/plugins.bundle.js` → `assets/js/scripts.bundle.js` → page vendor bundles (e.g. `assets/plugins/custom/datatables/datatables.bundle.js`) → page custom JS → `@stack('scripts')`.
- jQuery, ApexCharts, select2-required plugins all live INSIDE `plugins.bundle.js`; DataTables lives in `plugins/custom/datatables/datatables.bundle.{css,js}`.
- Admin body classes (index2.html): `<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">`.
- Auth body class (sign-in.html): `<body id="kt_body" class="bg-body">`.
- CONFIRMED on this dist: NO select2 bundled → use plain `<select multiple>`; charts optional (ApexCharts inside plugins bundle); no favicon in dist — do not reference one.

## 1. Admin layout — reference: `dist/index2.html` page shell

Outer skeleton (verbatim class names):

```html
<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
<div class="d-flex flex-column flex-root">
  <div class="page d-flex flex-row flex-column-fluid">
    <div id="kt_aside" class="aside aside-dark aside-hoverable" data-kt-drawer="true" data-kt-drawer-name="aside" ...>  <!-- sidebar -->
    <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
      <div id="kt_header" class="header align-items-stretch"> ... </div>   <!-- topbar -->
      <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
        <!-- page title + #kt_content_container.container-xxl -->
      </div>
      <div class="footer py-4 d-flex flex-lg-column" id="kt_footer"> ... </div>
    </div>
  </div>
</div>
<!-- scripts: plugins.bundle.js → scripts.bundle.js → @stack('scripts') -->
```

- Aside: logo block `#kt_aside_logo` (`aside-logo flex-column-auto`), menu wrapper `#kt_aside_menu_wrapper` (`hover-scroll-overlay-y my-5 my-lg-5` with `data-kt-scroll="true"`), menu root `.menu menu-column menu-title-gray-800 menu-state-title-primary menu-state-icon-primary menu-state-bullet-primary menu-arrow-gray-500` + `data-kt-menu="true"`.
- Menu item pattern: `.menu-item` → `a.menu-link` (`.active` for current) containing `span.menu-icon > span.svg-icon.svg-icon-2 > svg` (duotune icons) and `span.menu-title`. Section label: `.menu-content.pb-2 > span.menu-section.text-muted.text-uppercase.fs-8.ls-1`.
- Topbar: container `container-fluid d-flex align-items-stretch justify-content-between`; user menu toggle `#kt_header_user_menu_toggle` (`.d-flex.align-items-center.ms-1.ms-lg-3`); logout goes inside its dropdown menu (`.menu.menu-sub.menu-sub-dropdown` with `data-kt-menu="true"`).
- Footer: `#kt_footer` → `container-fluid d-flex flex-column flex-md-row align-items-center justify-content-between` with `.text-dark` copyright.
- Do NOT copy: search widget, notifications, quick-actions dropdowns, chat/widgets/demo scripts (`widgets.js`, `chat.js`, `create-app.js`, `upgrade-plan.js`, fullcalendar).

## 2. Dashboard — reference: `dist/index2.html` stat cards

- Card pattern: `.card.mb-5.mb-xxl-8` → `.card-header.border-0.pt-5` (h3.card-title.fw-bolder + optional `.card-toolbar`) → `.card-body`.
- Copy 2–3 simple stat cards (user count, role count). Avoid chart cards unless the ApexCharts init runs console-clean; the demo widgets require `widgets.js` which we do NOT copy — so stat cards without charts are the default.

## 3. Users / Roles / Permissions lists — reference: `dist/apps/user-management/users/list.html`

Toolbar + card:

```html
<div class="card-header border-0 pt-6">
  <div class="card-title">
    <div class="d-flex align-items-center position-relative my-1">
      <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search user"/>
    </div>
  </div>
  <div class="card-toolbar">
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add User</a>
  </div>
</div>
<div class="card-body pt-0">
  <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users"> ... </table>
</div>
```

- thead: `tr.text-start.text-muted.fw-bolder.fs-7.text-uppercase.gs-0`, `th.min-w-125px` per column, actions `th.text-end.min-w-100px`.
- tbody: `tbody.text-gray-600.fw-bold`; skip the checkbox column (not needed); user cell pattern: `td.d-flex.align-items-center` + `.symbol.symbol-circle.symbol-50px.overflow-hidden.me-3 > .symbol-label > img` (avatar; skip if none) + `.d-flex.flex-column > a.text-gray-800.text-hover-primary.mb-1` + `span` email.
- Badge: `.badge.badge-light.fw-bolder` / `.badge.badge-light-success.fw-bolder`.
- Row actions: dropdown button `a.btn.btn-light.btn-active-light-primary.btn-sm[data-kt-menu-trigger="click"]` wrapping `.menu.menu-sub.menu-sub-dropdown.menu-column.menu-rounded.menu-gray-600.menu-state-bg-light-primary.fw-bold.fs-7.w-125px.py-4[data-kt-menu="true"]` with `.menu-item.px-3 > a.menu-link.px-3` entries (Edit link, Delete submit button).
- DataTables init (adapted from `table.js`, keeps client-side search/sort, drops demo delete): id MUST be `kt_table_users` / `kt_table_roles` / `kt_table_permissions`:

```js
KTUtil.onDOMContentLoaded((function() {
  $(document.getElementById('kt_table_users')).DataTable({info:!1,order:[],pageLength:10,lengthChange:!1,columnDefs:[{orderable:!1,targets:0}]});
}));
```

- Script order on list pages only: `plugins.bundle.js` → `datatables.bundle.js` → `style/scripts.bundle.js` → `@push('scripts')`.
- DO NOT copy `export-users.js` / `add.js` / the demo's `delete_row` SweetAlert handler (client-side-only delete).

## 4. Users create/edit form — reference: `dist/documentation/base/forms/controls.html` + `forms/formvalidation/advanced.html`

- Field group: `div.fv-row.mb-10` → `label.required.fw-bold.fs-6.mb-2` (or `.form-label`) → `input.form-control.form-control-solid`.
- Laravel `@error` renders `<div class="text-danger fs-8">{{ $message }}</div>` right after the input inside the `fv-row`.
- Buttons: `button.btn.btn-primary` with `span.indicator-label` (+ optional `span.indicator-progress`); cancel `a.btn.btn-light`.
- Roles multi-select (no select2 on this dist): plain `<select class="form-select form-select-solid" multiple name="roles[]">`.

## 5. Roles edit (permission checkboxes) — reference: `dist/documentation/base/forms/controls.html` checkbox groups + list.html radio group pattern

- Checkbox: `label.form-check.form-check-custom.form-check-solid` → `input.form-check-input.me-3[type=checkbox][name="permissions[]"]` + `span.form-check-label` (or sibling label). Grid: wrap in `.row` / `.col-6` columns; group per resource with `.menu-section`-style headings (`span.menu-section.text-muted.text-uppercase.fs-8.ls-1`).

## 6. Profile — reference: `dist/pages/profile/overview.html` (card pattern) + `dist/documentation/forms/image-input.html`

- Profile card: `.card.mb-5.mb-xxl-8` → `.card-body.pt-9.pb-0` → `.d-flex.flex-wrap.flex-sm-nowrap` details row; avatar: `.symbol.symbol-100px.symbol-lg-160px.symbol-fixed.position-relative > img`.
- Sections (3): data (fv-row inputs), photo (image-input), password (fv-row inputs).
- Image-input markup:

```html
<div class="image-input image-input-outline" data-kt-image-input="true" style="background-image: url(...)">
  <div class="image-input-wrapper" style="background-image: url(...)"></div>
  <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
    <i class="bi bi-pencil-fill fs-7"></i>
    <input type="file" name="photo" accept=".png,.jpg,.jpeg" hidden />
    <input type="hidden" name="avatar_remove" value="0" />
  </label>
  <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" ...><i class="bi bi-x fs-2"></i></span>
  <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" ...><i class="bi bi-x fs-2"></i></span>
</div>
```

- `data-kt-image-input="true"` is auto-initialized by `scripts.bundle.js` — no extra JS needed. Gotcha: image-input mutates a base64 preview only; the actual file comes from the `<input type="file" name="photo">`, which is what Laravel validates/stores.

## 7. Login / forgot / reset — reference: `dist/authentication/layouts/basic/{sign-in,password-reset}.html`

- Auth shell: `<body id="kt_body" class="bg-body">` → `.d-flex.flex-column.flex-root` → `.d-flex.flex-center.flex-column.flex-column-fluid.p-10.pb-lg-20` → logo `img.h-40px` (`assets/media/logos/logo-1.svg`) → `.w-lg-500px.bg-body.rounded.shadow-sm.p-10.p-lg-15.mx-auto` → `form.form.w-100`.
- Fields: `.fv-row.mb-10` → `label.form-label.fs-6.fw-bolder.text-dark` → `input.form-control.form-control-lg.form-control-solid`.
- Submit: `button.btn.btn-lg.btn-primary.w-100` (indicator-label span).
- Forgot: single email field + `btn-lg btn-primary` + cancel `a.btn.btn-lg.btn-light-primary`.
- Reset (Fortify route): token hidden + email + password + password_confirmation fields, same fv-row pattern.
- Scripts: only `plugins.bundle.js` + `scripts.bundle.js` (do NOT copy `sign-in/general.js` or `password-reset/password-reset.js` — they bind demo validation to `#kt_sign_in_form` which we replace with real Fortify forms).
- Form field names are FIXED by Fortify: login `email`,`password`,`remember`; forgot `email`; reset `token`,`email`,`password`,`password_confirmation`. All forms `@csrf` — missing token = 419.

## Gotchas (must NOT be copied from demos)

1. Demo delete handlers (`data-kt-users-table-filter="delete_row"`, SweetAlert) — client-side only. Our delete = real `<form method="POST" ... @csrf @method('DELETE')>`.
2. Demo JS files (`widgets.js`, `chat.js`, `create-app.js`, `upgrade-plan.js`, `sign-in/general.js`, `password-reset/password-reset.js`, `export-users.js`, `add.js`, `documentation/*`) — never referenced.
3. Chart widgets need `widgets.js` — skip charts unless vanilla init proves console-clean.
4. No favicon in dist — no `<link rel="icon">` anywhere.
5. `fullcalendar` bundle is page-vendor for index2 only — not needed.
