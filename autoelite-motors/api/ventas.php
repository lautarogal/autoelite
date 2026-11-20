<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

api_handle(function (PDO $pdo): array {
    ensure_credito_columns($pdo);
    return [
        'clientes'   => $pdo->query('SELECT ID_Cliente,Nombre_Completo_Razon_Social FROM Cliente ORDER BY Nombre_Completo_Razon_Social')->fetchAll(),
        'vehiculos'  => $pdo->query("SELECT VIN,Marca,Modelo,Precio FROM Vehiculo WHERE Estado='Disponible' ORDER BY Marca,Modelo")->fetchAll(),
        'vendedores' => $pdo->query('SELECT ID_Vendedor,Nombre FROM Asesor_Ventas ORDER BY Nombre')->fetchAll(),
        'gerentes'   => $pdo->query('SELECT ID_FI,Nombre FROM Gerente_FI ORDER BY Nombre')->fetchAll(),
        'items'      => $pdo->query('SELECT v.*,c.Nombre_Completo_Razon_Social AS Cliente,vh.Marca,vh.Modelo,a.Nombre AS Vendedor FROM Venta v JOIN Cliente c ON c.ID_Cliente=v.ID_Cliente JOIN Vehiculo vh ON vh.VIN=v.VIN JOIN Asesor_Ventas a ON a.ID_Vendedor=v.ID_Vendedor ORDER BY v.ID_Venta DESC LIMIT 100')->fetchAll(),
    ];
}, function (PDO $pdo, string $action): array {
    if ($action !== 'venta_crear') throw new RuntimeException('Acción no reconocida.');
    ensure_credito_columns($pdo);
    $cliente = positive_int('cliente'); $vendedor = positive_int('vendedor'); $vin = strtoupper(post_string('vin', 30)); $metodo = post_string('metodo_pago', 20);
    $precio = (float)($_POST['precio_venta'] ?? 0); $impuestos = max(0, (float)($_POST['impuestos'] ?? 0)); $fi = positive_int('id_fi');
    if (!$cliente || !$vendedor || $vin === '' || $precio <= 0 || !in_array($metodo, ['Contado', 'Credito'], true)) throw new RuntimeException('Completá cliente, vehículo, asesor, método de pago y precio válido.');
    $cuotas = null; $recargo = 0.0;
    if ($metodo === 'Credito') {
        $cuotas = (int)($_POST['cuotas'] ?? 0);
        if ($cuotas < 1 || $cuotas > 120) throw new RuntimeException('Indicá en cuántas cuotas se financia la venta (entre 1 y 120).');
        if (($_POST['con_recargo'] ?? '0') === '1') {
            $recargo = round((float)($_POST['recargo'] ?? 0), 2);
            if ($recargo <= 0) throw new RuntimeException('Indicá el monto del recargo en pesos (mayor a 0) o elegí "sin recargo".');
            if ($recargo > 9999999999.99) throw new RuntimeException('El recargo es demasiado alto.');
        }
    }
    $pdo->beginTransaction();
    $q = $pdo->prepare('SELECT Estado FROM Vehiculo WHERE VIN=? FOR UPDATE'); $q->execute([$vin]); $veh = $q->fetch();
    if (!$veh) throw new RuntimeException('El vehículo seleccionado no existe.');
    if ($veh['Estado'] !== 'Disponible') throw new RuntimeException('El vehículo no está disponible para la venta.');
    $q = $pdo->prepare('SELECT ID_Cliente FROM Cliente WHERE ID_Cliente=?'); $q->execute([$cliente]); if (!$q->fetch()) throw new RuntimeException('El cliente no existe.');
    $q = $pdo->prepare('SELECT ID_Vendedor FROM Asesor_Ventas WHERE ID_Vendedor=?'); $q->execute([$vendedor]); if (!$q->fetch()) throw new RuntimeException('El asesor de ventas no existe.');
    $fi = $fi ?: null;
    if ($fi !== null) { $q = $pdo->prepare('SELECT ID_FI FROM Gerente_FI WHERE ID_FI=?'); $q->execute([$fi]); if (!$q->fetch()) throw new RuntimeException('El gerente financiero seleccionado no existe.'); }
    $q = $pdo->prepare('INSERT INTO Venta (Fecha,Precio_Venta,Impuestos,Margen_Utilidad,Metodo_Pago,ID_Cliente,VIN,ID_Vendedor,ID_FI,Cuotas,Recargo) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([($_POST['fecha'] ?? '') ?: date('Y-m-d'), $precio, $impuestos, null, $metodo, $cliente, $vin, $vendedor, $fi, $cuotas, $recargo]);
    $id = (int)$pdo->lastInsertId();
    $q = $pdo->prepare("UPDATE Vehiculo SET Estado='Vendido' WHERE VIN=? AND Estado='Disponible'"); $q->execute([$vin]);
    if ($q->rowCount() !== 1) throw new RuntimeException('No se pudo actualizar el estado del vehículo.');
    $pdo->commit();
    return ['message' => 'Venta registrada. Ya podés consultar e imprimir el recibo.', 'redirect' => 'recibo.html?id=' . $id];
});
