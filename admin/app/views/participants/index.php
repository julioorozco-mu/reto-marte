<?php
$queryBase = array_filter($filters ?? [], static fn($value) => $value !== '' && $value !== null);
$sortToggle = static function (string $column) use ($sort, $dir, $queryBase): string {
    $nextDir = ($sort === $column && strtoupper($dir) === 'ASC') ? 'DESC' : 'ASC';
    return admin_url('participants', array_merge($queryBase, ['sort' => $column, 'dir' => $nextDir]));
};
?>
<section class="page-head">
  <div>
    <p class="eyebrow">Modulo operativo</p>
    <h1>Participantes</h1>
  </div>
  <div class="page-actions">
    <a class="secondary-link" href="<?= admin_url('export', $queryBase) ?>">Exportar Excel (.xlsx)</a>
  </div>
</section>

<form class="filters-card" method="get" action="index.php">
  <input type="hidden" name="route" value="participants" />
  <div class="filters-grid">
    <label><span>Buscar</span><input type="search" name="q" value="<?= admin_h($filters['q'] ?? '') ?>" placeholder="Nombre, correo, CURP..." /></label>
    <label><span>Institucion</span>
      <select name="institution">
        <option value="">Todas</option>
        <option value="unach" <?= ($filters['institution'] ?? '') === 'unach' ? 'selected' : '' ?>>UNACH</option>
        <option value="cobach" <?= ($filters['institution'] ?? '') === 'cobach' ? 'selected' : '' ?>>COBACH</option>
      </select>
    </label>
    <label><span>Rol</span>
      <select name="role">
        <option value="">Todos</option>
        <option value="estudiante" <?= ($filters['role'] ?? '') === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
        <option value="docente" <?= ($filters['role'] ?? '') === 'docente' ? 'selected' : '' ?>>Docente</option>
      </select>
    </label>
    <label><span>Estatus</span>
      <select name="status">
        <option value="">Todos</option>
        <?php foreach (['Pendiente', 'Validado', 'Rechazado'] as $status): ?>
          <option value="<?= admin_h($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= admin_h($status) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label><span>Fecha inicial</span><input type="date" name="date_from" value="<?= admin_h($filters['date_from'] ?? '') ?>" /></label>
    <label><span>Fecha final</span><input type="date" name="date_to" value="<?= admin_h($filters['date_to'] ?? '') ?>" /></label>
    <label><span>Facultad</span><input type="text" name="faculty" value="<?= admin_h($filters['faculty'] ?? '') ?>" placeholder="Solo UNACH" /></label>
    <label><span>Plantel</span><input type="text" name="plantel" value="<?= admin_h($filters['plantel'] ?? '') ?>" placeholder="Solo COBACH" /></label>
  </div>
  <div class="filters-actions">
    <button type="submit">Aplicar filtros</button>
    <a href="<?= admin_url('participants') ?>">Limpiar</a>
  </div>
</form>

<div class="table-card">
  <div class="table-meta">
    <strong><?= admin_h((string) $total) ?></strong>
    <span>resultados encontrados</span>
  </div>
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th><a href="<?= $sortToggle('id') ?>">ID</a></th>
          <th><a href="<?= $sortToggle('date') ?>">Fecha</a></th>
          <th><a href="<?= $sortToggle('name') ?>">Nombre completo</a></th>
          <th><a href="<?= $sortToggle('institution') ?>">Institucion</a></th>
          <th>Rol</th>
          <th><a href="<?= $sortToggle('place') ?>">Facultad o Plantel</a></th>
          <th><a href="<?= $sortToggle('program') ?>">Carrera o Area</a></th>
          <th><a href="<?= $sortToggle('semester') ?>">Semestre</a></th>
          <th><a href="<?= $sortToggle('gender') ?>">Sexo</a></th>
          <th>Estado</th>
          <th>Municipio</th>
          <th>Correo</th>
          <th>Telefono</th>
          <th><a href="<?= $sortToggle('status') ?>">Estatus</a></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="14" class="empty-row">No hay registros con los filtros actuales.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><a href="<?= admin_url('participant_show', ['id' => $row['id']]) ?>">#<?= admin_h((string) $row['id']) ?></a></td>
              <td><?= admin_h(admin_format_date($row['created_at'])) ?></td>
              <td><?= admin_h($row['full_name']) ?></td>
              <td><span class="pill <?= admin_h($row['institution']) ?>"><?= admin_h(strtoupper($row['institution'])) ?></span></td>
              <td><span class="pill <?= (int)($row['is_teacher'] ?? 0) === 1 ? 'teacher' : 'student' ?>"><?= (int)($row['is_teacher'] ?? 0) === 1 ? 'DOCENTE' : 'ESTUDIANTE' ?></span></td>
              <td><?= admin_h($row['location_label'] ?: '-') ?></td>
              <td><?= admin_h($row['program_label'] ?: '-') ?></td>
              <td><?= admin_h($row['semester'] ?: '-') ?></td>
              <td><?= admin_h($row['gender'] ?: '-') ?></td>
              <td><?= admin_h($row['state_name'] ?: '-') ?></td>
              <td><?= admin_h($row['city_name'] ?: '-') ?></td>
              <td><?= admin_h($row['email']) ?></td>
              <td><?= admin_h($row['phone']) ?></td>
              <td><span class="status-badge status-<?= admin_h(strtolower(str_replace(' ', '-', $row['current_status']))) ?>"><?= admin_h($row['current_status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= admin_url('participants', array_merge($queryBase, ['page' => $i, 'sort' => $sort, 'dir' => $dir])) ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
