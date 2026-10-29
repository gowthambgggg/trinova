-- ============================================================================
-- TriNova - OPD Triage Assistant (Database Schema)
-- Target Server: MySQL / MariaDB (XAMPP default)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `trinova_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `trinova_db`;

-- 1. Table for Patient Assessments
CREATE TABLE IF NOT EXISTS `patient_assessments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` VARCHAR(32) NOT NULL,
    `patient_name` VARCHAR(100) NOT NULL,
    `age` INT NOT NULL,
    `chief_complaint` TEXT NOT NULL,
    `symptom_duration` VARCHAR(50) NOT NULL,
    `pain_location` VARCHAR(100) DEFAULT NULL,
    `pain_severity` INT DEFAULT 0,
    `bp_systolic` INT NOT NULL,
    `bp_diastolic` INT NOT NULL,
    `pulse_rate` INT NOT NULL,
    `spo2` INT NOT NULL,
    `temperature` DECIMAL(4,1) NOT NULL,
    `medical_history` TEXT DEFAULT NULL,
    `allergies` TEXT DEFAULT NULL,
    `is_emergency` TINYINT(1) DEFAULT 0,
    `red_flags_detected` TEXT DEFAULT NULL,
    `priority_level` INT NOT NULL COMMENT '1: Critical, 2: Very Urgent, 3: Urgent, 4: Less Urgent, 5: Routine',
    `priority_label` VARCHAR(50) NOT NULL,
    `primary_department` VARCHAR(100) NOT NULL,
    `alternative_departments` TEXT DEFAULT NULL,
    `clinical_impression` TEXT DEFAULT NULL,
    `rule_audit_trail` TEXT DEFAULT NULL,
    `missing_or_uncertain` TEXT DEFAULT NULL,
    `nurse_recommendation` TEXT NOT NULL,
    `is_acknowledged` TINYINT(1) DEFAULT 0,
    `acknowledged_at` DATETIME DEFAULT NULL,
    `acknowledged_by` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_priority_level` (`priority_level`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table for Nurse Acknowledgment Logs
CREATE TABLE IF NOT EXISTS `nurse_acknowledgments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `assessment_id` INT NOT NULL,
    `patient_id` VARCHAR(32) NOT NULL,
    `action_note` VARCHAR(255) NOT NULL,
    `acknowledged_by` VARCHAR(100) NOT NULL,
    `acknowledged_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`assessment_id`) REFERENCES `patient_assessments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
