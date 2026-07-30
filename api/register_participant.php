<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

function rm_respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function rm_to_upper(string $value): string
{
    return strtoupper(trim($value));
}

function rm_field(string $name, int $maxLength = 255): string
{
    $value = $_POST[$name] ?? '';
    if (!is_string($value)) {
        return '';
    }
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (mb_strlen($value) > $maxLength) {
        return mb_substr($value, 0, $maxLength);
    }
    return $value;
}

function rm_validate_file_upload(array $file, array $allowedMimes, array $allowedExtensions, int $maxBytes): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return 'No se recibio el archivo requerido.';
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        return 'El archivo excede el tamano permitido.';
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmpName)) {
        return 'El archivo recibido es invalido.';
    }

    $originalName = (string)($file['name'] ?? 'archivo');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtNormalized = array_map(static fn(string $ext): string => ltrim(strtolower($ext), '.'), $allowedExtensions);
    if (!in_array($extension, $allowedExtNormalized, true)) {
        return 'El tipo de archivo no esta permitido.';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmpName);
    if (!in_array($mime, $allowedMimes, true)) {
        return 'El tipo MIME del archivo no esta permitido.';
    }

    return null;
}

function rm_store_uploaded_file(string $fieldName, string $prefix): string
{
    $file = $_FILES[$fieldName] ?? null;
    if (!is_array($file)) {
        throw new RuntimeException('No se encontro el archivo requerido.');
    }

    $year = date('Y');
    $month = date('m');
    $relativeDir = 'uploads/participants/' . $year . '/' . $month;
    $absoluteDir = __DIR__ . '/../' . $relativeDir;

    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('No se pudo crear el directorio de archivos.');
    }

    $extension = strtolower(pathinfo((string)($file['name'] ?? 'archivo.bin'), PATHINFO_EXTENSION));
    $safeExtension = preg_replace('/[^a-z0-9]/', '', $extension);
    if ($safeExtension === '') {
        $safeExtension = 'bin';
    }

    $fileName = sprintf('%s_%s.%s', $prefix, bin2hex(random_bytes(8)), $safeExtension);
    $absolutePath = $absoluteDir . '/' . $fileName;

    $tmpName = (string)($file['tmp_name'] ?? '');
    if (!move_uploaded_file($tmpName, $absolutePath)) {
        throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
    }

    return $relativeDir . '/' . $fileName;
}

function rm_calculate_age(string $birthdate): int
{
    $birthDate = DateTimeImmutable::createFromFormat('Y-m-d', $birthdate);
    if (!$birthDate) {
        return -1;
    }

    $now = new DateTimeImmutable('today');
    if ($birthDate > $now) {
        return -1;
    }

    return (int)$birthDate->diff($now)->y;
}

