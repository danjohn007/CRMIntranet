-- Migration to update application status values
-- Date: 2026-02-11
-- Description: Update status enum in applications table to new status values

USE `crm_visas`;

-- Step 1: Modify the applications table to change the status enum type
ALTER TABLE `applications` 
MODIFY COLUMN `status` ENUM(
    'Formulario recibido',
    'Pago verificado',
    'En elaboración de hoja de información',
    'En revisión',
    'Rechazado (requiere corrección)',
    'Aprobado',
    'Cita solicitada',
    'Cita confirmada',
    'Proceso en embajada',
    'Finalizado'
) DEFAULT 'Formulario recibido';

-- Step 2: Update existing records to map old statuses to new ones
-- Mapping:
-- 'Creado' -> 'Formulario recibido'
-- 'Recepción de información y pago' -> 'Pago verificado'
-- 'En revisión' -> 'En revisión' (unchanged)
-- 'Información incompleta' -> 'Rechazado (requiere corrección)'
-- 'Documentación validada' -> 'En revisión'
-- 'En proceso' -> 'Proceso en embajada'
-- 'Aprobado' -> 'Aprobado' (unchanged)
-- 'Rechazado' -> 'Rechazado (requiere corrección)'
-- 'Finalizado (Trámite Entregado)' -> 'Finalizado'

-- Note: Due to ENUM limitations, we need to do this in steps
-- First, add new enum values to allow coexistence

ALTER TABLE `applications` 
MODIFY COLUMN `status` ENUM(
    'Creado',
    'Recepción de información y pago',
    'En revisión',
    'Información incompleta',
    'Documentación validada',
    'En proceso',
    'Aprobado',
    'Rechazado',
    'Finalizado (Trámite Entregado)',
    'Formulario recibido',
    'Pago verificado',
    'En elaboración de hoja de información',
    'Rechazado (requiere corrección)',
    'Cita solicitada',
    'Cita confirmada',
    'Proceso en embajada',
    'Finalizado'
) DEFAULT 'Creado';

-- Update records to new status values
UPDATE `applications` SET `status` = 'Formulario recibido' WHERE `status` = 'Creado';
UPDATE `applications` SET `status` = 'Pago verificado' WHERE `status` = 'Recepción de información y pago';
UPDATE `applications` SET `status` = 'Rechazado (requiere corrección)' WHERE `status` = 'Información incompleta';
UPDATE `applications` SET `status` = 'En revisión' WHERE `status` = 'Documentación validada';
UPDATE `applications` SET `status` = 'Proceso en embajada' WHERE `status` = 'En proceso';
UPDATE `applications` SET `status` = 'Rechazado (requiere corrección)' WHERE `status` = 'Rechazado';
UPDATE `applications` SET `status` = 'Finalizado' WHERE `status` = 'Finalizado (Trámite Entregado)';

-- Now remove old enum values, keeping only new ones
ALTER TABLE `applications` 
MODIFY COLUMN `status` ENUM(
    'Formulario recibido',
    'Pago verificado',
    'En elaboración de hoja de información',
    'En revisión',
    'Rechazado (requiere corrección)',
    'Aprobado',
    'Cita solicitada',
    'Cita confirmada',
    'Proceso en embajada',
    'Finalizado'
) DEFAULT 'Formulario recibido';
