<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/src/Support/helpers.php';

$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = 80;
$config = [
    'site' => ['url' => 'http://localhost:8000'],
    'security' => ['admin_session_idle_timeout' => 1800],
];

ensure_session_started($config);
$cookie = session_get_cookie_params();

if (($cookie['httponly'] ?? false) !== true || ($cookie['samesite'] ?? '') !== 'Lax') {
    throw new RuntimeException('Secure session cookie options were not applied.');
}

$beforeLogin = session_id();
set_admin_user([
    'id' => 1,
    'login' => 'admin',
    'name' => 'Administrator',
    'email' => 'admin@example.test',
    'role' => 'administrator',
]);

if ($beforeLogin === session_id()) {
    throw new RuntimeException('The session ID was not regenerated after login.');
}

$_SESSION['_admin_last_activity'] = time() - 1801;
if (is_admin_authenticated()) {
    throw new RuntimeException('An idle administrator session remained authenticated.');
}

session_destroy();

echo "PASS secure session cookie, login regeneration and idle timeout\n";
