<?php
declare(strict_types=1);

class AuthController
{
    public function __construct(private BackofficeModel $model)
    {
    }

    public function showLogin(): void
    {
        if (admin_is_logged_in()) {
            admin_redirect('dashboard');
        }

        admin_render('auth/login', [
            'pageTitle' => 'Ingreso al backoffice',
            'route' => 'login',
        ]);
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            admin_redirect('login');
        }

        admin_csrf_validate();

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            admin_flash_set('danger', 'Completa usuario y contraseña.');
            admin_redirect('login');
        }

        $user = $this->model->findUserByUsername($username);
        if (!$user || !(int) $user['is_active']) {
            admin_flash_set('danger', 'Usuario no autorizado.');
            admin_redirect('login');
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            admin_flash_set('danger', 'Credenciales invalidas.');
            admin_redirect('login');
        }

        session_regenerate_id(true);
        $_SESSION['rm_admin_user'] = [
            'rm_user_id' => (int) $user['rm_user_id'],
            'username' => (string) $user['username'],
            'full_name' => (string) $user['full_name'],
            'role_name' => (string) $user['role_name'],
        ];
        $this->model->updateLastLogin((int) $user['rm_user_id']);

        admin_flash_set('success', 'Bienvenido al panel de administracion.');
        admin_redirect('dashboard');
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        session_start();
        admin_flash_set('success', 'Sesion cerrada correctamente.');
        admin_redirect('login');
    }
}
