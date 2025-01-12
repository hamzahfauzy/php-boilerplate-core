# Core Boilerplate New

Core Boilerplate New

### Hook Boilerplate
- {module}/hooks/before-create-{tablename}.php
- {module}/hooks/after-create-{tablename}.php
- {module}/hooks/before-update-{tablename}.php
- {module}/hooks/after-update-{tablename}.php
- {module}/hooks/before-delete-{tablename}.php
- {module}/hooks/after-delete-{tablename}.php
- {module}/hooks/index-{tablename}.php
- {module}/hooks/index-fields-{tablename}.php
- {module}/hooks/action-button-{tablename}.php

### Command boilerplate
- php run web -> serving aplikasi
- php run db:migration -> menjalankan file migrasi ({module}/databases/migrations/migration-{n}.sql)
- php run publish:asset -> publish semua asset modul (symlink unix only)
- php run publish:storage -> publish media dari file upload
- php run publish:theme -> publish asset dari tema yang aktif
- php run schedulers:run -> menjalankan schedulers

### Theme Folder Structure
- assets -> folder yang berisi asset asset web
- footer.php -> file footer (get_footer())
- header.php -> file header (get_header())
- nav -> file nav (get_nav())
- sidebar -> file sidebar (get_sidebar())

### Module Folder Structure
- assets -> folder asset
- config -> folder config
- lang -> folder lang
  - en -> lang untuk bhs inggris
  - id -> lang untuk bhs indonesia
    - content.php -> localization content
    - label.php -> localization label
    - menu.php -> localization menu
  - menu.php -> structure data menu
  - table-fields.php -> structure data table
- databases -> folder keperluan database
  - migrations -> folder keperluan migration
    - migration-{n}.sql -> file migration sql
  - seeders -> folder keperluan seeder
    - {namafileseeder}.sql -> file seeder sql
- guards -> folder guard untuk kebutuhan sebelum process
  - {processpath}.php -> file guard
- hooks -> folder hooks (lihat bagian hooks)
- libraries -> folder untuk menempatkan class
- process -> folder process
- views -> folder view

## DEFAULT INSTALL
git clone https://github.com/hamzahfauzy/php-boilerplate-auth-module modules/auth
git clone https://github.com/hamzahfauzy/php-boilerplate-module-default modules/default
git clone https://github.com/hamzahfauzy/php-boilerplate-crud-module modules/crud
git clone https://github.com/andrewsoebagoes/theme-sneat.git themes/sneat

## Lisensi

Proyek ini dilisensikan di bawah [Z-Techno].
