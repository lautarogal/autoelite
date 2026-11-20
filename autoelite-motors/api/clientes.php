<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

api_handle(function (PDO $pdo): array {
    $search = trim((string)($_GET['q'] ?? ''));
    $editId = (int)($_GET['edit'] ?? 0);
    $out = ['edit' => null];
    if ($editId) {
        $q = $pdo->prepare('SELECT ID_Cliente,Nombre_Completo_Razon_Social,DNI,Direccion,Telefono,Correo_Electronico,Historial_Crediticio,Referencias_Bancarias,Tipo_Cliente FROM Cliente WHERE ID_Cliente=?');
        $q->execute([$editId]);
        $out['edit'] = $q->fetch() ?: null;
    }
    $sql = 'SELECT ID_Cliente,Nombre_Completo_Razon_Social,DNI,Direccion,Telefono,Correo_Electronico,Historial_Crediticio,Referencias_Bancarias,Tipo_Cliente FROM Cliente'; $args = [];
    if ($search !== '') {
        $sql .= ' WHERE Nombre_Completo_Razon_Social LIKE ? OR DNI LIKE ? OR Telefono LIKE ?';
        $like = "%{$search}%"; $args = [$like, preg_replace('/\D/', '', $search) !== '' ? '%' . preg_replace('/\D/', '', $search) . '%' : $like, $like];
    }
    $sql .= ' ORDER BY ID_Cliente DESC LIMIT 200';
    $q = $pdo->prepare($sql); $q->execute($args);
    $out['items'] = $q->fetchAll();
    return $out;
}, function (PDO $pdo, string $action): array {
    $tipos = ['Particular'];
    /* DNI opcional: se guarda solo con dígitos (7 u 8) y no puede repetirse entre clientes. */
    $dniUnico = function (?int $excluir) use ($pdo): ?string {
        $dni = preg_replace('/\D/', '', post_string('dni', 20));
        if ($dni === '') return null;
        if (!preg_match('/^\d{7,8}$/', $dni)) throw new RuntimeException('El DNI debe tener 7 u 8 dígitos.');
        $q = $pdo->prepare('SELECT ID_Cliente FROM Cliente WHERE DNI=? AND ID_Cliente<>?'); $q->execute([$dni, $excluir ?? 0]);
        if ($q->fetch()) throw new RuntimeException('Ya existe un cliente registrado con ese DNI.');
        return $dni;
    };
    switch ($action) {
        case 'cliente_crear':
            $nombre = post_string('nombre', 150); $tipo = post_string('tipo_cliente', 40);
            if ($nombre === '' || !in_array($tipo, $tipos, true)) throw new RuntimeException('Ingresá el nombre y un tipo de cliente válido.');
            $q = $pdo->prepare('INSERT INTO Cliente (Nombre_Completo_Razon_Social,DNI,Direccion,Telefono,Correo_Electronico,Historial_Crediticio,Referencias_Bancarias,Tipo_Cliente) VALUES (?,?,?,?,?,?,?,?)');
            $q->execute([$nombre, $dniUnico(null), post_string('direccion', 200) ?: null, post_string('telefono', 30) ?: null, post_string('correo', 100) ?: null, post_string('historial', 5000) ?: null, post_string('referencias', 5000) ?: null, $tipo]);
            return ['message' => 'Cliente registrado correctamente.', 'redirect' => 'clientes.html'];
        case 'cliente_editar':
            $id = positive_int('id'); $nombre = post_string('nombre', 150); $tipo = post_string('tipo_cliente', 40);
            if (!$id || $nombre === '' || !in_array($tipo, $tipos, true)) throw new RuntimeException('Revisá los datos del cliente.');
            $q = $pdo->prepare('UPDATE Cliente SET Nombre_Completo_Razon_Social=?,DNI=?,Direccion=?,Telefono=?,Correo_Electronico=?,Historial_Crediticio=?,Referencias_Bancarias=?,Tipo_Cliente=? WHERE ID_Cliente=?');
            $q->execute([$nombre, $dniUnico($id), post_string('direccion', 200) ?: null, post_string('telefono', 30) ?: null, post_string('correo', 100) ?: null, post_string('historial', 5000) ?: null, post_string('referencias', 5000) ?: null, $tipo, $id]);
            return ['message' => 'Datos del cliente actualizados.', 'redirect' => 'clientes.html'];
        case 'cliente_eliminar':
            $q = $pdo->prepare('DELETE FROM Cliente WHERE ID_Cliente=?'); $q->execute([positive_int('id')]);
            return ['message' => 'Cliente eliminado.', 'redirect' => 'clientes.html'];
        default:
            throw new RuntimeException('Acción no reconocida.');
    }
});
