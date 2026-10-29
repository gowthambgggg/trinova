<?php
/**
 * TriNova - OPD Triage Assistant
 * AI-Assisted Patient Prioritization (v0.3 Professional Safety Engine)
 * 
 * Safety Framework:
 *  - Three-Way Safety Determination: PRIORITIZE | FLAG | ABSTAIN
 *  - High Emergent-Case Recall Architecture
 *  - Final Clinical Authority: Qualified clinicians make final care decisions.
 * 
 * Priority Scale (Level 1 Lowest -> Level 5 Highest):
 *  - Level 5: Highest Priority (Emergency / Red Alert / Rank #1)
 *  - Level 4: High Priority (Very Urgent / Time-Critical)
 *  - Level 3: Prompt Attention (Urgent / Prompt Specialty)
 *  - Level 2: Routine Priority Review (Standard Outpatient Queue)
 *  - Level 1: Lowest Priority (Minimal / Non-Urgent)
 */

require_once __DIR__ . '/../db.php';

$app_name = "TriNova";
$app_tagline = "OPD Triage Assistant — AI-Assisted Patient Prioritization";
$initial_patient_id = "TRN-" . date('Y') . "-" . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_name . " — " . $app_tagline); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="app-body">

    <!-- Top Hospital Command Header -->
    <header class="app-header">
        <div class="header-inner">
            <div class="brand-container">
                <div class="brand-symbol">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                    </svg>
                </div>
                <div class="brand-details">
                    <div class="brand-title-row">
                        <h1 class="brand-title">TriNova</h1>
                        <span class="badge-edition">OPD Clinical Command</span>
                        <span class="badge-status-pill">
                            <span class="pulse-indicator"></span>
                            <?php echo $db_connected ? 'MySQL Active' : 'Offline Mode'; ?>
                        </span>
                    </div>
                    <div class="brand-subtitle"><?php echo htmlspecialchars($app_tagline); ?></div>
                </div>
            </div>

            <div class="header-aux">
                <div class="facility-meta">
                    <span class="meta-label">Facility</span>
                    <span class="meta-val">General Hospital OPD Triage</span>
                </div>
                <div class="facility-meta">
                    <span class="meta-label">Protocol</span>
                    <span class="meta-val">Level 5 (Lowest) &rarr; Level 1 (Highest)</span>
                </div>
                <a href="#queueSection" class="btn-history-jump">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"/>
                        <line x1="8" y1="12" x2="21" y2="12"/>
                        <line x1="8" y1="18" x2="21" y2="18"/>
                        <line x1="3" y1="6" x2="3.01" y2="6"/>
                        <line x1="3" y1="12" x2="3.01" y2="12"/>
                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                    Live Triage Queue
                </a>
            </div>
        </div>
    </header>

    <!-- Prominent Safety Disclaimer Banner -->
    <div class="safety-banner" role="alert">
        <div class="safety-banner-content">
            <div class="safety-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="safety-text">
                <strong>EDUCATIONAL PROTOTYPE — NOT FOR CLINICAL DIAGNOSIS OR LIVE TRIAGE.</strong> 
                TriNova enforces a high emergent-case recall protocol. 
                <strong>Qualified clinicians make final care decisions.</strong> 
                Missing an emergency red flag is a critical safety risk; safety checks prioritize, flag, or abstain.
            </div>
        </div>
    </div>

    <!-- Main Application Canvas -->
    <main class="app-canvas">

        <!-- Primary Dual-Column Triage Cockpit -->
        <div class="cockpit-grid">

            <!-- LEFT COLUMN: Patient Intake Form -->
            <section class="card intake-card">
                <div class="card-head">
                    <div class="card-head-title">
                        <div class="card-head-icon icon-teal">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="head-title-text">Patient Assessment Intake</h2>
                            <p class="head-subtitle-text">Enter patient presentation and vitals for prioritization and safety evaluation.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-id-regen" id="btnRegenId" title="Generate new Patient ID">
                        <span class="regen-icon">&#x21bb;</span> New ID
                    </button>
                </div>

                <form id="patientForm" novalidate>
                    
                    <!-- Patient Identity -->
                    <div class="form-segment">
                        <div class="segment-title">
                            <span class="segment-number">1</span>
                            Patient Identity & Demographics
                        </div>
                        
                        <div class="form-row row-3">
                            <div class="form-field">
                                <label for="patientId">Patient ID <span class="req">*</span></label>
                                <input type="text" id="patientId" value="<?php echo htmlspecialchars($initial_patient_id); ?>" readonly class="mono-input read-only-input">
                            </div>
                            <div class="form-field field-span-2">
                                <label for="patientName">Patient Name or Anonymous ID <span class="req">*</span></label>
                                <input type="text" id="patientName" placeholder="e.g., Fictional Patient Alpha" required maxlength="80">
                                <div class="err-hint" id="errName">Please enter patient name or identifier.</div>
                            </div>
                        </div>

                        <div class="form-row row-3">
                            <div class="form-field">
                                <label for="patientAge">Age <span class="req">*</span></label>
                                <div class="unit-wrapper">
                                    <input type="number" id="patientAge" placeholder="45" min="0" max="120" required class="mono-input">
                                    <span class="unit-label">yrs</span>
                                </div>
                                <div class="err-hint" id="errAge">Enter valid age (0–120).</div>
                            </div>
                            <div class="form-field field-span-2">
                                <label for="symptomDuration">Symptom Duration</label>
                                <select id="symptomDuration">
                                    <option value="Under 2 hours (Sudden Acute)">Under 2 hours (Sudden Acute Onset)</option>
                                    <option value="2 to 12 hours">2 to 12 hours</option>
                                    <option value="1 to 3 days" selected>1 to 3 days</option>
                                    <option value="1 to 4 weeks (Subacute)">1 to 4 weeks (Subacute)</option>
                                    <option value="Over 1 month (Chronic)">Over 1 month (Chronic)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Main Issue & Symptoms -->
                    <div class="form-segment">
                        <div class="segment-title">
                            <span class="segment-number">2</span>
                            Chief Complaint / Main Issue
                        </div>

                        <div class="form-field">
                            <label for="chiefComplaint">What is the main issue? (Symptoms & Clinical Complaint) <span class="req">*</span></label>
                            <textarea id="chiefComplaint" rows="3" placeholder="Enter patient's reported symptoms (e.g., 'Severe crushing chest pain radiating to left arm with cold sweat' or 'Twisted right ankle with joint swelling')" required></textarea>
                            <div class="err-hint" id="errComplaint">Chief complaint / main issue is required (minimum 3 characters).</div>
                        </div>

                        <div class="form-row row-2 mt-3">
                            <div class="form-field">
                                <label for="painLocation">Pain Location (If Applicable)</label>
                                <input type="text" id="painLocation" placeholder="e.g., Retrosternal Chest, Right Ankle, Abdomen">
                            </div>
                            <div class="form-field">
                                <div class="pain-label-row">
                                    <label for="painSeverity">Pain Severity Scale</label>
                                    <span class="pain-score-pill" id="painDisplay">0 - No Pain</span>
                                </div>
                                <input type="range" id="painSeverity" min="0" max="10" value="0" class="pain-range-slider">
                            </div>
                        </div>
                    </div>

                    <!-- Physiological Vitals -->
                    <div class="form-segment">
                        <div class="segment-title">
                            <span class="segment-number">3</span>
                            Physiological Vitals
                        </div>

                        <div class="form-row row-4">
                            <!-- Blood Pressure -->
                            <div class="form-field">
                                <label for="bpSystolic">Systolic BP <span class="req">*</span></label>
                                <div class="unit-wrapper">
                                    <input type="number" id="bpSystolic" placeholder="120" min="50" max="260" required class="mono-input">
                                    <span class="unit-label">mmHg</span>
                                </div>
                                <div class="err-hint" id="errSystolic">Range: 50–260</div>
                            </div>
                            <div class="form-field">
                                <label for="bpDiastolic">Diastolic BP <span class="req">*</span></label>
                                <div class="unit-wrapper">
                                    <input type="number" id="bpDiastolic" placeholder="80" min="30" max="160" required class="mono-input">
                                    <span class="unit-label">mmHg</span>
                                </div>
                                <div class="err-hint" id="errDiastolic">Range: 30–160</div>
                            </div>

                            <!-- Heart Rate / Pulse -->
                            <div class="form-field">
                                <label for="pulseRate">Pulse / HR <span class="req">*</span></label>
                                <div class="unit-wrapper">
                                    <input type="number" id="pulseRate" placeholder="75" min="30" max="240" required class="mono-input">
                                    <span class="unit-label">bpm</span>
                                </div>
                                <div class="err-hint" id="errPulse">Range: 30–240</div>
                            </div>

                            <!-- Oxygen Saturation -->
                            <div class="form-field">
                                <label for="spo2">Oxygen SpO2</label>
                                <div class="unit-wrapper">
                                    <input type="number" id="spo2" value="98" min="50" max="100" class="mono-input">
                                    <span class="unit-label">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-row row-2">
                            <!-- Temperature -->
                            <div class="form-field">
                                <label for="temperature">Body Temperature</label>
                                <div class="unit-wrapper">
                                    <input type="number" id="temperature" value="98.6" min="88.0" max="108.0" step="0.1" class="mono-input">
                                    <span class="unit-label">°F</span>
                                </div>
                            </div>

                            <!-- Medical History -->
                            <div class="form-field">
                                <label for="medicalHistory">Medical History / Chronic Conditions</label>
                                <input type="text" id="medicalHistory" placeholder="e.g., Hypertension, Diabetes, Asthma, None">
                            </div>
                        </div>
                    </div>

                    <!-- Form Global Error Alert Box -->
                    <div class="form-alert-container" id="globalErrorBox" style="display: none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <div id="globalErrorText">Please fill all required patient details accurately.</div>
                    </div>

                    <!-- Action Controls -->
                    <div class="form-btn-bar">
                        <button type="submit" class="btn-assess" id="btnSubmitAssess">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <circle cx="12" cy="12" r="10"/>
                                <polygon points="12 8 8 12 12 16 12 8"/>
                                <line x1="16" y1="12" x2="12" y2="12"/>
                            </svg>
                            Assess & Prioritize Patient
                        </button>
                        <button type="button" class="btn-reset" id="btnResetForm">
                            Clear / New Intake
                        </button>
                    </div>
                </form>
            </section>

            <!-- RIGHT COLUMN: Triage Decision & Assessment Result -->
            <section class="card results-cockpit" id="resultsCockpit">
                <div class="card-head">
                    <div class="card-head-title">
                        <div class="card-head-icon icon-cyan">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="head-title-text">Triage Assessment Result</h2>
                            <p class="head-subtitle-text">Safety determination, urgency ranking, and department allocation.</p>
                        </div>
                    </div>
                    <span class="badge-engine" id="badgeEngineStatus">Safety Engine: PRIORITIZE | FLAG | ABSTAIN</span>
                </div>

                <!-- Empty State -->
                <div class="cockpit-empty" id="cockpitEmpty">
                    <div class="empty-telemetry-icon">
                        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                        </svg>
                    </div>
                    <h3 class="empty-title">Awaiting Patient Assessment Intake</h3>
                    <p class="empty-desc">
                        Fill out the patient details on the left and click <strong>Assess & Prioritize Patient</strong>. The safety checks will determine whether to prioritize, flag as red alert, or abstain for human review.
                    </p>
                </div>

                <!-- Evaluated Result Content -->
                <div class="cockpit-body" id="cockpitResult" style="display: none;">

                    <!-- Three-Way Safety Determination Banner -->
                    <div class="safety-decision-bar" id="safetyDecisionBar">
                        <span class="decision-label">Safety Check Action:</span>
                        <span class="decision-pill" id="decisionPill">FLAG (EMERGENCY)</span>
                        <span class="decision-rationale" id="decisionRationale">Emergent red flag detected — high recall safety protocol triggered.</span>
                    </div>

                    <!-- 1. POTENTIAL EMERGENCY ALERT PANEL -->
                    <div class="emergency-alert-panel" id="emergencyPanel" style="display: none;">
                        <div class="emergency-panel-head">
                            <div class="emergency-badge-pulse">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polygon points="12 2 2 22 22 22 12 2"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                <span>POTENTIAL EMERGENCY — IMMEDIATE NURSE REVIEW REQUIRED</span>
                            </div>
                        </div>
                        
                        <div class="emergency-panel-body">
                            <p class="emergency-warning-directive">
                                <strong>HIGH-RECALL EMERGENCY ALERT:</strong> Missing a red flag carries catastrophic clinical risk. 
                                Alert emergency resuscitation team immediately. <em>Patient elevated to Rank #1 in Triage Queue.</em>
                            </p>

                            <div class="detected-red-flags-box">
                                <div class="flags-box-title">Detected Red-Flag Signs:</div>
                                <ul class="flags-list" id="detectedRedFlagsList">
                                    <!-- Populated dynamically -->
                                </ul>
                            </div>

                            <!-- Nurse Acknowledgment Workflow -->
                            <div class="nurse-ack-workflow" id="nurseAckWorkflow">
                                <div class="ack-status-line">
                                    <span class="ack-dot pulse-red" id="ackDot"></span>
                                    <span class="ack-text" id="ackText">
                                        Immediate review required — notify the nurse directly. (Unacknowledged)
                                    </span>
                                </div>
                                <button type="button" class="btn-acknowledge" id="btnAcknowledgeAlert">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Acknowledge Immediate Alert (Log Action)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Non-Emergency Safety Check Confirmation -->
                    <div class="safety-pass-box" id="safetyPassBox" style="display: none;">
                        <div class="pass-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                        </div>
                        <div class="pass-text">
                            <strong>Safety Check:</strong> No configured red flags detected — <u>clinician review still required</u>. (Qualified clinicians make final care decisions).
                        </div>
                    </div>

                    <!-- 3. Priority Level Showcase Card -->
                    <div class="priority-showcase-card" id="priorityShowcase">
                        <div class="priority-showcase-left">
                            <div class="patient-id-tag" id="resPatientIdentity">Patient: Demo Patient Alpha (TRN-2026-0001)</div>
                            <div class="priority-level-heading" id="resPriorityLabel">Level 5 — Highest Priority</div>
                            <div class="priority-level-sub" id="resPrioritySubtext">Possible immediately life-threatening emergency; immediate emergency-team assessment.</div>
                        </div>
                        <div class="priority-badge-box">
                            <span class="priority-huge-badge" id="resPriorityBadge">Level 5</span>
                        </div>
                    </div>

                    <!-- 4. Clinical Problem / Impression -->
                    <div class="decision-block">
                        <div class="decision-block-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            Detected Clinical Problem / Primary Impression
                        </div>
                        <div class="impression-box" id="resClinicalImpression">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- 5. Department Recommendation -->
                    <div class="decision-block">
                        <div class="decision-block-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Department Recommendation
                        </div>
                        <div class="department-showcase">
                            <div class="dept-primary-card">
                                <span class="dept-lead-label">Assigned Department</span>
                                <div class="dept-primary-name" id="resPrimaryDept">Cardiology & Acute Cardiac Care (Emergency)</div>
                            </div>
                            <div class="dept-alt-card" id="resAltDeptCard">
                                <span class="dept-alt-label">Alternative / Secondary Specialty</span>
                                <div class="dept-alt-list" id="resAltDepts">Emergency Medicine & Resuscitation</div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Vitals Risk Assessment Matrix -->
                    <div class="decision-block">
                        <div class="decision-block-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                            </svg>
                            Physiological Vitals Telemetry
                        </div>
                        <div class="vitals-matrix-row" id="resVitalsGrid">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- 7. Nurse Review Recommendation -->
                    <div class="decision-block">
                        <div class="decision-block-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <polyline points="16 11 18 13 22 9"/>
                            </svg>
                            Nurse Review & Facility Workflow
                        </div>
                        <div class="nurse-rec-box" id="resNurseRecommendation">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- 8. Rule Audit Trail -->
                    <div class="decision-block">
                        <div class="decision-block-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 11 12 14 22 4"/>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                            </svg>
                            Explainability: Decision Rules Evaluated
                        </div>
                        <div class="audit-tray">
                            <ul class="audit-rules-list" id="resAuditList">
                                <!-- Populated dynamically -->
                            </ul>
                        </div>
                    </div>

                </div>
            </section>
        </div>

        <!-- DYNAMIC PRIORITIZED PATIENT TRIAGE QUEUE SECTION -->
        <section class="card history-section" id="queueSection">
            <div class="card-head">
                <div class="card-head-title">
                    <div class="card-head-icon icon-blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"/>
                            <line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/>
                            <line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/>
                            <line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="head-title-text">Live Prioritized Patient Triage Queue</h2>
                        <p class="head-subtitle-text">
                            <strong>Dynamic Triage Ranking:</strong> Sorted by Urgency (Level 5 Highest &rarr; Level 1 Lowest). 
                            Emergencies automatically take <strong>Queue Rank #1</strong> ahead of earlier routine arrivals.
                        </p>
                    </div>
                </div>
                <div class="queue-btn-group">
                    <button type="button" class="btn-refresh" id="btnRefreshHistory">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"/>
                            <polyline points="1 20 1 14 7 14"/>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                        </svg>
                        Refresh Queue
                    </button>
                    <button type="button" class="btn-clear-queue" id="btnClearQueue">
                        Reset Queue
                    </button>
                </div>
            </div>

            <div class="table-container">
                <table class="clinical-table" id="historyTable">
                    <thead>
                        <tr>
                            <th>Queue Rank</th>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th>Age</th>
                            <th>Reported Issue</th>
                            <th>Priority Category</th>
                            <th>Department</th>
                            <th>Vitals</th>
                            <th>Alert Status</th>
                            <th>Arrival Time</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <tr>
                            <td colspan="10" class="text-center py-4">Loading prioritized triage queue...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <!-- Professional Page Footer -->
    <footer class="app-footer">
        <div class="footer-inner">
            <div class="footer-left">
                <strong>TriNova OPD Triage Assistant</strong> &bull; Healthcare Hackathon Demonstration Edition
                <div class="footer-url">Active Local Endpoint: <code>http://localhost/trinova/</code></div>
            </div>
            <div class="footer-right">
                <span class="footer-pill">Prioritize | Flag | Abstain Safety Engine</span>
                <span class="footer-pill">MySQL Persisted</span>
                <span class="footer-pill">Educational Prototype Only</span>
            </div>
        </div>
    </footer>

    <!-- App JavaScript -->
    <script src="script.js"></script>
</body>
</html>
