<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/config/admin.php';
json_out(['ok' => true, 'logged' => admin_logged_in()]);
