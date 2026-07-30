<section class="page-head">
  <div>
    <p class="eyebrow">Resumen general</p>
    <h1>Dashboard</h1>
  </div>
  <div class="page-actions">
    <a class="secondary-link" href="<?= admin_url('participants') ?>">Ver participantes</a>
    <a class="secondary-link" href="<?= admin_url('reports') ?>">Exportar reportes</a>
    <button class="secondary-link" id="btn-export-pdf" style="cursor: pointer; background: transparent; border: 1px solid currentColor;">Exportar PDF</button>
  </div>
</section>

<!-- Carga de librerías para gráficas y PDF -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
.charts-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
  margin-bottom: 2rem;
}
.chart-card {
  background: #fff;
  padding: 1.5rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  text-align: center;
}
.chart-card h3 {
  margin-top: 0;
  margin-bottom: 1.25rem;
  font-size: 1.1rem;
  color: #1a202c;
  font-weight: 600;
}
.chart-container {
  position: relative;
  height: 240px;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>

<div id="dashboard-report-area">
  <!-- Cabecera exclusiva para el PDF -->
  <div class="report-header" style="display: none; margin-bottom: 25px; font-family: 'Poppins', sans-serif;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f9fc2; padding-bottom: 10px;">
      <div>
        <h1 style="color: #0f9fc2; margin: 0; font-size: 24px; font-weight: 700; text-transform: uppercase;">Reto Marte</h1>
        <small style="color: #555; font-weight: 600; letter-spacing: 0.5px;">REPORTE DE SEGUIMIENTO OPERATIVO</small>
      </div>
      <div style="text-align: right;">
        <p style="margin: 0; font-size: 13px; color: #333;"><strong>Fecha de emisión:</strong> <?= date('d/m/Y H:i') ?></p>
        <p style="margin: 0; font-size: 12px; color: #666;">Panel de Administración (Backoffice)</p>
      </div>
    </div>
  </div>

  <section class="stats-grid" style="margin-bottom: 0;">
    <?php
      $cards = [
        ['Total de participantes', $stats['total_participants'] ?? 0],
        ['Participantes UNACH', $stats['unach_participants'] ?? 0],
        ['Participantes COBACH', $stats['cobach_participants'] ?? 0],
        ['Registros del dia', $stats['today_participants'] ?? 0],
        ['Registros de la semana', $stats['week_participants'] ?? 0],
        ['Pendientes', $stats['pending_participants'] ?? 0],
        ['Validados', $stats['validated_participants'] ?? 0],
      ];
    ?>
    <?php foreach ($cards as [$label, $value]): ?>
      <article class="stat-card">
        <span><?= admin_h($label) ?></span>
        <strong><?= admin_h((string) $value) ?></strong>
      </article>
    <?php endforeach; ?>
  </section>

  <!-- Fila de Gráficas -->
  <div class="charts-row">
    <div class="chart-card">
      <h3>Participantes por Institución</h3>
      <div class="chart-container">
        <canvas id="chartInstitution"></canvas>
      </div>
    </div>
    <div class="chart-card">
      <h3>Estatus de Validación</h3>
      <div class="chart-container">
        <canvas id="chartStatus"></canvas>
      </div>
    </div>
  </div>
</div>

<section class="panel-grid">
  <article class="panel-card">
    <h2>Accesos rapidos</h2>
    <p>Gestiona participantes, valida documentos y revisa el historial de cambios.</p>
    <div class="quick-links">
      <a href="<?= admin_url('participants') ?>">Abrir modulo de participantes</a>
      <a href="<?= admin_url('users') ?>">Administrar usuarios</a>
      <a href="<?= admin_url('settings') ?>">Revisar configuracion</a>
    </div>
  </article>
  <article class="panel-card">
    <h2>Flujo operativo</h2>
    <ol class="timeline-list">
      <li>Ingreso de registros desde la landing.</li>
      <li>Revision de seguimiento y documentos.</li>
      <li>Validacion o rechazo con observaciones.</li>
      <li>Exportacion de reportes filtrados.</li>
    </ol>
  </article>
</section>

<script>
document.addEventListener("DOMContentLoaded", () => {
  // Configuración de gráfica de Instituciones
  const ctxInst = document.getElementById('chartInstitution').getContext('2d');
  new Chart(ctxInst, {
    type: 'pie',
    data: {
      labels: ['UNACH', 'COBACH'],
      datasets: [{
        data: [
          <?= (int) ($stats['unach_participants'] ?? 0) ?>,
          <?= (int) ($stats['cobach_participants'] ?? 0) ?>
        ],
        backgroundColor: ['#0f9fc2', '#ffbf69'],
        borderWidth: 1,
        borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 12,
            font: { family: "'Poppins', sans-serif", size: 12 }
          }
        }
      }
    }
  });

  // Configuración de gráfica de Estatus
  const ctxStatus = document.getElementById('chartStatus').getContext('2d');
  new Chart(ctxStatus, {
    type: 'pie',
    data: {
      labels: ['Pendientes', 'Validados', 'Rechazados'],
      datasets: [{
        data: [
          <?= (int) ($stats['pending_participants'] ?? 0) ?>,
          <?= (int) ($stats['validated_participants'] ?? 0) ?>,
          <?= max(0, (int) ($stats['total_participants'] ?? 0) - (int) ($stats['pending_participants'] ?? 0) - (int) ($stats['validated_participants'] ?? 0)) ?>
        ],
        backgroundColor: ['#ffbf69', '#72f6b5', '#df5a3d'],
        borderWidth: 1,
        borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 12,
            font: { family: "'Poppins', sans-serif", size: 12 }
          }
        }
      }
    }
  });

  // Exportar a PDF
  document.getElementById('btn-export-pdf').addEventListener('click', () => {
    const element = document.getElementById('dashboard-report-area');
    const reportHeader = document.querySelector('.report-header');
    
    // Mostrar cabecera en el clon para el PDF
    reportHeader.style.display = 'block';
    
    const opt = {
      margin:       [12, 12, 12, 12],
      filename:     'reporte-seguimiento-retomarte.pdf',
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2, useCORS: true },
      jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().set(opt).from(element).save().then(() => {
      // Ocultar cabecera nuevamente
      reportHeader.style.display = 'none';
    });
  });
});
</script>
