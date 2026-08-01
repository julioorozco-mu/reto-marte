<?php
declare(strict_types=1);

class BackofficeController
{
    public function __construct(private BackofficeModel $model)
    {
    }

    public function dashboard(): void
    {
        admin_require_auth();
        $stats = $this->model->dashboardStats();
        admin_render('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'route' => 'dashboard',
            'stats' => $stats,
            'currentUser' => admin_current_user(),
        ]);
    }

    public function participants(): void
    {
        admin_require_auth();
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'institution' => trim((string) ($_GET['institution'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'role' => trim((string) ($_GET['role'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
            'faculty' => trim((string) ($_GET['faculty'] ?? '')),
            'plantel' => trim((string) ($_GET['plantel'] ?? '')),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $sort = trim((string) ($_GET['sort'] ?? 'date'));
        $dir = strtoupper((string) ($_GET['dir'] ?? 'DESC'));
        $result = $this->model->listParticipants($filters, $page, 15, $sort, $dir);

        admin_render('participants/index', [
            'pageTitle' => 'Participantes',
            'route' => 'participants',
            'filters' => $filters,
            'rows' => $result['rows'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $page,
            'sort' => $sort,
            'dir' => $dir,
            'currentUser' => admin_current_user(),
        ]);
    }

    public function participantShow(): void
    {
        admin_require_auth();
        $participantId = (int) ($_GET['id'] ?? 0);
        if ($participantId <= 0) {
            admin_redirect('participants');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            admin_csrf_validate();
            $action = (string) ($_POST['action'] ?? '');
            $user = admin_current_user();
            $userId = (int) ($user['rm_user_id'] ?? 0);

            if ($action === 'save_followup') {
                $status = trim((string) ($_POST['status'] ?? 'Pendiente'));
                $observations = trim((string) ($_POST['observations'] ?? ''));
                $this->model->saveFollowUp($participantId, $status, $observations, $userId);
                admin_flash_set('success', 'Seguimiento actualizado.');
                admin_redirect('participant_show', ['id' => $participantId]);
            }

            if ($action === 'upload_document') {
                $documentType = trim((string) ($_POST['document_type'] ?? 'adjunto'));
                if (empty($_FILES['document_file']) || !is_array($_FILES['document_file'])) {
                    admin_flash_set('danger', 'Selecciona un archivo para subir.');
                    admin_redirect('participant_show', ['id' => $participantId]);
                }

                $file = $_FILES['document_file'];
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    admin_flash_set('danger', 'No se pudo recibir el archivo.');
                    admin_redirect('participant_show', ['id' => $participantId]);
                }

                $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
                $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = (string) $finfo->file((string) $file['tmp_name']);
                $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
                if (!in_array($mime, $allowedMimes, true) || !in_array($extension, $allowedExtensions, true)) {
                    admin_flash_set('danger', 'Solo se permiten archivos PDF, JPG o PNG.');
                    admin_redirect('participant_show', ['id' => $participantId]);
                }

                $documentDir = RM_STORAGE_PATH . '/documents/' . $participantId . '/' . $documentType;
                if (!is_dir($documentDir) && !mkdir($documentDir, 0775, true) && !is_dir($documentDir)) {
                    throw new RuntimeException('No se pudo crear la carpeta de documentos.');
                }

                $safeName = admin_safe_filename(pathinfo((string) $file['name'], PATHINFO_FILENAME));
                $storedName = $safeName . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
                $storedPath = $documentDir . '/' . $storedName;
                if (!move_uploaded_file((string) $file['tmp_name'], $storedPath)) {
                    admin_flash_set('danger', 'No se pudo guardar el archivo.');
                    admin_redirect('participant_show', ['id' => $participantId]);
                }

                $relativePath = 'storage/documents/' . $participantId . '/' . $documentType . '/' . $storedName;
                $this->model->saveDocument($participantId, $userId, $documentType, (string) $file['name'], $relativePath, $mime);
                admin_flash_set('success', 'Documento cargado correctamente.');
                admin_redirect('participant_show', ['id' => $participantId]);
            }
        }

        $participant = $this->model->findParticipant($participantId);
        if (!$participant) {
            admin_redirect('participants');
        }

        $this->model->findSubmission($participantId);
        $this->model->getDocuments($participantId);
        $this->model->getObservationsHistory($participantId);
        admin_render('participants/show', [
            'pageTitle' => 'Detalle del participante',
            'route' => 'participants',
            'participant' => $participant,
            'submission' => $this->model->findSubmission($participantId),
            'documents' => $this->model->getDocuments($participantId),
            'history' => $this->model->getObservationsHistory($participantId),
            'currentUser' => admin_current_user(),
        ]);
    }

    public function reports(): void
    {
        admin_require_auth();
        admin_render('reports/index', [
            'pageTitle' => 'Reportes',
            'route' => 'reports',
            'currentUser' => admin_current_user(),
        ]);
    }

    public function export(): void
    {
        admin_require_auth();
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'institution' => trim((string) ($_GET['institution'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'role' => trim((string) ($_GET['role'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
            'faculty' => trim((string) ($_GET['faculty'] ?? '')),
            'plantel' => trim((string) ($_GET['plantel'] ?? '')),
        ];
        $rows = $this->model->exportParticipants($filters);
        AdminExcelExporter::downloadParticipants($rows, $filters, 'rm_participantes.xlsx');
    }

    public function users(): void
    {
        admin_require_auth();
        $currentUser = admin_current_user();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            admin_csrf_validate();
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'create_user') {
                $username = trim((string) ($_POST['username'] ?? ''));
                $fullName = trim((string) ($_POST['full_name'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $roleName = trim((string) ($_POST['role_name'] ?? 'admin'));

                if ($username === '' || $fullName === '' || $password === '') {
                    admin_flash_set('danger', 'Completa los campos requeridos.');
                    admin_redirect('users');
                }

                try {
                    $this->model->createUser([
                        'username' => $username,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'full_name' => $fullName,
                        'email' => $email !== '' ? $email : null,
                        'role_name' => in_array($roleName, ['superadmin', 'admin', 'editor'], true) ? $roleName : 'admin',
                        'is_active' => 1,
                    ]);
                    admin_flash_set('success', 'Usuario creado correctamente.');
                } catch (Throwable $exception) {
                    admin_flash_set('danger', 'No se pudo crear el usuario. Verifica que no exista el username.');
                }

                admin_redirect('users');
            }

            if ($action === 'update_user') {
                $userId = (int) ($_POST['user_id'] ?? 0);
                $username = trim((string) ($_POST['username'] ?? ''));
                $fullName = trim((string) ($_POST['full_name'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $roleName = trim((string) ($_POST['role_name'] ?? 'admin'));
                $isActive = (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0;

                if ($userId <= 0 || $username === '' || $fullName === '') {
                    admin_flash_set('danger', 'Completa los campos requeridos para actualizar el usuario.');
                    admin_redirect('users', ['edit' => $userId > 0 ? $userId : null]);
                }

                try {
                    $this->model->updateUser([
                        'rm_user_id' => $userId,
                        'username' => $username,
                        'full_name' => $fullName,
                        'email' => $email !== '' ? $email : null,
                        'role_name' => in_array($roleName, ['superadmin', 'admin', 'editor'], true) ? $roleName : 'admin',
                        'is_active' => $isActive,
                        'password_hash' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
                    ]);
                    admin_flash_set('success', 'Usuario actualizado correctamente.');
                } catch (Throwable $exception) {
                    admin_flash_set('danger', 'No se pudo actualizar el usuario. Verifica que el username no este duplicado.');
                    admin_redirect('users', ['edit' => $userId]);
                }

                admin_redirect('users');
            }

            if ($action === 'delete_user') {
                $userId = (int) ($_POST['user_id'] ?? 0);
                if ($userId <= 0) {
                    admin_flash_set('danger', 'Usuario invalido.');
                    admin_redirect('users');
                }

                if ((int) ($currentUser['rm_user_id'] ?? 0) === $userId) {
                    admin_flash_set('danger', 'No puedes eliminar tu propio usuario mientras la sesion esta activa.');
                    admin_redirect('users');
                }

                try {
                    $this->model->deleteUser($userId);
                    admin_flash_set('success', 'Usuario eliminado correctamente.');
                } catch (Throwable $exception) {
                    admin_flash_set('danger', 'No se pudo eliminar el usuario.');
                }

                admin_redirect('users');
            }
        }

        $editUserId = (int) ($_GET['edit'] ?? 0);
        $editUser = $editUserId > 0 ? $this->model->findUserById($editUserId) : null;

        admin_render('users/index', [
            'pageTitle' => 'Usuarios',
            'route' => 'users',
            'users' => $this->model->listUsers(),
            'editUser' => $editUser,
            'availableRoles' => ['admin', 'superadmin', 'editor'],
            'currentUser' => $currentUser,
        ]);
    }

    public function settings(): void
    {
        admin_require_auth();
        admin_render('settings/index', [
            'pageTitle' => 'Configuracion',
            'route' => 'settings',
            'currentUser' => admin_current_user(),
        ]);
    }

    public function notFound(): void
    {
        http_response_code(404);
        admin_render('reports/index', [
            'pageTitle' => 'Ruta no encontrada',
            'route' => 'dashboard',
            'currentUser' => admin_current_user(),
        ]);
    }
}
