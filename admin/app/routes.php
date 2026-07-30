<?php
declare(strict_types=1);

return [
    'login' => [AuthController::class, 'showLogin'],
    'authenticate' => [AuthController::class, 'login'],
    'logout' => [AuthController::class, 'logout'],
    'dashboard' => [BackofficeController::class, 'dashboard'],
    'participants' => [BackofficeController::class, 'participants'],
    'participant_show' => [BackofficeController::class, 'participantShow'],
    'reports' => [BackofficeController::class, 'reports'],
    'export' => [BackofficeController::class, 'export'],
    'users' => [BackofficeController::class, 'users'],
    'settings' => [BackofficeController::class, 'settings'],
    'not_found' => [BackofficeController::class, 'notFound'],
];
