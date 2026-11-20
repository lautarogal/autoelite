<?php
declare(strict_types=1);

/* Credenciales del panel /admin. La contraseña se guarda como hash bcrypt (no en texto plano).
   Para cambiarla: password_hash('nueva-clave', PASSWORD_DEFAULT) y pegar el resultado en ADMIN_PASSWORD_HASH. */
const ADMIN_USER = 'admin1234';
const ADMIN_PASSWORD_HASH = '$2y$10$82/DS8KzzaiokIeQ70P1Te2bLTTJk5xaoQIyzlhyWDVnT.AIreW2.';
const ADMIN_IDLE_SECONDS = 1800;   // la sesión de admin vence tras 30 min sin actividad
const ADMIN_MAX_ATTEMPTS = 5;      // intentos fallidos antes de bloquear
const ADMIN_LOCK_SECONDS = 60;     // duración del bloqueo

function admin_logged_in(): bool {
    if (empty($_SESSION['admin_auth'])) return false;
    if (time() - (int)($_SESSION['admin_last'] ?? 0) > ADMIN_IDLE_SECONDS) {
        unset($_SESSION['admin_auth'], $_SESSION['admin_last']);
        return false;
    }
    $_SESSION['admin_last'] = time();
    return true;
}
