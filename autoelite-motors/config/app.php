<?php
declare(strict_types=1);

function load_env(string $file): void {
    if (!is_file($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key); $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) $value = substr($value, 1, -1);
        if (!array_key_exists($key, $_ENV)) $_ENV[$key] = $value;
    }
}
function envv(string $key, string $default = ''): string { return (string)($_ENV[$key] ?? $default); }
load_env(dirname(__DIR__) . '/.env');
date_default_timezone_set('America/Argentina/Buenos_Aires');
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly'=>true, 'secure'=>!empty($_SERVER['HTTPS']), 'samesite'=>'Lax']);
    session_start();
}
if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.htmlspecialchars($_SESSION['_csrf'], ENT_QUOTES, 'UTF-8').'">'; }
function verify_csrf(): void {
    $token = (string)($_POST['_csrf'] ?? '');
    if (!hash_equals((string)($_SESSION['_csrf'] ?? ''), $token)) { http_response_code(419); exit('La sesión del formulario venció. Volvé atrás y recargá la página.'); }
}
function e(mixed $value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(mixed $value): string { return '$ ' . number_format((float)$value, 2, ',', '.'); }
function redirect(string $page, array $params = []): never { header('Location: index.php?' . http_build_query(['page'=>$page] + $params)); exit; }
function flash(string $type, string $message): void { $_SESSION['_flash'] = ['type'=>$type, 'message'=>$message]; }
function take_flash(): ?array { $f = $_SESSION['_flash'] ?? null; unset($_SESSION['_flash']); return $f; }
function post_string(string $key, int $max = 500): string { return mb_substr(trim((string)($_POST[$key] ?? '')), 0, $max); }
function positive_int(string $key): int { return max(0, (int)($_POST[$key] ?? 0)); }
