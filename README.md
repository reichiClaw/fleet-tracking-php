# Fuhrpark Management Laravel App

Laravel-Webanwendung fuer die Verwaltung eines Fahrzeugpools ohne Docker. Die App ist fuer einen klassischen Apache- oder Nginx-Webserver mit PHP und MySQL/MariaDB gedacht.

## Enthaltene Funktionen

- Login mit Rollen `admin` und `fleet_manager`
- Dashboard mit Statusuebersicht und ueberfaelligen Rueckgaben
- Fahrzeugpool mit Suche, Filtern und Status
- Fahrzeugverwaltung mit Kategorien und Excel/CSV-Import
- QR-Code pro Fahrzeug mit geschuetzter Scan-URL
- Check-in bei Anlieferung mit KM, Betriebsstunden, Zustand, Schaeden und Fotos
- Hersteller-Auschecken mit Protokoll und Statuswechsel
- Verleih an Subfirma oder internen Fahrer mit geplanter Rueckgabe und Signaturfeld
- Rueckgabe mit KM, Betriebsstunden, Schaeden und Fotos
- Fahrer-Stammdatenbank
- Private Foto-/Signaturablage lokal oder optional per SFTP
- REST API v1 mit Laravel Sanctum fuer spaetere Mobile-App-Anbindung
- Audit-Log-Grundlage fuer wichtige Workflow-Aktionen

## Technischer Stack

- PHP 8.3+
- Laravel 13
- MySQL/MariaDB in Produktion, SQLite fuer lokale Tests moeglich
- Blade + Tailwind CSS + kleines JavaScript fuer Signaturen
- Laravel Sanctum fuer API Tokens
- PhpSpreadsheet fuer Excel/CSV-Import
- chillerlan/php-qrcode fuer QR-Codes
- Laravel Filesystem mit lokaler privater Disk oder SFTP

## Serveranforderungen

Installiert sein sollten:

```bash
php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd
composer
nodejs npm
mysql-server oder mariadb-server
```

Wichtige PHP Extensions:

- pdo_mysql
- mbstring
- fileinfo
- gd
- zip
- xml
- curl
- openssl

## Installation auf einem normalen Webserver

### Variante A: fertiges Bundle ohne npm auf dem Server

Wenn auf dem Webserver kein npm verfuegbar ist, nutze das fertige Release-Bundle:

```text
release/fuhrpark-app-ready.tar.gz
```

Dieses Archiv enthaelt bereits:

- den Laravel-Code
- `vendor/` mit Produktions-Composer-Abhaengigkeiten
- `public/build/` mit fertig gebauten Frontend-Assets

Auf dem Server:

```bash
mkdir -p /var/www/fuhrpark-app
tar -xzf fuhrpark-app-ready.tar.gz --strip-components=1 -C /var/www/fuhrpark-app
```

Der Webserver zeigt weiterhin auf:

```text
/var/www/fuhrpark-app/public
```

Wenn du nur FTP-Zugriff hast, lade den entpackten Ordner per FTP hoch und oeffne danach im Browser:

```text
https://deine-domain.example/install.php
```

Der Installer fragt alle benoetigten Informationen ab:

- Server-Voraussetzungen wie PHP-Version, PHP-Extensions, Schreibrechte, `vendor/` und `public/build/`
- App-Name und App-URL
- MySQL/MariaDB Host, Port, Datenbank, Benutzer und Passwort
- erster Admin-Benutzer
- optionaler Verwaltungsuser
- lokale Dateiablage oder SFTP-Ablage

Der Installer erledigt danach:

- blockiert die Installation, falls eine kritische Server-Voraussetzung fehlt
- `.env` erzeugen
- Datenbankverbindung testen
- Migrationen ausfuehren
- Admin-Benutzer anlegen
- Standard-Fahrzeugkategorien anlegen
- Storage-Link erzeugen, falls der Hoster Symlinks erlaubt
- Laravel Config/Routes/Views cachen
- Installer per `storage/app/install.lock` sperren

