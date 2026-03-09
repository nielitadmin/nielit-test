# NIELIT Bhubaneswar Portal (Local XAMPP)
This package contains a ready-to-run PHP + MySQL project for local development (XAMPP).
It includes Tailwind/Bootstrap based theme, PHP pages, a CLI worker to parse Excel using PhpSpreadsheet (requires Composer), and DB schema.

## Setup (local XAMPP)
1. Copy this folder into your XAMPP `htdocs` directory (for example `C:/xampp/htdocs/nielit_portal`).
2. Create database `nielit_db` and import `db_schema.sql`:
   - Use phpMyAdmin or MySQL CLI:
     - `CREATE DATABASE nielit_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
     - Import `db_schema.sql`.
3. Install Composer dependencies (for Excel parsing):
   - In project root run: `composer install`
     - Requires Composer: https://getcomposer.org/
4. Update `.env` with your local DB credentials (default is configured for XAMPP).
5. Ensure `uploads/` is writable by web server.
6. Place your logo file at `assets/images/nblogo.jpg`. A placeholder is included if available.

## Run background worker (CLI)
- After uploading Excel files via TP Dashboard, run:
  ```
  php worker/parse_excel.php
  ```
  This will scan `uploads/submissions` for new Excel files, parse students, and insert into `students` table.

## Paytm
- Paytm integration placeholders are present. You must configure Paytm credentials and implement checksum with official SDK.

## Notes
- This is a local development package; hardening and production configuration required before deployment.
