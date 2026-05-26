# Webserver-ready release bundle

This folder is used for ready-to-upload Laravel release artifacts.

## Current bundle

```text
release/fuhrpark-app-ready.tar.gz
release/SHA256SUMS
```

The archive contains:

- application source
- production Composer dependencies in `vendor/`
- built frontend assets in `public/build/`
- deployment instructions in `DEPLOYMENT.txt`

The target webserver does **not** need npm. It also does **not** need Composer for installing PHP dependencies, because `vendor/` is included in the archive.

For FTP-only hosting, upload the extracted archive and open:

```text
https://your-domain.example/install.php
```

The browser installer asks for the required setup information and runs the Laravel setup from PHP.
It also checks the target server first, including PHP version, PHP extensions,
MySQL/MariaDB PDO support, writable folders, included `vendor/` dependencies,
and built `public/build/` assets.

## Rebuild the bundle

From the repository root:

```bash
bash scripts/build-webserver-release.sh
```

Then upload `release/fuhrpark-app-ready.tar.gz` to the server and extract it under your web root, for example:

```bash
mkdir -p /var/www/fuhrpark-app
tar -xzf fuhrpark-app-ready.tar.gz --strip-components=1 -C /var/www/fuhrpark-app
```

Configure Apache/Nginx to use:

```text
/var/www/fuhrpark-app/public
```

as the document root.

After the installer succeeds, delete `public/install.php` by FTP.
