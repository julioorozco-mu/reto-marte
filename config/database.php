<?php
declare(strict_types=1);

// Detección automática del entorno (Producción vs Desarrollo local)
$isProduction = true;
if (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    if ($host === 'localhost' || $host === '127.0.0.1' || strpos($host, '192.168.') === 0 || strpos($host, '10.') === 0) {
        $isProduction = false;
    }
} else {
    // Si se ejecuta por consola (CLI), verificamos si estamos en la ruta de XAMPP
    if (strpos(str_replace('\\', '/', __DIR__), '/xampp/') !== false) {
        $isProduction = false;
    }
}
define('IS_PRODUCTION', $isProduction); 

if (IS_PRODUCTION) {
    // Credenciales de Producción
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'clubemprende');
    define('DB_USER', 'clubemprende');
    define('DB_PASS', 'wowi5idire6iPruy53');
} else {
    // Credenciales de Desarrollo Local
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'retomarte');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

// Prefijo de las tablas
define('DB_PREFIX', 'rm_');

function rm_get_pdo(): PDO
{
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
