# Laravel 12 Admin Panel Template

Reusable starter template: clone → `docker compose up` → working login + Metronic admin panel with users/roles/permissions management and profile page.

**Stack:** Laravel 12, PHP 8.3 (fpm-alpine), Fortify, Spatie Laravel Permission v6, Metronic 8.3 demo1 (Bootstrap 5 dist), nginx-alpine, MySQL 8.4 (external container).

## Preconditions

- Docker Engine + compose plugin.
- An existing MySQL 8.4 container on a docker network named `mysql_net` (database `app`, user `app`, password `app` — **DEV-ONLY** defaults; adjust `.env` if yours differ).
- Host user uid/gid `1000` (default on single-user Linux). If different, build with `--build-arg UID=<your-uid>` (see `docker-compose.yml` build args).
- No host PHP/composer needed — everything runs in-container.
- No Metronic source needed — assets are committed under `public/assets/`.

## Setup (exact order, all in-container)

```bash
cp .env.example .env            # adjust DB_* if your MySQL differs
docker compose up -d --build
docker compose exec -u www-data app composer install
docker compose exec -u www-data app php artisan key:generate
docker compose exec -u www-data app php artisan migrate:fresh --seed
docker compose exec -u www-data app php artisan storage:link
```

## Login

`http://localhost:8088/login` (host port is `8088:80` in `docker-compose.yml` — override it there if that port is taken on your machine, e.g. a project already bound to it)

| Account | Password | Access |
|---|---|---|
| `superadmin@example.com` | `password` | Everything (via `Gate::before`) |
| `user@example.com` | `password` | Dashboard + own profile only |

## Roles & permissions

- Exactly 9 permissions: `users.{view,create,edit,delete}`, `roles.{view,create,edit,delete}`, `permissions.view`.
- `super-admin` bypasses all checks via `Gate::before` — no per-permission assignment.
- Role `user` has zero permissions: sidebar shows only Dashboard + Profile; `/admin/users` etc. return 403.
- Route middleware gates `.view` per resource; controllers authorize `create`/`edit`/`delete` per action.

## Forgot password

Mail goes to `storage/logs/laravel.log` (`MAIL_MAILER=log`). Copy the reset URL from that file into the browser.

## Notes

- `DB_HOST=mysql` is the container-network hostname of the external MySQL — never a hard-coded credential.
- Profile photos upload to `storage/app/public/avatars` (2MB max, jpg/jpeg/png) served via the `public/storage` symlink.
- All composer/artisan commands run as `docker compose exec -u www-data app ...` (pool user rebuilt to uid 1000 so bind-mount writes stay host-owned).
