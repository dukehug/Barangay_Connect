# Barangay Connect

A barangay service portal with a resident-facing application and a staff administration panel. Residents can register, request documents, submit complaints, read announcements, and track their applications. Staff review requests and complaints through Filament at `/admin`.

The project runs on PHP 8.5, Laravel 13, Filament 5, and PostgreSQL. The frontend uses Blade, Livewire, Tailwind CSS 4, Vite 8, and Leaflet 1.9.4. Private attachments use Spatie Laravel Media Library 11 with image and PDF previews. Use `composer.lock` and `package-lock.json` for reproducible dependency versions.

## Features

- **Resident portal:** registration, login with remember-me, profile and photo updates, published announcements, dashboard, activity history, and searchable document and complaint lists.
- **Document requests:** Barangay Clearance, Barangay Certification, and Request for Use / Closure, with supporting uploads, reference numbers, review notes, revision requests, and event history.
- **Office workflows:** payment and receipt recording, manual RBI verification, printing, officer signatures, pickup, and release tracking.
- **Resource reservations:** street use or closure and equipment borrowing, availability checks, partial arrangements requiring resident confirmation, and equipment issue/return tracking.
- **Complaints:** configurable complaint types, optional evidence and map location, staff review messages, and status tracking.
- **Administration:** announcements with rich content, dashboard statistics, global search, employee accounts, role permissions, and audit logs.

## Local setup

Install PHP 8.5 with PDO PostgreSQL, Composer, and Node.js 22.12+ with npm. Enable PDO SQLite for the default test suite. Create a PostgreSQL database and user before starting.

```bash
git clone <your-repository-url>
cd barangay-connect
cp .env.example .env
composer install
php artisan key:generate --no-interaction
```

Edit `.env`: set `APP_NAME`, `APP_URL`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `SEED_ADMIN_EMAIL`, and `SEED_ADMIN_PASSWORD`. Keep `DB_CONNECTION=pgsql`. Optional `SEED_ADMIN_FIRST_NAME` / `SEED_ADMIN_LAST_NAME` set the administrator's name, and `BARANGAY_OFFICE_NAME` overrides the office name in `config/barangay.php`. Quote passwords containing spaces or `#`. Do not commit `.env`.

```bash
php artisan config:clear --no-interaction
php artisan migrate --seed --no-interaction
php artisan storage:link --no-interaction
npm ci
npm run build
composer run dev
```

`composer run dev` starts the application server, queue listener, log viewer, and Vite development server. For serving built assets without these development processes, use `php artisan serve --no-interaction` instead. Composer publishes Filament assets automatically; the resident portal also requires the Vite build or a running Vite development server.

| Entry point | URL |
| --- | --- |
| Resident login | `http://localhost:8000/` |
| Resident registration | `http://localhost:8000/register` |
| Resident dashboard | `http://localhost:8000/dashboard` |
| Staff panel | `http://localhost:8000/admin` |

Log in to the staff panel with the configured seed administrator. Resident authentication uses the `resident` guard; staff authentication uses the separate `employee` guard.

Run `php artisan schedule:work --no-interaction` in a separate terminal when testing confirmation expiry and temporary-upload cleanup. The development command does not start the scheduler.

Do not run `migrate:fresh` on an existing database whose data you need. The setup above uses additive migrations.

## Seeders and demo accounts

`DatabaseSeeder` always runs `AdminSeeder`, `DocumentTypeSeeder`, and `ComplaintTypeSeeder`; it runs `DemoDataSeeder` automatically only when `APP_ENV=local`.

- `AdminSeeder` reads `config/barangay.php`, not `env()` directly. Missing credentials fail with a clear message. Re-running it updates the matching administrator's password, name, role and enabled state to the configured values. Do not rerun it accidentally with outdated credentials.
- `DocumentTypeSeeder` restores the three document types from `config/barangay.php`. Barangay Clearance has a fee of **50.00** and a processing time of **5 days**. Barangay Certification and Request for Use / Closure have a fee of **0.00** and no configured processing-day estimate. All three enable supporting documents; required upload kinds depend on the application.
- `ComplaintTypeSeeder` creates Noise, Sanitation, Public Safety, Roads and Infrastructure, Barangay Services, and Other without overwriting existing types.
- `ResourceItemSeeder` is optional and is not called by `DatabaseSeeder`. It creates Chairs, Tables, and Tents as inactive equipment with zero stock. An administrator must set verified quantities and enable items before residents can request them.
- `DemoDataSeeder` and the older `WorkflowDemoSeeder` reject all environments except `local` and `testing`, including direct calls. They are not production seeders.
- DemoDataSeeder adds 15 residents, 30 requests across the current and previous five calendar months, 10 complaints, and 6 announcements (3 published). It reuses residents and document types. A second run does not duplicate these records or reset their processing state.
- Local demo employee: `demo-staff@example.test` / `password`.
- Disabled local employee: `demo-inactive@example.test` / `password` (login must fail).
- Local demo residents: `demo-resident-1@example.test` through `demo-resident-15@example.test` / `password`. Log in at `/`.
- Demo attachment: `storage/app/public/demo/seed-evidence.txt`.

