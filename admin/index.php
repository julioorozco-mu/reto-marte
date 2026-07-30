<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pdo = rm_get_pdo();
$model = new BackofficeModel($pdo);
$authController = new AuthController($model);
$backofficeController = new BackofficeController($model);

$route = $_GET['route'] ?? (admin_is_logged_in() ? 'dashboard' : 'login');
$routes = [
	'login' => [$authController, 'showLogin'],
	'authenticate' => [$authController, 'login'],
	'logout' => [$authController, 'logout'],
	'dashboard' => [$backofficeController, 'dashboard'],
	'participants' => [$backofficeController, 'participants'],
	'participant_show' => [$backofficeController, 'participantShow'],
	'reports' => [$backofficeController, 'reports'],
	'export' => [$backofficeController, 'export'],
	'users' => [$backofficeController, 'users'],
	'settings' => [$backofficeController, 'settings'],
	'not_found' => [$backofficeController, 'notFound'],
];
$action = $routes[$route] ?? $routes['not_found'];

call_user_func($action, $route);
