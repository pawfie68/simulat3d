<?php
declare(strict_types=1);

// PHP 8.2+. Secrets and ZIPs live OUTSIDE the public web root.
header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function reply(int $status, array $data): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

$private = getenv('SIMULAT3D_PRIVATE_DIR') ?: dirname(__DIR__, 2) . '/simulat3d-private';
$configFile = $private . '/config.php';
if (!is_file($configFile)) reply(503, ['error' => 'Beta access is not configured.']);
$config = require $configFile;
if (!is_array($config) || empty($config['password_hash']) || empty($config['rate_secret']) || strlen($config['rate_secret']) < 32) {
    reply(503, ['error' => 'Beta access is not configured.']);
}
$local = PHP_SAPI === 'cli-server' && getenv('SIMULAT3D_LOCAL') === '1';
if (!$local && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
    reply(400, ['error' => 'HTTPS is required.']);
}
$action = $_GET['action'] ?? 'status';
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($action, ['status', 'login', 'logout', 'download'], true)) reply(404, ['error' => 'Not found.']);
if (($action === 'login' || $action === 'logout') ? $method !== 'POST' : $method !== 'GET') {
    reply(405, ['error' => 'Method not allowed.']);
}
if ($method === 'POST') {
    $allowed = $local ? ['http://127.0.0.1:4321', 'http://127.0.0.1:4322'] : ['https://simulat3d.eu', 'https://www.simulat3d.eu'];
    if (!in_array($_SERVER['HTTP_ORIGIN'] ?? '', $allowed, true) || ($_SERVER['HTTP_X_SIMULAT3D'] ?? '') !== 'beta') {
        reply(403, ['error' => 'Invalid request origin.']);
    }
    if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
        reply(415, ['error' => 'JSON required.']);
    }
}

const TTL = 604800; // Fixed seven days; not extended on page views.
$sessionDir = $private . '/sessions';
if (!is_dir($sessionDir) && !@mkdir($sessionDir, 0700, true)) reply(503, ['error' => 'Session storage unavailable.']);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.serialize_handler', 'php');
ini_set('session.gc_maxlifetime', (string) TTL);
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');
session_save_path($sessionDir);
session_name($local ? 'simulat3d_beta_local' : '__Secure-simulat3d_beta');
session_set_cookie_params(['lifetime' => TTL, 'path' => '/api/', 'secure' => !$local, 'httponly' => true, 'samesite' => 'Strict']);

function endSession(): void {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/api/', 'secure' => !(PHP_SAPI === 'cli-server' && getenv('SIMULAT3D_LOCAL') === '1'), 'httponly' => true, 'samesite' => 'Strict']);
}
function authenticated(array $config): bool {
    return isset($_SESSION['expires'], $_SESSION['credential']) && $_SESSION['expires'] > time()
        && hash_equals(hash('sha256', $config['password_hash']), $_SESSION['credential']);
}

// Visits alone do not create a cookie. Resume only an existing session.
if (isset($_COOKIE[session_name()])) {
    if (!session_start()) reply(503, ['error' => 'Session unavailable.']);
    if (!authenticated($config)) endSession();
}
if ($action === 'status') reply(200, ['authenticated' => authenticated($config)]);
if ($action === 'logout') { endSession(); reply(200, ['ok' => true]); }

if ($action === 'login') {
    $raw = file_get_contents('php://input', false, null, 0, 2049);
    if (strlen($raw) > 2048) reply(413, ['error' => 'Request too large.']);
    $body = json_decode($raw, true);
    $password = is_array($body) ? ($body['password'] ?? null) : null;
    if (!is_string($password) || strlen($password) < 1 || strlen($password) > 256) reply(400, ['error' => 'Enter a valid password.']);

    // No raw IP addresses in application rate-limit files. HMAC identifiers are still personal data.
    $rateDir = $private . '/rate';
    if (!is_dir($rateDir) && !@mkdir($rateDir, 0700, true)) reply(503, ['error' => 'Access temporarily unavailable.']);
    foreach (glob($rateDir . '/*.json') ?: [] as $old) { if (filemtime($old) < time() - 900) @unlink($old); }
    $key = hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown', $config['rate_secret']);
    $handle = @fopen($rateDir . '/' . $key . '.json', 'c+');
    if (!$handle || !flock($handle, LOCK_EX)) reply(503, ['error' => 'Access temporarily unavailable.']);
    $rate = json_decode(stream_get_contents($handle), true) ?: ['start' => time(), 'count' => 0];
    if ($rate['start'] < time() - 900) $rate = ['start' => time(), 'count' => 0];
    if ($rate['count'] >= 10) {
        flock($handle, LOCK_UN); fclose($handle);
        header('Retry-After: ' . max(1, 900 - (time() - $rate['start'])));
        reply(429, ['error' => 'Too many attempts. Try again in 15 minutes.']);
    }
    $valid = password_verify($password, $config['password_hash']);
    $rate['count']++;
    rewind($handle); ftruncate($handle, 0); fwrite($handle, json_encode($rate)); fflush($handle); flock($handle, LOCK_UN); fclose($handle);
    if (!$valid) reply(401, ['error' => 'Incorrect password.']);
    if (session_status() !== PHP_SESSION_ACTIVE && !session_start()) reply(503, ['error' => 'Session unavailable.']);
    session_regenerate_id(true);
    $_SESSION = ['expires' => time() + TTL, 'credential' => hash('sha256', $config['password_hash'])];
    session_write_close();
    reply(200, ['ok' => true]);
}

if (!authenticated($config)) reply(401, ['error' => 'Sign in at /msfs-beta/ before downloading.']);
$version = $config['version'] ?? '';
if (!is_string($version) || !preg_match('/\A[0-9]+(?:\.[0-9]+){1,3}\z/', $version)) reply(503, ['error' => 'Package unavailable.']);
$name = 'SKALARKIe_MSFSInterface_v' . $version . '.zip';
$file = $private . '/downloads/' . $name;
if (!is_file($file) || !is_readable($file)) reply(404, ['error' => 'Package not available yet.']);
session_write_close();
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($file));
readfile($file);
