<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

api_handle(function (PDO $pdo): array {
    return [
        'disponibles'  => (int)$pdo->query("SELECT COUNT(*) FROM Vehiculo WHERE Estado='Disponible'")->fetchColumn(),
        'reservados'   => (int)$pdo->query("SELECT COUNT(*) FROM Vehiculo WHERE Estado='Reservado'")->fetchColumn(),
        'vendidos'     => (int)$pdo->query("SELECT COUNT(*) FROM Vehiculo WHERE Estado='Vendido'")->fetchColumn(),
        'clientes'     => (int)$pdo->query('SELECT COUNT(*) FROM Cliente')->fetchColumn(),
        'ventas'       => (int)$pdo->query('SELECT COUNT(*) FROM Venta')->fetchColumn(),
        'reparaciones' => (int)$pdo->query("SELECT COUNT(*) FROM Orden_Reparacion WHERE Estado NOT IN ('Finalizada','Entregada')")->fetchColumn(),
        'ultimas'      => $pdo->query('SELECT v.ID_Venta,v.Fecha,v.Precio_Venta,c.Nombre_Completo_Razon_Social AS Cliente, vh.Marca,vh.Modelo FROM Venta v JOIN Cliente c ON c.ID_Cliente=v.ID_Cliente JOIN Vehiculo vh ON vh.VIN=v.VIN ORDER BY v.ID_Venta DESC LIMIT 6')->fetchAll(),
    ];
});
