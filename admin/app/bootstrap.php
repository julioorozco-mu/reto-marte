<?php
declare(strict_types=1);

define('RM_PROJECT_ROOT', dirname(__DIR__, 2));
define('RM_ADMIN_PATH', RM_PROJECT_ROOT . '/admin');
define('RM_APP_PATH', RM_ADMIN_PATH . '/app');
define('RM_VIEWS_PATH', RM_APP_PATH . '/views');
define('RM_STORAGE_PATH', RM_ADMIN_PATH . '/storage');

date_default_timezone_set('America/Mexico_City');

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');

$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('RMADMINSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once RM_PROJECT_ROOT . '/config/database.php';
require_once RM_APP_PATH . '/helpers.php';
require_once RM_APP_PATH . '/services/ExcelExporter.php';
require_once RM_APP_PATH . '/services/PdfGenerator.php';
require_once RM_APP_PATH . '/models/BackofficeModel.php';
require_once RM_APP_PATH . '/controllers/AuthController.php';
require_once RM_APP_PATH . '/controllers/BackofficeController.php';
