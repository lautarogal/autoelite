<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

/**
 * Crédito: agrega a Venta las columnas Cuotas y Recargo si todavía no existen
 * (también incluido en database/concesionaria_completa.sql). No modifica datos existentes.
 */
function ensure_credito_columns(PDO $pdo): void {
    $q = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='Venta' AND COLUMN_NAME IN ('Cuotas','Recargo')");
    $have = $q->fetchAll(PDO::FETCH_COLUMN);
    $have = array_map('strtolower', $have);
    if (!in_array('cuotas', $have, true)) $pdo->exec('ALTER TABLE Venta ADD COLUMN Cuotas INT NULL');
    if (!in_array('recargo', $have, true)) $pdo->exec('ALTER TABLE Venta ADD COLUMN Recargo DECIMAL(12,2) NOT NULL DEFAULT 0');
}

/**
 * Actualización v3 (también incluida en database/concesionaria_completa.sql). Es NO destructiva:
 *  - Cliente: la columna CURP se renombra a DNI (conserva los datos). RFC y Preferencias_CRM quedan sin usar.
 *  - Asesor_Ventas, Gerente_FI, Asesor_Servicio y Mecanico: agrega DNI, Edad, Direccion y Horario_Trabajo.
 *  - Orden_Reparacion.ID_Asesor_Srv pasa a admitir NULL (ya no se solicita el asesor de servicio).
 * Los campos viejos de empleados no se borran: simplemente dejan de usarse.
 */
function ensure_schema_v3(PDO $pdo): void {
    /* En Windows MySQL devuelve los nombres en minúsculas: se compara todo en minúsculas. */
    $emp = ['asesor_ventas' => 'uq_asesor_ventas_dni', 'gerente_fi' => 'uq_gerente_fi_dni', 'asesor_servicio' => 'uq_asesor_servicio_dni', 'mecanico' => 'uq_mecanico_dni'];
    $rows = $pdo->query("SELECT TABLE_NAME,COLUMN_NAME,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND (
        (TABLE_NAME='Cliente' AND COLUMN_NAME IN ('CURP','DNI')) OR
        (TABLE_NAME IN ('Asesor_Ventas','Gerente_FI','Asesor_Servicio','Mecanico') AND COLUMN_NAME IN ('DNI','Edad','Direccion','Horario_Trabajo')) OR
        (TABLE_NAME='Orden_Reparacion' AND COLUMN_NAME='ID_Asesor_Srv'))")->fetchAll();
    $have = [];
    foreach ($rows as $r) $have[strtolower($r['TABLE_NAME'])][strtolower($r['COLUMN_NAME'])] = $r['IS_NULLABLE'];

    $ok = isset($have['cliente']['dni']) && ($have['orden_reparacion']['id_asesor_srv'] ?? 'YES') === 'YES';
    foreach (array_keys($emp) as $t) $ok = $ok && count($have[$t] ?? []) === 4;
    if ($ok) return;

    /* Ejecuta un ALTER ignorando "ya existe" (columna o índice duplicado): así es seguro repetirlo. */
    $run = function (string $sql) use ($pdo): void {
        try { $pdo->exec($sql); }
        catch (PDOException $ex) { if (!in_array((string)$ex->getCode(), ['42S21', '42S11'], true)) throw $ex; }
    };

    if (!isset($have['cliente']['dni'])) {
        $run(isset($have['cliente']['curp']) ? 'ALTER TABLE Cliente CHANGE COLUMN CURP DNI VARCHAR(20) NULL' : 'ALTER TABLE Cliente ADD COLUMN DNI VARCHAR(20) NULL');
    }
    $defs = ['DNI' => 'VARCHAR(20) NULL', 'Edad' => 'TINYINT UNSIGNED NULL', 'Direccion' => 'VARCHAR(200) NULL', 'Horario_Trabajo' => 'VARCHAR(100) NULL'];
    $idx = array_map('strtolower', $pdo->query("SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE()")->fetchAll(PDO::FETCH_COLUMN));
    foreach ($emp as $t => $ix) {
        foreach ($defs as $c => $def) {
            if (!isset($have[$t][strtolower($c)])) $run("ALTER TABLE {$t} ADD COLUMN {$c} {$def}");
        }
        if (!in_array($ix, $idx, true)) $run("CREATE UNIQUE INDEX {$ix} ON {$t} (DNI)");
    }
    if (($have['orden_reparacion']['id_asesor_srv'] ?? 'YES') === 'NO') $pdo->exec('ALTER TABLE Orden_Reparacion MODIFY ID_Asesor_Srv INT NULL');
}

function json_out(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

/**
 * Punto de entrada común de los endpoints.
 * GET  -> $onGet($pdo): devuelve datos para que el HTML los dibuje.
 * POST -> $onPost($pdo, $action): ejecuta la operación (con verificación CSRF).
 */
function api_handle(callable $onGet, ?callable $onPost = null): never {
    $pdo = db();
    $debug = envv('APP_DEBUG', 'false') === 'true';
    try {
        $q = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('Cliente','Vehiculo','Venta','Orden_Reparacion','Mecanico')");
        if ((int)$q->fetchColumn() < 5) {
            json_out(['ok' => false, 'message' => 'La base de datos está vacía o incompleta. Importá database/concesionaria_completa.sql en phpMyAdmin (pestaña Importar) y recargá la página.'], 500);
        }
        ensure_schema_v3($pdo);
    } catch (PDOException $ex) {
        error_log('AutoElite schema: ' . $ex->getMessage());
        json_out(['ok' => false, 'message' => 'No se pudo actualizar la estructura de la base de datos.' . ($debug ? ' ' . $ex->getMessage() : ' Revisá los permisos del usuario MySQL en .env.')], 500);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $onPost !== null) {
        $token = (string)($_POST['_csrf'] ?? '');
        if (!hash_equals((string)($_SESSION['_csrf'] ?? ''), $token)) {
            json_out(['ok' => false, 'message' => 'La sesión del formulario venció. Recargá la página.'], 419);
        }
        session_write_close();
        try {
            $result = $onPost($pdo, (string)($_POST['action'] ?? ''));
            json_out(['ok' => true] + $result);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('AutoElite: ' . $ex->getMessage());
            $msg = $ex instanceof PDOException && str_contains($ex->getMessage(), '23000')
                ? 'La operación no se puede completar porque el registro ya existe o está relacionado con otros datos.'
                : $ex->getMessage();
            $visible = $ex instanceof RuntimeException || $debug;
            json_out(['ok' => false, 'message' => $visible ? $msg : 'No se pudo completar la operación. Revisá los datos e intentá nuevamente.'], 400);
        }
    }

    session_write_close();
    try {
        json_out(['ok' => true] + $onGet($pdo));
    } catch (Throwable $ex) {
        error_log('AutoElite read: ' . $ex->getMessage());
        json_out(['ok' => false, 'message' => $debug ? $ex->getMessage() : 'No se pudieron cargar los datos. Verificá que el SQL esté importado correctamente.'], 500);
    }
}
