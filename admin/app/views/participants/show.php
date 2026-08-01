<?php
$docsByType = [];
foreach ($documents as $doc) {
  $docsByType[$doc['document_type']] = $doc;
}

$formData = is_array($submission ?? null) ? $submission : $participant;

$isTeacher = (int)($formData['is_teacher'] ?? ($participant['is_teacher'] ?? 0)) === 1;

$orderedResponses = $participant['institution'] === 'unach'
  ? ($isTeacher
    ? [
        'Rol' => 'Docente',
        'Unidad academica' => $formData['unach_unit'] ?? ($participant['unach_unit'] ?? ''),
        'Nombre(s)' => $formData['unach_first_name'] ?? ($participant['first_name'] ?? ''),
        'Apellido paterno' => $formData['unach_last_name_1'] ?? ($participant['last_name_paternal'] ?? ''),
        'Apellido materno' => $formData['unach_last_name_2'] ?? ($participant['last_name_maternal'] ?? ''),
        'Fecha de nacimiento' => $formData['unach_birthdate'] ?? ($participant['birthdate'] ?? ''),
        'Edad' => (string)($formData['unach_age'] ?? ($participant['age'] ?? '')),
        'Sexo' => $formData['unach_gender'] ?? ($participant['gender'] ?? ''),
        'CURP' => $formData['unach_curp'] ?? ($participant['curp'] ?? ''),
        'Correo electronico' => $formData['unach_email'] ?? ($participant['email'] ?? ''),
        'Numero de telefono celular' => $formData['unach_phone'] ?? ($participant['phone'] ?? ''),
        'Estado' => $formData['unach_state'] ?? ($participant['state_name'] ?? ''),
        'Municipio' => $formData['unach_city'] ?? ($participant['city_name'] ?? ''),
        '¿Pertenece al SNII?' => $formData['teacher_snii'] ?? ($participant['teacher_snii'] ?? '-'),
        '¿Pertenece al SEI?' => $formData['teacher_sei'] ?? ($participant['teacher_sei'] ?? '-'),
        '¿Pertenece al Club Empren-D UNACH?' => $formData['teacher_emprend'] ?? ($participant['teacher_emprend'] ?? '-'),
        '¿Ha participado en el programa Wadhwani?' => $formData['teacher_wadhwani'] ?? ($participant['teacher_wadhwani'] ?? '-'),
      ]
    : [
        'Rol' => 'Estudiante',
        'Unidad academica' => $formData['unach_unit'] ?? ($participant['unach_unit'] ?? ''),
        'Semestre' => $formData['unach_semester'] ?? ($participant['semester'] ?? ''),
        'Carrera' => $formData['unach_major'] ?? ($participant['unach_major'] ?? ''),
        'Nombre(s)' => $formData['unach_first_name'] ?? ($participant['first_name'] ?? ''),
        'Apellido paterno' => $formData['unach_last_name_1'] ?? ($participant['last_name_paternal'] ?? ''),
        'Apellido materno' => $formData['unach_last_name_2'] ?? ($participant['last_name_maternal'] ?? ''),
        'Fecha de nacimiento' => $formData['unach_birthdate'] ?? ($participant['birthdate'] ?? ''),
        'Edad' => (string)($formData['unach_age'] ?? ($participant['age'] ?? '')),
        'Sexo' => $formData['unach_gender'] ?? ($participant['gender'] ?? ''),
        'CURP' => $formData['unach_curp'] ?? ($participant['curp'] ?? ''),
        'Correo electronico' => $formData['unach_email'] ?? ($participant['email'] ?? ''),
        'Numero de telefono celular' => $formData['unach_phone'] ?? ($participant['phone'] ?? ''),
        'Estado' => $formData['unach_state'] ?? ($participant['state_name'] ?? ''),
        'Municipio' => $formData['unach_city'] ?? ($participant['city_name'] ?? ''),
      ]
  )
  : [
    'Plantel COBACH' => $formData['cobach_campus'] ?? ($participant['cobach_campus'] ?? ''),
    'Semestre' => $formData['cobach_semester'] ?? ($participant['semester'] ?? ''),
    'Area de formacion' => $formData['cobach_area'] ?? ($participant['cobach_area'] ?? ''),
    'Nombre(s)' => $formData['cobach_first_name'] ?? ($participant['first_name'] ?? ''),
    'Apellido paterno' => $formData['cobach_last_name_1'] ?? ($participant['last_name_paternal'] ?? ''),
    'Apellido materno' => $formData['cobach_last_name_2'] ?? ($participant['last_name_maternal'] ?? ''),
    'Fecha de nacimiento' => $formData['cobach_birthdate'] ?? ($participant['birthdate'] ?? ''),
    'Edad' => (string)($formData['cobach_age'] ?? ($participant['age'] ?? '')),
    'Sexo' => $formData['cobach_gender'] ?? ($participant['gender'] ?? ''),
    'CURP' => $formData['cobach_curp'] ?? ($participant['curp'] ?? ''),
    'Correo electronico' => $formData['cobach_email'] ?? ($participant['email'] ?? ''),
    'Numero de telefono celular' => $formData['cobach_phone'] ?? ($participant['phone'] ?? ''),
    'Estado' => $formData['cobach_state'] ?? ($participant['state_name'] ?? ''),
    'Municipio' => $formData['cobach_city'] ?? ($participant['city_name'] ?? ''),
    'Carta responsiva' => !empty($formData['cobach_responsiva_path'] ?? $participant['responsiva_file_path'] ?? '') ? ($formData['cobach_responsiva_path'] ?? $participant['responsiva_file_path']) : 'Sin archivo',
    'Certificado de estudios' => !empty($formData['cobach_certificado_path'] ?? $participant['certificado_file_path'] ?? '') ? ($formData['cobach_certificado_path'] ?? $participant['certificado_file_path']) : 'Sin archivo',
  ];
