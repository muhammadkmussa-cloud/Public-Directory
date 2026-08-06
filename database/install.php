<?php
/**
 * Ummah Directory — Installer
 *
 * Creates the database schema, loads sample data, and creates demo users.
 *
 * Usage (choose one):
 *   CLI:   php database/install.php
 *   Web:   open https://yourdomain.com/database/install.php   ← DELETE THIS FILE AFTER INSTALL
 *
 * Requires: PHP 7.4+ with pdo_mysql, and a MySQL database that already exists
 * (DirectAdmin creates the database + user for you — just update config/config.php).
 */

require_once __DIR__ . '/../config/config.php';

$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

// Safety lock: delete database/install.lock to re-run
if (file_exists(__DIR__ . '/install.lock')) {
    out("Install lock file exists. Delete database/install.lock to re-run the installer.\n");
    exit(1);
}

out("== Ummah Directory installer ==\n");

/* ---- 1. connect ---------------------------------------------------------- */
try {
    $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . DB_NAME . '`');
    out("[ok] Connected to MySQL, database: " . DB_NAME . "\n");
} catch (PDOException $e) {
    out("[fail] Cannot connect: " . $e->getMessage() . "\n");
    out("Check DB_HOST/DB_USER/DB_PASS in config/config.php\n");
    exit(1);
}

/* ---- 2. schema ----------------------------------------------------------- */
runSqlFile($pdo, __DIR__ . '/schema.sql', 'schema');
out("[ok] Schema created (28 tables)\n");

/* ---- 3. demo users (before seed data, listings reference them) ----------- */
$users = [
    [1, 'admin',      'admin@example.com',   'Admin@123', 'Site Administrator', '',       'admin'],
    [2, 'amina',      'demo@example.com',    'Demo@123',  'Amina Hassan',       '+254700000001', 'regular'],
    [3, 'yusuf',      'yusuf@example.com',   'Demo@123',  'Yusuf Omar',         '+254700000002', 'regular'],
    [4, 'halima',     'halima@example.com',  'Demo@123',  'Halima Noor',        '+254700000003', 'business_owner'],
    [5, 'abdullahi',  'abdullahi@example.com','Demo@123', 'Abdullahi Said',     '+254700000004', 'fundi'],
    [6, 'fatuma',     'fatuma@example.com',  'Demo@123',  'Fatuma Ali',         '+254700000005', 'fundi'],
    [7, 'musa',       'musa@example.com',    'Demo@123',  'Musa Kiprop',        '+254700000006', 'fundi'],
];

$count = 0;
foreach ($users as [$id, $username, $email, $password, $fullName, $phone, $type]) {
    $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $exists->execute([$email]);
    if ($exists->fetch()) {
        continue;
    }
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    $stmt = $pdo->prepare(
        'INSERT INTO users (id, username, email, password_hash, full_name, phone, user_type, is_active, is_verified)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)'
    );
    $stmt->execute([$id, $username, $email, $hash, $fullName, $phone, $type]);
    $count++;
}
out("[ok] Demo users ready ({$count} new)\n");

/* ---- 4. sample data ------------------------------------------------------ */
runSqlFile($pdo, __DIR__ . '/seed.sql', 'sample data');
out("[ok] Sample data loaded\n");

/* ---- 4b. sample data expansion ------------------------------------------- */
runSqlFile($pdo, __DIR__ . '/seed_expansion.sql', 'sample data expansion');
out("[ok] Sample data expansion loaded (30 businesses, 14 mosques, 11 fundis)\n");

/* ---- 5. uploads folder --------------------------------------------------- */
$uploadDir = UPLOAD_PATH;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$ht = $uploadDir . '.htaccess';
if (!file_exists($ht)) {
    file_put_contents($ht, "# Block PHP execution in uploads\n<FilesMatch \"\\.(php|phtml|php5|phar)$\">\n  Require all denied\n</FilesMatch>\nOptions -Indexes\n");
}
out("[ok] Uploads folder ready (uploads/.htaccess protects it)\n");

/* ---- done ---------------------------------------------------------------- */
file_put_contents(__DIR__ . '/install.lock', date('c'));
out("\n== Install complete ==");
out("\nDemo logins:");
out("  Admin : admin@example.com  / Admin@123");
out("  User  : demo@example.com   / Demo@123");
out("\nIMPORTANT: delete database/install.php (and install.lock) from your server now.");
out("\n");

function runSqlFile($pdo, $file, $label)
{
    if (!file_exists($file)) {
        out("[fail] Missing file: $file\n");
        exit(1);
    }
    $sql = file_get_contents($file);
    $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);   // strip comments
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $i = 0;
    foreach ($statements as $stmt) {
        if ($stmt === '' || strtoupper(substr($stmt, 0, 4)) === 'SET ') {
            continue; // skip SET SQL_MODE / time_zone directives
        }
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            out("[warn] $label statement #{$i}: " . $e->getMessage() . "\n");
        }
        $i++;
    }
}

function out($s)
{
    echo $s;
}