function rm_table_exists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name LIMIT 1');
    $stmt->execute(['table_name' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

function rm_add_column_if_not_exists(PDO $pdo, string $tableName, string $columnName, string $definition): void
{
    $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name LIMIT 1');
    $stmt->execute(['table_name' => $tableName, 'column_name' => $columnName]);
    if (!$stmt->fetchColumn()) {
        $pdo->exec("ALTER TABLE {$tableName} ADD COLUMN {$columnName} {$definition}");
    }
}

function rm_sync_backoffice_document(PDO $pdo, int $participantId, string $documentType, string $documentName, string $filePath, string $mimeType): void
{
    if (!rm_table_exists($pdo, DB_PREFIX . 'participant_documents')) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO ' . DB_PREFIX . 'participant_documents (participant_id, document_type, document_name, file_path, mime_type, uploaded_by) VALUES (:participant_id, :document_type, :document_name, :file_path, :mime_type, NULL) ON DUPLICATE KEY UPDATE document_name = VALUES(document_name), file_path = VALUES(file_path), mime_type = VALUES(mime_type), updated_at = CURRENT_TIMESTAMP');
    $stmt->execute([
        'participant_id' => $participantId,
        'document_type' => $documentType,
        'document_name' => $documentName,
        'file_path' => $filePath,
        'mime_type' => $mimeType,
    ]);
}

function rm_sync_submission_data(PDO $pdo, int $participantId, array $payload): void
{
    $prefix = DB_PREFIX;
    $sql = <<<SQL
INSERT INTO {$prefix}participant_submissions (
    participant_id,
    institution,
    unach_unit,
    unach_semester,
    unach_major,
    unach_first_name,
    unach_last_name_1,
    unach_last_name_2,
    unach_birthdate,
    unach_age,
    unach_gender,
    unach_curp,
    unach_email,
    unach_phone,
    unach_state,
    unach_city,
    cobach_campus,
    cobach_semester,
    cobach_area,
    cobach_first_name,
    cobach_last_name_1,
    cobach_last_name_2,
    cobach_birthdate,
    cobach_age,
    cobach_gender,
    cobach_curp,
    cobach_email,
    cobach_phone,
    cobach_state,
    cobach_city,
    cobach_responsiva_path,
    cobach_certificado_path,
    is_teacher,
    teacher_snii,
    teacher_sei,
    teacher_emprend,
    teacher_wadhwani
) VALUES (
    :participant_id,
    :institution,
    :unach_unit,
    :unach_semester,
    :unach_major,
    :unach_first_name,
    :unach_last_name_1,
    :unach_last_name_2,
    :unach_birthdate,
    :unach_age,
    :unach_gender,
    :unach_curp,
    :unach_email,
    :unach_phone,
    :unach_state,
    :unach_city,
    :cobach_campus,
    :cobach_semester,
    :cobach_area,
    :cobach_first_name,
    :cobach_last_name_1,
    :cobach_last_name_2,
    :cobach_birthdate,
    :cobach_age,
    :cobach_gender,
    :cobach_curp,
    :cobach_email,
    :cobach_phone,
    :cobach_state,
    :cobach_city,
    :cobach_responsiva_path,
    :cobach_certificado_path,
    :is_teacher,
    :teacher_snii,
    :teacher_sei,
    :teacher_emprend,
    :teacher_wadhwani
) ON DUPLICATE KEY UPDATE
    institution = VALUES(institution),
    unach_unit = VALUES(unach_unit),
    unach_semester = VALUES(unach_semester),
    unach_major = VALUES(unach_major),
    unach_first_name = VALUES(unach_first_name),
    unach_last_name_1 = VALUES(unach_last_name_1),
    unach_last_name_2 = VALUES(unach_last_name_2),
    unach_birthdate = VALUES(unach_birthdate),
    unach_age = VALUES(unach_age),
    unach_gender = VALUES(unach_gender),
    unach_curp = VALUES(unach_curp),
    unach_email = VALUES(unach_email),
    unach_phone = VALUES(unach_phone),
    unach_state = VALUES(unach_state),
    unach_city = VALUES(unach_city),
    cobach_campus = VALUES(cobach_campus),
    cobach_semester = VALUES(cobach_semester),
    cobach_area = VALUES(cobach_area),
    cobach_first_name = VALUES(cobach_first_name),
    cobach_last_name_1 = VALUES(cobach_last_name_1),
    cobach_last_name_2 = VALUES(cobach_last_name_2),
    cobach_birthdate = VALUES(cobach_birthdate),
    cobach_age = VALUES(cobach_age),
    cobach_gender = VALUES(cobach_gender),
    cobach_curp = VALUES(cobach_curp),
    cobach_email = VALUES(cobach_email),
    cobach_phone = VALUES(cobach_phone),
    cobach_state = VALUES(cobach_state),
    cobach_city = VALUES(cobach_city),
    cobach_responsiva_path = VALUES(cobach_responsiva_path),
    cobach_certificado_path = VALUES(cobach_certificado_path),
    is_teacher = VALUES(is_teacher),
    teacher_snii = VALUES(teacher_snii),
    teacher_sei = VALUES(teacher_sei),
    teacher_emprend = VALUES(teacher_emprend),
    teacher_wadhwani = VALUES(teacher_wadhwani),
    updated_at = CURRENT_TIMESTAMP
SQL;

    $stmt = $pdo->prepare($sql);

    $stmt->execute($payload);
}

function rm_ensure_schema(PDO $pdo): void
{
    $prefix = DB_PREFIX;
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$prefix}participants (
  rm_participant_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  institution ENUM('unach', 'cobach') NOT NULL,
  first_name VARCHAR(120) NOT NULL,
  last_name_paternal VARCHAR(120) NOT NULL,
  last_name_maternal VARCHAR(120) NOT NULL,
  birthdate DATE NOT NULL,
  age TINYINT UNSIGNED NOT NULL,
  gender VARCHAR(30) NOT NULL,
  curp VARCHAR(18) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  state_name VARCHAR(120) NOT NULL,
  city_name VARCHAR(120) NOT NULL,
  semester VARCHAR(8) NOT NULL,
  unach_unit VARCHAR(255) NULL,
  unach_major VARCHAR(180) NULL,
  cobach_campus VARCHAR(180) NULL,
  cobach_area VARCHAR(180) NULL,
  responsiva_file_path VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (rm_participant_id),
  KEY rm_idx_institution (institution),
  KEY rm_idx_email (email),
  KEY rm_idx_curp (curp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    $pdo->exec($sql);
    
    rm_add_column_if_not_exists($pdo, $prefix . 'participants', 'is_teacher', 'TINYINT(1) NOT NULL DEFAULT 0');
    rm_add_column_if_not_exists($pdo, $prefix . 'participants', 'teacher_snii', 'VARCHAR(2) NULL');
    rm_add_column_if_not_exists($pdo, $prefix . 'participants', 'teacher_sei', 'VARCHAR(2) NULL');
    rm_add_column_if_not_exists($pdo, $prefix . 'participants', 'teacher_emprend', 'VARCHAR(2) NULL');
    rm_add_column_if_not_exists($pdo, $prefix . 'participants', 'teacher_wadhwani', 'VARCHAR(2) NULL');
}

function rm_ensure_submission_schema(PDO $pdo): void
{
    $prefix = DB_PREFIX;
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$prefix}participant_submissions (
  rm_submission_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  participant_id BIGINT UNSIGNED NOT NULL,
  institution ENUM('unach', 'cobach') NOT NULL,
  unach_unit VARCHAR(255) NULL,
  unach_semester VARCHAR(8) NULL,
  unach_major VARCHAR(180) NULL,
  unach_first_name VARCHAR(120) NULL,
  unach_last_name_1 VARCHAR(120) NULL,
  unach_last_name_2 VARCHAR(120) NULL,
  unach_birthdate DATE NULL,
  unach_age TINYINT UNSIGNED NULL,
  unach_gender VARCHAR(30) NULL,
  unach_curp VARCHAR(18) NULL,
  unach_email VARCHAR(190) NULL,
  unach_phone VARCHAR(30) NULL,
  unach_state VARCHAR(120) NULL,
  unach_city VARCHAR(120) NULL,
  cobach_campus VARCHAR(180) NULL,
  cobach_semester VARCHAR(8) NULL,
  cobach_area VARCHAR(180) NULL,
  cobach_first_name VARCHAR(120) NULL,
  cobach_last_name_1 VARCHAR(120) NULL,
  cobach_last_name_2 VARCHAR(120) NULL,
  cobach_birthdate DATE NULL,
  cobach_age TINYINT UNSIGNED NULL,
  cobach_gender VARCHAR(30) NULL,
  cobach_curp VARCHAR(18) NULL,
  cobach_email VARCHAR(190) NULL,
  cobach_phone VARCHAR(30) NULL,
  cobach_state VARCHAR(120) NULL,
  cobach_city VARCHAR(120) NULL,
  cobach_responsiva_path VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (rm_submission_id),
  UNIQUE KEY rm_submission_participant_unique (participant_id),
  KEY rm_submission_institution_idx (institution),
  CONSTRAINT rm_submission_participant_fk FOREIGN KEY (participant_id) REFERENCES rm_participants (rm_participant_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    $pdo->exec($sql);
    
    rm_add_column_if_not_exists($pdo, $prefix . 'participant_submissions', 'is_teacher', 'TINYINT(1) NOT NULL DEFAULT 0');
    rm_add_column_if_not_exists($pdo, $prefix . 'participant_submissions', 'teacher_snii', 'VARCHAR(2) NULL');
    rm_add_column_if_not_exists($pdo, $prefix . 'participant_submissions', 'teacher_sei', 'VARCHAR(2) NULL');
    rm_add_column_if_not_exists($pdo, $prefix . 'participant_submissions', 'teacher_emprend', 'VARCHAR(2) NULL');
    rm_add_column_if_not_exists($pdo, $prefix . 'participant_submissions', 'teacher_wadhwani', 'VARCHAR(2) NULL');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rm_respond(405, ['ok' => false, 'message' => 'Metodo no permitido.']);
}

$institution = rm_field('institution', 10);
if (!in_array($institution, ['unach', 'cobach'], true)) {
    rm_respond(422, ['ok' => false, 'message' => 'Institucion invalida.']);
}

$semesterMapUnach = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX'];
$semesterMapCobach = ['I', 'II', 'III', 'IV', 'V', 'VI'];
$genderMap = ['Masculino', 'Femenino', 'Prefiero no decirlo'];

$data = [
    'institution' => $institution,
    'first_name' => '',
    'last_name_paternal' => '',
    'last_name_maternal' => '',
    'birthdate' => '',
    'age' => 0,
    'gender' => '',
    'curp' => '',
    'email' => '',
    'phone' => '',
    'state_name' => '',
    'city_name' => '',
    'semester' => '',
    'unach_unit' => null,
    'unach_major' => null,
    'cobach_campus' => null,
    'cobach_area' => null,
    'responsiva_file_path' => null,
    'is_teacher' => 0,
    'teacher_snii' => null,
    'teacher_sei' => null,
    'teacher_emprend' => null,
    'teacher_wadhwani' => null,
];

$submissionData = [
    'participant_id' => 0,
    'institution' => $institution,
    'unach_unit' => null,
    'unach_semester' => null,
    'unach_major' => null,
    'unach_first_name' => null,
    'unach_last_name_1' => null,
    'unach_last_name_2' => null,
    'unach_birthdate' => null,
    'unach_age' => null,
    'unach_gender' => null,
    'unach_curp' => null,
    'unach_email' => null,
    'unach_phone' => null,
    'unach_state' => null,
    'unach_city' => null,
    'cobach_campus' => null,
    'cobach_semester' => null,
    'cobach_area' => null,
    'cobach_first_name' => null,
    'cobach_last_name_1' => null,
    'cobach_last_name_2' => null,
    'cobach_birthdate' => null,
    'cobach_age' => null,
    'cobach_gender' => null,
    'cobach_curp' => null,
    'cobach_email' => null,
    'cobach_phone' => null,
    'cobach_state' => null,
    'cobach_city' => null,
    'cobach_responsiva_path' => null,
    'cobach_certificado_path' => null,
    'is_teacher' => 0,
    'teacher_snii' => null,
    'teacher_sei' => null,
    'teacher_emprend' => null,
    'teacher_wadhwani' => null,
];

$responsivaOriginalName = '';
$responsivaMime = 'application/pdf';
$certificadoOriginalName = '';
$certificadoMime = 'application/pdf';

if ($institution === 'unach') {
    $unachRole = rm_field('unach_role', 20);
    if ($unachRole === 'docente') {
        $data['is_teacher'] = 1;
        $data['unach_unit'] = rm_field('unach_teacher_unit', 255);
        $data['unach_major'] = 'Docente';
        $data['first_name'] = rm_field('unach_teacher_first_name', 120);
        $data['last_name_paternal'] = rm_field('unach_teacher_last_name_1', 120);
        $data['last_name_maternal'] = rm_field('unach_teacher_last_name_2', 120);
        $data['gender'] = rm_field('unach_teacher_gender', 30);
        $data['curp'] = rm_to_upper(rm_field('unach_teacher_curp', 18));
        $data['email'] = rm_field('unach_teacher_email', 190);
        $data['phone'] = rm_field('unach_teacher_phone', 30);
        
        $data['teacher_snii'] = rm_field('unach_teacher_snii', 2);
        $data['teacher_sei'] = rm_field('unach_teacher_sei', 2);
        $data['teacher_emprend'] = rm_field('unach_teacher_emprend', 2);
        $data['teacher_wadhwani'] = rm_field('unach_teacher_wadhwani', 2);
        
        $data['birthdate'] = '1970-01-01';
        if (preg_match('/^[A-Z]{4}(\d{2})(\d{2})(\d{2})[HM]/', $data['curp'], $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];
            $year += ($year > 30) ? 1900 : 2000;
            if (checkdate($month, $day, $year)) {
                $data['birthdate'] = sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }
        
        $data['state_name'] = 'Chiapas';
        $data['city_name'] = 'Tuxtla Gutiérrez';
        $data['semester'] = 'N/A';
        
        $submissionData['teacher_snii'] = $data['teacher_snii'];
        $submissionData['teacher_sei'] = $data['teacher_sei'];
        $submissionData['teacher_emprend'] = $data['teacher_emprend'];
        $submissionData['teacher_wadhwani'] = $data['teacher_wadhwani'];
    } else {
        $data['unach_unit'] = rm_field('unach_unit', 255);
        $data['unach_major'] = rm_field('unach_major', 180);
        $data['first_name'] = rm_field('unach_first_name', 120);
        $data['last_name_paternal'] = rm_field('unach_last_name_1', 120);
        $data['last_name_maternal'] = rm_field('unach_last_name_2', 120);
        $data['birthdate'] = rm_field('unach_birthdate', 10);
        $data['gender'] = rm_field('unach_gender', 30);
        $data['curp'] = rm_to_upper(rm_field('unach_curp', 18));
        $data['email'] = rm_field('unach_email', 190);
        $data['phone'] = rm_field('unach_phone', 30);
        $data['state_name'] = rm_field('unach_state', 120);
        $data['city_name'] = rm_field('unach_city', 120);
        $data['semester'] = rm_field('unach_semester', 8);
    }

    $submissionData['is_teacher'] = $data['is_teacher'];
    $submissionData['unach_unit'] = $data['unach_unit'];
    $submissionData['unach_semester'] = $data['semester'];
    $submissionData['unach_major'] = $data['unach_major'];
    $submissionData['unach_first_name'] = $data['first_name'];
    $submissionData['unach_last_name_1'] = $data['last_name_paternal'];
    $submissionData['unach_last_name_2'] = $data['last_name_maternal'];
    $submissionData['unach_birthdate'] = $data['birthdate'];
    $submissionData['unach_gender'] = $data['gender'];
    $submissionData['unach_curp'] = $data['curp'];
    $submissionData['unach_email'] = $data['email'];
    $submissionData['unach_phone'] = $data['phone'];
    $submissionData['unach_state'] = $data['state_name'];
    $submissionData['unach_city'] = $data['city_name'];
} else {
    $data['cobach_campus'] = rm_field('cobach_campus', 180);
    $data['cobach_area'] = rm_field('cobach_area', 180);
    $data['first_name'] = rm_field('cobach_first_name', 120);
    $data['last_name_paternal'] = rm_field('cobach_last_name_1', 120);
    $data['last_name_maternal'] = rm_field('cobach_last_name_2', 120);
    $data['birthdate'] = rm_field('cobach_birthdate', 10);
    $data['gender'] = rm_field('cobach_gender', 30);
    $data['curp'] = rm_to_upper(rm_field('cobach_curp', 18));
    $data['email'] = rm_field('cobach_email', 190);
    $data['phone'] = rm_field('cobach_phone', 30);
    $data['state_name'] = rm_field('cobach_state', 120);
    $data['city_name'] = rm_field('cobach_city', 120);
    $data['semester'] = rm_field('cobach_semester', 8);

    $submissionData['cobach_campus'] = $data['cobach_campus'];
    $submissionData['cobach_semester'] = $data['semester'];
    $submissionData['cobach_area'] = $data['cobach_area'];
    $submissionData['cobach_first_name'] = $data['first_name'];
    $submissionData['cobach_last_name_1'] = $data['last_name_paternal'];
    $submissionData['cobach_last_name_2'] = $data['last_name_maternal'];
    $submissionData['cobach_birthdate'] = $data['birthdate'];
    $submissionData['cobach_gender'] = $data['gender'];
    $submissionData['cobach_curp'] = $data['curp'];
    $submissionData['cobach_email'] = $data['email'];
    $submissionData['cobach_phone'] = $data['phone'];
    $submissionData['cobach_state'] = $data['state_name'];
    $submissionData['cobach_city'] = $data['city_name'];

    $responsivaError = rm_validate_file_upload(
        $_FILES['cobach_responsiva'] ?? [],
        ['application/pdf'],
        ['pdf'],
        1024 * 1024
    );
    if ($responsivaError !== null) {
        rm_respond(422, ['ok' => false, 'message' => 'Carta responsiva: ' . $responsivaError]);
    }

    $responsivaOriginalName = (string)($_FILES['cobach_responsiva']['name'] ?? 'carta_responsiva.pdf');
    $tmpPath = (string)($_FILES['cobach_responsiva']['tmp_name'] ?? '');
    if ($tmpPath !== '' && is_uploaded_file($tmpPath)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $responsivaMime = (string)$finfo->file($tmpPath);
    }

    $certificadoError = rm_validate_file_upload(
        $_FILES['cobach_certificado'] ?? [],
        ['application/pdf'],
        ['pdf'],
        1024 * 1024
    );
    if ($certificadoError !== null) {
        rm_respond(422, ['ok' => false, 'message' => 'Certificado de estudios: ' . $certificadoError]);
    }

    $certificadoOriginalName = (string)($_FILES['cobach_certificado']['name'] ?? 'certificado_estudios.pdf');
    $tmpPathCert = (string)($_FILES['cobach_certificado']['tmp_name'] ?? '');
    if ($tmpPathCert !== '' && is_uploaded_file($tmpPathCert)) {
        $finfoCert = new finfo(FILEINFO_MIME_TYPE);
        $certificadoMime = (string)$finfoCert->file($tmpPathCert);
    }
}

$requiredTextFields = [
    'first_name',
    'last_name_paternal',
    'last_name_maternal',
    'birthdate',
    'gender',
    'curp',
    'email',
    'phone',
    'state_name',
    'city_name',
    'semester',
];

foreach ($requiredTextFields as $field) {
    if ($data[$field] === '') {
        rm_respond(422, ['ok' => false, 'message' => 'Falta completar campos obligatorios.']);
    }
}

if ($institution === 'unach' && ($data['unach_unit'] === '' || $data['unach_major'] === '')) {
    rm_respond(422, ['ok' => false, 'message' => 'Completa unidad academica y carrera de UNACH.']);
}
if ($institution === 'cobach' && ($data['cobach_campus'] === '' || $data['cobach_area'] === '')) {
    rm_respond(422, ['ok' => false, 'message' => 'Completa plantel y area de formacion de COBACH.']);
}

if ($institution === 'unach' && !$data['is_teacher'] && !in_array($data['semester'], $semesterMapUnach, true)) {
    rm_respond(422, ['ok' => false, 'message' => 'Semestre invalido para UNACH.']);
}
if ($institution === 'cobach' && !in_array($data['semester'], $semesterMapCobach, true)) {
    rm_respond(422, ['ok' => false, 'message' => 'Semestre invalido.']);
}
if (!in_array($data['gender'], $genderMap, true)) {
    rm_respond(422, ['ok' => false, 'message' => 'Sexo invalido.']);
}
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    rm_respond(422, ['ok' => false, 'message' => 'Correo electronico invalido.']);
}
if (!preg_match('/^[0-9+()\-\s]{7,30}$/', $data['phone'])) {
    rm_respond(422, ['ok' => false, 'message' => 'Numero de telefono invalido.']);
}
if (!preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/', $data['curp'])) {
    rm_respond(422, ['ok' => false, 'message' => 'CURP invalida.']);
}

$data['age'] = rm_calculate_age($data['birthdate']);
if ($data['age'] < 10 || $data['age'] > 99) {
    rm_respond(422, ['ok' => false, 'message' => 'Edad fuera de rango permitido.']);
}

try {
    $pdo = rm_get_pdo();
    rm_ensure_schema($pdo);
    rm_ensure_submission_schema($pdo);

    if ($institution === 'cobach') {
        $data['responsiva_file_path'] = rm_store_uploaded_file('cobach_responsiva', 'cobach_responsiva');
        $submissionData['cobach_responsiva_path'] = (string)$data['responsiva_file_path'];

        $data['certificado_file_path'] = rm_store_uploaded_file('cobach_certificado', 'cobach_certificado');
        $submissionData['cobach_certificado_path'] = (string)$data['certificado_file_path'];
    } else {
        $data['certificado_file_path'] = null;
    }

    $sql = 'INSERT INTO ' . DB_PREFIX . 'participants (
        institution,
        first_name,
        last_name_paternal,
        last_name_maternal,
        birthdate,
        age,
        gender,
        curp,
        email,
        phone,
        state_name,
        city_name,
        semester,
        unach_unit,
        unach_major,
        cobach_campus,
        cobach_area,
        responsiva_file_path,
        certificado_file_path,
        is_teacher,
        teacher_snii,
        teacher_sei,
        teacher_emprend,
        teacher_wadhwani
    ) VALUES (
        :institution,
        :first_name,
        :last_name_paternal,
        :last_name_maternal,
        :birthdate,
        :age,
        :gender,
        :curp,
        :email,
        :phone,
        :state_name,
        :city_name,
        :semester,
        :unach_unit,
        :unach_major,
        :cobach_campus,
        :cobach_area,
        :responsiva_file_path,
        :certificado_file_path,
        :is_teacher,
        :teacher_snii,
        :teacher_sei,
        :teacher_emprend,
        :teacher_wadhwani
    )';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);

    $participantId = (int)$pdo->lastInsertId();
    $submissionData['participant_id'] = $participantId;
    if ($institution === 'unach') {
        $submissionData['unach_age'] = $data['age'];
    } else {
        $submissionData['cobach_age'] = $data['age'];
    }
    rm_sync_submission_data($pdo, $participantId, $submissionData);

    if ($institution === 'cobach' && !empty($data['responsiva_file_path'])) {
        rm_sync_backoffice_document(
            $pdo,
            $participantId,
            'adjunto',
            $responsivaOriginalName !== '' ? $responsivaOriginalName : 'carta_responsiva.pdf',
            (string)$data['responsiva_file_path'],
            $responsivaMime !== '' ? $responsivaMime : 'application/pdf'
        );
    }

    if ($institution === 'cobach' && !empty($data['certificado_file_path'])) {
        rm_sync_backoffice_document(
            $pdo,
            $participantId,
            'certificado',
            $certificadoOriginalName !== '' ? $certificadoOriginalName : 'certificado_estudios.pdf',
            (string)$data['certificado_file_path'],
            $certificadoMime !== '' ? $certificadoMime : 'application/pdf'
        );
    }

    rm_respond(201, [
        'ok' => true,
        'message' => 'Registro guardado correctamente.',
        'participant_id' => $participantId,
    ]);
} catch (Throwable $exception) {
    rm_respond(500, [
        'ok' => false,
        'message' => 'Error al guardar el registro.',
        'error' => $exception->getMessage(),
    ]);
}
