<section class="auth-card">
  <div class="auth-brand">
    <span class="auth-mark">RM</span>
    <div>
      <p>Reto Marte</p>
      <h1>Backoffice</h1>
    </div>
  </div>
  <p class="auth-copy">Ingresa con credenciales autorizadas para administrar los registros de participantes.</p>

  <form class="auth-form" method="post" action="<?= admin_url('authenticate') ?>" autocomplete="off">
    <?= admin_csrf_input() ?>
    <label>
      <span>Usuario</span>
      <input type="text" name="username" autocomplete="off" required />
    </label>
    <label>
      <span>Contraseña</span>
      <input type="password" name="password" autocomplete="new-password" required />
    </label>
    <button type="submit">Entrar al panel</button>
  </form>

  <div class="auth-footnote">Usuario inicial: <strong>admin</strong></div>
</section>
