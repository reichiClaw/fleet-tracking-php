<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$basePath = dirname(__DIR__);
$envPath = $basePath.'/.env';
$lockPath = $basePath.'/storage/app/install.lock';
$storageAppPath = $basePath.'/storage/app';

function installer_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function installer_value(string $key, string $default = ''): string
{
    return installer_h($_POST[$key] ?? $default);
}

function installer_env_value(?string $value): string
{
    $value = (string) $value;

    if ($value === '') {
        return '';
    }

    if (preg_match('/^[A-Za-z0-9_.:\\/@-]+$/', $value)) {
        return $value;
    }

    return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
}

function installer_write_env(string $envPath, array $data): void
{
    $lines = [
        'APP_NAME='.installer_env_value($data['app_name']),
        'APP_ENV=production',
        'APP_KEY='.$data['app_key'],
        'APP_DEBUG=false',
        'APP_URL='.installer_env_value($data['app_url']),
        'APP_INSTALLED='.($data['installed'] ? 'true' : 'false'),
        '',
        'APP_LOCALE=de',
        'APP_FALLBACK_LOCALE=en',
        'APP_FAKER_LOCALE=de_DE',
        '',
        'BCRYPT_ROUNDS=12',
        '',
        'LOG_CHANNEL=stack',
        'LOG_STACK=single',
        'LOG_LEVEL=warning',
        '',
        'DB_CONNECTION=mysql',
        'DB_HOST='.installer_env_value($data['db_host']),
        'DB_PORT='.installer_env_value($data['db_port']),
        'DB_DATABASE='.installer_env_value($data['db_database']),
        'DB_USERNAME='.installer_env_value($data['db_username']),
        'DB_PASSWORD='.installer_env_value($data['db_password']),
        '',
        'SESSION_DRIVER=database',
        'SESSION_LIFETIME=120',
        'SESSION_ENCRYPT=false',
        'SESSION_PATH=/',
        'SESSION_DOMAIN=null',
        '',
        'BROADCAST_CONNECTION=log',
        'FILESYSTEM_DISK=local',
        'FLEET_STORAGE_DISK='.installer_env_value($data['fleet_disk']),
        'QUEUE_CONNECTION=database',
        'CACHE_STORE=database',
        '',
        'MAIL_MAILER=log',
        'MAIL_FROM_ADDRESS="fuhrpark@example.com"',
        'MAIL_FROM_NAME="${APP_NAME}"',
        '',
        'AWS_ACCESS_KEY_ID=',
        'AWS_SECRET_ACCESS_KEY=',
        'AWS_DEFAULT_REGION=us-east-1',
        'AWS_BUCKET=',
        'AWS_USE_PATH_STYLE_ENDPOINT=false',
        '',
        'VITE_APP_NAME="${APP_NAME}"',
    ];

    if ($data['fleet_disk'] === 'fleet_sftp') {
        $lines = array_merge($lines, [
            '',
            'FLEET_SFTP_HOST='.installer_env_value($data['sftp_host']),
            'FLEET_SFTP_PORT='.installer_env_value($data['sftp_port']),
            'FLEET_SFTP_USERNAME='.installer_env_value($data['sftp_username']),
            'FLEET_SFTP_PASSWORD='.installer_env_value($data['sftp_password']),
            'FLEET_SFTP_PRIVATE_KEY='.installer_env_value($data['sftp_private_key']),
            'FLEET_SFTP_PASSPHRASE='.installer_env_value($data['sftp_passphrase']),
            'FLEET_SFTP_ROOT='.installer_env_value($data['sftp_root']),
        ]);
    }

    if (file_put_contents($envPath, implode(PHP_EOL, $lines).PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Could not write .env. Please check file permissions.');
    }
}

function installer_test_database(array $data): void
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $data['db_host'],
        $data['db_port'],
        $data['db_database']
    );

    new PDO($dsn, $data['db_username'], $data['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
}

function installer_can_write_env(string $envPath, string $basePath): bool
{
    if (file_exists($envPath)) {
        return is_writable($envPath);
    }

    return is_writable($basePath);
}

function installer_post_bool(string $key): bool
{
    return isset($_POST[$key]) && in_array((string) $_POST[$key], ['1', 'true', 'on', 'yes'], true);
}


function installer_prerequisites(string $basePath, string $envPath): array
{
    $checks = [];

    $add = static function (string $label, bool $ok, bool $required, string $message) use (&$checks): void {
        $checks[] = [
            'label' => $label,
            'ok' => $ok,
            'required' => $required,
            'message' => $message,
        ];
    };

    $add('PHP version', version_compare(PHP_VERSION, '8.3.0', '>='), true, 'Found PHP '.PHP_VERSION.'. Required: PHP 8.3 or newer.');

    foreach (['pdo', 'pdo_mysql', 'mbstring', 'fileinfo', 'gd', 'zip', 'xml', 'dom', 'simplexml', 'curl', 'openssl', 'tokenizer', 'ctype', 'json'] as $extension) {
        $add('PHP extension: '.$extension, extension_loaded($extension), true, extension_loaded($extension) ? 'Loaded.' : 'Missing. Enable/install php-'.$extension.' on the server.');
    }

    $pdoMysqlAvailable = class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers(), true);
    $add('PDO MySQL driver', $pdoMysqlAvailable, true, 'Required for MySQL/MariaDB database access.');
    $add('Composer vendor files', file_exists($basePath.'/vendor/autoload.php'), true, 'The ready bundle must include vendor/autoload.php. Re-upload the complete release archive if missing.');
    $add('Laravel bootstrap', file_exists($basePath.'/bootstrap/app.php'), true, 'bootstrap/app.php must exist.');
    $add('Built frontend assets', file_exists($basePath.'/public/build/manifest.json'), true, 'public/build/manifest.json must exist. Rebuild/re-upload the ready bundle if missing.');
    $add('.env writable', installer_can_write_env($envPath, $basePath), true, 'The installer must be able to create or update .env.');

    foreach (['storage', 'storage/app', 'storage/framework', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'bootstrap/cache'] as $directory) {
        $path = $basePath.'/'.$directory;
        $add($directory.' writable', is_dir($path) && is_writable($path), true, $path);
    }

    $add('APP_KEY generation', function_exists('random_bytes'), true, 'PHP random_bytes() is required to generate the app key.');
    $add('File uploads enabled', (bool) ini_get('file_uploads'), false, 'Required later for vehicle photos. Current upload_max_filesize: '.ini_get('upload_max_filesize').'.');
    $add('Symlink support', function_exists('symlink'), false, 'Recommended for php artisan storage:link. Some shared hosters disable symlinks; uploads can still be stored privately.');

    return $checks;
}