Nach erfolgreicher Installation sollte `public/install.php` per FTP geloescht werden.

Das Bundle kann neu erzeugt werden mit:

```bash
bash scripts/build-webserver-release.sh
```

### Variante B: Installation mit Composer und npm auf dem Server

```bash
cd /var/www
git clone <repo-url> fuhrpark-app
cd fuhrpark-app

cp .env.example .env
composer install --no-dev --optimize-autoloader
npm install
npm run build

php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

Der Webserver muss auf dieses Verzeichnis zeigen:

```text
/var/www/fuhrpark-app/public
```

Nicht auf das Projekt-Hauptverzeichnis.

## Beispiel-Login nach Seed

```text
Admin:       admin@example.com / password
Verwaltung:  verwaltung@example.com / password
```

Passwoerter nach der Installation sofort aendern.

## MySQL/MariaDB `.env`

```env
APP_NAME="Fuhrpark Management"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://fuhrpark.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fuhrpark
DB_USERNAME=fuhrpark_user
DB_PASSWORD=secure_password

FILESYSTEM_DISK=local
FLEET_STORAGE_DISK=fleet_private
```

## Optionale SFTP-Dateiablage

Die Datenbank bleibt immer MySQL/MariaDB. SFTP ist nur fuer Fotos, Signaturen und spaetere Dokumente gedacht.

```env
FLEET_STORAGE_DISK=fleet_sftp
FLEET_SFTP_HOST=fileserver.example.com
FLEET_SFTP_PORT=22
FLEET_SFTP_USERNAME=fuhrpark
FLEET_SFTP_PASSWORD=secret
FLEET_SFTP_ROOT=/data/fuhrpark
```

Alternativ kann ein Private Key verwendet werden:

```env
FLEET_SFTP_PRIVATE_KEY=/var/www/.ssh/fuhrpark_storage
FLEET_SFTP_PASSPHRASE=
```

## Apache VirtualHost

```apache
<VirtualHost *:80>
    ServerName fuhrpark.example.com
    DocumentRoot /var/www/fuhrpark-app/public

    <Directory /var/www/fuhrpark-app/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/fuhrpark_error.log
    CustomLog ${APACHE_LOG_DIR}/fuhrpark_access.log combined
</VirtualHost>
```

Danach HTTPS z. B. mit Certbot aktivieren.

## Nginx Server Block

```nginx
server {
    listen 80;
    server_name fuhrpark.example.com;

    root /var/www/fuhrpark-app/public;
    index index.php index.html;
    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

## REST API fuer spaetere App

Basis: `/api/v1`

```http
POST /api/v1/login
GET  /api/v1/me
POST /api/v1/logout
GET  /api/v1/vehicles
GET  /api/v1/vehicles/{vehicle}
POST /api/v1/vehicles/scan/{token}
POST /api/v1/vehicles/{vehicle}/check-in
POST /api/v1/vehicles/{vehicle}/loan
POST /api/v1/loans/{loan}/return
```

Login liefert ein Sanctum Token. Mobile Apps senden danach:

```http
Authorization: Bearer <token>
Accept: application/json
```

## Tests

```bash
php artisan test
npm run build
```

## Backup

Regelmaessig sichern:

- MySQL/MariaDB Datenbank
- `storage/app/fleet`
- `.env`
- optional SFTP Zielordner

Beispiel Datenbankbackup:

```bash
mysqldump -u fuhrpark_user -p fuhrpark > backup-fuhrpark.sql
```

Beispiel Datei-Backup:

```bash
rsync -a /var/www/fuhrpark-app/storage/app/fleet/ /backup/fuhrpark-files/
```

## Naechste sinnvolle Ausbaustufen

- PDF-Protokolle fuer Check-in, Verleih und Rueckgabe
- Reparaturworkflow fuer Schaeden
- QR-Code Sammeldruck als Etikettenbogen
- Benachrichtigungen fuer ueberfaellige Rueckgaben
- feinere Rollen/Rechte pro Standort
- OpenAPI-Dokumentation fuer die Mobile App
