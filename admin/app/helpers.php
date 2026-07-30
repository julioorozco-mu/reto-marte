<?php
declare(strict_types=1);

function admin_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_url(string $route, array $params = []): string
{
    $query = array_merge(['route' => $route], $params);
    return 'index.php?' . http_build_query($query);
}

function admin_asset(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

function admin_redirect(string $route, array $params = []): never
{
    header('Location: ' . admin_url($route, $params));
    exit;
}

function admin_flash_set(string $type, string $message): void
{
    $_SESSION['_rm_flash'] = ['type' => $type, 'message' => $message];
}

function admin_flash_get(): ?array
{
    if (!isset($_SESSION['_rm_flash'])) {
        return null;
    }

    $flash = $_SESSION['_rm_flash'];
    unset($_SESSION['_rm_flash']);
    return is_array($flash) ? $flash : null;
}

function admin_current_user(): ?array
{
    return isset($_SESSION['rm_admin_user']) && is_array($_SESSION['rm_admin_user']) ? $_SESSION['rm_admin_user'] : null;
}

function admin_is_logged_in(): bool
{
    return admin_current_user() !== null;
}

function admin_require_auth(): void
{
    if (!admin_is_logged_in()) {
        admin_flash_set('warning', 'Debes iniciar sesion para acceder al backoffice.');
        admin_redirect('login');
    }
}

function admin_csrf_token(): string
{
    if (empty($_SESSION['_rm_csrf'])) {
        $_SESSION['_rm_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_rm_csrf'];
}

function admin_csrf_input(): string
{
    return '<input type="hidden" name="_csrf" value="' . admin_h(admin_csrf_token()) . '" />';
}

function admin_csrf_validate(): void
{
    $posted = $_POST['_csrf'] ?? '';
    $stored = $_SESSION['_rm_csrf'] ?? '';
    if (!is_string($posted) || !is_string($stored) || $posted === '' || !hash_equals($stored, $posted)) {
        http_response_code(419);
        exit('CSRF token invalido.');
    }
}

function admin_render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewFile = RM_VIEWS_PATH . '/' . $view . '.php';
    if (!is_file($viewFile)) {
        throw new RuntimeException('View no encontrada: ' . $view);
    }

    ob_start();
    include $viewFile;
    $content = ob_get_clean();

    $layoutFile = RM_VIEWS_PATH . '/layout.php';
    include $layoutFile;
}

function admin_format_date(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : $value;
}

function admin_safe_filename(string $name): string
{
    $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
    return trim((string) $name, '_');
}

function admin_doc_label(string $type): string
{
    return match ($type) {
        'curp' => 'CURP',
        'certificado' => 'Certificado de estudios',
        'adjunto' => 'Adjunto',
        default => ucfirst($type),
    };
}
