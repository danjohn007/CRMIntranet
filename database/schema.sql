-- CRM de Solicitudes de Visas y Pasaportes
-- Base de datos con datos de ejemplo del estado de Querétaro

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS `crm_visas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `crm_visas`;

-- Tabla de Usuarios
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('Administrador','Gerente','Asesor') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuarios de ejemplo
-- Contraseña para todos: password123
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `phone`, `is_active`) VALUES
('admin', 'admin@crmvisas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos Administrador', 'Administrador', '4421234567', 1),
('gerente01', 'gerente@crmvisas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María González López', 'Gerente', '4421234568', 1),
('asesor01', 'asesor1@crmvisas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Pérez Ramírez', 'Asesor', '4421234569', 1),
('asesor02', 'asesor2@crmvisas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana Martínez Sánchez', 'Asesor', '4421234570', 1);

-- Tabla de Formularios Dinámicos
DROP TABLE IF EXISTS `forms`;
CREATE TABLE `forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `type` enum('Visa','Pasaporte') NOT NULL,
  `subtype` varchar(50) DEFAULT NULL COMMENT 'Primera vez, Renovación, etc',
  `version` int(11) DEFAULT 1,
  `is_published` tinyint(1) DEFAULT 0,
  `fields_json` longtext NOT NULL COMMENT 'Estructura del formulario en JSON',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `forms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formularios de ejemplo
INSERT INTO `forms` (`name`, `description`, `type`, `subtype`, `version`, `is_published`, `fields_json`, `created_by`) VALUES
('Visa Americana - Primera Vez', 'Formulario para solicitud de visa americana por primera vez', 'Visa', 'Primera Vez', 1, 1, '{"fields":[{"id":"nombre","type":"text","label":"Nombre Completo","required":true},{"id":"pasaporte","type":"text","label":"Número de Pasaporte","required":true},{"id":"fecha_nacimiento","type":"date","label":"Fecha de Nacimiento","required":true},{"id":"motivo","type":"select","label":"Motivo del Viaje","options":["Turismo","Negocios","Estudios","Trabajo"],"required":true},{"id":"documento_pasaporte","type":"file","label":"Copia del Pasaporte","required":true}]}', 1),
('Pasaporte Mexicano - Renovación', 'Formulario para renovación de pasaporte mexicano', 'Pasaporte', 'Renovación', 1, 1, '{"fields":[{"id":"nombre","type":"text","label":"Nombre Completo","required":true},{"id":"curp","type":"text","label":"CURP","required":true},{"id":"pasaporte_anterior","type":"text","label":"Número de Pasaporte Anterior","required":true},{"id":"acta_nacimiento","type":"file","label":"Acta de Nacimiento","required":true}]}', 1);

-- Tabla de Solicitudes
DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `folio` varchar(50) NOT NULL COMMENT 'VISA-YYYY-NNNNNN',
  `form_id` int(11) NOT NULL,
  `form_version` int(11) NOT NULL,
  `type` enum('Visa','Pasaporte') NOT NULL,
  `subtype` varchar(50) DEFAULT NULL,
  `status` enum('Creado','En revisión','Información incompleta','Documentación validada','En proceso','Aprobado','Rechazado','Finalizado') DEFAULT 'Creado',
  `data_json` longtext NOT NULL COMMENT 'Datos del formulario en JSON',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `folio` (`folio`),
  KEY `form_id` (`form_id`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`),
  CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solicitudes de ejemplo con datos de meses pasados para gráficas mejoradas
-- Se incluyen datos de 8 meses atrás hasta la fecha actual

