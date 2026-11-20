<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Método no permitido.'], 405);
if (!hash_equals((string)($_SESSION['_csrf'] ?? ''), (string)($_POST['_csrf'] ?? ''))) {
    json_out(['ok' => false, 'message' => 'La sesión del formulario venció. Recargá la página.'], 419);
}
unset($_SESSION['admin_auth'], $_SESSION['admin_last']);
session_regenerate_id(true);
json_out(['ok' => true]);
