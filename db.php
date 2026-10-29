<?php
/**
 * TriNova - OPD Triage Assistant
 * Database Connection & Auto-Migration Layer
 * 
 * Safely connects to MySQL in XAMPP.
 * Automatically checks and provisions 'trinova_db' and required tables.
 */

declare(strict_types=1);

$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'trinova_db';
$db_user = 'root';
$db_pass = '';

$pdo = null;
$db_connected = false;
$db_error = null;

try {
    // 1. Initial connection without database to ensure DB exists
    $dsn_server = "mysql:host={$db_host};port={$db_port};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 3,
    ];

    $server_pdo = new PDO($dsn_server, $db_user, $db_pass, $options);
    $server_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 2. Connect to the specific database
    $dsn_db = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn_db, $db_user, $db_pass, $options);

    // 3. Ensure required tables exist
    $pdo->exec("
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
            `priority_level` INT NOT NULL,
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
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `nurse_acknowledgments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `assessment_id` INT NOT NULL,
            `patient_id` VARCHAR(32) NOT NULL,
            `action_note` VARCHAR(255) NOT NULL,
            `acknowledged_by` VARCHAR(100) NOT NULL,
            `acknowledged_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $db_connected = true;

} catch (Throwable $e) {
    $db_connected = false;
    $db_error = $e->getMessage();
    // Do not terminate execution; allow frontend to operate gracefully even in offline/demo mode
}
