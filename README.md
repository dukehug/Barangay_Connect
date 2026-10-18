# Barangay Connect — Laravel / Filament backend

PHP 8.5, Laravel 13, Filament 5, PostgreSQL. The staff panel is `/admin` and uses the `employee` guard. The resident guard is configured; the resident-facing application is not implemented yet.

## Local setup

Install PHP with PDO PostgreSQL and Composer, and create a PostgreSQL database and user before starting.

```bash
git clone <your-repository-url>
cd barangay-connect
cp .env.example .env
composer install
php artisan key:generate
```

Edit `.env`: set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `APP_URL`, `SEED_ADMIN_EMAIL` and `SEED_ADMIN_PASSWORD`. Optional `SEED_ADMIN_FIRST_NAME` / `SEED_ADMIN_LAST_NAME` set the administrator's name. Quote passwords containing spaces or `#`. Do not commit `.env`.

```bash
php artisan config:clear
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Log in at `http://localhost:8000/admin` with the configured seed administrator. Composer publishes Filament assets automatically. For changes to the application Vite assets, also run `npm install` and `npm run build`.

Do not run `migrate:fresh` on an existing database whose data you need. The setup above uses additive migrations.

## Seeders and demo accounts

`DatabaseSeeder` always runs `AdminSeeder` and `DocumentTypeSeeder`; it runs `DemoDataSeeder` automatically only when `APP_ENV=local`.

- `AdminSeeder` reads `config/barangay.php`, not `env()` directly. Missing credentials fail with a clear message. Re-running it updates the matching administrator's password, name, role and enabled state to the configured values. Do not rerun it accidentally with outdated credentials.
- `DocumentTypeSeeder` uses the confirmed business values: **Barangay Clearance, fee 50.00, processing time 5 days, no supporting documents required**. Re-running it restores these configured values.
- `DemoDataSeeder` and the older `WorkflowDemoSeeder` reject all environments except `local` and `testing`, including direct calls. They are not production seeders.
- DemoDataSeeder adds 15 residents, 30 requests across the current and previous five calendar months, 10 complaints, and 6 announcements (3 published). It reuses residents and document types. A second run does not duplicate these records or reset their processing state.
- Local demo employee: `demo-staff@example.test` / `password`.
- Disabled local employee: `demo-inactive@example.test` / `password` (login must fail).
- Demo resident emails are `demo-resident-1@example.test` through `demo-resident-15@example.test`. The resident UI is not available yet.
- Demo attachment: `storage/app/public/demo/seed-evidence.txt`.

Seed only the confirmed document type with `php artisan db:seed --class=DocumentTypeSeeder`. Seed demo data explicitly with `php artisan db:seed --class=DemoDataSeeder`. Do not include demo credentials in production provisioning.

Factories support `BarangayEmployee::factory()->admin()` and `->inactive()`. Pending requests/complaints have no handler; other generated statuses have an employee. Published announcements have `posted_at`; drafts do not.

## Tests

Default tests use SQLite in memory and never the development PostgreSQL database:

```bash
php artisan config:clear
php artisan test
```

For PostgreSQL compatibility, create a separate database named **barangay_connect_test** on the test server. The configured DB user must own it or have permission to create/drop tables within it. Use `.env.testing` for dedicated test host/user/password settings; it is ignored by Git. Never point testing at a production server.

```bash
php artisan test --configuration=phpunit.pgsql.xml
```

`phpunit.pgsql.xml` forces the database to `barangay_connect_test` and clears `DB_URL`; connection credentials come from `.env.testing` or the environment. Tests will rebuild this database. `tests/TestCase.php` rejects cached configuration and any database except SQLite `:memory:` or PostgreSQL `barangay_connect_test`, before database-refresh traits execute. Do not run concurrent suites against the same PostgreSQL test database.

Coverage includes the complete role matrix, login/disable flow, self-lockout protection, request handler preservation, audits, dashboard date boundaries, duplicate form fields, password preservation, seed repeatability, and production demo exclusion.

## Authorization