Run individual seeders as needed:

```bash
php artisan db:seed --class=DocumentTypeSeeder --no-interaction
php artisan db:seed --class=ComplaintTypeSeeder --no-interaction
php artisan db:seed --class=ResourceItemSeeder --no-interaction
php artisan db:seed --class=DemoDataSeeder --no-interaction
```

Do not include demo credentials in production provisioning.

Factories support `BarangayEmployee::factory()->admin()` and `->inactive()`. Pending requests/complaints have no handler; other generated statuses have an employee. Published announcements have `posted_at`; drafts do not.

## Document workflows and storage

| Category | Main review flow |
| --- | --- |
| Clearance | Pending review → office payment → ready to print → ready for pickup → released |
| Certification | Pending review → manual RBI verification / ready to print → officer signature → ready for pickup → released |
| Use / Closure | Pending review → approved arrangement, or resident confirmation of an offered arrangement → ready → released |

Staff can return pending applications for revision or reject them with a review note. Residents can revise eligible applications and accept an offered arrangement, request its revision, or cancel when the current status permits. Partial resource arrangements require confirmation. Confirmation hours default to 48 in `workflow_settings`; expired offers release resource holds. Payments are recorded at the office; there is no online checkout.

Document and complaint evidence supports PDF, JPEG, and PNG files up to 10 MB each. New evidence is stored on the `private` disk at `storage/app/private`; previews and downloads check resident ownership or employee access. `storage:link` exposes only the public disk, including legacy demo evidence. Temporary resident uploads expire after 24 hours and are pruned hourly by the scheduler.

For an existing installation with legacy request attachments, run this after migrations:

```bash
php artisan documents:import-attachments --no-interaction
```

The import copies legacy private files into Media Library, retains originals, skips attachments already imported, and reports missing files or other failures. Back up both the database and uploaded files before upgrading.

## Tests

Default tests use SQLite in memory and never the development PostgreSQL database:

```bash
php artisan config:clear --no-interaction
php artisan test --compact --no-interaction
node --test tests/Unit/*.test.js
```

For PostgreSQL compatibility, create a separate database named **barangay_connect_test** on the test server. The configured DB user must own it or have permission to create/drop tables within it. Use `.env.testing` for dedicated test host/user/password settings; it is ignored by Git. Never point testing at a production server.

```bash
php artisan test --compact --configuration=phpunit.pgsql.xml --no-interaction
```

`phpunit.pgsql.xml` forces the database to `barangay_connect_test` and clears `DB_URL`; connection credentials come from `.env.testing` or the environment. Tests will rebuild this database. `tests/TestCase.php` rejects cached configuration and any database except SQLite `:memory:` or PostgreSQL `barangay_connect_test`, before database-refresh traits execute. Do not run concurrent suites against the same PostgreSQL test database.

PHP coverage includes resident registration and sessions, ownership checks, document workflows and uploads, resource allocation, complaint types and locations, staff review messages, rich announcements, search, role permissions, auditing, and seeder safety. The separate Node tests cover resident alerts, session behavior, and complaint location handling.

Run a focused PHP test with `php artisan test --compact tests/Feature/DocumentWorkflowTest.php --no-interaction`. After PHP changes, format with `vendor/bin/pint --dirty --format agent`.

## Authorization

Both roles must be active to enter the panel; authentication middleware also persists across Livewire requests. Authorization is defined in `app/Policies`, automatically discovered by Laravel.

| Resource | View | Create | Update | Delete |
| --- | --- | --- | --- | --- |
| Announcement | Admin, Staff | Admin, Staff | Admin, Staff | Admin |
| Complaint | Admin, Staff | No | Admin, Staff (review status and message) | Admin |
| ComplaintType | Admin | Admin | Admin | Admin, only without complaints |
| DocumentRequest | Admin, Staff | No | Admin, Staff (workflow actions) | No |
| DocumentType | Admin, Staff | Admin, Staff | Admin, Staff | Admin, only without requests |
| ResourceItem | Admin, Staff | Admin | Admin | No |
| Resident | Admin, Staff | No | No | Admin, only without requests or complaints |
| BarangayEmployee | Admin | Admin | Admin | No |
| AuditLog | Admin | No | No | No |

