<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
json_out(['ok' => true, 'token' => (string)$_SESSION['_csrf']]);
