<?php
declare(strict_types=1);

class BackofficeModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findUserByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rm_usuarios WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findUserById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT rm_user_id, username, full_name, email, role_name, is_active, last_login_at, created_at FROM rm_usuarios WHERE rm_user_id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE rm_usuarios SET last_login_at = CURRENT_TIMESTAMP WHERE rm_user_id = :id');
        $stmt->execute(['id' => $userId]);
    }

    public function countUsers(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM rm_usuarios WHERE is_active = 1')->fetchColumn();
    }

    public function listUsers(): array
    {
        $stmt = $this->pdo->query('SELECT rm_user_id, username, full_name, email, role_name, is_active, last_login_at, created_at FROM rm_usuarios ORDER BY rm_user_id DESC');
        return $stmt->fetchAll();
    }

    public function createUser(array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO rm_usuarios (username, password_hash, full_name, email, role_name, is_active) VALUES (:username, :password_hash, :full_name, :email, :role_name, :is_active)');
        $stmt->execute($data);
    }

    public function updateUser(array $data): void
    {
        $fields = [
            'username = :username',
            'full_name = :full_name',
            'email = :email',
            'role_name = :role_name',
            'is_active = :is_active',
        ];

        if (!empty($data['password_hash'])) {
            $fields[] = 'password_hash = :password_hash';
        } else {
            unset($data['password_hash']);
        }

        $stmt = $this->pdo->prepare('UPDATE rm_usuarios SET ' . implode(', ', $fields) . ' WHERE rm_user_id = :rm_user_id');
        $stmt->execute($data);
    }

    public function deleteUser(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM rm_usuarios WHERE rm_user_id = :id');
        $stmt->execute(['id' => $userId]);
    }

    public function currentStatusJoinSql(): string
    {
        return "LEFT JOIN (
            SELECT o1.*
            FROM rm_observaciones o1
            INNER JOIN (
                SELECT participant_id, MAX(rm_observation_id) AS latest_id
                FROM rm_observaciones
                GROUP BY participant_id
            ) latest ON latest.latest_id = o1.rm_observation_id
        ) obs ON obs.participant_id = p.rm_participant_id";
    }

    private function buildParticipantWhereSql(array $filters): string
    {
        $where = [];

        if (!empty($filters['q'])) {
            $q = $this->pdo->quote('%' . $filters['q'] . '%');
            $where[] = "(p.first_name LIKE {$q} OR p.last_name_paternal LIKE {$q} OR p.last_name_maternal LIKE {$q} OR p.email LIKE {$q} OR p.phone LIKE {$q} OR p.curp LIKE {$q})";
        }

        if (!empty($filters['institution'])) {
            $where[] = 'p.institution = ' . $this->pdo->quote($filters['institution']);
        }

        if (!empty($filters['status'])) {
            $where[] = "COALESCE(obs.status, 'Pendiente') = " . $this->pdo->quote($filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(p.created_at) >= ' . $this->pdo->quote($filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(p.created_at) <= ' . $this->pdo->quote($filters['date_to']);
        }

        if (!empty($filters['faculty'])) {
            $where[] = 'p.unach_unit = ' . $this->pdo->quote($filters['faculty']);
        }

        if (!empty($filters['plantel'])) {
            $where[] = 'p.cobach_campus = ' . $this->pdo->quote($filters['plantel']);
        }

        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }

    public function dashboardStats(): array
    {
        $statusJoin = $this->currentStatusJoinSql();
        $sql = "SELECT
            (SELECT COUNT(*) FROM rm_participants) AS total_participants,
            (SELECT COUNT(*) FROM rm_participants WHERE institution = 'unach') AS unach_participants,
            (SELECT COUNT(*) FROM rm_participants WHERE institution = 'cobach') AS cobach_participants,
            (SELECT COUNT(*) FROM rm_participants WHERE DATE(created_at) = CURDATE()) AS today_participants,
            (SELECT COUNT(*) FROM rm_participants WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)) AS week_participants,
            (SELECT COUNT(*) FROM rm_participants p {$statusJoin} WHERE COALESCE(obs.status, 'Pendiente') = 'Pendiente') AS pending_participants,
            (SELECT COUNT(*) FROM rm_participants p {$statusJoin} WHERE COALESCE(obs.status, 'Pendiente') = 'Validado') AS validated_participants";
        return $this->pdo->query($sql)->fetch() ?: [];
    }

    public function listParticipants(array $filters, int $page, int $perPage, string $sort, string $dir): array
    {
        $statusJoin = $this->currentStatusJoinSql();
        $baseSql = "FROM rm_participants p {$statusJoin}";

        $allowedSort = [
            'id' => 'p.rm_participant_id',
            'date' => 'p.created_at',
            'name' => 'full_name',
            'institution' => 'p.institution',
            'place' => 'location_label',
            'program' => 'program_label',
            'semester' => 'p.semester',
            'gender' => 'p.gender',
            'email' => 'p.email',
            'phone' => 'p.phone',
            'status' => 'current_status',
        ];

        $sortColumn = $allowedSort[$sort] ?? $allowedSort['date'];
        $direction = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $whereSql = $this->buildParticipantWhereSql($filters);
        $total = (int) $this->pdo->query('SELECT COUNT(*) ' . $baseSql . $whereSql)->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT
            p.rm_participant_id AS id,
            p.created_at,
            CONCAT_WS(' ', p.first_name, p.last_name_paternal, p.last_name_maternal) AS full_name,
            p.institution,
            CASE WHEN p.institution = 'unach' THEN p.unach_unit ELSE p.cobach_campus END AS location_label,
            CASE WHEN p.institution = 'unach' THEN p.unach_major ELSE p.cobach_area END AS program_label,
            p.semester,
            p.gender,
            p.state_name,
            p.city_name,
            p.email,
            p.phone,
            COALESCE(obs.status, 'Pendiente') AS current_status,
            COALESCE(obs.observations, '') AS current_observations
            {$baseSql}{$whereSql}
            ORDER BY {$sortColumn} {$direction}
            LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->query($sql);

        return [
            'rows' => $stmt ? $stmt->fetchAll() : [],
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function findParticipant(int $id): ?array
    {
        $statusJoin = $this->currentStatusJoinSql();
        $stmt = $this->pdo->prepare("SELECT
            p.*,
            CONCAT_WS(' ', p.first_name, p.last_name_paternal, p.last_name_maternal) AS full_name,
            CASE WHEN p.institution = 'unach' THEN p.unach_unit ELSE p.cobach_campus END AS location_label,
            CASE WHEN p.institution = 'unach' THEN p.unach_major ELSE p.cobach_area END AS program_label,
            COALESCE(obs.status, 'Pendiente') AS current_status,
            COALESCE(obs.observations, '') AS current_observations,
            obs.rm_observation_id AS observation_id,
            obs.updated_at AS observation_updated_at
            FROM rm_participants p {$statusJoin}
            WHERE p.rm_participant_id = :id
            LIMIT 1");
        $stmt->execute(['id' => $id]);
        $participant = $stmt->fetch();
        return $participant ?: null;
    }

    public function findSubmission(int $participantId): ?array
    {
        if (!$this->hasSubmissionMirrorSchema()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM rm_participant_submissions WHERE participant_id = :id LIMIT 1');
        $stmt->execute(['id' => $participantId]);
        $submission = $stmt->fetch();
        return $submission ?: null;
    }

    private function tableExists(string $tableName): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name LIMIT 1');
        $stmt->execute(['table_name' => $tableName]);
        return (bool) $stmt->fetchColumn();
    }

    private function tableHasColumn(string $tableName, string $columnName): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name LIMIT 1');
        $stmt->execute([
            'table_name' => $tableName,
            'column_name' => $columnName,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    private function hasSubmissionMirrorSchema(): bool
    {
        if (!$this->tableExists('rm_participant_submissions')) {
            return false;
        }

        foreach (['participant_id', 'unach_unit', 'unach_semester', 'cobach_campus', 'cobach_responsiva_path'] as $columnName) {
            if (!$this->tableHasColumn('rm_participant_submissions', $columnName)) {
                return false;
            }
        }

        return true;
    }

    public function getObservationsHistory(int $participantId): array
    {
        $stmt = $this->pdo->prepare('SELECT o.*, u.full_name AS admin_name FROM rm_historial o LEFT JOIN rm_usuarios u ON u.rm_user_id = o.user_id WHERE o.participant_id = :id ORDER BY o.created_at DESC, o.rm_history_id DESC');
        $stmt->execute(['id' => $participantId]);
        return $stmt->fetchAll();
    }

    public function getDocuments(int $participantId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rm_participant_documents WHERE participant_id = :id ORDER BY created_at DESC, rm_document_id DESC');
        $stmt->execute(['id' => $participantId]);
        $documents = $stmt->fetchAll();

        $participantStmt = $this->pdo->prepare('SELECT responsiva_file_path, certificado_file_path FROM rm_participants WHERE rm_participant_id = :id LIMIT 1');
        $participantStmt->execute(['id' => $participantId]);
        $participant = $participantStmt->fetch() ?: null;

        $hasAdjunto = false;
        foreach ($documents as $document) {
            if (($document['document_type'] ?? '') === 'adjunto') {
                $hasAdjunto = true;
                break;
            }
        }

        $responsivaPath = (string)($participant['responsiva_file_path'] ?? '');
        if (!$hasAdjunto && $responsivaPath !== '') {
            $documents[] = [
                'document_type' => 'adjunto',
                'document_name' => basename($responsivaPath),
                'file_path' => $responsivaPath,
                'mime_type' => 'application/pdf',
            ];
        }

        $hasCertificado = false;
        foreach ($documents as $document) {
            if (($document['document_type'] ?? '') === 'certificado') {
                $hasCertificado = true;
                break;
            }
        }
        $certificadoPath = (string)($participant['certificado_file_path'] ?? '');
        if (!$hasCertificado && $certificadoPath !== '') {
            $documents[] = [
                'document_type' => 'certificado',
                'document_name' => basename($certificadoPath),
                'file_path' => $certificadoPath,
                'mime_type' => 'application/pdf',
            ];
        }

        return $documents;
    }

    public function saveFollowUp(int $participantId, string $status, string $observations, int $userId): void
    {
        $allowed = ['Pendiente', 'Validado', 'Rechazado'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('Estatus invalido.');
        }

        $currentStmt = $this->pdo->prepare('SELECT * FROM rm_observaciones WHERE participant_id = :id LIMIT 1');
        $currentStmt->execute(['id' => $participantId]);
        $current = $currentStmt->fetch() ?: null;

        $oldStatus = $current['status'] ?? 'Pendiente';
        $oldObservations = $current['observations'] ?? '';

        $upsert = $this->pdo->prepare('INSERT INTO rm_observaciones (participant_id, status, observations, updated_by) VALUES (:participant_id, :status, :observations, :updated_by) ON DUPLICATE KEY UPDATE status = VALUES(status), observations = VALUES(observations), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP');
        $upsert->execute([
            'participant_id' => $participantId,
            'status' => $status,
            'observations' => $observations,
            'updated_by' => $userId,
        ]);

        $history = $this->pdo->prepare('INSERT INTO rm_historial (participant_id, user_id, action_name, old_status, new_status, old_observations, new_observations) VALUES (:participant_id, :user_id, :action_name, :old_status, :new_status, :old_observations, :new_observations)');
        $history->execute([
            'participant_id' => $participantId,
            'user_id' => $userId,
            'action_name' => 'update_followup',
            'old_status' => $oldStatus,
            'new_status' => $status,
            'old_observations' => $oldObservations,
            'new_observations' => $observations,
        ]);
    }

    public function saveDocument(int $participantId, int $userId, string $documentType, string $fileName, string $filePath, string $mimeType): void
    {
        $allowed = ['curp', 'certificado', 'adjunto'];
        if (!in_array($documentType, $allowed, true)) {
            throw new RuntimeException('Tipo de documento invalido.');
        }

        $stmt = $this->pdo->prepare('INSERT INTO rm_participant_documents (participant_id, document_type, document_name, file_path, mime_type, uploaded_by) VALUES (:participant_id, :document_type, :document_name, :file_path, :mime_type, :uploaded_by) ON DUPLICATE KEY UPDATE document_name = VALUES(document_name), file_path = VALUES(file_path), mime_type = VALUES(mime_type), uploaded_by = VALUES(uploaded_by), created_at = CURRENT_TIMESTAMP');
        $stmt->execute([
            'participant_id' => $participantId,
            'document_type' => $documentType,
            'document_name' => $fileName,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'uploaded_by' => $userId,
        ]);
    }

    public function exportParticipants(array $filters): array
    {
        $statusJoin = $this->currentStatusJoinSql();
        $submissionReady = $this->hasSubmissionMirrorSchema();
        $submissionJoin = $submissionReady
            ? 'LEFT JOIN rm_participant_submissions s ON s.participant_id = p.rm_participant_id'
            : '';
        $whereSql = $this->buildParticipantWhereSql($filters);

        $submissionUnachUnit = $submissionReady ? 'COALESCE(s.unach_unit, p.unach_unit)' : 'p.unach_unit';
        $submissionUnachSemester = $submissionReady ? "COALESCE(s.unach_semester, CASE WHEN p.institution = 'unach' THEN p.semester ELSE NULL END)" : "CASE WHEN p.institution = 'unach' THEN p.semester ELSE NULL END";
        $submissionUnachMajor = $submissionReady ? 'COALESCE(s.unach_major, p.unach_major)' : 'p.unach_major';
        $submissionUnachFirstName = $submissionReady ? "COALESCE(s.unach_first_name, CASE WHEN p.institution = 'unach' THEN p.first_name ELSE NULL END)" : "CASE WHEN p.institution = 'unach' THEN p.first_name ELSE NULL END";
        $submissionUnachLastName1 = $submissionReady ? "COALESCE(s.unach_last_name_1, CASE WHEN p.institution = 'unach' THEN p.last_name_paternal ELSE NULL END)" : "CASE WHEN p.institution = 'unach' THEN p.last_name_paternal ELSE NULL END";
        $submissionUnachLastName2 = $submissionReady ? "COALESCE(s.unach_last_name_2, CASE WHEN p.institution = 'unach' THEN p.last_name_maternal ELSE NULL END)" : "CASE WHEN p.institution = 'unach' THEN p.last_name_maternal ELSE NULL END";
        $submissionUnachBirthdate = $submissionReady ? "COALESCE(s.unach_birthdate, CASE WHEN p.institution = 'unach' THEN p.birthdate ELSE NULL END)" : "CASE WHEN p.institution = 'unach' THEN p.birthdate ELSE NULL END";
        $submissionUnachAge = $submissionReady ? "COALESCE(s.unach_age, CASE WHEN p.institution = 'unach' THEN p.age ELSE NULL END)" : "CASE WHEN p.institution = 'unach' THEN p.age ELSE NULL END";
        $submissionUnachGender = $submissionReady ? "COALESCE(s.unach_gender, CASE WHEN p.institution = 'unach' THEN p.gender ELSE NULL END)" : "CASE WHEN p.institution = 'unach' THEN p.gender ELSE NULL END";
        $submissionUnachCurp = $submissionReady ? "COALESCE(s.unach_curp, CASE WHEN p.institution = 'unach' THEN p.curp ELSE NULL END)" : "CASE WHEN p.institution = 'unach' THEN p.curp ELSE NULL END";
        $submissionUnachEmail = $submissionReady ? "COALESCE(s.unach_email, CASE WHEN p.institution = 'unach' THEN p.email ELSE NULL END)" : "CASE WHEN p.institution = 'unach' THEN p.email ELSE NULL END";
        $submissionUnachPhone = $submissionReady ? "COALESCE(s.unach_phone, CASE WHEN p.institution = 'unach' THEN p.phone ELSE NULL END)" : "CASE WHEN p.institution = 'unach' THEN p.phone ELSE NULL END";
        $submissionUnachState = $submissionReady ? "COALESCE(s.unach_state, CASE WHEN p.institution = 'unach' THEN p.state_name ELSE NULL END)" : "CASE WHEN p.institution = 'unach' THEN p.state_name ELSE NULL END";
        $submissionUnachCity = $submissionReady ? "COALESCE(s.unach_city, CASE WHEN p.institution = 'unach' THEN p.city_name ELSE NULL END)" : "CASE WHEN p.institution = 'unach' THEN p.city_name ELSE NULL END";
        $submissionCobachCampus = $submissionReady ? 'COALESCE(s.cobach_campus, p.cobach_campus)' : 'p.cobach_campus';
        $submissionCobachSemester = $submissionReady ? "COALESCE(s.cobach_semester, CASE WHEN p.institution = 'cobach' THEN p.semester ELSE NULL END)" : "CASE WHEN p.institution = 'cobach' THEN p.semester ELSE NULL END";
        $submissionCobachArea = $submissionReady ? 'COALESCE(s.cobach_area, p.cobach_area)' : 'p.cobach_area';
        $submissionCobachFirstName = $submissionReady ? "COALESCE(s.cobach_first_name, CASE WHEN p.institution = 'cobach' THEN p.first_name ELSE NULL END)" : "CASE WHEN p.institution = 'cobach' THEN p.first_name ELSE NULL END";
        $submissionCobachLastName1 = $submissionReady ? "COALESCE(s.cobach_last_name_1, CASE WHEN p.institution = 'cobach' THEN p.last_name_paternal ELSE NULL END)" : "CASE WHEN p.institution = 'cobach' THEN p.last_name_paternal ELSE NULL END";
        $submissionCobachLastName2 = $submissionReady ? "COALESCE(s.cobach_last_name_2, CASE WHEN p.institution = 'cobach' THEN p.last_name_maternal ELSE NULL END)" : "CASE WHEN p.institution = 'cobach' THEN p.last_name_maternal ELSE NULL END";
        $submissionCobachBirthdate = $submissionReady ? "COALESCE(s.cobach_birthdate, CASE WHEN p.institution = 'cobach' THEN p.birthdate ELSE NULL END)" : "CASE WHEN p.institution = 'cobach' THEN p.birthdate ELSE NULL END";
        $submissionCobachAge = $submissionReady ? "COALESCE(s.cobach_age, CASE WHEN p.institution = 'cobach' THEN p.age ELSE NULL END)" : "CASE WHEN p.institution = 'cobach' THEN p.age ELSE NULL END";
        $submissionCobachGender = $submissionReady ? "COALESCE(s.cobach_gender, CASE WHEN p.institution = 'cobach' THEN p.gender ELSE NULL END)" : "CASE WHEN p.institution = 'cobach' THEN p.gender ELSE NULL END";
        $submissionCobachCurp = $submissionReady ? "COALESCE(s.cobach_curp, CASE WHEN p.institution = 'cobach' THEN p.curp ELSE NULL END)" : "CASE WHEN p.institution = 'cobach' THEN p.curp ELSE NULL END";
        $submissionCobachEmail = $submissionReady ? "COALESCE(s.cobach_email, CASE WHEN p.institution = 'cobach' THEN p.email ELSE NULL END)" : "CASE WHEN p.institution = 'cobach' THEN p.email ELSE NULL END";
        $submissionCobachPhone = $submissionReady ? "COALESCE(s.cobach_phone, CASE WHEN p.institution = 'cobach' THEN p.phone ELSE NULL END)" : "CASE WHEN p.institution = 'cobach' THEN p.phone ELSE NULL END";
        $submissionCobachState = $submissionReady ? "COALESCE(s.cobach_state, CASE WHEN p.institution = 'cobach' THEN p.state_name ELSE NULL END)" : "CASE WHEN p.institution = 'cobach' THEN p.state_name ELSE NULL END";
        $submissionCobachCity = $submissionReady ? "COALESCE(s.cobach_city, CASE WHEN p.institution = 'cobach' THEN p.city_name ELSE NULL END)" : "CASE WHEN p.institution = 'cobach' THEN p.city_name ELSE NULL END";
        $submissionCobachResponsiva = $submissionReady ? 'COALESCE(s.cobach_responsiva_path, p.responsiva_file_path)' : 'p.responsiva_file_path';
        $submissionCobachCertificado = $submissionReady ? 'COALESCE(s.cobach_certificado_path, p.certificado_file_path)' : 'p.certificado_file_path';

        $sql = "SELECT
            p.rm_participant_id AS id,
            p.created_at,
            COALESCE(obs.status, 'Pendiente') AS current_status,
            p.institution,
            CONCAT_WS(' ', p.first_name, p.last_name_paternal, p.last_name_maternal) AS full_name,
            CASE WHEN p.institution = 'unach' THEN p.unach_unit ELSE p.cobach_campus END AS location_label,
            CASE WHEN p.institution = 'unach' THEN p.unach_major ELSE p.cobach_area END AS program_label,
            p.semester,
            p.gender,
            p.state_name,
            p.city_name,
            p.email,
            p.phone,
            p.birthdate,
            p.age,
            p.curp,
            p.unach_unit,
            p.unach_major,
            p.cobach_campus,
            p.cobach_area,
            p.responsiva_file_path,
            p.certificado_file_path,
            {$submissionUnachUnit} AS submission_unach_unit,
            {$submissionUnachSemester} AS submission_unach_semester,
            {$submissionUnachMajor} AS submission_unach_major,
            {$submissionUnachFirstName} AS submission_unach_first_name,
            {$submissionUnachLastName1} AS submission_unach_last_name_1,
            {$submissionUnachLastName2} AS submission_unach_last_name_2,
            {$submissionUnachBirthdate} AS submission_unach_birthdate,
            {$submissionUnachAge} AS submission_unach_age,
            {$submissionUnachGender} AS submission_unach_gender,
            {$submissionUnachCurp} AS submission_unach_curp,
            {$submissionUnachEmail} AS submission_unach_email,
            {$submissionUnachPhone} AS submission_unach_phone,
            {$submissionUnachState} AS submission_unach_state,
            {$submissionUnachCity} AS submission_unach_city,
            {$submissionCobachCampus} AS submission_cobach_campus,
            {$submissionCobachSemester} AS submission_cobach_semester,
            {$submissionCobachArea} AS submission_cobach_area,
            {$submissionCobachFirstName} AS submission_cobach_first_name,
            {$submissionCobachLastName1} AS submission_cobach_last_name_1,
            {$submissionCobachLastName2} AS submission_cobach_last_name_2,
            {$submissionCobachBirthdate} AS submission_cobach_birthdate,
            {$submissionCobachAge} AS submission_cobach_age,
            {$submissionCobachGender} AS submission_cobach_gender,
            {$submissionCobachCurp} AS submission_cobach_curp,
            {$submissionCobachEmail} AS submission_cobach_email,
            {$submissionCobachPhone} AS submission_cobach_phone,
            {$submissionCobachState} AS submission_cobach_state,
            {$submissionCobachCity} AS submission_cobach_city,
            {$submissionCobachResponsiva} AS submission_cobach_responsiva_path,
            {$submissionCobachCertificado} AS submission_cobach_certificado_path
            FROM rm_participants p
            {$statusJoin}
            {$submissionJoin}
            {$whereSql}
            ORDER BY p.created_at DESC, p.rm_participant_id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt ? $stmt->fetchAll() : [];
    }
}
