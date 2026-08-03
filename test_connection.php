<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

try {
    echo "Intentando conectar a la base de datos...<br>";
    echo "Entorno: " . (IS_PRODUCTION ? "PRODUCCIÓN" : "DESARROLLO LOCAL") . "<br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Base de datos: " . DB_NAME . "<br><br>";

    // Intentamos obtener la conexión PDO
    $pdo = rm_get_pdo();
    
    echo "<strong style='color: green; font-size: 18px;'>¡Éxito! La conexión se ha establecido correctamente.</strong>";
} catch (PDOException $e) {
    echo "<strong style='color: red; font-size: 18px;'>Error al conectar a la base de datos:</strong><br>";
    echo "<pre style='background: #fff0f0; padding: 10px; border: 1px solid #ffa0a0;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