Both roles must be active to enter the panel; authentication middleware also persists across Livewire requests. Authorization is defined in `app/Policies`, automatically discovered by Laravel.

| Resource | View | Create | Update | Delete |
| --- | --- | --- | --- | --- |
| Announcement | Admin, Staff | Admin, Staff | Admin, Staff | Admin |
| Complaint | Admin, Staff | No | Admin, Staff (status only) | Admin |
| DocumentRequest | Admin, Staff | No | Admin, Staff (status only) | Admin |
| DocumentType | Admin, Staff | Admin, Staff | Admin, Staff | Admin, only without requests |
| Resident | Admin, Staff | No | No | Admin, only without requests or complaints |
| BarangayEmployee | Admin | Admin | Admin | No |
| AuditLog | Admin | No | No | No |

No resource allows bulk deletion. Administrators cannot change their own role or enabled state through the employee form; the save hook also strips attempted changes to these fields. Blank edit passwords preserve the original hash.

## Schema / ERD handoff

The migrations are the source of truth. Names below are the actual implemented columns, not conceptual ERD aliases.

| Table | Important implemented fields / differences |
| --- | --- |
| barangay_employees | `first_name`, `middle_name`, `last_name`, `contact_number`, `email`, `password`, `role`, `is_active`, `remember_token`, timestamps |
| residents | `email_verified_at`, `birth_date`, `contact_number`, `resident_address`, `profile_image_url`, `password`, `remember_token`; registration time is `created_at` |
| document_types | `document_type_name`, **`document_type_description`**, **`document_type_fee`**, `processing_days`, `is_active`, `is_required_other_documents` |
| document_requests | `resident_id`, `document_type_id`, nullable `staff_id`, `purpose`, `status`, timestamps |
| complaints | `resident_id`, nullable `staff_id`, `complaint_type`, `content`, `attachment_url`, `status`, timestamps |
| announcements | `staff_id`, `title`, `content`, `category`, `published`, nullable `posted_at`, timestamps |
| audit_logs | `staff_id`, `action`, `table_affected`, `record_id`, **only `created_at`**; `record_id` has no foreign key |

RequestStatus: `pending`, `processing`, `ready`, `released`, `rejected`.
ComplaintStatus: `pending`, `in_progress`, `resolved`, `rejected`.
EmployeeRole: `admin`, `staff`.

These are PHP backed enums; database status columns remain strings. Eloquent rejects invalid values, but direct SQL bypasses casts and model events.

Requests and complaints retain their first handler when they leave pending. AuditObserver tracks employee-originated create/update/delete events on six models, excludes AuditLog, ignores timestamp-only / employee remember-token-only updates, and skips operations without an authenticated employee. Bulk SQL updates do not trigger it. Audit logs contain no passwords or change payloads. Real password changes are still audited as generic updates.

## Production checklist

- Configure `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, and `SESSION_SECURE_COOKIE=true`. Enforce HTTPS at the web server / load balancer. Do not change local development values to production just to test this checklist.
- Use `composer install --no-dev --optimize-autoloader`. Boost is a dev dependency; MCP is a transitive dev dependency in `composer.lock`. Neither belongs in production installation.
- Use `php artisan migrate --force`, `php artisan db:seed --force`, and `php artisan storage:link` after setting real administrator credentials. No demo seeder runs in production.
- Change the provisioned admin password after handoff. Remember that re-running AdminSeeder resets it to the configured seed password.
- Run `php artisan optimize` and `php artisan filament:optimize` after environment setup. `storage` and `bootstrap/cache` must be writable by the runtime user.
- Arrange PostgreSQL backups on the deployment infrastructure using `pg_dump`, with a protected `.pgpass` or secret store, a defined schedule, retention and off-host storage. Prove restoration into a separate restore-test database with `pg_restore` before calling backups operational. This repository does not provision a server backup schedule or HTTPS certificate.
- Before handoff, review `git status`, include new source/config/test files in a commit and push it. A local working tree containing untracked files is not a reproducible remote clone.

Resident registration, resident ownership authorization, notifications and employee self-service password changes remain separate work.
