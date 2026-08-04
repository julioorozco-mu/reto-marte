# Historial de Cambios (Changelog) - Reto Marte

Este archivo detalla las modificaciones y adiciones técnicas realizadas en el proyecto para facilitar el mantenimiento del código a futuros desarrolladores.

---

## [1.1.2] - 2026-08-04

### Corrección de Base de Datos y Reporte de Errores
- **Soporte de Columna Faltante**:
  - Se agregó la columna `cobach_certificado_path` en la creación de la tabla `rm_participant_submissions` y en su migración dinámica (`rm_add_column_if_not_exists`). Esto soluciona el fallo en producción al registrar alumnos de COBACH que subían su certificado de estudios.
- **Mejora en Diagnóstico del Cliente**:
  - Se modificó `script.js` para que ante una respuesta de error de la API (500), se visualice el detalle técnico (`result.error`) en el cuadro de diálogo. Esto previene quedar a ciegas ante problemas de base de datos o de red en producción.

---

## [1.1.1] - 2026-08-03

### Mapeo de Carreras y Unidades Académicas
- **Actualización de Catálogo Oficial**:
  - Se sincronizó el archivo `carreras.json` con la información oficial del Excel `carreras.xlsx` respetando codificación UTF-8 con acentos.
  - Se depuraron y ajustaron las licenciaturas para las 27 unidades académicas oficiales de la UNACH.

---

## [1.1.0] - 2026-08-01

### Formulario de Registro (Frontend)
- **Módulo de Docentes (UNACH)**: 
  - Se añadió un selector de rol (Estudiante / Docente) en el formulario de la UNACH.
  - Se habilitaron preguntas condicionales exclusivas para docentes: pertenencia al SNII, SEI, Club de Emprendimiento y participación en programas Wadhwani.
  - Se agregaron validaciones de expresiones regulares específicas para el CURP de docentes y se calculó la fecha de nacimiento y edad automáticamente mediante JavaScript a partir del CURP.
- **Alineación de Unidades y Carreras con Excel**:
  - Se removieron opciones que no figuraban en el Excel oficial (`Centro Mesoamericano física Teórica` y `Centro de Transferencia...`).
  - Se normalizaron las 27 unidades académicas en los selectores de [index.html](index.html) para coincidir exactamente con [carreras.xlsx](carreras.xlsx).
  - Se generó el archivo de mapeo dinámico [carreras.json](carreras.json) para cargar las carreras oficiales de cada sede de forma dinámica sin recargar la página.

### Backend y Base de Datos (API)
- **Automatización de Migraciones**:
  - Se modificó [register_participant.php](api/register_participant.php) para añadir de forma dinámica mediante código PHP (`rm_add_column_if_not_exists`) las nuevas columnas en la tabla `rm_participants` y `rm_participant_submissions` si no existieran en la base de datos de producción (`is_teacher`, `teacher_snii`, `teacher_sei`, `teacher_emprend`, `teacher_wadhwani`, `certificado_file_path`). Esto evita tener que correr scripts SQL manuales al desplegar.
- **Configuración de Entornos**:
  - Se integró la constante `IS_PRODUCTION` en [database.php](config/database.php) para alternar automáticamente entre las credenciales de desarrollo local (XAMPP) y producción sin cambiar el código en Git.

### Panel de Administración (Backoffice)
- **Dashboard**:
  - Se agregaron métricas de conteo individuales para Estudiantes UNACH y Docentes UNACH, y se hizo el diseño adaptable (`auto-fit`).
- **Listado de Participantes**:
  - Se añadió la columna **Rol** inmediatamente al lado derecho de la columna **Institución**, utilizando etiquetas visuales estilizadas (`DOCENTE` en color púrpura y `ESTUDIANTE` en azul).
  - Se modificó la consulta en [BackofficeModel.php](admin/app/models/BackofficeModel.php) para que cuando el rol sea "Docente", el Semestre y la Carrera muestren automáticamente **"N/A"**.
  - Se implementó un nuevo filtro desplegable por **Rol** (Todos, Estudiante, Docente) en la barra de búsqueda del Backoffice.
- **Detalle de Participantes**:
  - Se adecuó la vista de detalle ([show.php](admin/app/views/participants/show.php)) para mostrar las respuestas específicas de docentes de manera ordenada.

### Reportes (Excel Exporter)
- **Exportación en Excel**:
  - Se actualizó el exportador [ExcelExporter.php](admin/app/services/ExcelExporter.php) para posicionar la columna **Rol** al lado de la columna **Institución**.
  - Se implementó lógica para formatear Carrera y Semestre como **"N/A"** para docentes y exportar las nuevas columnas del SNII, SEI, Club Empren-D y Wadhwani al final de la fila.
