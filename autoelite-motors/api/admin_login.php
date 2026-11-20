<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/config/admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Método no permitido.'], 405);
if (!hash_equals((string)($_SESSION['_csrf'] ?? ''), (string)($_POST['_csrf'] ?? ''))) {
    json_out(['ok' => false, 'message' => 'La sesión del formulario venció. Recargá la página.'], 419);
}

$lockedUntil = (int)($_SESSION['admin_locked_until'] ?? 0);
if ($lockedUntil > time()) {
    json_out(['ok' => false, 'message' => 'Demasiados intentos fallidos. Esperá ' . ($lockedUntil - time()) . ' segundos.'], 429);
}

$user = (string)($_POST['usuario'] ?? '');
$pass = (string)($_POST['clave'] ?? '');
// Se evalúan ambas comprobaciones siempre, para no revelar cuál de las dos falló.
$okUser = hash_equals(ADMIN_USER, $user);
$okPass = password_verify($pass, ADMIN_PASSWORD_HASH);

if ($okUser && $okPass) {
    session_regenerate_id(true);
    $_SESSION['admin_auth'] = true;
    $_SESSION['admin_last'] = time();
    unset($_SESSION['admin_fails'], $_SESSION['admin_locked_until']);
    json_out(['ok' => true, 'message' => 'Sesión iniciada.']);
}

$_SESSION['admin_fails'] = (int)($_SESSION['admin_fails'] ?? 0) + 1;
if ($_SESSION['admin_fails'] >= ADMIN_MAX_ATTEMPTS) {
    $_SESSION['admin_locked_until'] = time() + ADMIN_LOCK_SECONDS;
    $_SESSION['admin_fails'] = 0;
}
json_out(['ok' => false, 'message' => 'Usuario o contraseña incorrectos.'], 401);
