<?php
declare(strict_types=1);
require_once __DIR__ . '/app.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = envv('DB_HOST', '127.0.0.1'); $port = envv('DB_PORT', '3306');
    $name = envv('DB_DATABASE', 'concesionaria'); $charset = envv('DB_CHARSET', 'utf8mb4');
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    try {
        $pdo = new PDO($dsn, envv('DB_USERNAME', 'root'), envv('DB_PASSWORD', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $ex) {
        error_log('AutoElite DB: ' . $ex->getMessage());
        http_response_code(500);
        $debug = envv('APP_DEBUG', 'false') === 'true';
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['ok' => false, 'message' => 'No se pudo conectar a la base de datos. Verificá Apache/MySQL, el archivo .env y que exista la base ' . $name . '.' . ($debug ? ' ' . $ex->getMessage() : '')], JSON_UNESCAPED_UNICODE));
    }
    return $pdo;
}
