<section class="page-head">
  <div>
    <p class="eyebrow">Administracion</p>
    <h1>Usuarios</h1>
  </div>
</section>

<?php
$editing = is_array($editUser ?? null);
$roles = is_array($availableRoles ?? null) ? $availableRoles : ['admin', 'superadmin', 'editor'];
?>

<section class="panel-grid">
  <article class="panel-card">
    <h2><?= $editing ? 'Editar usuario' : 'Crear usuario' ?></h2>
    <p><?= $editing ? 'Actualiza el rol, datos de acceso o estado del usuario seleccionado.' : 'Este modulo queda listo para ampliar accesos al backoffice.' ?></p>
    <form method="post" action="<?= admin_url('users') ?>" class="stack-form">
      <?= admin_csrf_input() ?>
      <input type="hidden" name="action" value="<?= $editing ? 'update_user' : 'create_user' ?>" />
      <?php if ($editing): ?>
        <input type="hidden" name="user_id" value="<?= admin_h((string) $editUser['rm_user_id']) ?>" />
      <?php endif; ?>
      <label><span>Usuario</span><input type="text" name="username" value="<?= admin_h($editUser['username'] ?? '') ?>" required /></label>
      <label><span>Nombre completo</span><input type="text" name="full_name" value="<?= admin_h($editUser['full_name'] ?? '') ?>" required /></label>
      <label><span>Correo</span><input type="email" name="email" value="<?= admin_h($editUser['email'] ?? '') ?>" /></label>
      <label><span>Contraseña<?= $editing ? ' nueva (opcional)' : '' ?></span><input type="password" name="password" <?= $editing ? '' : 'required' ?> /></label>
      <label><span>Rol</span>
        <select name="role_name">
          <?php foreach ($roles as $role): ?>
            <option value="<?= admin_h($role) ?>" <?= ($editUser['role_name'] ?? 'admin') === $role ? 'selected' : '' ?>><?= admin_h($role) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if ($editing): ?>
        <label><span>Estatus</span>
          <select name="is_active">
            <option value="1" <?= ((int) ($editUser['is_active'] ?? 1) === 1) ? 'selected' : '' ?>>Activo</option>
            <option value="0" <?= ((int) ($editUser['is_active'] ?? 1) === 0) ? 'selected' : '' ?>>Inactivo</option>
          </select>
        </label>
      <?php endif; ?>
      <button type="submit"><?= $editing ? 'Actualizar usuario' : 'Guardar usuario' ?></button>
      <?php if ($editing): ?>
        <a class="secondary-link" href="<?= admin_url('users') ?>">Cancelar edicion</a>
      <?php endif; ?>
    </form>
  </article>
  <article class="panel-card">
    <h2>Usuarios registrados</h2>
    <div class="table-scroll compact">
      <table>
        <thead><tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Activo</th><th>Ultimo acceso</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td>#<?= admin_h((string) $user['rm_user_id']) ?></td>
              <td><?= admin_h($user['username']) ?></td>
              <td><?= admin_h($user['full_name']) ?></td>
              <td><?= admin_h($user['role_name']) ?></td>
              <td><?= ((int) $user['is_active']) ? 'Si' : 'No' ?></td>
              <td><?= admin_h(admin_format_date($user['last_login_at'])) ?></td>
              <td>
                <div class="table-actions">
                  <a class="secondary-link" href="<?= admin_url('users', ['edit' => (int) $user['rm_user_id']]) ?>">Editar</a>
                  <?php if ((int) ($currentUser['rm_user_id'] ?? 0) !== (int) $user['rm_user_id']): ?>
                    <form method="post" action="<?= admin_url('users') ?>" onsubmit="return confirm('¿Eliminar este usuario?');" style="display:inline;">
                      <?= admin_csrf_input() ?>
                      <input type="hidden" name="action" value="delete_user" />
                      <input type="hidden" name="user_id" value="<?= admin_h((string) $user['rm_user_id']) ?>" />
                      <button type="submit" class="danger-link">Eliminar</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </article>
</section>