function installer_prerequisite_errors(array $checks): array
{
    $errors = [];

    foreach ($checks as $check) {
        if ($check['required'] && ! $check['ok']) {
            $errors[] = 'Server prerequisite missing: '.$check['label'].' - '.$check['message'];
        }
    }

    return $errors;
}

function installer_status_badge(bool $ok, bool $required): string
{
    if ($ok) {
        return '<span class="badge badge-ok">OK</span>';
    }

    return $required
        ? '<span class="badge badge-error">Required</span>'
        : '<span class="badge badge-warn">Optional</span>';
}

function installer_render(array $errors = [], array $warnings = [], bool $success = false): void
{
    global $basePath, $envPath;

    $prerequisites = installer_prerequisites($basePath, $envPath);
    $hasBlockingPrerequisite = (bool) installer_prerequisite_errors($prerequisites);
    $isSftp = ($_POST['fleet_disk'] ?? 'fleet_private') === 'fleet_sftp';
    http_response_code($errors ? 422 : 200);
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fuhrpark Installer</title>
    <style>
        body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f1f5f9;color:#0f172a;margin:0}
        main{max-width:980px;margin:0 auto;padding:32px 16px}
        .card{background:#fff;border-radius:14px;padding:24px;box-shadow:0 10px 25px rgba(15,23,42,.08);margin-bottom:18px}
        h1{margin-top:0}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        label{font-weight:650;font-size:14px} input,select{display:block;width:100%;box-sizing:border-box;margin-top:6px;border:1px solid #cbd5e1;border-radius:10px;padding:10px;font:inherit}
        .full{grid-column:1/-1}.hint{font-size:13px;color:#64748b}.error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}.warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a}.ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
        .box{border-radius:10px;padding:12px;margin-bottom:14px}.btn{border:0;border-radius:10px;background:#0f172a;color:white;padding:12px 18px;font-weight:700;cursor:pointer}.btn[disabled]{background:#94a3b8;cursor:not-allowed}
        code{background:#e2e8f0;border-radius:5px;padding:2px 5px}.toggle{display:flex;align-items:center;gap:8px}.toggle input{width:auto}
        table{width:100%;border-collapse:collapse;font-size:14px}td,th{border-top:1px solid #e2e8f0;padding:9px;text-align:left;vertical-align:top}.badge{display:inline-block;border-radius:999px;padding:3px 8px;font-size:12px;font-weight:800}.badge-ok{background:#dcfce7;color:#166534}.badge-error{background:#fee2e2;color:#991b1b}.badge-warn{background:#fef3c7;color:#92400e}
        @media(max-width:720px){.grid{grid-template-columns:1fr}}
    </style>
    <script>
        function toggleSftp(){
            const disk = document.querySelector('[name="fleet_disk"]').value;
            document.querySelectorAll('[data-sftp]').forEach(el => el.style.display = disk === 'fleet_sftp' ? '' : 'none');
        }
        window.addEventListener('DOMContentLoaded', toggleSftp);
    </script>
</head>
<body>
<main>
    <div class="card">
        <h1>Fuhrpark Management Installer</h1>
        <p class="hint">Use this wizard after uploading the ready bundle by FTP. It creates <code>.env</code>, tests the database, runs migrations, creates the first admin user, links storage, caches Laravel config, and locks the installer.</p>
    </div>

    <?php if ($success): ?>
        <div class="box ok">
            <strong>Installation completed.</strong>
            <p>You can now open the application. For security, delete <code>public/install.php</code> by FTP or keep the generated lock file <code>storage/app/install.lock</code>.</p>
        </div>
        <?php if ($warnings): ?>
            <div class="box warn">
                <strong>Warnings:</strong>
                <ul><?php foreach ($warnings as $warning): ?><li><?= installer_h($warning) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        <p><a class="btn" href="index.php">Open application</a></p>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="box error">
                <strong>Please fix these issues:</strong>
                <ul><?php foreach ($errors as $error): ?><li><?= installer_h($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <?php if ($warnings): ?>
            <div class="box warn">
                <strong>Warnings:</strong>
                <ul><?php foreach ($warnings as $warning): ?><li><?= installer_h($warning) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>


        <div class="card">
            <h2>Server prerequisites</h2>
            <p class="hint">These checks run directly on this webserver. Required red items must be fixed before installation can continue.</p>
            <table>
                <thead><tr><th>Status</th><th>Check</th><th>Details</th></tr></thead>
                <tbody>
                    <?php foreach ($prerequisites as $check): ?>
                        <tr>
                            <td><?= installer_status_badge($check['ok'], $check['required']) ?></td>
                            <td><?= installer_h($check['label']) ?></td>
                            <td><?= installer_h($check['message']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form method="post">
            <div class="card">
                <h2>Application</h2>
                <div class="grid">
                    <label>Application name
                        <input name="app_name" required value="<?= installer_value('app_name', 'Fuhrpark Management') ?>">
                    </label>
                    <label>Application URL
                        <input name="app_url" required placeholder="https://fuhrpark.example.com" value="<?= installer_value('app_url', 'https://') ?>">
                    </label>
                </div>
            </div>

            <div class="card">
                <h2>Database</h2>
                <p class="hint">Create an empty MySQL/MariaDB database at your hosting provider first, then enter the credentials here.</p>
                <div class="grid">
                    <label>DB host
                        <input name="db_host" required value="<?= installer_value('db_host', '127.0.0.1') ?>">
                    </label>
                    <label>DB port
                        <input name="db_port" required value="<?= installer_value('db_port', '3306') ?>">
                    </label>
                    <label>DB name
                        <input name="db_database" required value="<?= installer_value('db_database') ?>">
                    </label>
                    <label>DB user
                        <input name="db_username" required value="<?= installer_value('db_username') ?>">
                    </label>
                    <label class="full">DB password
                        <input name="db_password" type="password" value="<?= installer_value('db_password') ?>">
                    </label>
                </div>
            </div>

            <div class="card">
                <h2>First admin user</h2>
                <div class="grid">
                    <label>Admin name
                        <input name="admin_name" required value="<?= installer_value('admin_name', 'Admin') ?>">
                    </label>
                    <label>Admin email
                        <input name="admin_email" type="email" required value="<?= installer_value('admin_email') ?>">
                    </label>
                    <label>Admin password
                        <input name="admin_password" type="password" required minlength="8">
                    </label>
                    <label>Repeat password
                        <input name="admin_password_confirmation" type="password" required minlength="8">
                    </label>
                    <label class="toggle full">
                        <input type="checkbox" name="create_manager" value="1" <?= installer_post_bool('create_manager') ? 'checked' : '' ?>>
                        Also create a fleet manager user
                    </label>
                    <label>Fleet manager email
                        <input name="manager_email" type="email" value="<?= installer_value('manager_email', 'verwaltung@example.com') ?>">
                    </label>
                    <label>Fleet manager password
                        <input name="manager_password" type="password" minlength="8">
                    </label>
                </div>
            </div>

            <div class="card">
                <h2>File storage</h2>
                <div class="grid">
                    <label>Storage type
                        <select name="fleet_disk" onchange="toggleSftp()">
                            <option value="fleet_private" <?= $isSftp ? '' : 'selected' ?>>Local server storage</option>
                            <option value="fleet_sftp" <?= $isSftp ? 'selected' : '' ?>>SFTP storage</option>
                        </select>
                    </label>
                    <div class="hint">Local storage is easiest. SFTP is only for uploaded photos/signatures, not for the database.</div>
                    <label data-sftp>SFTP host
                        <input name="sftp_host" value="<?= installer_value('sftp_host') ?>">
                    </label>
                    <label data-sftp>SFTP port
                        <input name="sftp_port" value="<?= installer_value('sftp_port', '22') ?>">
                    </label>
                    <label data-sftp>SFTP username
                        <input name="sftp_username" value="<?= installer_value('sftp_username') ?>">
                    </label>
                    <label data-sftp>SFTP password
                        <input name="sftp_password" type="password" value="<?= installer_value('sftp_password') ?>">
                    </label>
                    <label data-sftp>SFTP root
                        <input name="sftp_root" value="<?= installer_value('sftp_root', '/data/fuhrpark') ?>">
                    </label>
                    <label data-sftp>Private key path, optional
                        <input name="sftp_private_key" value="<?= installer_value('sftp_private_key') ?>">
                    </label>
                    <label data-sftp>Private key passphrase, optional
                        <input name="sftp_passphrase" type="password" value="<?= installer_value('sftp_passphrase') ?>">
                    </label>
                </div>
            </div>

            <div class="card">
                <h2>Run installer</h2>
                <p class="hint">The installer will run database migrations. If tables already exist, make a backup first.</p>
                <?php if ($hasBlockingPrerequisite): ?><p class="box error">Installation is blocked until all required server prerequisites are OK.</p><?php endif; ?>
                <button class="btn" type="submit" <?= $hasBlockingPrerequisite ? 'disabled' : '' ?>>Install now</button>
            </div>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
    <?php
}

if (file_exists($lockPath)) {
    installer_render(['This application is already installed. Delete storage/app/install.lock by FTP only if you intentionally want to run the installer again.']);
    exit;
}

$warnings = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (! installer_can_write_env($envPath, $basePath)) {
        $warnings[] = 'The installer may not be able to write .env. If installation fails, create .env manually or adjust permissions through your hosting panel.';
    }

    installer_render([], $warnings);
    exit;
}

$required = [
    'app_name', 'app_url', 'db_host', 'db_port', 'db_database', 'db_username',
    'admin_name', 'admin_email', 'admin_password', 'admin_password_confirmation',
];

$errors = installer_prerequisite_errors(installer_prerequisites($basePath, $envPath));
foreach ($required as $field) {
    if (trim((string) ($_POST[$field] ?? '')) === '') {
        $errors[] = 'Missing required field: '.$field;
    }
}

if (! filter_var($_POST['admin_email'] ?? '', FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Admin email is invalid.';
}

if ((string) ($_POST['admin_password'] ?? '') !== (string) ($_POST['admin_password_confirmation'] ?? '')) {
    $errors[] = 'Admin passwords do not match.';
}

if (strlen((string) ($_POST['admin_password'] ?? '')) < 8) {
    $errors[] = 'Admin password must have at least 8 characters.';
}

if (installer_post_bool('create_manager')) {
    if (! filter_var($_POST['manager_email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Fleet manager email is invalid.';
    }
    if (strlen((string) ($_POST['manager_password'] ?? '')) < 8) {
        $errors[] = 'Fleet manager password must have at least 8 characters.';
    }
}

if (($_POST['fleet_disk'] ?? 'fleet_private') === 'fleet_sftp') {
    foreach (['sftp_host', 'sftp_port', 'sftp_username', 'sftp_root'] as $field) {
        if (trim((string) ($_POST[$field] ?? '')) === '') {
            $errors[] = 'Missing SFTP field: '.$field;
        }
    }
}

foreach ([$storageAppPath, $basePath.'/bootstrap/cache', $basePath.'/storage/framework'] as $path) {
    if (! is_dir($path) || ! is_writable($path)) {
        $errors[] = 'Directory is not writable by PHP: '.$path;
    }
}

if (! installer_can_write_env($envPath, $basePath)) {
    $errors[] = 'Cannot write .env. Make the project folder or existing .env writable by PHP.';
}

if ($errors) {
    installer_render($errors, $warnings);
    exit;
}

$data = [
    'app_name' => trim((string) $_POST['app_name']),
    'app_url' => rtrim(trim((string) $_POST['app_url']), '/'),
    'app_key' => 'base64:'.base64_encode(random_bytes(32)),
    'installed' => false,
    'db_host' => trim((string) $_POST['db_host']),
    'db_port' => trim((string) $_POST['db_port']),
    'db_database' => trim((string) $_POST['db_database']),
    'db_username' => trim((string) $_POST['db_username']),
    'db_password' => (string) ($_POST['db_password'] ?? ''),
    'fleet_disk' => ($_POST['fleet_disk'] ?? 'fleet_private') === 'fleet_sftp' ? 'fleet_sftp' : 'fleet_private',
    'sftp_host' => trim((string) ($_POST['sftp_host'] ?? '')),
    'sftp_port' => trim((string) ($_POST['sftp_port'] ?? '22')),
    'sftp_username' => trim((string) ($_POST['sftp_username'] ?? '')),
    'sftp_password' => (string) ($_POST['sftp_password'] ?? ''),
    'sftp_private_key' => trim((string) ($_POST['sftp_private_key'] ?? '')),
    'sftp_passphrase' => (string) ($_POST['sftp_passphrase'] ?? ''),
    'sftp_root' => trim((string) ($_POST['sftp_root'] ?? '/data/fuhrpark')),
];

try {
    installer_test_database($data);
    installer_write_env($envPath, $data);

    require $basePath.'/vendor/autoload.php';

    $app = require $basePath.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    Illuminate\Support\Facades\Artisan::call('config:clear');
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

    $categories = ['Steiger', 'Golf Car', 'Lader', 'Teleskoplader', 'Hebebuehne', 'Sonstige'];
    foreach ($categories as $index => $name) {
        App\Models\VehicleCategory::firstOrCreate(
            ['name' => $name],
            ['slug' => Illuminate\Support\Str::slug($name), 'sort_order' => $index + 1, 'is_active' => true]
        );
    }

    App\Models\User::updateOrCreate(
        ['email' => trim((string) $_POST['admin_email'])],
        [
            'name' => trim((string) $_POST['admin_name']),
            'password' => Illuminate\Support\Facades\Hash::make((string) $_POST['admin_password']),
            'role' => App\Models\User::ROLE_ADMIN,
            'is_active' => true,
        ]
    );

    if (installer_post_bool('create_manager')) {
        App\Models\User::updateOrCreate(
            ['email' => trim((string) $_POST['manager_email'])],
            [
                'name' => 'Verwaltung',
                'password' => Illuminate\Support\Facades\Hash::make((string) $_POST['manager_password']),
                'role' => App\Models\User::ROLE_MANAGER,
                'is_active' => true,
            ]
        );
    }

    try {
        Illuminate\Support\Facades\Artisan::call('storage:link');
    } catch (Throwable $exception) {
        $warnings[] = 'storage:link could not be created automatically: '.$exception->getMessage();
    }

    Illuminate\Support\Facades\Artisan::call('config:cache');
    Illuminate\Support\Facades\Artisan::call('route:cache');
    Illuminate\Support\Facades\Artisan::call('view:cache');

    $data['installed'] = true;
    installer_write_env($envPath, $data);

    if (! is_dir($storageAppPath)) {
        mkdir($storageAppPath, 0775, true);
    }

    file_put_contents($lockPath, 'Installed at '.date(DATE_ATOM).PHP_EOL, LOCK_EX);

    installer_render([], $warnings, true);
} catch (Throwable $exception) {
    installer_render([
        $exception->getMessage(),
        'If this happened after .env was written, fix the issue and reload install.php. The installer is not locked until setup succeeds.',
    ], $warnings);
}
