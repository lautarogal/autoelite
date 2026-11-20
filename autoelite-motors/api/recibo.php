<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

api_handle(function (PDO $pdo): array {
    ensure_credito_columns($pdo);
    $id = (int)($_GET['id'] ?? 0);
    $q = $pdo->prepare('SELECT v.*,c.Nombre_Completo_Razon_Social AS Cliente,c.Direccion,c.Telefono,c.Correo_Electronico,vh.VIN,vh.Marca,vh.Modelo,vh.Anio,a.Nombre AS Vendedor FROM Venta v JOIN Cliente c ON c.ID_Cliente=v.ID_Cliente JOIN Vehiculo vh ON vh.VIN=v.VIN JOIN Asesor_Ventas a ON a.ID_Vendedor=v.ID_Vendedor WHERE v.ID_Venta=?');
    $q->execute([$id]);
    return ['item' => $q->fetch() ?: null];
});
