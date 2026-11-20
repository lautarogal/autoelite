<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/config/admin.php';

/* Único endpoint de escritura de empleados: solo responde a una sesión de administrador activa. */
if (!admin_logged_in()) {
    json_out(['ok' => false, 'message' => 'Acceso restringido. Iniciá sesión en /admin.'], 401);
}

/* tipo => [tabla, clave primaria, nombre legible]. Las cuatro tablas comparten las columnas Nombre, DNI, Edad, Direccion y Horario_Trabajo. */
const EMPLEADO_TABLAS = [
    'vendedor'        => ['Asesor_Ventas', 'ID_Vendedor', 'Asesor de ventas'],
    'gerente_fi'      => ['Gerente_FI', 'ID_FI', 'Gerente financiero'],
    'asesor_servicio' => ['Asesor_Servicio', 'ID_Asesor_Srv', 'Asesor de servicio'],
    'mecanico'        => ['Mecanico', 'ID_Mecanico', 'Mecánico'],
];

/** Valida y normaliza los datos comunes. $id = empleado que se edita (0 si es alta). */
function empleado_datos(PDO $pdo, string $tipo, int $id): array {
    $nombre = post_string('nombre', 100);
    if (mb_strlen($nombre) < 2) throw new RuntimeException('Ingresá el nombre completo del empleado.');

    $dni = preg_replace('/\D/', '', post_string('dni', 20));
    if (!preg_match('/^\d{7,8}$/', $dni)) throw new RuntimeException('El DNI debe tener 7 u 8 dígitos.');

    $edadRaw = trim((string)($_POST['edad'] ?? ''));
    if ($edadRaw === '' || !ctype_digit($edadRaw) || (int)$edadRaw < 18 || (int)$edadRaw > 99) throw new RuntimeException('La edad debe ser un número entero entre 18 y 99.');

    $direccion = post_string('direccion', 200);
    if (mb_strlen($direccion) < 5) throw new RuntimeException('Ingresá la dirección del empleado.');
    $horario = post_string('horario_trabajo', 100);
    if (mb_strlen($horario) < 3) throw new RuntimeException('Ingresá el horario de trabajo (ej: Lunes a Viernes de 9:00 a 18:00).');

    /* El DNI no puede repetirse en ninguna de las áreas. */
    foreach (EMPLEADO_TABLAS as $t => [$tabla, $pk]) {
        $q = $pdo->prepare("SELECT {$pk} FROM {$tabla} WHERE DNI=?"); $q->execute([$dni]);
        $existente = $q->fetchColumn();
        if ($existente !== false && !($t === $tipo && (int)$existente === $id)) throw new RuntimeException('Ya existe un empleado con ese DNI.');
    }
    return [$nombre, $dni, (int)$edadRaw, $direccion, $horario];
}

api_handle(function (PDO $pdo): array {
    $items = [];
    foreach (EMPLEADO_TABLAS as $tipo => [$tabla, $pk, $label]) {
        foreach ($pdo->query("SELECT {$pk} AS id,Nombre,DNI,Edad,Direccion,Horario_Trabajo FROM {$tabla} ORDER BY Nombre")->fetchAll() as $r) {
            $items[] = ['tipo' => $tipo, 'area' => $label] + $r;
        }
    }
    return ['items' => $items];
}, function (PDO $pdo, string $action): array {
    $tipo = post_string('tipo', 30);
    if (!isset(EMPLEADO_TABLAS[$tipo])) throw new RuntimeException('Elegí el área del empleado.');
    [$tabla, $pk, $label] = EMPLEADO_TABLAS[$tipo];

    switch ($action) {
        case 'empleado_crear':
            [$nombre, $dni, $edad, $dir, $horario] = empleado_datos($pdo, $tipo, 0);
            $q = $pdo->prepare("INSERT INTO {$tabla} (Nombre,DNI,Edad,Direccion,Horario_Trabajo) VALUES (?,?,?,?,?)");
            $q->execute([$nombre, $dni, $edad, $dir, $horario]);
            return ['message' => "{$label} «{$nombre}» agregado correctamente."];

        case 'empleado_editar':
            $id = positive_int('id');
            $q = $pdo->prepare("SELECT 1 FROM {$tabla} WHERE {$pk}=?"); $q->execute([$id]);
            if (!$id || !$q->fetchColumn()) throw new RuntimeException('El empleado que querés modificar no existe.');
            [$nombre, $dni, $edad, $dir, $horario] = empleado_datos($pdo, $tipo, $id);
            $q = $pdo->prepare("UPDATE {$tabla} SET Nombre=?,DNI=?,Edad=?,Direccion=?,Horario_Trabajo=? WHERE {$pk}=?");
            $q->execute([$nombre, $dni, $edad, $dir, $horario, $id]);
            return ['message' => "Datos de «{$nombre}» actualizados."];

        case 'empleado_eliminar':
            $id = positive_int('id');
            try {
                $q = $pdo->prepare("DELETE FROM {$tabla} WHERE {$pk}=?"); $q->execute([$id]);
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') throw new RuntimeException('No se puede eliminar: el empleado tiene ventas u órdenes de reparación asociadas.');
                throw $ex;
            }
            if ($q->rowCount() === 0) throw new RuntimeException('El empleado ya no existe.');
            return ['message' => 'Empleado eliminado.'];

        default:
            throw new RuntimeException('Acción no reconocida.');
    }
});
