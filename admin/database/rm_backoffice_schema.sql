CREATE TABLE IF NOT EXISTS rm_usuarios (
  rm_user_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NULL,
  role_name ENUM('superadmin', 'admin', 'editor') NOT NULL DEFAULT 'admin',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (rm_user_id),
  UNIQUE KEY rm_users_username_unique (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rm_observaciones (
  rm_observation_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  participant_id BIGINT UNSIGNED NOT NULL,
  status ENUM('Pendiente', 'Documentacion incompleta', 'Validado', 'Rechazado') NOT NULL DEFAULT 'Pendiente',
  observations TEXT NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (rm_observation_id),
  UNIQUE KEY rm_observaciones_participant_unique (participant_id),
  KEY rm_observaciones_status_idx (status),
  CONSTRAINT rm_observaciones_participant_fk FOREIGN KEY (participant_id) REFERENCES rm_participants (rm_participant_id) ON DELETE CASCADE,
  CONSTRAINT rm_observaciones_user_fk FOREIGN KEY (updated_by) REFERENCES rm_usuarios (rm_user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rm_historial (
  rm_history_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  participant_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  action_name VARCHAR(80) NOT NULL,
  old_status VARCHAR(50) NULL,
  new_status VARCHAR(50) NULL,
  old_observations TEXT NULL,
  new_observations TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rm_history_id),
  KEY rm_historial_participant_idx (participant_id),
  CONSTRAINT rm_historial_participant_fk FOREIGN KEY (participant_id) REFERENCES rm_participants (rm_participant_id) ON DELETE CASCADE,
  CONSTRAINT rm_historial_user_fk FOREIGN KEY (user_id) REFERENCES rm_usuarios (rm_user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rm_participant_documents (
  rm_document_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  participant_id BIGINT UNSIGNED NOT NULL,
  document_type ENUM('curp', 'certificado', 'adjunto') NOT NULL DEFAULT 'adjunto',
  document_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (rm_document_id),
  UNIQUE KEY rm_documents_participant_type_unique (participant_id, document_type),
  KEY rm_documents_participant_idx (participant_id),
  CONSTRAINT rm_documents_participant_fk FOREIGN KEY (participant_id) REFERENCES rm_participants (rm_participant_id) ON DELETE CASCADE,
  CONSTRAINT rm_documents_user_fk FOREIGN KEY (uploaded_by) REFERENCES rm_usuarios (rm_user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO rm_usuarios (username, password_hash, full_name, email, role_name, is_active)
VALUES ('admin', '$2y$10$E0XLGgFMotO0TSdt/wqTDu4V0KVREG.u4hC7nMFRTW9KA.w2TZOfW', 'Administrador Reto Marte', 'admin@retomarte.local', 'superadmin', 1)
ON DUPLICATE KEY UPDATE
  full_name = VALUES(full_name),
  email = VALUES(email),
  role_name = VALUES(role_name),
  is_active = VALUES(is_active);
