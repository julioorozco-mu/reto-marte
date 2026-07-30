<?php if (($route ?? '') === 'login'): ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= admin_h($pageTitle ?? 'Backoffice') ?></title>
  <link rel="stylesheet" href="<?= admin_asset('admin.css') ?>" />
  <link rel="icon" href="../img/UNACHm.png" type="image/png" />
</head>
<body class="auth-shell">
  <?= $content ?>
</body>
</html>
<?php return; endif; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= admin_h(($pageTitle ?? 'Backoffice') . ' | Reto Marte') ?></title>
  <link rel="stylesheet" href="<?= admin_asset('admin.css') ?>" />
  <link rel="icon" href="../img/UNACHm.png" type="image/png" />
</head>
<body class="admin-shell">
  <div class="admin-app">
    <aside class="sidebar" id="sidebar">
      <div class="brand-block">
        <div class="brand-mark">RM</div>
        <div>
          <strong>Reto Marte</strong>
          <span>Backoffice</span>
        </div>
      </div>
      <nav class="sidebar-nav">
        <a class="<?= ($route ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= admin_url('dashboard') ?>">Dashboard</a>
        <a class="<?= ($route ?? '') === 'participants' ? 'active' : '' ?>" href="<?= admin_url('participants') ?>">Participantes</a>
        <a class="<?= ($route ?? '') === 'reports' ? 'active' : '' ?>" href="<?= admin_url('reports') ?>">Reportes</a>
        <a class="<?= ($route ?? '') === 'users' ? 'active' : '' ?>" href="<?= admin_url('users') ?>">Usuarios</a>
        <a class="<?= ($route ?? '') === 'settings' ? 'active' : '' ?>" href="<?= admin_url('settings') ?>">Configuracion</a>
      </nav>
    </aside>
    <div class="admin-main">
      <header class="topbar">
        <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Abrir menu">☰</button>
        <div class="topbar-title">
          <span><?= admin_h($pageTitle ?? 'Panel') ?></span>
          <small>Base de datos marte / prefijo rm_</small>
        </div>
        <div class="topbar-user">
          <div class="user-meta">
            <strong><?= admin_h($currentUser['full_name'] ?? 'Administrador') ?></strong>
            <span><?= admin_h($currentUser['role_name'] ?? 'admin') ?></span>
          </div>
          <a class="logout-link" href="<?= admin_url('logout') ?>">Cerrar sesion</a>
        </div>
      </header>
      <main class="content-wrap">
        <?php if ($flash = admin_flash_get()): ?>
          <div class="flash flash-<?= admin_h($flash['type'] ?? 'info') ?>"><?= admin_h($flash['message'] ?? '') ?></div>
        <?php endif; ?>
        <?= $content ?>
      </main>
    </div>
  </div>
  <script src="<?= admin_asset('admin.js') ?>"></script>
</body>
</html>
