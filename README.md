# Alarm Monitoring

Aplikasi monitoring alarm codered dan codeblue berbasis PHP, MySQL, dan Bootstrap.

## Fitur

- Dashboard monitoring dengan lampu indikator per lokasi
- Alarm bunyi dan blink saat state aktif
- Log history alarm
- Manage user dengan role `codered`, `codeblue`, dan `admin`
- Endpoint polling JSON untuk refresh dashboard

## Struktur

- `public/` - entry point aplikasi
- `src/` - database, auth, repository, helper
- `views/` - layout dan halaman bootstrap
- `assets/` - CSS dan JavaScript
- `sql/schema.sql` - skema database

## Login awal

- Username: `admin`
- Password: `admin123`

## Setup

1. Import `sql/schema.sql` ke MySQL.
2. Sesuaikan `config/database.php`.
3. Jalankan server dari folder `public`.
4. Buka aplikasi di browser.

Contoh:

```bash
php -S 127.0.0.1:8000 -t public
```
