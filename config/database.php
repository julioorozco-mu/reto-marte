<?php
declare(strict_types=1);

// true = Producción, false = Desarrollo local (XAMPP)
define('IS_PRODUCTION', true); 

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