No resource allows bulk deletion. Administrators cannot change their own role or enabled state through the employee form; the save hook also strips attempted changes to these fields. Blank edit passwords preserve the original hash.

This table describes staff-panel access. Residents create their own requests and complaints through the portal; resident controllers enforce ownership for private records and attachments.

## Schema / ERD handoff

The migrations are the source of truth. Names below are the actual implemented columns, not conceptual ERD aliases.

| Table | Important implemented fields / differences |
| --- | --- |
| barangay_employees | `first_name`, `middle_name`, `last_name`, `contact_number`, `email`, `password`, `role`, `is_active`, `remember_token`, timestamps |
| residents | `email_verified_at`, `birth_date`, `contact_number`, `resident_address`, `profile_image_url`, `password`, `remember_token`; registration time is `created_at` |
| document_types | `document_type_name`, `document_type_description`, `document_type_fee`, `category`, `processing_days`, `is_active`, `is_required_other_documents` |
| document_requests | `reference`, `resident_id`, `document_type_id`, nullable `staff_id`, `purpose`, `category`, `application_kind`, `status`, `review_note`, payment and confirmation fields |
| request_attachments / media | Request attachment kind, file metadata, display order, and Media Library storage records |
| resident_document_uploads | Resident-owned temporary upload tokens and kinds, linked to Media Library files |
| resource_items / request_reservations | Resource inventory, requested/approved/issued/returned quantities, and reservation start/end times |
| request_events / workflow_settings | Request action history with notes/details, and confirmation-hour settings |
| complaints | `reference`, `resident_id`, nullable `staff_id`, `complaint_type_id`, `complaint_type`, `content`, legacy `attachment_url`, `status`, `review_note`, `latitude`, `longitude` |
| complaint_types | `name`, `is_active`, `sort_order`, timestamps |
| announcements | `staff_id`, `title`, `content`, `category`, `published`, nullable `posted_at`, timestamps |
| audit_logs | `staff_id`, `action`, `table_affected`, `record_id`, **only `created_at`**; `record_id` has no foreign key |

RequestStatus: `pending`, `needs_revision`, `awaiting_payment`, `awaiting_confirmation`, `approved`, `processing`, `awaiting_signature`, `ready`, `released`, `rejected`, `cancelled`, `expired`.
ComplaintStatus: `pending`, `in_progress`, `resolved`, `rejected`.
EmployeeRole: `admin`, `staff`.

These are PHP backed enums; database status columns remain strings. Eloquent rejects invalid values, but direct SQL bypasses casts and model events.

AuditObserver tracks employee-originated create/update/delete events on the nine models registered in `AppServiceProvider`, excludes AuditLog, ignores timestamp-only / employee remember-token-only updates, and skips operations without an authenticated employee. Bulk SQL updates do not trigger it. Audit logs contain no passwords or change payloads. Real password changes are still audited as generic updates. Document request events separately retain workflow actions, notes, and details.

## Production checklist

- Configure `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, and `SESSION_SECURE_COOKIE=true`. Enforce HTTPS at the web server / load balancer. Do not change local development values to production just to test this checklist.
- Use `composer install --no-dev --optimize-autoloader`. Boost is a dev dependency; MCP is a transitive dev dependency in `composer.lock`. Neither belongs in production installation.
- Build frontend assets with `npm ci && npm run build` and include `public/build` in the deployment. Point the web server document root at `public/`.
- Use `php artisan migrate --force --no-interaction`, `php artisan db:seed --force --no-interaction`, and `php artisan storage:link --no-interaction` after setting real administrator credentials. No demo seeder runs in production.
- Change the provisioned admin password after handoff. Remember that re-running AdminSeeder resets it to the configured seed password.
- Review `config/barangay.php` for the office name, contact numbers, and document defaults. Configure actual resource inventory in the staff panel before enabling reservations.
- Run `php artisan optimize --no-interaction` and `php artisan filament:optimize --no-interaction` after environment setup. `storage` and `bootstrap/cache` must be writable by the runtime user.
- Configure the deployment scheduler to run `php artisan schedule:run --no-interaction` every minute from the application directory. It expires overdue confirmations every minute and prunes temporary resident uploads hourly.
- Arrange PostgreSQL and uploaded-file backups on the deployment infrastructure, with protected credentials, a defined schedule, retention, and off-host storage. Verify restoration into a separate environment. This repository does not provision a server backup schedule or HTTPS certificate.
- Before handoff, review `git status`, include new source/config/test files in a commit and push it. A local working tree containing untracked files is not a reproducible remote clone.