?>
<section class="page-head">
  <div>
    <p class="eyebrow">Detalle del participante</p>
    <h1><?= admin_h($participant['full_name']) ?></h1>
  </div>
  <div class="page-actions">
    <a class="secondary-link" href="<?= admin_url('participants') ?>">Volver</a>
  </div>
</section>

<section class="detail-grid">
  <article class="detail-card">
    <h2>Informacion general</h2>
    <dl class="detail-list">
      <div><dt>ID</dt><dd>#<?= admin_h((string) $participant['rm_participant_id']) ?></dd></div>
      <div><dt>Fecha de registro</dt><dd><?= admin_h(admin_format_date($participant['created_at'])) ?></dd></div>
      <div><dt>Nombre(s)</dt><dd><?= admin_h($participant['first_name']) ?></dd></div>
      <div><dt>Apellido paterno</dt><dd><?= admin_h($participant['last_name_paternal']) ?></dd></div>
      <div><dt>Apellido materno</dt><dd><?= admin_h($participant['last_name_maternal']) ?></dd></div>
      <div><dt>Institucion</dt><dd><?= admin_h(strtoupper($participant['institution'])) ?></dd></div>
      <div><dt>Unidad / Plantel</dt><dd><?= admin_h($participant['location_label'] ?: '-') ?></dd></div>
      <div><dt>Carrera / Area</dt><dd><?= admin_h($participant['program_label'] ?: '-') ?></dd></div>
      <div><dt>Semestre</dt><dd><?= admin_h($participant['semester']) ?></dd></div>
      <div><dt>Sexo</dt><dd><?= admin_h($participant['gender']) ?></dd></div>
      <div><dt>CURP</dt><dd><?= admin_h($participant['curp']) ?></dd></div>
      <div><dt>Correo</dt><dd><?= admin_h($participant['email']) ?></dd></div>
      <div><dt>Telefono</dt><dd><?= admin_h($participant['phone']) ?></dd></div>
      <div><dt>Estado</dt><dd><?= admin_h($participant['state_name']) ?></dd></div>
      <div><dt>Municipio</dt><dd><?= admin_h($participant['city_name']) ?></dd></div>
      <div><dt>Fecha de nacimiento</dt><dd><?= admin_h($participant['birthdate']) ?></dd></div>
      <div><dt>Edad</dt><dd><?= admin_h((string) $participant['age']) ?></dd></div>
    </dl>
  </article>

  <article class="detail-card">
    <h2>Seguimiento</h2>
    <form method="post" action="<?= admin_url('participant_show', ['id' => $participant['rm_participant_id']]) ?>" class="followup-form">
      <?= admin_csrf_input() ?>
      <input type="hidden" name="action" value="save_followup" />
      <label>
        <span>Estado del participante</span>
        <select name="status" required>
          <?php foreach (['Pendiente', 'Validado', 'Rechazado'] as $status): ?>
            <option value="<?= admin_h($status) ?>" <?= ($participant['current_status'] ?? 'Pendiente') === $status ? 'selected' : '' ?>><?= admin_h($status) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        <span>Observaciones</span>
        <textarea name="observations" rows="6" placeholder="Escribe comentarios del seguimiento..."><?= admin_h($participant['current_observations'] ?? '') ?></textarea>
      </label>
      <button type="submit">Guardar seguimiento</button>
    </form>
  </article>
