<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

api_handle(function (PDO $pdo): array {
    $search = trim((string)($_GET['q'] ?? ''));
    $editVin = (string)($_GET['edit'] ?? '');
    $out = ['edit' => null];
    if ($editVin !== '') {
        $q = $pdo->prepare('SELECT * FROM Vehiculo WHERE VIN=?');
        $q->execute([$editVin]);
        $out['edit'] = $q->fetch() ?: null;
    }
    $sql = 'SELECT * FROM Vehiculo'; $args = [];
    if ($search !== '') {
        $sql .= ' WHERE VIN LIKE ? OR Marca LIKE ? OR Modelo LIKE ? OR Estado LIKE ?';
        $like = "%{$search}%"; $args = [$like, $like, $like, $like];
    }
    $sql .= ' ORDER BY Estado,Marca,Modelo LIMIT 300';
    $q = $pdo->prepare($sql); $q->execute($args);
    $out['items'] = $q->fetchAll();
    return $out;
}, function (PDO $pdo, string $action): array {
    switch ($action) {
        case 'vehiculo_crear':
            $vin = strtoupper(post_string('vin', 30)); $marca = post_string('marca', 50); $modelo = post_string('modelo', 100); $tipo = post_string('tipo_vehiculo', 20); $precio = (float)($_POST['precio'] ?? 0);
            if ($vin === '' || $marca === '' || $modelo === '' || $precio <= 0 || !in_array($tipo, ['Nuevo', 'Usado'], true)) throw new RuntimeException('Completá la patente, marca, modelo, tipo y un precio mayor que cero.');
            $q = $pdo->prepare('INSERT INTO Vehiculo (VIN,Marca,Modelo,Anio,Kilometraje,Tipo_Vehiculo,Precio,Garantia,Estado) VALUES (?,?,?,?,?,?,?,?,?)');
            $q->execute([$vin, $marca, $modelo, ($_POST['anio'] ?? '') !== '' ? (int)$_POST['anio'] : null, max(0, (int)($_POST['kilometraje'] ?? 0)), $tipo, $precio, $tipo === 'Nuevo' ? (post_string('garantia', 200) ?: null) : null, 'Disponible']);
            return ['message' => 'Vehículo incorporado al inventario.', 'redirect' => 'vehiculos.html'];
        case 'vehiculo_editar':
            $vin = strtoupper(post_string('vin', 30)); $marca = post_string('marca', 50); $modelo = post_string('modelo', 100); $estado = post_string('estado', 30); $tipo = post_string('tipo_vehiculo', 20); $precio = (float)($_POST['precio'] ?? 0);
            if ($vin === '' || $marca === '' || $modelo === '' || $precio <= 0 || !in_array($tipo, ['Nuevo', 'Usado'], true) || !in_array($estado, ['Disponible', 'Reservado', 'Vendido', 'En Reparacion'], true)) throw new RuntimeException('Revisá los datos del vehículo.');
            $q = $pdo->prepare('UPDATE Vehiculo SET Marca=?,Modelo=?,Anio=?,Kilometraje=?,Tipo_Vehiculo=?,Precio=?,Garantia=?,Estado=? WHERE VIN=?');
            $q->execute([$marca, $modelo, ($_POST['anio'] ?? '') !== '' ? (int)$_POST['anio'] : null, max(0, (int)($_POST['kilometraje'] ?? 0)), $tipo, $precio, $tipo === 'Nuevo' ? (post_string('garantia', 200) ?: null) : null, $estado, $vin]);
            return ['message' => 'Vehículo actualizado.', 'redirect' => 'vehiculos.html'];
        case 'vehiculo_eliminar':
            $q = $pdo->prepare('DELETE FROM Vehiculo WHERE VIN=?'); $q->execute([strtoupper(post_string('vin', 30))]);
            return ['message' => 'Vehículo eliminado.', 'redirect' => 'vehiculos.html'];
        default:
            throw new RuntimeException('Acción no reconocida.');
    }
});
