<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

api_handle(function (PDO $pdo): array {
    return [
        'clientes'  => $pdo->query('SELECT ID_Cliente,Nombre_Completo_Razon_Social FROM Cliente ORDER BY Nombre_Completo_Razon_Social')->fetchAll(),
        'vehiculos' => $pdo->query("SELECT VIN,Marca,Modelo,Estado FROM Vehiculo WHERE Estado<>'Vendido' ORDER BY Marca,Modelo")->fetchAll(),
        'mecanicos' => $pdo->query('SELECT ID_Mecanico,Nombre FROM Mecanico ORDER BY Nombre')->fetchAll(),
        'items'     => $pdo->query('SELECT o.*,c.Nombre_Completo_Razon_Social AS Cliente,v.Marca,v.Modelo,m.Nombre AS Mecanico FROM Orden_Reparacion o JOIN Cliente c ON c.ID_Cliente=o.ID_Cliente JOIN Vehiculo v ON v.VIN=o.VIN JOIN Mecanico m ON m.ID_Mecanico=o.ID_Mecanico ORDER BY o.ID_OR DESC LIMIT 150')->fetchAll(),
    ];
}, function (PDO $pdo, string $action): array {
    switch ($action) {
        case 'reparacion_crear':
            $cliente = positive_int('cliente'); $vin = strtoupper(post_string('vin', 30)); $mecanico = positive_int('mecanico');
            if (!$cliente || !$vin || !$mecanico) throw new RuntimeException('Completá cliente, vehículo y mecánico.');
            $pdo->beginTransaction();
            $q = $pdo->prepare('SELECT Estado FROM Vehiculo WHERE VIN=? FOR UPDATE'); $q->execute([$vin]); $veh = $q->fetch();
            if (!$veh) throw new RuntimeException('El vehículo no existe.');
            if ($veh['Estado'] === 'Vendido') throw new RuntimeException('El vehículo figura vendido y no puede abrirse una reparación desde este módulo.');
            foreach ([['Cliente', 'ID_Cliente', $cliente], ['Mecanico', 'ID_Mecanico', $mecanico]] as [$tabla, $col, $idref]) {
                $q = $pdo->prepare("SELECT {$col} FROM {$tabla} WHERE {$col}=?"); $q->execute([$idref]);
                if (!$q->fetch()) throw new RuntimeException('El cliente o el mecánico seleccionado no existe.');
            }
            $ingreso = ($_POST['fecha_ingreso'] ?? '') ?: date('Y-m-d');
            $salida = ($_POST['fecha_salida'] ?? '') ?: null;
            foreach ([$ingreso, $salida] as $f) { if ($f !== null && (!($d = DateTime::createFromFormat('Y-m-d', $f)) || $d->format('Y-m-d') !== $f)) throw new RuntimeException('Fecha inválida.'); }
            if ($salida !== null && $salida < $ingreso) throw new RuntimeException('La fecha de salida no puede ser anterior a la de ingreso.');
            $q = $pdo->prepare("INSERT INTO Orden_Reparacion (Fecha_Apertura,Fecha_Cierre,Diagnostico,Estado,Costo_Total,ID_Cliente,VIN,ID_Mecanico) VALUES (?,?,?,'Pendiente',?,?,?,?)");
            $q->execute([$ingreso, $salida, post_string('diagnostico', 5000) ?: null, max(0, (float)($_POST['costo'] ?? 0)), $cliente, $vin, $mecanico]);
            $q = $pdo->prepare("UPDATE Vehiculo SET Estado='En Reparacion' WHERE VIN=? AND Estado<>'Vendido'"); $q->execute([$vin]);
            $pdo->commit();
            return ['message' => 'Orden de reparación creada.', 'redirect' => 'reparaciones.html'];
        case 'reparacion_estado':
            $id = positive_int('id'); $estado = post_string('estado', 30);
            if (!$id || !in_array($estado, ['Pendiente', 'En Diagnostico', 'En Reparacion', 'Finalizada', 'Entregada'], true)) throw new RuntimeException('Estado de reparación no válido.');
            $pdo->beginTransaction();
            $q = $pdo->prepare('SELECT VIN,Fecha_Apertura FROM Orden_Reparacion WHERE ID_OR=? FOR UPDATE'); $q->execute([$id]); $orden = $q->fetch();
            if (!$orden) throw new RuntimeException('No se encontró la orden.');
            if (in_array($estado, ['Finalizada', 'Entregada'], true)) {
                $hoy = max(date('Y-m-d'), (string)$orden['Fecha_Apertura']);
                $q = $pdo->prepare('UPDATE Orden_Reparacion SET Estado=?,Fecha_Cierre=COALESCE(Fecha_Cierre,?) WHERE ID_OR=?'); $q->execute([$estado, $hoy, $id]);
            } else {
                $q = $pdo->prepare('UPDATE Orden_Reparacion SET Estado=? WHERE ID_OR=?'); $q->execute([$estado, $id]);
            }
            if ($estado === 'Entregada') {
                $q = $pdo->prepare("UPDATE Vehiculo SET Estado='Disponible' WHERE VIN=? AND Estado='En Reparacion' AND NOT EXISTS (SELECT 1 FROM Orden_Reparacion WHERE VIN=? AND ID_OR<>? AND Estado NOT IN ('Finalizada','Entregada'))");
                $q->execute([$orden['VIN'], $orden['VIN'], $id]);
            } elseif (in_array($estado, ['Pendiente', 'En Diagnostico', 'En Reparacion'], true)) {
                $q = $pdo->prepare("UPDATE Vehiculo SET Estado='En Reparacion' WHERE VIN=? AND Estado<>'Vendido'"); $q->execute([$orden['VIN']]);
            }
            $pdo->commit();
            return ['message' => 'Estado de la orden actualizado.', 'redirect' => 'reparaciones.html'];
        default:
            throw new RuntimeException('Acción no reconocida.');
    }
});