-- Agosto 2025 (8 meses atrás) - 6 solicitudes
INSERT INTO `applications` (`folio`, `form_id`, `form_version`, `type`, `subtype`, `status`, `data_json`, `created_by`, `created_at`) VALUES
('VISA-2025-000001', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Roberto García Méndez","pasaporte":"M123456789","fecha_nacimiento":"1985-05-15","motivo":"Turismo"}', 3, '2025-08-05 09:30:00'),
('VISA-2025-000002', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Laura Hernández Torres","pasaporte":"M987654321","fecha_nacimiento":"1990-08-22","motivo":"Negocios"}', 3, '2025-08-08 10:15:00'),
('VISA-2025-000003', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Pedro Ramírez Luna","curp":"RALP850315HQTMND01","pasaporte_anterior":"M111222333"}', 4, '2025-08-12 14:20:00'),
('VISA-2025-000004', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Diana Flores Castro","pasaporte":"M555666777","fecha_nacimiento":"1995-03-10","motivo":"Estudios"}', 3, '2025-08-18 11:45:00'),
('VISA-2025-000005', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Miguel Ángel Ortiz","curp":"OIGM920612HQTRTG03","pasaporte_anterior":"M444555666"}', 4, '2025-08-22 15:30:00'),
('VISA-2025-000006', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Carmen Sánchez Ruiz","pasaporte":"M234567890","fecha_nacimiento":"1988-11-05","motivo":"Turismo"}', 3, '2025-08-28 09:00:00'),

-- Septiembre 2025 (7 meses atrás) - 8 solicitudes
('VISA-2025-000007', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"José Luis Mendoza","pasaporte":"M345678901","fecha_nacimiento":"1992-03-12","motivo":"Negocios"}', 3, '2025-09-03 10:20:00'),
('VISA-2025-000008', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Patricia Jiménez Vega","curp":"JIVP870520MQTMGT08","pasaporte_anterior":"M222333444"}', 4, '2025-09-05 13:40:00'),
('VISA-2025-000009', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Fernando Torres Aguilar","pasaporte":"M456789012","fecha_nacimiento":"1986-09-25","motivo":"Turismo"}', 3, '2025-09-10 11:15:00'),
('VISA-2025-000010', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Gabriela Morales Castro","curp":"MOCG910415MQTRBL02","pasaporte_anterior":"M333444555"}', 4, '2025-09-12 14:50:00'),
('VISA-2025-000011', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Ricardo Vargas Pérez","pasaporte":"M567890123","fecha_nacimiento":"1989-07-08","motivo":"Estudios"}', 3, '2025-09-17 09:30:00'),
('VISA-2025-000012', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Sandra López Martínez","pasaporte":"M678901234","fecha_nacimiento":"1993-12-20","motivo":"Negocios"}', 4, '2025-09-20 16:00:00'),
('VISA-2025-000013', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Alberto Ramírez González","curp":"RAGA840225HQTMZL09","pasaporte_anterior":"M444555666"}', 3, '2025-09-24 10:45:00'),
('VISA-2025-000014', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Verónica Castro Díaz","pasaporte":"M789012345","fecha_nacimiento":"1991-05-14","motivo":"Turismo"}', 4, '2025-09-28 15:20:00'),

-- Octubre 2025 (6 meses atrás) - 10 solicitudes
('VISA-2025-000015', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Héctor Gutiérrez Silva","pasaporte":"M890123456","fecha_nacimiento":"1987-01-30","motivo":"Negocios"}', 3, '2025-10-02 09:15:00'),
('VISA-2025-000016', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Monica Fernández Rojas","curp":"FERM880710MQTRNL07","pasaporte_anterior":"M555666777"}', 4, '2025-10-05 11:30:00'),
('VISA-2025-000017', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Andrés Pacheco Méndez","pasaporte":"M901234567","fecha_nacimiento":"1994-08-18","motivo":"Turismo"}', 3, '2025-10-08 14:00:00'),
('VISA-2025-000018', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Isabel Reyes Cruz","pasaporte":"M012345678","fecha_nacimiento":"1990-04-22","motivo":"Estudios"}', 3, '2025-10-11 10:20:00'),
('VISA-2025-000019', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"David Soto Navarro","curp":"SOND860915HQTTVY04","pasaporte_anterior":"M666777888"}', 4, '2025-10-14 15:45:00'),
('VISA-2025-000020', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Claudia Herrera Ponce","pasaporte":"M123450987","fecha_nacimiento":"1992-11-03","motivo":"Negocios"}', 3, '2025-10-17 09:50:00'),
('VISA-2025-000021', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Ernesto Ríos Campos","pasaporte":"M234561098","fecha_nacimiento":"1988-06-16","motivo":"Turismo"}', 4, '2025-10-20 13:25:00'),
('VISA-2025-000022', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Leticia Valdez Ochoa","curp":"VAOL890320MQTLCT06","pasaporte_anterior":"M777888999"}', 3, '2025-10-23 11:10:00'),
('VISA-2025-000023', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Pablo Domínguez Luna","pasaporte":"M345672109","fecha_nacimiento":"1993-09-27","motivo":"Negocios"}', 4, '2025-10-26 16:30:00'),
('VISA-2025-000024', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Adriana Paredes Solis","pasaporte":"M456783210","fecha_nacimiento":"1991-02-11","motivo":"Turismo"}', 3, '2025-10-30 10:00:00'),

-- Noviembre 2025 (5 meses atrás) - 12 solicitudes
('VISA-2025-000025', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Rodrigo Maldonado Ortiz","curp":"MAOR850618HQTLRD05","pasaporte_anterior":"M888999000"}', 4, '2025-11-02 09:30:00'),
('VISA-2025-000026', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Elena Campos Rojas","pasaporte":"M567894321","fecha_nacimiento":"1989-12-05","motivo":"Estudios"}', 3, '2025-11-05 14:15:00'),
('VISA-2025-000027', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Manuel Núñez Castillo","pasaporte":"M678905432","fecha_nacimiento":"1987-10-19","motivo":"Turismo"}', 3, '2025-11-08 10:40:00'),
('VISA-2025-000028', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Rosa María Salazar Vega","curp":"SAVR880425MQTLGS08","pasaporte_anterior":"M999000111"}', 4, '2025-11-11 15:20:00'),
('VISA-2025-000029', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Jorge Iván Cervantes","pasaporte":"M789016543","fecha_nacimiento":"1994-03-28","motivo":"Negocios"}', 3, '2025-11-14 11:50:00'),
('VISA-2025-000030', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Mariana Espinoza Torres","pasaporte":"M890127654","fecha_nacimiento":"1992-07-15","motivo":"Turismo"}', 4, '2025-11-17 09:20:00'),
('VISA-2025-000031', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Francisco Robles Martín","curp":"ROMF860810HQTBRY03","pasaporte_anterior":"M000111222"}', 3, '2025-11-20 13:45:00'),
('VISA-2025-000032', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Silvia Cordero Navarro","pasaporte":"M901238765","fecha_nacimiento":"1990-11-22","motivo":"Estudios"}', 4, '2025-11-22 16:10:00'),
('VISA-2025-000033', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Raúl Mejía Delgado","pasaporte":"M012349876","fecha_nacimiento":"1988-04-07","motivo":"Negocios"}', 3, '2025-11-24 10:30:00'),
('VISA-2025-000034', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Beatriz Fuentes Arias","pasaporte":"M123459087","fecha_nacimiento":"1993-08-13","motivo":"Turismo"}', 3, '2025-11-26 14:55:00'),
('VISA-2025-000035', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Guillermo Acosta Peña","curp":"AOPG870522HQTCLL01","pasaporte_anterior":"M111222333"}', 4, '2025-11-28 11:25:00'),
('VISA-2025-000036', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Norma Alicia Ruiz Vera","pasaporte":"M234560198","fecha_nacimiento":"1991-01-26","motivo":"Turismo"}', 3, '2025-11-30 15:40:00'),

-- Diciembre 2025 (4 meses atrás) - 14 solicitudes
('VISA-2025-000037', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Oscar Téllez Bravo","pasaporte":"M345671209","fecha_nacimiento":"1989-05-09","motivo":"Negocios"}', 4, '2025-12-02 09:45:00'),
('VISA-2025-000038', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Teresa Márquez Gómez","curp":"MAGT840715MQTRML04","pasaporte_anterior":"M222333444"}', 3, '2025-12-04 13:15:00'),
('VISA-2025-000039', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Javier Montoya Ramos","pasaporte":"M456782310","fecha_nacimiento":"1992-09-21","motivo":"Turismo"}', 3, '2025-12-06 10:20:00'),
('VISA-2025-000040', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Lorena Bautista Cruz","pasaporte":"M567893421","fecha_nacimiento":"1990-12-14","motivo":"Estudios"}', 4, '2025-12-09 14:50:00'),
('VISA-2025-000041', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Arturo Villanueva Mora","curp":"VIMA860330HQTLRR07","pasaporte_anterior":"M333444555"}', 3, '2025-12-11 11:10:00'),
('VISA-2025-000042', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Cecilia Medina Santos","pasaporte":"M678904532","fecha_nacimiento":"1994-06-18","motivo":"Negocios"}', 3, '2025-12-13 15:35:00'),
('VISA-2025-000043', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Alfredo Carrillo Peña","pasaporte":"M789015643","fecha_nacimiento":"1988-10-29","motivo":"Turismo"}', 4, '2025-12-15 09:55:00'),
('VISA-2025-000044', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Victoria Sandoval Lara","curp":"SALV890205MQTNDL02","pasaporte_anterior":"M444555666"}', 3, '2025-12-17 13:20:00'),
('VISA-2025-000045', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Sergio Zamora Flores","pasaporte":"M890126754","fecha_nacimiento":"1991-03-16","motivo":"Negocios"}', 4, '2025-12-19 16:45:00'),
('VISA-2025-000046', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Daniela Olvera Martínez","pasaporte":"M901237865","fecha_nacimiento":"1993-11-08","motivo":"Turismo"}', 3, '2025-12-21 10:15:00'),
('VISA-2025-000047', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Ramón Galván Muñoz","curp":"GAMR870628HQTLXN06","pasaporte_anterior":"M555666777"}', 3, '2025-12-23 14:30:00'),
('VISA-2025-000048', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Martha Lucía Parra","pasaporte":"M012348976","fecha_nacimiento":"1989-08-24","motivo":"Estudios"}', 4, '2025-12-26 11:40:00'),
('VISA-2025-000049', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Enrique Cortés Díaz","pasaporte":"M123458087","fecha_nacimiento":"1992-04-12","motivo":"Turismo"}', 3, '2025-12-28 15:05:00'),
('VISA-2025-000050', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Lucía Escobar Rivas","pasaporte":"M234569198","fecha_nacimiento":"1990-07-31","motivo":"Negocios"}', 4, '2025-12-30 09:25:00'),

-- Enero 2026 (actual) - 15 solicitudes con diferentes estados
('VISA-2026-000001', 1, 1, 'Visa', 'Primera Vez', 'Finalizado', '{"nombre":"Carlos Ibarra Moreno","pasaporte":"M345670209","fecha_nacimiento":"1987-02-19","motivo":"Turismo"}', 3, '2026-01-02 10:30:00'),
('VISA-2026-000002', 2, 1, 'Pasaporte', 'Renovación', 'Finalizado', '{"nombre":"Gloria Contreras Lima","curp":"COLG880912MQTNLR09","pasaporte_anterior":"M666777888"}', 4, '2026-01-04 13:50:00'),
('VISA-2026-000003', 1, 1, 'Visa', 'Primera Vez', 'Aprobado', '{"nombre":"Tomás Quintero Vega","pasaporte":"M456781320","fecha_nacimiento":"1994-10-06","motivo":"Negocios"}', 3, '2026-01-06 11:15:00'),
('VISA-2026-000004', 1, 1, 'Visa', 'Primera Vez', 'Aprobado', '{"nombre":"Angélica Barrera Soto","pasaporte":"M567892431","fecha_nacimiento":"1991-05-23","motivo":"Estudios"}', 3, '2026-01-08 14:40:00'),
('VISA-2026-000005', 2, 1, 'Pasaporte', 'Renovación', 'En proceso', '{"nombre":"Miguel Ángel Cárdenas","curp":"CARM850417HQTRGY08","pasaporte_anterior":"M777888999"}', 4, '2026-01-10 09:20:00'),
('VISA-2026-000006', 1, 1, 'Visa', 'Primera Vez', 'En proceso', '{"nombre":"Alejandra Beltrán Cruz","pasaporte":"M678903542","fecha_nacimiento":"1992-08-15","motivo":"Turismo"}', 3, '2026-01-12 15:10:00'),
('VISA-2026-000007', 1, 1, 'Visa', 'Primera Vez', 'Documentación validada', '{"nombre":"Felipe Miranda Ortega","pasaporte":"M789014653","fecha_nacimiento":"1989-12-28","motivo":"Negocios"}', 4, '2026-01-14 10:45:00'),
('VISA-2026-000008', 2, 1, 'Pasaporte', 'Renovación', 'Documentación validada', '{"nombre":"Susana Padilla Velasco","curp":"PAVS860820MQTDLS05","pasaporte_anterior":"M888999000"}', 3, '2026-01-16 13:25:00'),
('VISA-2026-000009', 1, 1, 'Visa', 'Primera Vez', 'En revisión', '{"nombre":"Gustavo Chávez Ríos","pasaporte":"M890125764","fecha_nacimiento":"1993-03-11","motivo":"Turismo"}', 3, '2026-01-18 11:55:00'),
('VISA-2026-000010', 1, 1, 'Visa', 'Primera Vez', 'En revisión', '{"nombre":"Patricia Trejo Campos","pasaporte":"M901236875","fecha_nacimiento":"1990-09-04","motivo":"Estudios"}', 4, '2026-01-20 14:20:00'),
('VISA-2026-000011', 2, 1, 'Pasaporte', 'Renovación', 'En revisión', '{"nombre":"Leonardo Ochoa Silva","curp":"OOSL870505HQTCHV03","pasaporte_anterior":"M999000111"}', 3, '2026-01-22 09:40:00'),
('VISA-2026-000012', 1, 1, 'Visa', 'Primera Vez', 'Información incompleta', '{"nombre":"Irene Cabrera Muñoz","pasaporte":"M012347986","fecha_nacimiento":"1992-11-17","motivo":"Negocios"}', 3, '2026-01-24 16:00:00'),
('VISA-2026-000013', 1, 1, 'Visa', 'Primera Vez', 'Creado', '{"nombre":"Mario Delgado Rosas","pasaporte":"M123457097","fecha_nacimiento":"1988-06-29","motivo":"Turismo"}', 4, '2026-01-26 10:30:00'),
('VISA-2026-000014', 2, 1, 'Pasaporte', 'Renovación', 'Creado', '{"nombre":"Rocío Aguirre León","curp":"AULR890130MQTGNC07","pasaporte_anterior":"M000111222"}', 3, '2026-01-28 13:15:00'),
('VISA-2026-000015', 1, 1, 'Visa', 'Primera Vez', 'Creado', '{"nombre":"Eduardo Luna Benítez","pasaporte":"M234568108","fecha_nacimiento":"1991-04-20","motivo":"Negocios"}', 4, '2026-01-30 11:50:00');

-- Tabla de Historial de Estatus
DROP TABLE IF EXISTS `status_history`;
CREATE TABLE `status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `comment` text,
  `changed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `status_history_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de ejemplo con muchos datos distribuidos en el tiempo
-- Incluimos historial para solicitudes finalizadas y en proceso

-- Historial para solicitudes finalizadas (ID 1-50, 51-52)
INSERT INTO `status_history` (`application_id`, `previous_status`, `new_status`, `comment`, `changed_by`) VALUES
-- Agosto 2025
(1, NULL, 'Creado', 'Solicitud registrada', 3),
(1, 'Creado', 'En revisión', 'Documentos recibidos', 2),
(1, 'En revisión', 'Aprobado', 'Visa aprobada', 2),
(1, 'Aprobado', 'Finalizado', 'Entregada al cliente', 2),
(2, NULL, 'Creado', 'Solicitud registrada', 3),
(2, 'Creado', 'Documentación validada', 'Documentos completos', 2),
(2, 'Documentación validada', 'Aprobado', 'Visa aprobada', 2),
(2, 'Aprobado', 'Finalizado', 'Entregada al cliente', 2),
(3, NULL, 'Creado', 'Solicitud registrada', 4),
(3, 'Creado', 'En proceso', 'Trámite en SRE', 2),
(3, 'En proceso', 'Aprobado', 'Pasaporte listo', 2),
(3, 'Aprobado', 'Finalizado', 'Entregado al cliente', 2),
(4, NULL, 'Creado', 'Solicitud registrada', 3),
(4, 'Creado', 'En revisión', 'Revisando documentación', 2),
(4, 'En revisión', 'Aprobado', 'Aprobada', 2),
(4, 'Aprobado', 'Finalizado', 'Entregada', 2),
(5, NULL, 'Creado', 'Solicitud registrada', 4),
(5, 'Creado', 'En proceso', 'En trámite', 2),
(5, 'En proceso', 'Finalizado', 'Completado', 2),
(6, NULL, 'Creado', 'Solicitud registrada', 3),
(6, 'Creado', 'Aprobado', 'Aprobada', 2),
(6, 'Aprobado', 'Finalizado', 'Entregada', 2),

-- Historial para solicitudes activas de Enero 2026
(53, NULL, 'Creado', 'Solicitud registrada', 3),
(53, 'Creado', 'En revisión', 'En proceso de validación', 2),
(53, 'En revisión', 'Aprobado', 'Visa aprobada', 2),
(53, 'Aprobado', 'Finalizado', 'Entregada al cliente', 2),
(54, NULL, 'Creado', 'Solicitud registrada', 4),
(54, 'Creado', 'En proceso', 'Trámite en curso', 2),
(54, 'En proceso', 'Aprobado', 'Aprobada', 2),
(54, 'Aprobado', 'Finalizado', 'Completada', 2),
(55, NULL, 'Creado', 'Solicitud registrada', 3),
(55, 'Creado', 'En revisión', 'Revisando documentos', 2),
(55, 'En revisión', 'Aprobado', 'Aprobada', 2),
(56, NULL, 'Creado', 'Solicitud registrada', 3),
(56, 'Creado', 'En revisión', 'Validando información', 2),
(56, 'En revisión', 'Aprobado', 'Aprobada', 2),
(57, NULL, 'Creado', 'Solicitud registrada', 4),
(57, 'Creado', 'En revisión', 'Documentos bajo revisión', 2),
(57, 'En revisión', 'En proceso', 'En trámite', 2),
(58, NULL, 'Creado', 'Solicitud registrada', 3),
(58, 'Creado', 'En revisión', 'Revisando', 2),
(58, 'En revisión', 'En proceso', 'Procesando', 2),
(59, NULL, 'Creado', 'Solicitud registrada', 4),
(59, 'Creado', 'En revisión', 'En validación', 2),
(59, 'En revisión', 'Documentación validada', 'Documentos correctos', 2),
(60, NULL, 'Creado', 'Solicitud registrada', 3),
(60, 'Creado', 'En revisión', 'Revisión inicial', 2),
(60, 'En revisión', 'Documentación validada', 'Validada', 2),
(61, NULL, 'Creado', 'Solicitud registrada', 3),
(61, 'Creado', 'En revisión', 'Revisando', 2),
(62, NULL, 'Creado', 'Solicitud registrada', 4),
(62, 'Creado', 'En revisión', 'En proceso inicial', 2),
(63, NULL, 'Creado', 'Solicitud registrada', 3),
(63, 'Creado', 'En revisión', 'Validación de documentos', 2),
(64, NULL, 'Creado', 'Solicitud registrada', 3),
(64, 'Creado', 'Información incompleta', 'Faltan documentos', 2),
(65, NULL, 'Creado', 'Solicitud registrada', 4),
(66, NULL, 'Creado', 'Solicitud registrada', 3),
(67, NULL, 'Creado', 'Solicitud registrada', 4);

-- Tabla de Documentos
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL,
  `version` int(11) DEFAULT 1,
  `is_validated` tinyint(1) DEFAULT 0,
  `validation_comment` text,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Costos Financieros
DROP TABLE IF EXISTS `financial_costs`;
CREATE TABLE `financial_costs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `concept` varchar(200) NOT NULL COMMENT 'Honorarios, Derechos, Servicios adicionales',
  `amount` decimal(10,2) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `financial_costs_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `financial_costs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Costos de ejemplo para todas las solicitudes generadas
-- Costos para Visas: Honorarios (2500) + Derechos consulares (3500) = 6000
-- Costos para Pasaportes: Honorarios (1800) + Derechos SRE (1345) = 3145

INSERT INTO `financial_costs` (`application_id`, `concept`, `amount`, `created_by`) VALUES
-- Agosto 2025 (IDs 1-6)
(1, 'Honorarios de gestión', 2500.00, 2),
(1, 'Derechos consulares', 3500.00, 2),
(2, 'Honorarios de gestión', 2500.00, 2),
(2, 'Derechos consulares', 3500.00, 2),
(3, 'Honorarios de renovación', 1800.00, 2),
(3, 'Derechos SRE', 1345.00, 2),
(4, 'Honorarios de gestión', 2500.00, 2),
(4, 'Derechos consulares', 3500.00, 2),
(5, 'Honorarios de renovación', 1800.00, 2),
(5, 'Derechos SRE', 1345.00, 2),
(6, 'Honorarios de gestión', 2500.00, 2),
(6, 'Derechos consulares', 3500.00, 2),

-- Septiembre 2025 (IDs 7-14)
(7, 'Honorarios de gestión', 2500.00, 2),
(7, 'Derechos consulares', 3500.00, 2),
(8, 'Honorarios de renovación', 1800.00, 2),
(8, 'Derechos SRE', 1345.00, 2),
(9, 'Honorarios de gestión', 2500.00, 2),
(9, 'Derechos consulares', 3500.00, 2),
(10, 'Honorarios de renovación', 1800.00, 2),
(10, 'Derechos SRE', 1345.00, 2),
(11, 'Honorarios de gestión', 2500.00, 2),
(11, 'Derechos consulares', 3500.00, 2),
(12, 'Honorarios de gestión', 2500.00, 2),
(12, 'Derechos consulares', 3500.00, 2),
(13, 'Honorarios de renovación', 1800.00, 2),
(13, 'Derechos SRE', 1345.00, 2),
(14, 'Honorarios de gestión', 2500.00, 2),
(14, 'Derechos consulares', 3500.00, 2),

-- Octubre 2025 (IDs 15-24)
(15, 'Honorarios de gestión', 2500.00, 2),
(15, 'Derechos consulares', 3500.00, 2),
(16, 'Honorarios de renovación', 1800.00, 2),
(16, 'Derechos SRE', 1345.00, 2),
(17, 'Honorarios de gestión', 2500.00, 2),
(17, 'Derechos consulares', 3500.00, 2),
(18, 'Honorarios de gestión', 2500.00, 2),
(18, 'Derechos consulares', 3500.00, 2),
(19, 'Honorarios de renovación', 1800.00, 2),
(19, 'Derechos SRE', 1345.00, 2),
(20, 'Honorarios de gestión', 2500.00, 2),
(20, 'Derechos consulares', 3500.00, 2),
(21, 'Honorarios de gestión', 2500.00, 2),
(21, 'Derechos consulares', 3500.00, 2),
(22, 'Honorarios de renovación', 1800.00, 2),
(22, 'Derechos SRE', 1345.00, 2),
(23, 'Honorarios de gestión', 2500.00, 2),
(23, 'Derechos consulares', 3500.00, 2),
(24, 'Honorarios de gestión', 2500.00, 2),
(24, 'Derechos consulares', 3500.00, 2),

-- Noviembre 2025 (IDs 25-36)
(25, 'Honorarios de renovación', 1800.00, 2),
(25, 'Derechos SRE', 1345.00, 2),
(26, 'Honorarios de gestión', 2500.00, 2),
(26, 'Derechos consulares', 3500.00, 2),
(27, 'Honorarios de gestión', 2500.00, 2),
(27, 'Derechos consulares', 3500.00, 2),
(28, 'Honorarios de renovación', 1800.00, 2),
(28, 'Derechos SRE', 1345.00, 2),
(29, 'Honorarios de gestión', 2500.00, 2),
(29, 'Derechos consulares', 3500.00, 2),
(30, 'Honorarios de gestión', 2500.00, 2),
(30, 'Derechos consulares', 3500.00, 2),
(31, 'Honorarios de renovación', 1800.00, 2),
(31, 'Derechos SRE', 1345.00, 2),
(32, 'Honorarios de gestión', 2500.00, 2),
(32, 'Derechos consulares', 3500.00, 2),
(33, 'Honorarios de gestión', 2500.00, 2),
(33, 'Derechos consulares', 3500.00, 2),
(34, 'Honorarios de gestión', 2500.00, 2),
(34, 'Derechos consulares', 3500.00, 2),
(35, 'Honorarios de renovación', 1800.00, 2),
(35, 'Derechos SRE', 1345.00, 2),
(36, 'Honorarios de gestión', 2500.00, 2),
(36, 'Derechos consulares', 3500.00, 2),

-- Diciembre 2025 (IDs 37-50)
(37, 'Honorarios de gestión', 2500.00, 2),
(37, 'Derechos consulares', 3500.00, 2),
(38, 'Honorarios de renovación', 1800.00, 2),
(38, 'Derechos SRE', 1345.00, 2),
(39, 'Honorarios de gestión', 2500.00, 2),
(39, 'Derechos consulares', 3500.00, 2),
(40, 'Honorarios de gestión', 2500.00, 2),
(40, 'Derechos consulares', 3500.00, 2),
(41, 'Honorarios de renovación', 1800.00, 2),
(41, 'Derechos SRE', 1345.00, 2),
(42, 'Honorarios de gestión', 2500.00, 2),
(42, 'Derechos consulares', 3500.00, 2),
(43, 'Honorarios de gestión', 2500.00, 2),
(43, 'Derechos consulares', 3500.00, 2),
(44, 'Honorarios de renovación', 1800.00, 2),
(44, 'Derechos SRE', 1345.00, 2),
(45, 'Honorarios de gestión', 2500.00, 2),
(45, 'Derechos consulares', 3500.00, 2),
(46, 'Honorarios de gestión', 2500.00, 2),
(46, 'Derechos consulares', 3500.00, 2),
(47, 'Honorarios de renovación', 1800.00, 2),
(47, 'Derechos SRE', 1345.00, 2),
(48, 'Honorarios de gestión', 2500.00, 2),
(48, 'Derechos consulares', 3500.00, 2),
(49, 'Honorarios de gestión', 2500.00, 2),
(49, 'Derechos consulares', 3500.00, 2),
(50, 'Honorarios de gestión', 2500.00, 2),
(50, 'Derechos consulares', 3500.00, 2),

-- Enero 2026 (IDs 51-65)
(51, 'Honorarios de gestión', 2500.00, 2),
(51, 'Derechos consulares', 3500.00, 2),
(52, 'Honorarios de renovación', 1800.00, 2),
(52, 'Derechos SRE', 1345.00, 2),
(53, 'Honorarios de gestión', 2500.00, 2),
(53, 'Derechos consulares', 3500.00, 2),
(54, 'Honorarios de gestión', 2500.00, 2),
(54, 'Derechos consulares', 3500.00, 2),
(55, 'Honorarios de renovación', 1800.00, 2),
(55, 'Derechos SRE', 1345.00, 2),
(56, 'Honorarios de gestión', 2500.00, 2),
(56, 'Derechos consulares', 3500.00, 2),
(57, 'Honorarios de gestión', 2500.00, 2),
(57, 'Derechos consulares', 3500.00, 2),
(58, 'Honorarios de renovación', 1800.00, 2),
(58, 'Derechos SRE', 1345.00, 2),
(59, 'Honorarios de gestión', 2500.00, 2),
(59, 'Derechos consulares', 3500.00, 2),
(60, 'Honorarios de gestión', 2500.00, 2),
(60, 'Derechos consulares', 3500.00, 2),
(61, 'Honorarios de renovación', 1800.00, 2),
(61, 'Derechos SRE', 1345.00, 2),
(62, 'Honorarios de gestión', 2500.00, 2),
(62, 'Derechos consulares', 3500.00, 2),
(63, 'Honorarios de gestión', 2500.00, 2),
(63, 'Derechos consulares', 3500.00, 2),
(64, 'Honorarios de renovación', 1800.00, 2),
(64, 'Derechos SRE', 1345.00, 2),
(65, 'Honorarios de gestión', 2500.00, 2),
(65, 'Derechos consulares', 3500.00, 2);

-- Tabla de Pagos
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL COMMENT 'Efectivo, Transferencia, Tarjeta, PayPal',
  `reference` varchar(100) DEFAULT NULL,
  `notes` text,
  `registered_by` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `registered_by` (`registered_by`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagos de ejemplo con diferentes métodos distribuidos en el tiempo
-- Incluye pagos completos y parciales con variedad de métodos

INSERT INTO `payments` (`application_id`, `amount`, `payment_method`, `reference`, `registered_by`, `payment_date`) VALUES
-- Agosto 2025
(1, 6000.00, 'Efectivo', NULL, 2, '2025-08-06'),
(2, 3000.00, 'Transferencia', 'TRANS-20250808-001', 2, '2025-08-08'),
(2, 3000.00, 'Tarjeta', 'CARD-****2345', 2, '2025-08-15'),
(3, 3145.00, 'Tarjeta', 'CARD-****3456', 2, '2025-08-13'),
(4, 6000.00, 'PayPal', 'PP-20250818-123', 2, '2025-08-19'),
(5, 3145.00, 'Efectivo', NULL, 2, '2025-08-23'),
(6, 4000.00, 'Transferencia', 'TRANS-20250829-002', 2, '2025-08-29'),
(6, 2000.00, 'Efectivo', NULL, 2, '2025-08-30'),

-- Septiembre 2025
(7, 6000.00, 'Tarjeta', 'CARD-****4567', 2, '2025-09-04'),
(8, 1500.00, 'Efectivo', NULL, 2, '2025-09-06'),
(8, 1645.00, 'Transferencia', 'TRANS-20250906-003', 2, '2025-09-06'),
(9, 6000.00, 'PayPal', 'PP-20250910-456', 2, '2025-09-11'),
(10, 3145.00, 'Tarjeta', 'CARD-****5678', 2, '2025-09-13'),
(11, 3000.00, 'Transferencia', 'TRANS-20250918-004', 2, '2025-09-18'),
(11, 3000.00, 'Efectivo', NULL, 2, '2025-09-25'),
(12, 6000.00, 'Efectivo', NULL, 2, '2025-09-21'),
(13, 3145.00, 'Transferencia', 'TRANS-20250925-005', 2, '2025-09-25'),
(14, 6000.00, 'Tarjeta', 'CARD-****6789', 2, '2025-09-29'),

-- Octubre 2025
(15, 6000.00, 'PayPal', 'PP-20251003-789', 2, '2025-10-03'),
(16, 3145.00, 'Efectivo', NULL, 2, '2025-10-06'),
(17, 2500.00, 'Transferencia', 'TRANS-20251009-006', 2, '2025-10-09'),
(17, 3500.00, 'Tarjeta', 'CARD-****7890', 2, '2025-10-16'),
(18, 6000.00, 'Efectivo', NULL, 2, '2025-10-12'),
(19, 3145.00, 'Transferencia', 'TRANS-20251015-007', 2, '2025-10-15'),
(20, 6000.00, 'Tarjeta', 'CARD-****8901', 2, '2025-10-18'),
(21, 6000.00, 'PayPal', 'PP-20251021-012', 2, '2025-10-21'),
(22, 1800.00, 'Efectivo', NULL, 2, '2025-10-24'),
(22, 1345.00, 'Efectivo', NULL, 2, '2025-10-24'),
(23, 6000.00, 'Transferencia', 'TRANS-20251027-008', 2, '2025-10-27'),
(24, 6000.00, 'Tarjeta', 'CARD-****9012', 2, '2025-10-31'),

-- Noviembre 2025
(25, 3145.00, 'Efectivo', NULL, 2, '2025-11-03'),
(26, 4000.00, 'Transferencia', 'TRANS-20251106-009', 2, '2025-11-06'),
(26, 2000.00, 'Tarjeta', 'CARD-****0123', 2, '2025-11-13'),
(27, 6000.00, 'PayPal', 'PP-20251109-345', 2, '2025-11-09'),
(28, 3145.00, 'Efectivo', NULL, 2, '2025-11-12'),
(29, 6000.00, 'Tarjeta', 'CARD-****1234', 2, '2025-11-15'),
(30, 6000.00, 'Transferencia', 'TRANS-20251118-010', 2, '2025-11-18'),
(31, 3145.00, 'Efectivo', NULL, 2, '2025-11-21'),
(32, 3500.00, 'Transferencia', 'TRANS-20251123-011', 2, '2025-11-23'),
(32, 2500.00, 'Efectivo', NULL, 2, '2025-11-30'),
(33, 6000.00, 'Tarjeta', 'CARD-****2345', 2, '2025-11-25'),
(34, 6000.00, 'PayPal', 'PP-20251127-678', 2, '2025-11-27'),
(35, 3145.00, 'Efectivo', NULL, 2, '2025-11-29'),
(36, 6000.00, 'Transferencia', 'TRANS-20251201-012', 2, '2025-12-01'),

-- Diciembre 2025
(37, 6000.00, 'Tarjeta', 'CARD-****3456', 2, '2025-12-03'),
(38, 3145.00, 'Efectivo', NULL, 2, '2025-12-05'),
(39, 6000.00, 'Transferencia', 'TRANS-20251207-013', 2, '2025-12-07'),
(40, 6000.00, 'PayPal', 'PP-20251210-901', 2, '2025-12-10'),
(41, 1800.00, 'Efectivo', NULL, 2, '2025-12-12'),
(41, 1345.00, 'Transferencia', 'TRANS-20251212-014', 2, '2025-12-12'),
(42, 6000.00, 'Tarjeta', 'CARD-****4567', 2, '2025-12-14'),
(43, 6000.00, 'Efectivo', NULL, 2, '2025-12-16'),
(44, 3145.00, 'Transferencia', 'TRANS-20251218-015', 2, '2025-12-18'),
(45, 6000.00, 'Tarjeta', 'CARD-****5678', 2, '2025-12-20'),
(46, 3000.00, 'PayPal', 'PP-20251222-234', 2, '2025-12-22'),
(46, 3000.00, 'Efectivo', NULL, 2, '2025-12-28'),
(47, 3145.00, 'Transferencia', 'TRANS-20251224-016', 2, '2025-12-24'),
(48, 6000.00, 'Tarjeta', 'CARD-****6789', 2, '2025-12-27'),
(49, 6000.00, 'Efectivo', NULL, 2, '2025-12-29'),
(50, 6000.00, 'Transferencia', 'TRANS-20251231-017', 2, '2025-12-31'),

-- Enero 2026
(51, 6000.00, 'Tarjeta', 'CARD-****7890', 2, '2026-01-03'),
(52, 3145.00, 'Efectivo', NULL, 2, '2026-01-05'),
(53, 4000.00, 'Transferencia', 'TRANS-20260107-018', 2, '2026-01-07'),
(53, 2000.00, 'PayPal', 'PP-20260114-567', 2, '2026-01-14'),
(54, 6000.00, 'Tarjeta', 'CARD-****8901', 2, '2026-01-09'),
(55, 3145.00, 'Efectivo', NULL, 2, '2026-01-11'),
(56, 6000.00, 'Transferencia', 'TRANS-20260113-019', 2, '2026-01-13'),
(57, 3000.00, 'Tarjeta', 'CARD-****9012', 2, '2026-01-15'),
(58, 1800.00, 'Efectivo', NULL, 2, '2026-01-17'),
(59, 6000.00, 'Transferencia', 'TRANS-20260119-020', 2, '2026-01-19'),
(60, 3500.00, 'PayPal', 'PP-20260121-890', 2, '2026-01-21'),
(61, 1500.00, 'Tarjeta', 'CARD-****0123', 2, '2026-01-23'),
(62, 2000.00, 'Efectivo', NULL, 2, '2026-01-25'),
(63, 3000.00, 'Transferencia', 'TRANS-20260127-021', 2, '2026-01-27');

-- Tabla de Estado Financiero por Solicitud
DROP TABLE IF EXISTS `financial_status`;
CREATE TABLE `financial_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `total_costs` decimal(10,2) DEFAULT 0.00,
  `total_paid` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `status` enum('Pendiente','Parcial','Pagado') DEFAULT 'Pendiente',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_id`),
  CONSTRAINT `financial_status_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estado financiero de ejemplo para todas las solicitudes
-- Pagado: solicitudes finalizadas del pasado
-- Parcial/Pendiente: solicitudes activas de enero 2026

INSERT INTO `financial_status` (`application_id`, `total_costs`, `total_paid`, `balance`, `status`) VALUES
-- Agosto 2025 - Todas pagadas
(1, 6000.00, 6000.00, 0.00, 'Pagado'),
(2, 6000.00, 6000.00, 0.00, 'Pagado'),
(3, 3145.00, 3145.00, 0.00, 'Pagado'),
(4, 6000.00, 6000.00, 0.00, 'Pagado'),
(5, 3145.00, 3145.00, 0.00, 'Pagado'),
(6, 6000.00, 6000.00, 0.00, 'Pagado'),

-- Septiembre 2025 - Todas pagadas
(7, 6000.00, 6000.00, 0.00, 'Pagado'),
(8, 3145.00, 3145.00, 0.00, 'Pagado'),
(9, 6000.00, 6000.00, 0.00, 'Pagado'),
(10, 3145.00, 3145.00, 0.00, 'Pagado'),
(11, 6000.00, 6000.00, 0.00, 'Pagado'),
(12, 6000.00, 6000.00, 0.00, 'Pagado'),
(13, 3145.00, 3145.00, 0.00, 'Pagado'),
(14, 6000.00, 6000.00, 0.00, 'Pagado'),

-- Octubre 2025 - Todas pagadas
(15, 6000.00, 6000.00, 0.00, 'Pagado'),
(16, 3145.00, 3145.00, 0.00, 'Pagado'),
(17, 6000.00, 6000.00, 0.00, 'Pagado'),
(18, 6000.00, 6000.00, 0.00, 'Pagado'),
(19, 3145.00, 3145.00, 0.00, 'Pagado'),
(20, 6000.00, 6000.00, 0.00, 'Pagado'),
(21, 6000.00, 6000.00, 0.00, 'Pagado'),
(22, 3145.00, 3145.00, 0.00, 'Pagado'),
(23, 6000.00, 6000.00, 0.00, 'Pagado'),
(24, 6000.00, 6000.00, 0.00, 'Pagado'),

-- Noviembre 2025 - Todas pagadas
(25, 3145.00, 3145.00, 0.00, 'Pagado'),
(26, 6000.00, 6000.00, 0.00, 'Pagado'),
(27, 6000.00, 6000.00, 0.00, 'Pagado'),
(28, 3145.00, 3145.00, 0.00, 'Pagado'),
(29, 6000.00, 6000.00, 0.00, 'Pagado'),
(30, 6000.00, 6000.00, 0.00, 'Pagado'),
(31, 3145.00, 3145.00, 0.00, 'Pagado'),
(32, 6000.00, 6000.00, 0.00, 'Pagado'),
(33, 6000.00, 6000.00, 0.00, 'Pagado'),
(34, 6000.00, 6000.00, 0.00, 'Pagado'),
(35, 3145.00, 3145.00, 0.00, 'Pagado'),
(36, 6000.00, 6000.00, 0.00, 'Pagado'),

-- Diciembre 2025 - Todas pagadas
(37, 6000.00, 6000.00, 0.00, 'Pagado'),
(38, 3145.00, 3145.00, 0.00, 'Pagado'),
(39, 6000.00, 6000.00, 0.00, 'Pagado'),
(40, 6000.00, 6000.00, 0.00, 'Pagado'),
(41, 3145.00, 3145.00, 0.00, 'Pagado'),
(42, 6000.00, 6000.00, 0.00, 'Pagado'),
(43, 6000.00, 6000.00, 0.00, 'Pagado'),
(44, 3145.00, 3145.00, 0.00, 'Pagado'),
(45, 6000.00, 6000.00, 0.00, 'Pagado'),
(46, 6000.00, 6000.00, 0.00, 'Pagado'),
(47, 3145.00, 3145.00, 0.00, 'Pagado'),
(48, 6000.00, 6000.00, 0.00, 'Pagado'),
(49, 6000.00, 6000.00, 0.00, 'Pagado'),
(50, 6000.00, 6000.00, 0.00, 'Pagado'),

-- Enero 2026 - Con variedad de estados
(51, 6000.00, 6000.00, 0.00, 'Pagado'),
(52, 3145.00, 3145.00, 0.00, 'Pagado'),
(53, 6000.00, 6000.00, 0.00, 'Pagado'),
(54, 6000.00, 6000.00, 0.00, 'Pagado'),
(55, 3145.00, 3145.00, 0.00, 'Pagado'),
(56, 6000.00, 6000.00, 0.00, 'Pagado'),
(57, 6000.00, 3000.00, 3000.00, 'Parcial'),
(58, 3145.00, 1800.00, 1345.00, 'Parcial'),
(59, 6000.00, 6000.00, 0.00, 'Pagado'),
(60, 6000.00, 3500.00, 2500.00, 'Parcial'),
(61, 3145.00, 1500.00, 1645.00, 'Parcial'),
(62, 6000.00, 2000.00, 4000.00, 'Parcial'),
(63, 6000.00, 3000.00, 3000.00, 'Parcial'),
(64, 3145.00, 0.00, 3145.00, 'Pendiente'),
(65, 6000.00, 0.00, 6000.00, 'Pendiente');

-- Tabla de Configuración Global
DROP TABLE IF EXISTS `global_config`;
CREATE TABLE `global_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text,
  `config_type` varchar(50) DEFAULT 'text' COMMENT 'text, json, file',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuraciones por defecto
INSERT INTO `global_config` (`config_key`, `config_value`, `config_type`) VALUES
('site_name', 'CRM Visas y Pasaportes Querétaro', 'text'),
('site_logo', '', 'file'),
('email_from', 'noreply@crmvisas.com', 'text'),
('contact_phone', '442-123-4567', 'text'),
('contact_phone_2', '442-765-4321', 'text'),
('business_hours', 'Lunes a Viernes: 9:00 AM - 6:00 PM, Sábados: 9:00 AM - 2:00 PM', 'text'),
('primary_color', '#3b82f6', 'text'),
('secondary_color', '#1e40af', 'text'),
('paypal_client_id', '', 'text'),
('paypal_secret', '', 'text'),
('qr_api_key', '', 'text'),
('qr_api_url', '', 'text');

-- Tabla de Dispositivos HikVision
DROP TABLE IF EXISTS `hikvision_devices`;
CREATE TABLE `hikvision_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `port` int(11) DEFAULT 80,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Dispositivos Shelly Cloud
DROP TABLE IF EXISTS `shelly_devices`;
CREATE TABLE `shelly_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `device_id` varchar(100) NOT NULL,
  `auth_key` varchar(255) NOT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Auditoría del Sistema
DROP TABLE IF EXISTS `audit_trail`;
CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL COMMENT 'login, logout, create, update, delete, etc',
  `module` varchar(100) NOT NULL COMMENT 'usuarios, solicitudes, formularios, etc',
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `module` (`module`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Crear índices adicionales para optimización
CREATE INDEX idx_applications_created_by_status ON applications(created_by, status);
CREATE INDEX idx_applications_created_at ON applications(created_at);
CREATE INDEX idx_status_history_created_at ON status_history(created_at);
CREATE INDEX idx_payments_payment_date ON payments(payment_date);