</section>

<section class="detail-grid two-up">
  <?php if ($participant['institution'] !== 'unach'): ?>
  <article class="detail-card">
    <h2>Documentos</h2>
    <div class="document-list">
      <?php foreach (['certificado' => 'Certificado de estudios', 'adjunto' => 'Carta responsiva'] as $type => $label): ?>
        <?php $doc = $docsByType[$type] ?? null; ?>
        <div class="document-item">
          <div>
            <strong><?= admin_h($label) ?></strong>
            <span><?= $doc ? admin_h($doc['document_name']) : 'Sin archivo' ?></span>
          </div>
          <?php if ($doc): ?>
            <?php
              $rawPath = (string)($doc['file_path'] ?? '');
              $docUrl = str_starts_with($rawPath, 'uploads/') ? '../' . $rawPath : $rawPath;
            ?>
            <div class="doc-actions">
              <a href="<?= admin_h($docUrl) ?>" target="_blank" rel="noopener">Abrir</a>
              <a href="<?= admin_h($docUrl) ?>" download>Descargar</a>
            </div>
          <?php else: ?>
            <span class="muted">Pendiente</span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <form method="post" action="<?= admin_url('participant_show', ['id' => $participant['rm_participant_id']]) ?>" class="upload-form" enctype="multipart/form-data">
      <?= admin_csrf_input() ?>
      <input type="hidden" name="action" value="upload_document" />
      <label>
        <span>Tipo de documento</span>
        <select name="document_type" required>
          <option value="certificado">Certificado de estudios</option>
          <option value="adjunto">Carta responsiva</option>
        </select>
      </label>
      <label>
        <span>Archivo</span>
        <input type="file" name="document_file" accept="application/pdf,image/jpeg,image/png" required />
      </label>
      <button type="submit">Subir documento</button>
    </form>
  </article>
  <?php endif; ?>

  <article class="detail-card">
    <h2>Historial de cambios</h2>
    <div class="history-list">
      <?php if (empty($history)): ?>
        <p class="muted">Aun no hay movimientos registrados.</p>
      <?php else: ?>
        <?php foreach ($history as $item): ?>
          <div class="history-item">
            <strong><?= admin_h($item['action_name']) ?></strong>
            <span><?= admin_h(($item['old_status'] ?? 'Pendiente') . ' -> ' . ($item['new_status'] ?? 'Pendiente')) ?></span>
            <p><?= admin_h($item['new_observations'] ?? '') ?></p>
            <small><?= admin_h(admin_format_date($item['created_at'])) ?><?= !empty($item['admin_name']) ? ' | ' . admin_h($item['admin_name']) : '' ?></small>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </article>
</section>

<section class="detail-grid two-up">
  <article class="detail-card">
    <h2>Respuestas capturadas (orden del formulario)</h2>
    <div class="response-order-list">
      <?php foreach ($orderedResponses as $label => $value): ?>
        <div class="response-order-item">
          <strong><?= admin_h($label) ?></strong>
          <span><?= admin_h((string)($value !== '' ? $value : '-')) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </article>
</section>
