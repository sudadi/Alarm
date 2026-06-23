# Alarm Monitoring

Aplikasi monitoring alarm codered dan codeblue berbasis PHP, MySQL, dan Bootstrap.

## Fitur

- Dashboard monitoring dengan lampu indikator per lokasi
- Alarm bunyi dan blink saat state aktif
- Log history alarm
- Manage user dengan role `codered`, `codeblue`, dan `admin`
- Endpoint polling JSON untuk refresh dashboard
- Endpoint POST JSON untuk menerima data button dari Arduino

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

## API Device

Endpoint:

```text
POST /?page=api/device
Content-Type: application/json
X-API-Key: isi_dari_API_KEY_di_env
```

Payload update state alarm:

```json
{
  "client_id": "arduino_PB_12",
  "code_button": "BTN12",
  "button": "Red",
  "status": "ON",
  "ip": "192.168.118.12"
}
```

Payload heartbeat device:

```json
{
  "client_id": "arduino_PB_12",
  "code_button": "BTN12",
  "device_type": "Button",
  "ip": "192.168.118.12"
}
```

Contoh test:

```bash
curl -X POST 'http://127.0.0.1:8000/?page=api/device' \
  -H 'Content-Type: application/json' \
  -H 'X-API-Key: change-me-arduino-secret' \
  -d '{"client_id":"arduino_PB_12","code_button":"BTN12","button":"Red","status":"ON","ip":"192.168.118.12"}'
```

## MQTT Device

Jalankan MQTT worker:

```bash
php bin/mqtt-worker.php
```

Default topic:

```text
alarm/button/+/state
alarm/button/+/heartbeat
```

Contoh publish state:

```bash
mosquitto_pub -h 127.0.0.1 -p 1883 \
  -t 'alarm/button/BTN12/state' \
  -m '{"client_id":"arduino_PB_12","code_button":"BTN12","button":"Red","status":"ON","ip":"192.168.118.12"}'
```

Contoh publish heartbeat:

```bash
mosquitto_pub -h 127.0.0.1 -p 1883 \
  -t 'alarm/button/BTN12/heartbeat' \
  -m '{"client_id":"arduino_PB_12","code_button":"BTN12","device_type":"Button","ip":"192.168.118.12"}'
```
