<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

api_handle(function (PDO $pdo): array {
    return [
        'vendedores' => $pdo->query('SELECT ID_Vendedor,Nombre,DNI,Edad,Direccion,Horario_Trabajo FROM Asesor_Ventas ORDER BY Nombre')->fetchAll(),
        'gerentes'   => $pdo->query('SELECT ID_FI,Nombre,DNI,Edad,Direccion,Horario_Trabajo FROM Gerente_FI ORDER BY Nombre')->fetchAll(),
        'asesores'   => $pdo->query('SELECT ID_Asesor_Srv,Nombre,DNI,Edad,Direccion,Horario_Trabajo FROM Asesor_Servicio ORDER BY Nombre')->fetchAll(),
        'mecanicos'  => $pdo->query('SELECT ID_Mecanico,Nombre,DNI,Edad,Direccion,Horario_Trabajo FROM Mecanico ORDER BY Nombre')->fetchAll(),
    ];
});
