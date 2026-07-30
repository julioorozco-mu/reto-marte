-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-07-2026 a las 22:59:04
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `marte`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rm_historial`
--

CREATE TABLE `rm_historial` (
  `rm_history_id` bigint(20) UNSIGNED NOT NULL,
  `participant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action_name` varchar(80) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `old_observations` text DEFAULT NULL,
  `new_observations` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rm_historial`
--

INSERT INTO `rm_historial` (`rm_history_id`, `participant_id`, `user_id`, `action_name`, `old_status`, `new_status`, `old_observations`, `new_observations`, `created_at`) VALUES
(4, 2, 1, 'update_followup', 'Pendiente', 'Validado', '', '', '2026-07-03 18:00:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rm_observaciones`
--

CREATE TABLE `rm_observaciones` (
  `rm_observation_id` bigint(20) UNSIGNED NOT NULL,
  `participant_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('Pendiente','Documentacion incompleta','Validado','Rechazado') NOT NULL DEFAULT 'Pendiente',
  `observations` text DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rm_observaciones`
--

INSERT INTO `rm_observaciones` (`rm_observation_id`, `participant_id`, `status`, `observations`, `updated_by`, `created_at`, `updated_at`) VALUES
(4, 2, 'Validado', '', 1, '2026-07-03 18:00:19', '2026-07-03 18:00:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rm_participants`
--

CREATE TABLE `rm_participants` (
  `rm_participant_id` bigint(20) UNSIGNED NOT NULL,
  `institution` enum('unach','cobach') NOT NULL,
  `first_name` varchar(120) NOT NULL,
  `last_name_paternal` varchar(120) NOT NULL,
  `last_name_maternal` varchar(120) NOT NULL,
  `birthdate` date NOT NULL,
  `age` tinyint(3) UNSIGNED NOT NULL,
  `gender` varchar(30) NOT NULL,
  `curp` varchar(18) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `state_name` varchar(120) NOT NULL,
  `city_name` varchar(120) NOT NULL,
  `semester` varchar(8) NOT NULL,
  `unach_unit` varchar(255) DEFAULT NULL,
  `unach_major` varchar(180) DEFAULT NULL,
  `cobach_campus` varchar(180) DEFAULT NULL,
  `cobach_area` varchar(180) DEFAULT NULL,
  `responsiva_file_path` varchar(255) DEFAULT NULL,
  `certificado_file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rm_participants`
--

INSERT INTO `rm_participants` (`rm_participant_id`, `institution`, `first_name`, `last_name_paternal`, `last_name_maternal`, `birthdate`, `age`, `gender`, `curp`, `email`, `phone`, `state_name`, `city_name`, `semester`, `unach_unit`, `unach_major`, `cobach_campus`, `cobach_area`, `responsiva_file_path`, `certificado_file_path`, `created_at`, `updated_at`) VALUES
(1, 'cobach', 'Victor', 'Rodriguez', 'Hernandez', '1990-03-20', 36, 'Masculino', 'HEJV850326HCSRVC00', 'victor.jovel85@gmail.com', '11651616151', 'Chiapas', 'Tuxtla', 'III', NULL, NULL, 'Plantel 35 Tuxtla Norte', 'Tecnologías de la Información y la Comunicación (TIC)', 'uploads/participants/2026/07/cobach_responsiva_7a94b3ac9f7a8c40.pdf', 'uploads/participants/2026/07/cobach_certificado_cb2312b9f8297218.pdf', '2026-07-03 17:19:12', '2026-07-03 17:19:12'),
(2, 'unach', 'Martha', 'De Gyves', 'Mendizabal', '1990-01-30', 36, 'Femenino', 'HEJV850326HCSRVC00', 'victor.jovel85@gmail.com', '9611209361', 'chiapas', 'Tuxtla gutiérrez', 'II', 'FACULTAD DE ARQUITECTURA C-I', 'Arquitectura', NULL, NULL, NULL, NULL, '2026-07-03 17:21:02', '2026-07-03 17:21:02'),
(3, 'unach', 'Carlos', 'hernandez', 'Hernandez', '2000-02-01', 26, 'Masculino', 'HEJV850326HCSRVC00', 'victor.jovel85@gmail.com', '9611209361', 'chiapas', 'tuxtla gutierrez', 'IX', 'FACULTAD DE CIENCIAS DE LA ADMINISTRACION C.IV EXT CACAHOATAN', 'Sistemas', NULL, NULL, NULL, NULL, '2026-07-03 18:01:10', '2026-07-03 18:01:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rm_participant_documents`
--

CREATE TABLE `rm_participant_documents` (
  `rm_document_id` bigint(20) UNSIGNED NOT NULL,
  `participant_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` enum('curp','certificado','adjunto') NOT NULL DEFAULT 'adjunto',
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rm_participant_documents`
--

INSERT INTO `rm_participant_documents` (`rm_document_id`, `participant_id`, `document_type`, `document_name`, `file_path`, `mime_type`, `uploaded_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'adjunto', 'COT_2026_001_UNACH_LENOVO_ACTUALIZADA_V2.pdf', 'uploads/participants/2026/07/cobach_responsiva_7a94b3ac9f7a8c40.pdf', 'application/pdf', NULL, '2026-07-03 17:19:12', '2026-07-03 17:19:12'),
(2, 1, 'certificado', 'Currículum Vitae CV commmunity manager minimalista.pdf', 'uploads/participants/2026/07/cobach_certificado_cb2312b9f8297218.pdf', 'application/pdf', NULL, '2026-07-03 17:19:12', '2026-07-03 17:19:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rm_participant_submissions`
--

CREATE TABLE `rm_participant_submissions` (
  `rm_submission_id` bigint(20) UNSIGNED NOT NULL,
  `participant_id` bigint(20) UNSIGNED NOT NULL,
  `institution` enum('unach','cobach') NOT NULL,
  `unach_unit` varchar(255) DEFAULT NULL,
  `unach_semester` varchar(8) DEFAULT NULL,
  `unach_major` varchar(180) DEFAULT NULL,
  `unach_first_name` varchar(120) DEFAULT NULL,
  `unach_last_name_1` varchar(120) DEFAULT NULL,
  `unach_last_name_2` varchar(120) DEFAULT NULL,
  `unach_birthdate` date DEFAULT NULL,
  `unach_age` tinyint(3) UNSIGNED DEFAULT NULL,
  `unach_gender` varchar(30) DEFAULT NULL,
  `unach_curp` varchar(18) DEFAULT NULL,
  `unach_email` varchar(190) DEFAULT NULL,
  `unach_phone` varchar(30) DEFAULT NULL,
  `unach_state` varchar(120) DEFAULT NULL,
  `unach_city` varchar(120) DEFAULT NULL,
  `cobach_campus` varchar(180) DEFAULT NULL,
  `cobach_semester` varchar(8) DEFAULT NULL,
  `cobach_area` varchar(180) DEFAULT NULL,
  `cobach_first_name` varchar(120) DEFAULT NULL,
  `cobach_last_name_1` varchar(120) DEFAULT NULL,
  `cobach_last_name_2` varchar(120) DEFAULT NULL,
  `cobach_birthdate` date DEFAULT NULL,
  `cobach_age` tinyint(3) UNSIGNED DEFAULT NULL,
  `cobach_gender` varchar(30) DEFAULT NULL,
  `cobach_curp` varchar(18) DEFAULT NULL,
  `cobach_email` varchar(190) DEFAULT NULL,
  `cobach_phone` varchar(30) DEFAULT NULL,
  `cobach_state` varchar(120) DEFAULT NULL,
  `cobach_city` varchar(120) DEFAULT NULL,
  `cobach_responsiva_path` varchar(255) DEFAULT NULL,
  `cobach_certificado_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rm_participant_submissions`
--

INSERT INTO `rm_participant_submissions` (`rm_submission_id`, `participant_id`, `institution`, `unach_unit`, `unach_semester`, `unach_major`, `unach_first_name`, `unach_last_name_1`, `unach_last_name_2`, `unach_birthdate`, `unach_age`, `unach_gender`, `unach_curp`, `unach_email`, `unach_phone`, `unach_state`, `unach_city`, `cobach_campus`, `cobach_semester`, `cobach_area`, `cobach_first_name`, `cobach_last_name_1`, `cobach_last_name_2`, `cobach_birthdate`, `cobach_age`, `cobach_gender`, `cobach_curp`, `cobach_email`, `cobach_phone`, `cobach_state`, `cobach_city`, `cobach_responsiva_path`, `cobach_certificado_path`, `created_at`, `updated_at`) VALUES
(1, 1, 'cobach', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Plantel 35 Tuxtla Norte', 'III', 'Tecnologías de la Información y la Comunicación (TIC)', 'Victor', 'Rodriguez', 'Hernandez', '1990-03-20', 36, 'Masculino', 'HEJV850326HCSRVC00', 'victor.jovel85@gmail.com', '11651616151', 'Chiapas', 'Tuxtla', 'uploads/participants/2026/07/cobach_responsiva_7a94b3ac9f7a8c40.pdf', 'uploads/participants/2026/07/cobach_certificado_cb2312b9f8297218.pdf', '2026-07-03 17:19:12', '2026-07-03 17:19:12'),
(2, 2, 'unach', 'FACULTAD DE ARQUITECTURA C-I', 'II', 'Arquitectura', 'Martha', 'De Gyves', 'Mendizabal', '1990-01-30', 36, 'Femenino', 'HEJV850326HCSRVC00', 'victor.jovel85@gmail.com', '9611209361', 'chiapas', 'Tuxtla gutiérrez', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-03 17:21:02', '2026-07-03 17:21:02'),
(3, 3, 'unach', 'FACULTAD DE CIENCIAS DE LA ADMINISTRACION C.IV EXT CACAHOATAN', 'IX', 'Sistemas', 'Carlos', 'hernandez', 'Hernandez', '2000-02-01', 26, 'Masculino', 'HEJV850326HCSRVC00', 'victor.jovel85@gmail.com', '9611209361', 'chiapas', 'tuxtla gutierrez', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-03 18:01:10', '2026-07-03 18:01:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rm_usuarios`
--

CREATE TABLE `rm_usuarios` (
  `rm_user_id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `role_name` enum('superadmin','admin','editor') NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rm_usuarios`
--

INSERT INTO `rm_usuarios` (`rm_user_id`, `username`, `password_hash`, `full_name`, `email`, `role_name`, `is_active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$fX/FhEBiXtrXv5yRfmJnjuz7j0hcNrlhmtPZqICyzjD5OVFA9e7.q', 'Administrador Reto Marte', 'admin@retomarte.local', 'superadmin', 1, '2026-07-03 20:48:13', '2026-07-01 21:40:40', '2026-07-03 20:48:13'),
(2, 'mariaamor', '$2y$10$vWuOAll2nWMQTadt/4mWWucQSaCtdJng.m2z5Ub9nAPkoEW9Opv/.', 'María Amor', NULL, 'admin', 1, '2026-07-02 16:22:01', '2026-07-02 16:14:44', '2026-07-02 16:22:01');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `rm_historial`
--
ALTER TABLE `rm_historial`
  ADD PRIMARY KEY (`rm_history_id`),
  ADD KEY `rm_historial_participant_idx` (`participant_id`),
  ADD KEY `rm_historial_user_fk` (`user_id`);

--
-- Indices de la tabla `rm_observaciones`
--
ALTER TABLE `rm_observaciones`
  ADD PRIMARY KEY (`rm_observation_id`),
  ADD UNIQUE KEY `rm_observaciones_participant_unique` (`participant_id`),
  ADD KEY `rm_observaciones_status_idx` (`status`),
  ADD KEY `rm_observaciones_user_fk` (`updated_by`);

--
-- Indices de la tabla `rm_participants`
--
ALTER TABLE `rm_participants`
  ADD PRIMARY KEY (`rm_participant_id`),
  ADD KEY `rm_idx_institution` (`institution`),
  ADD KEY `rm_idx_email` (`email`),
  ADD KEY `rm_idx_curp` (`curp`);

--
-- Indices de la tabla `rm_participant_documents`
--
ALTER TABLE `rm_participant_documents`
  ADD PRIMARY KEY (`rm_document_id`),
  ADD UNIQUE KEY `rm_documents_participant_type_unique` (`participant_id`,`document_type`),
  ADD KEY `rm_documents_participant_idx` (`participant_id`),
  ADD KEY `rm_documents_user_fk` (`uploaded_by`);

--
-- Indices de la tabla `rm_participant_submissions`
--
ALTER TABLE `rm_participant_submissions`
  ADD PRIMARY KEY (`rm_submission_id`),
  ADD UNIQUE KEY `rm_submission_participant_unique` (`participant_id`),
  ADD KEY `rm_submission_institution_idx` (`institution`);

--
-- Indices de la tabla `rm_usuarios`
--
ALTER TABLE `rm_usuarios`
  ADD PRIMARY KEY (`rm_user_id`),
  ADD UNIQUE KEY `rm_users_username_unique` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `rm_historial`
--
ALTER TABLE `rm_historial`
  MODIFY `rm_history_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `rm_observaciones`
--
ALTER TABLE `rm_observaciones`
  MODIFY `rm_observation_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `rm_participants`
--
ALTER TABLE `rm_participants`
  MODIFY `rm_participant_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `rm_participant_documents`
--
ALTER TABLE `rm_participant_documents`
  MODIFY `rm_document_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `rm_participant_submissions`
--
ALTER TABLE `rm_participant_submissions`
  MODIFY `rm_submission_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `rm_usuarios`
--
ALTER TABLE `rm_usuarios`
  MODIFY `rm_user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `rm_historial`
--
ALTER TABLE `rm_historial`
  ADD CONSTRAINT `rm_historial_participant_fk` FOREIGN KEY (`participant_id`) REFERENCES `rm_participants` (`rm_participant_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rm_historial_user_fk` FOREIGN KEY (`user_id`) REFERENCES `rm_usuarios` (`rm_user_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `rm_observaciones`
--
ALTER TABLE `rm_observaciones`
  ADD CONSTRAINT `rm_observaciones_participant_fk` FOREIGN KEY (`participant_id`) REFERENCES `rm_participants` (`rm_participant_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rm_observaciones_user_fk` FOREIGN KEY (`updated_by`) REFERENCES `rm_usuarios` (`rm_user_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `rm_participant_documents`
--
ALTER TABLE `rm_participant_documents`
  ADD CONSTRAINT `rm_documents_participant_fk` FOREIGN KEY (`participant_id`) REFERENCES `rm_participants` (`rm_participant_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rm_documents_user_fk` FOREIGN KEY (`uploaded_by`) REFERENCES `rm_usuarios` (`rm_user_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `rm_participant_submissions`
--
ALTER TABLE `rm_participant_submissions`
  ADD CONSTRAINT `rm_submission_participant_fk` FOREIGN KEY (`participant_id`) REFERENCES `rm_participants` (`rm_participant_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
