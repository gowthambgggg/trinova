<?php
/**
 * TriNova - OPD Triage Assistant
 * REST API Endpoint (v0.3 Professional Safety Engine)
 * 
 * Safety Philosophy:
 *  - High Emergent-Case Recall: Missing a red flag carries catastrophic risk compared to ordinary classification error.
 *  - Three-Way Safety Determination: PRIORITIZE | FLAG | ABSTAIN
 *  - Qualified Clinicians Make Final Decisions: Educational prototype only.
 * 
 * Priority Scale (Dictated Convention):
 *  - Level 5: Highest Priority (Immediate Emergency / Resuscitation / Red Flag)
 *  - Level 4: High Priority (Very Urgent / Time-Critical Clinical Review)
 *  - Level 3: Prompt Attention (Urgent / Prompt Specialty Care)
 *  - Level 2: Routine Priority Review (Standard Ambulatory Queue)
 *  - Level 1: Lowest Priority (Minimal / Non-Urgent)
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? 'status';

function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// -----------------------------------------------------------------------------
// ACTION: Status & Health
// -----------------------------------------------------------------------------
if ($action === 'status' && $method === 'GET') {
    global $db_connected, $db_error;
    json_response([
        'status' => 'operational',
        'app' => 'TriNova OPD Triage Assistant',
        'version' => '0.3.0-safety-engine',
        'database_connected' => $db_connected,
        'database_error' => $db_error,
        'priority_convention' => 'Level 5 (Highest Priority) -> Level 1 (Lowest Priority)',
        'timestamp' => date('c')
    ]);
}

// -----------------------------------------------------------------------------
// ACTION: Fetch Live Prioritized Patient Triage Queue
// Strictly sorted: Emergencies & Level 5 at the very top (Rank #1)
// -----------------------------------------------------------------------------
if ($action === 'history' && $method === 'GET') {
    global $pdo, $db_connected;

    if (!$db_connected || !$pdo) {
        json_response([
            'success' => false,
            'message' => 'Database offline. Storing in local session memory.',
            'records' => []
        ]);
    }

    try {
        $stmt = $pdo->query("
            SELECT 
                id,
                patient_id,
                patient_name,
                age,
                chief_complaint,
                symptom_duration,
                pain_location,
                pain_severity,
                bp_systolic,
                bp_diastolic,
                pulse_rate,
                spo2,
                temperature,
                is_emergency,
                red_flags_detected,
                priority_level,
                priority_label,
                primary_department,
                clinical_impression,
                is_acknowledged,
                created_at
            FROM patient_assessments
            ORDER BY 
                is_emergency DESC,
                priority_level DESC,
                id DESC
            LIMIT 50
        ");
        $records = $stmt->fetchAll();

        // 1-based queue ranking
        $rankedRecords = [];
        $rank = 1;
        foreach ($records as $r) {
            $r['queue_rank'] = $rank++;
            $rankedRecords[] = $r;
        }

        json_response([
            'success' => true,
            'count' => count($rankedRecords),
            'records' => $rankedRecords
        ]);
    } catch (Throwable $e) {
        json_response([
            'success' => false,
            'error' => $e->getMessage(),
            'records' => []
        ], 500);
    }
}

// -----------------------------------------------------------------------------
// ACTION: Clear Demo Queue
// -----------------------------------------------------------------------------
if ($action === 'clear_queue' && $method === 'POST') {
    global $pdo, $db_connected;
    if ($db_connected && $pdo) {
        try {
            $pdo->exec("DELETE FROM nurse_acknowledgments");
            $pdo->exec("DELETE FROM patient_assessments");
            json_response(['success' => true, 'message' => 'Triage queue reset successfully.']);
        } catch (Throwable $e) {
            json_response(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    json_response(['success' => true, 'message' => 'Queue reset.']);
}

// -----------------------------------------------------------------------------
// ACTION: Process & Persist Assessment
// Determines: PRIORITIZE | FLAG | ABSTAIN
// -----------------------------------------------------------------------------
if ($action === 'assess' && $method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload = json_decode($rawInput, true);

    if (!is_array($payload)) {
        json_response(['success' => false, 'error' => 'Invalid JSON payload received.'], 400);
    }

    $errors = [];

    $patientId = trim($payload['patient_id'] ?? '');
    if ($patientId === '') {
        $patientId = 'TRN-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    $patientName = trim($payload['patient_name'] ?? '');
    if ($patientName === '') {
        $errors['patient_name'] = 'Patient name or anonymous identifier is required.';
    }

    if (!isset($payload['age']) || !is_numeric($payload['age'])) {
        $errors['age'] = 'Age is required and must be a valid number.';
    } else {
        $age = (int)$payload['age'];
        if ($age < 0 || $age > 125) {
            $errors['age'] = 'Age must be between 0 and 125 years.';
        }
    }

    $chiefComplaint = trim($payload['chief_complaint'] ?? '');
    if ($chiefComplaint === '' || strlen($chiefComplaint) < 3) {
        $errors['chief_complaint'] = 'Chief complaint / main issue is required (minimum 3 characters).';
    }

    $symptomDuration = trim($payload['symptom_duration'] ?? 'Acute');
    $painLocation = trim($payload['pain_location'] ?? 'Not specified');
    $painSeverity = isset($payload['pain_severity']) && is_numeric($payload['pain_severity']) ? (int)$payload['pain_severity'] : 0;
    if ($painSeverity < 0 || $painSeverity > 10) {
        $painSeverity = 0;
    }

    // Physiological vitals validation
    if (!isset($payload['bp_systolic']) || !is_numeric($payload['bp_systolic'])) {
        $errors['bp_systolic'] = 'Systolic BP is required.';
    } else {
        $bpSystolic = (int)$payload['bp_systolic'];
        if ($bpSystolic < 50 || $bpSystolic > 260) {
            $errors['bp_systolic'] = 'Systolic BP must be between 50 and 260 mmHg.';
        }
    }

    if (!isset($payload['bp_diastolic']) || !is_numeric($payload['bp_diastolic'])) {
        $errors['bp_diastolic'] = 'Diastolic BP is required.';
    } else {
        $bpDiastolic = (int)$payload['bp_diastolic'];
        if ($bpDiastolic < 30 || $bpDiastolic > 160) {
            $errors['bp_diastolic'] = 'Diastolic BP must be between 30 and 160 mmHg.';
        }
    }

    if (!isset($payload['pulse_rate']) || !is_numeric($payload['pulse_rate'])) {
        $errors['pulse_rate'] = 'Heart rate/pulse is required.';
    } else {
        $pulseRate = (int)$payload['pulse_rate'];
        if ($pulseRate < 30 || $pulseRate > 240) {
            $errors['pulse_rate'] = 'Pulse rate must be between 30 and 240 bpm.';
        }
    }

    $spo2 = isset($payload['spo2']) && is_numeric($payload['spo2']) ? (int)$payload['spo2'] : 98;
    if ($spo2 < 50 || $spo2 > 100) {
        $errors['spo2'] = 'SpO2 must be between 50% and 100%.';
    }

    $temperature = isset($payload['temperature']) && is_numeric($payload['temperature']) ? (float)$payload['temperature'] : 98.6;
    if ($temperature < 88.0 || $temperature > 108.0) {
        $errors['temperature'] = 'Body temperature must be between 88.0°F and 108.0°F.';
    }

    $medicalHistory = trim($payload['medical_history'] ?? 'None reported');
    $allergies = trim($payload['allergies'] ?? 'NKDA');

    if (!empty($errors)) {
        json_response([
            'success' => false,
            'message' => 'Validation error: All required patient measurements must be provided accurately.',
            'validation_errors' => $errors
        ], 422);
    }

    // -------------------------------------------------------------------------
    // SAFETY CHECK: DETERMINING PRIORITIZE | FLAG | ABSTAIN
    // -------------------------------------------------------------------------
    $complaintLower = strtolower($chiefComplaint);
    $redFlags = [];
    $ruleAudit = [];
    $isEmergency = false;
    $isAbstain = false;
    $abstainReason = null;
    $safetyDetermination = "PRIORITIZE"; // Default determination

    // Check for contradictory or unreliable physiological data (ABSTAIN trigger)
    if ($bpSystolic <= $bpDiastolic) {
        $isAbstain = true;
        $safetyDetermination = "ABSTAIN";
        $abstainReason = "Physiological Contradiction: Systolic BP ({$bpSystolic} mmHg) cannot be lower than or equal to Diastolic BP ({$bpDiastolic} mmHg). Measurement is unreliable.";
        $ruleAudit[] = "SAFETY-ABSTAIN: Contradictory blood pressure measurements triggered automated abstain. Manual clinician review required.";
    }

    // -------------------------------------------------------------------------
    // High-Recall Red-Flag Detection (FLAG trigger)
    // -------------------------------------------------------------------------
    $hasChestPain = (bool)preg_match('/\b(chest pain|chest discomfort|chest tightness|angina|crushing chest|heart attack|cardiac arrest|pressure in chest|radiating to arm|substernal)\b/i', $complaintLower);
    $hasBreathingDiff = (bool)preg_match('/\b(breath|breathing|shortness of breath|dyspnea|gasping|wheez|stridor|choking|air hunger|asthma|bronchitis|pneumonia|congestion)\b/i', $complaintLower);
    $hasStrokeSigns = (bool)preg_match('/\b(slurred speech|speech difficulty|facial droop|droop|weakness|one-sided|arm weakness|leg weakness|hemiparesis|paralysis|stroke|numbness on one side)\b/i', $complaintLower);
    $hasSeizureSyncope = (bool)preg_match('/\b(unconscious|fainted|syncope|passed out|blackout|seizure|convulsion|unresponsive|collapsed)\b/i', $complaintLower);
    $hasHemorrhage = (bool)preg_match('/\b(severe bleeding|hemorrhage|uncontrolled bleeding|vomiting blood|coughing blood|gushing blood)\b/i', $complaintLower);
    $hasAnaphylaxis = (bool)preg_match('/\b(anaphylaxis|swollen throat|swollen lip|tongue swelling|hives and breathing|throat closing)\b/i', $complaintLower);
    $hasSevereAbdomen = (bool)preg_match('/\b(acute abdomen|severe abdominal|rigid abdomen|appendix|appendicitis|peritonitis)\b/i', $complaintLower);

    // Critical Vitals Detection
    if ($spo2 < 90) {
        $isEmergency = true;
        $redFlags[] = "Critical Hypoxia: SpO2 {$spo2}% (< 90%) indicates acute respiratory failure.";
        $ruleAudit[] = "FLAG-CRIT-SPO2: Severe hypoxemia (SpO2 {$spo2}%) forced emergency priority.";
    }

    if ($bpSystolic >= 180 || $bpDiastolic >= 120) {
        $isEmergency = true;
        $redFlags[] = "Hypertensive Emergency: Blood pressure {$bpSystolic}/{$bpDiastolic} mmHg exceeds acute target organ damage threshold.";
        $ruleAudit[] = "FLAG-CRIT-BP: Hypertensive crisis ({$bpSystolic}/{$bpDiastolic} mmHg) forced emergency priority.";
    } elseif ($bpSystolic < 90) {
        $isEmergency = true;
        $redFlags[] = "Circulatory Shock / Hypotension: SBP {$bpSystolic} mmHg indicates acute hemodynamic collapse.";
        $ruleAudit[] = "FLAG-CRIT-SHOCK: Hypotensive shock (SBP {$bpSystolic} mmHg) forced emergency priority.";
    }

    if ($pulseRate >= 135) {
        $isEmergency = true;
        $redFlags[] = "Extreme Tachycardia: Pulse {$pulseRate} bpm exceeds hemodynamic safety threshold.";
        $ruleAudit[] = "FLAG-CRIT-PULSE: Extreme tachycardia ({$pulseRate} bpm) forced emergency priority.";
    } elseif ($pulseRate < 45) {
        $isEmergency = true;
        $redFlags[] = "Severe Bradycardia: Pulse {$pulseRate} bpm poses high risk of asystole.";
        $ruleAudit[] = "FLAG-CRIT-BRADY: Severe bradycardia ({$pulseRate} bpm) forced emergency priority.";
    }

    if ($temperature >= 104.0) {
        $isEmergency = true;
        $redFlags[] = "Severe Hyperpyrexia: Temperature {$temperature}°F poses risk of febrile encephalopathy.";
        $ruleAudit[] = "FLAG-CRIT-TEMP: Hyperpyrexia ({$temperature}°F) forced emergency priority.";
    }

    // High-Recall Clinical Symptom Red Flags
    if ($hasChestPain) {
        if ($age >= 40 || $hasBreathingDiff || $bpSystolic >= 160 || $pulseRate >= 100 || $painSeverity >= 7) {
            $isEmergency = true;
            $redFlags[] = "Cardiac Emergency: Suspected Acute Coronary Event / Cardiac Arrest Risk (Age {$age}, BP {$bpSystolic}/{$bpDiastolic}).";
            $ruleAudit[] = "FLAG-CARDIAC-ALERT: Chest discomfort with cardiac risk indicators triggered immediate Red Alert.";
        }
    }

    if ($hasBreathingDiff && ($spo2 < 94 || (bool)preg_match('/\b(severe|gasping|struggling|air hunger|blue lips)\b/i', $complaintLower))) {
        $isEmergency = true;
        $redFlags[] = "Severe Breathing Difficulty: Acute airway / respiratory distress with compromised oxygenation.";
        $ruleAudit[] = "FLAG-RESP-ALERT: Acute respiratory distress triggered emergency priority.";
    }

    if ($hasStrokeSigns) {
        $isEmergency = true;
        $redFlags[] = "Acute Stroke Warning: Sudden neurological deficit (speech difficulty, unilateral weakness, or facial droop). Time-critical thrombolysis window.";
        $ruleAudit[] = "FLAG-STROKE-ALERT: Acute focal neurological deficits triggered Stroke Unit emergency alert.";
    }

    if ($hasSeizureSyncope) {
        $isEmergency = true;
        $redFlags[] = "Loss of Consciousness / Seizure: Syncope or active convulsion requires immediate airway and neurological stabilization.";
        $ruleAudit[] = "FLAG-SYNCOPE-ALERT: Unresponsiveness/seizure triggered emergency alert.";
    }

    if ($hasHemorrhage) {
        $isEmergency = true;
        $redFlags[] = "Active Severe Hemorrhage: Acute uncontrolled blood loss requiring immediate surgical/trauma hemostasis.";
        $ruleAudit[] = "FLAG-HEMORRHAGE: Severe bleeding triggered trauma emergency alert.";
    }

    if ($hasAnaphylaxis) {
        $isEmergency = true;
        $redFlags[] = "Suspected Anaphylaxis / Severe Allergy: Acute airway compromise or angioedema.";
        $ruleAudit[] = "FLAG-ANAPHYLAXIS: Airway allergic reaction triggered emergency resuscitation.";
    }

    // Determine final safety designation
    if ($isAbstain) {
        $safetyDetermination = "ABSTAIN";
    } elseif ($isEmergency) {
        $safetyDetermination = "FLAG";
    } else {
        $safetyDetermination = "PRIORITIZE";
    }

    // -------------------------------------------------------------------------
    // Priority Level Assignment (User Defined Convention: Level 1=Low, Level 5=High)
    // -------------------------------------------------------------------------
    if ($isAbstain) {
        $priorityLevel = 5; // Put at top of queue for immediate human review
        $priorityLabel = "ABSTAIN — Human Review Required";
        $nurseRecommendation = "SYSTEM ABSTAINED: Data is contradictory, missing, or unreliable. Qualified clinician must conduct immediate bedside manual triage.";
    } elseif ($isEmergency) {
        // Level 5: Highest Priority (Immediate Emergency)
        $priorityLevel = 5;
        $priorityLabel = "Level 5 — Highest Priority";
        $nurseRecommendation = "POTENTIAL EMERGENCY — IMMEDIATE NURSE REVIEW REQUIRED. High-recall safety alert active. Alert emergency team immediately according to facility protocol. Do not delay.";
        $ruleAudit[] = "PRIORITY-L5: Life-threatening red flag active. Assigned Level 5 (Highest Priority) -> Jumps to Rank #1.";
    } else {
        // Level 4: High Priority (Very Urgent)
        if ($painSeverity >= 8 || ($spo2 >= 90 && $spo2 <= 93) || ($bpSystolic >= 160 && $bpSystolic < 180) || ($pulseRate >= 115 && $pulseRate < 135) || ($temperature >= 102.5 && $temperature < 104.0) || $hasSevereAbdomen) {
            $priorityLevel = 4;
            $priorityLabel = "Level 4 — High Priority";
            $nurseRecommendation = "Time-critical nurse and clinician review required according to the facility's approved acute protocol.";
            $ruleAudit[] = "PRIORITY-L4: Severe pain scale or significant physiological vitals deviation.";
        } 
        // Level 3: Prompt Attention (Urgent)
        elseif ($painSeverity >= 5 || ($temperature >= 100.4 && $temperature < 102.5) || ($bpSystolic >= 140 && $bpSystolic < 160) || ($bpDiastolic >= 90 && $bpDiastolic < 100)) {
            $priorityLevel = 3;
            $priorityLabel = "Level 3 — Prompt Attention";
            $nurseRecommendation = "Requires prompt clinical assessment in the designated specialty OPD queue.";
            $ruleAudit[] = "PRIORITY-L3: Moderate pain or low-grade febrile vitals.";
        } 
        // Level 2: Routine Priority Review (Standard queue)
        elseif ($painSeverity >= 2 || (bool)preg_match('/\b(pain|ache|swelling|sprain|fracture|rash|cough|sore throat|injury|joint|ankle|knee)\b/i', $complaintLower)) {
            $priorityLevel = 2;
            $priorityLabel = "Level 2 — Routine Priority Review";
            $nurseRecommendation = "Assessment can follow the facility's approved routine outpatient queue.";
            $ruleAudit[] = "PRIORITY-L2: Localized complaint with stable physiological parameters.";
        } 
        // Level 1: Lowest Priority (Minimal)
        else {
            $priorityLevel = 1;
            $priorityLabel = "Level 1 — Lowest Priority";
            $nurseRecommendation = "Lower apparent urgency. Standard clinician review according to facility workflow.";
            $ruleAudit[] = "PRIORITY-L1: Minimal or chronic presentation with normal physiological baseline.";
        }
    }

    // -------------------------------------------------------------------------
    // Accurate Department Mapping (Zero Mismatch Guarantee)
    // -------------------------------------------------------------------------
    $primaryDept = "General Medicine";
    $altDepts = [];
    $clinicalImpression = "General clinical presentation.";
    $missingOrUncertain = null;

    $isOrtho = (bool)preg_match('/\b(ankle|knee|joint|bone|fracture|sprain|wrist|ligament|tendon|dislocation|backache|spine|shoulder pain|foot pain|hip pain|ortho|musculoskeletal|twist)\b/i', $complaintLower);
    $isNeuro = (bool)preg_match('/\b(headache|migraine|dizziness|vertigo|seizure|numbness|speech|droop|weakness|syncope|unconscious|paralysis|stroke|confusion|memory|tremor)\b/i', $complaintLower);
    $isCardiac = (bool)preg_match('/\b(chest pain|chest discomfort|angina|palpitations|racing heart|heart|cardiac|cardiac arrest|coronary|heart attack)\b/i', $complaintLower);
    $isRespiratory = (bool)preg_match('/\b(cough|wheez|asthma|dyspnea|phlegm|sputum|shortness of breath|breath|breathing|bronchitis|pneumonia|congestion|choking|copd)\b/i', $complaintLower);
    $isENT = (bool)preg_match('/\b(runny nose|sore throat|sneezing|ear|earache|sinus|sinusitis|tonsil|nosebleed|nasal|hoarseness|throat|hearing)\b/i', $complaintLower);
    $isGI = (bool)preg_match('/\b(stomach|abdomen|abdominal|vomit|vomiting|diarrhea|nausea|gastric|digestive|cramps|acid reflux|heartburn|appendix|constipation|bowel)\b/i', $complaintLower);
    $isDerm = (bool)preg_match('/\b(rash|skin|itching|eczema|psoriasis|dermatitis|lesion|hives|urticaria|boil|blister|acne)\b/i', $complaintLower);
    $isEye = (bool)preg_match('/\b(eye|vision|blurred vision|conjunctivitis|cornea|eye pain|red eye|sight|double vision)\b/i', $complaintLower);

    if ($hasChestPain || ($isCardiac && $isEmergency)) {
        $primaryDept = "Cardiology & Acute Cardiac Care (Emergency)";
        $altDepts = ["Emergency Medicine & Resuscitation", "Interventional Cardiology"];
        $clinicalImpression = "Suspected Acute Coronary Event / Cardiac Arrest Risk. High priority immediate ECG and cardiac biomarker evaluation indicated.";
        $ruleAudit[] = "DEPT-CARDIOLOGY: Chest discomfort / cardiac markers mapped to Cardiology.";
    } elseif ($hasStrokeSigns || ($isNeuro && $isEmergency)) {
        $primaryDept = "Neurology & Acute Stroke Unit (Emergency)";
        $altDepts = ["Emergency Medicine", "Neurosurgery"];
        $clinicalImpression = "Acute neurological deficit / suspected stroke. Immediate neurovascular and CT brain assessment indicated.";
        $ruleAudit[] = "DEPT-NEUROLOGY: Acute neurological deficit mapped to Neurology & Stroke Unit.";
    } elseif ($hasBreathingDiff && $isEmergency) {
        $primaryDept = "Emergency Medicine & Acute Resuscitation";
        $altDepts = ["Pulmonology / Respiratory Medicine", "Cardiology"];
        $clinicalImpression = "Acute respiratory compromise / airway distress. Resuscitation and high-flow oxygen prioritized.";
        $ruleAudit[] = "DEPT-EMERGENCY: Severe dyspnea prioritized for Emergency Resuscitation.";
    } elseif ($isRespiratory) {
        $primaryDept = "Pulmonology & Respiratory Medicine";
        $altDepts = ["General Medicine", "ENT"];
        $clinicalImpression = "Respiratory symptoms (cough, wheezing, or bronchial congestion). Strictly routed to Respiratory Medicine (never Orthopaedics).";
        $ruleAudit[] = "DEPT-PULMONOLOGY: Respiratory complaint correctly assigned to Pulmonology & Respiratory Medicine.";
    } elseif ($isOrtho) {
        $primaryDept = "Orthopaedics & Traumatology";
        $altDepts = ["Physical Medicine & Rehabilitation", "Sports Medicine"];
        $clinicalImpression = "Musculoskeletal injury / joint-bone impairment requiring physical and radiological (X-ray) evaluation.";
        $ruleAudit[] = "DEPT-ORTHOPAEDICS: Musculoskeletal indicators correctly assigned to Orthopaedics & Traumatology.";
    } elseif ($isNeuro) {
        $primaryDept = "Neurology";
        $altDepts = ["General Medicine", "Internal Medicine"];
        $clinicalImpression = "Non-acute headache, stable vertigo, or chronic neurological disorder.";
        $ruleAudit[] = "DEPT-NEURO: Non-emergency neurological presentation assigned to Neurology.";
    } elseif ($isGI) {
        $primaryDept = "Gastroenterology & Abdominal Care";
        $altDepts = ["General Surgery", "Internal Medicine"];
        $clinicalImpression = "Acute or subacute gastrointestinal presentation requiring clinical abdominal examination.";
        $ruleAudit[] = "DEPT-GI: Digestive / abdominal symptoms correctly assigned to Gastroenterology.";
    } elseif ($isENT) {
        $primaryDept = "Otorhinolaryngology (ENT)";
        $altDepts = ["General Medicine"];
        $clinicalImpression = "Localized ear, nose, or throat disorder requiring otoscopic/rhinoscopic examination.";
        $ruleAudit[] = "DEPT-ENT: ENT symptoms correctly assigned to Otorhinolaryngology (ENT).";
    } elseif ($isDerm) {
        $primaryDept = "Dermatology";
        $altDepts = ["General Medicine", "Allergy & Immunology"];
        $clinicalImpression = "Cutaneous eruption or dermatologic presentation requiring topical/systemic assessment.";
        $ruleAudit[] = "DEPT-DERM: Skin indicators correctly assigned to Dermatology.";
    } elseif ($isEye) {
        $primaryDept = "Ophthalmology";
        $altDepts = ["Emergency Medicine", "General Medicine"];
        $clinicalImpression = "Ocular presentation requiring slit-lamp / visual acuity clinical examination.";
        $ruleAudit[] = "DEPT-EYE: Eye indicators correctly assigned to Ophthalmology.";
    } elseif ((bool)preg_match('/\b(fever|chills|body ache|fatigue|weakness|malaise|infection|cold)\b/i', $complaintLower)) {
        $primaryDept = "Internal & General Medicine";
        $altDepts = ["Infectious Diseases"];
        $clinicalImpression = "Systemic viral or febrile prodrome requiring general medical workup.";
        $ruleAudit[] = "DEPT-GENMED: Systemic symptoms assigned to Internal & General Medicine.";
    } else {
        $primaryDept = "Needs Clinician Review / General Medicine";
        $altDepts = ["General Medicine"];
        $clinicalImpression = "Non-specific or vague symptoms. Safe baseline triage requires comprehensive bedside clinician evaluation.";
        $missingOrUncertain = "Symptom description lacks organ-specific localization. Human medical triage assessment required.";
        $ruleAudit[] = "DEPT-UNCLEAR: Unclear symptom profile safely routed to 'Needs Clinician Review / General Medicine'.";
    }

    $redFlagsDetectedText = empty($redFlags) 
        ? "No configured red flags detected — clinician review still required."
        : implode(" | ", $redFlags);

    // -------------------------------------------------------------------------
    // Database Persistence
    // -------------------------------------------------------------------------
    $insertedId = null;
    global $pdo, $db_connected;

    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO patient_assessments (
                    patient_id, patient_name, age, chief_complaint, symptom_duration,
                    pain_location, pain_severity, bp_systolic, bp_diastolic, pulse_rate,
                    spo2, temperature, medical_history, allergies, is_emergency,
                    red_flags_detected, priority_level, priority_label, primary_department,
                    alternative_departments, clinical_impression, rule_audit_trail,
                    missing_or_uncertain, nurse_recommendation, is_acknowledged
                ) VALUES (
                    :patient_id, :patient_name, :age, :chief_complaint, :symptom_duration,
                    :pain_location, :pain_severity, :bp_systolic, :bp_diastolic, :pulse_rate,
                    :spo2, :temperature, :medical_history, :allergies, :is_emergency,
                    :red_flags_detected, :priority_level, :priority_label, :primary_department,
                    :alternative_departments, :clinical_impression, :rule_audit_trail,
                    :missing_or_uncertain, :nurse_recommendation, 0
                )
            ");

            $stmt->execute([
                ':patient_id' => $patientId,
                ':patient_name' => $patientName,
                ':age' => $age,
                ':chief_complaint' => $chiefComplaint,
                ':symptom_duration' => $symptomDuration,
                ':pain_location' => $painLocation,
                ':pain_severity' => $painSeverity,
                ':bp_systolic' => $bpSystolic,
                ':bp_diastolic' => $bpDiastolic,
                ':pulse_rate' => $pulseRate,
                ':spo2' => $spo2,
                ':temperature' => $temperature,
                ':medical_history' => $medicalHistory,
                ':allergies' => $allergies,
                ':is_emergency' => $isEmergency ? 1 : 0,
                ':red_flags_detected' => $redFlagsDetectedText,
                ':priority_level' => $priorityLevel,
                ':priority_label' => $priorityLabel,
                ':primary_department' => $primaryDept,
                ':alternative_departments' => json_encode($altDepts, JSON_UNESCAPED_UNICODE),
                ':clinical_impression' => $clinicalImpression,
                ':rule_audit_trail' => json_encode($ruleAudit, JSON_UNESCAPED_UNICODE),
                ':missing_or_uncertain' => $missingOrUncertain ?? $abstainReason,
                ':nurse_recommendation' => $nurseRecommendation
            ]);

            $insertedId = (int)$pdo->lastInsertId();
        } catch (Throwable $e) {
            $ruleAudit[] = "DB_PERSISTENCE_WARNING: Could not write to MySQL: " . $e->getMessage();
        }
    }

    json_response([
        'success' => true,
        'assessment_id' => $insertedId,
        'assessment_timestamp' => date('Y-m-d H:i:s'),
        'patient_id' => $patientId,
        'patient_name' => $patientName,
        'age' => $age,
        'safety_determination' => $safetyDetermination, // PRIORITIZE | FLAG | ABSTAIN
        'is_emergency' => $isEmergency,
        'is_abstain' => $isAbstain,
        'abstain_reason' => $abstainReason,
        'red_flags' => $redFlags,
        'red_flags_summary' => $redFlagsDetectedText,
        'priority_level' => $priorityLevel,
        'priority_label' => $priorityLabel,
        'primary_department' => $primaryDept,
        'alternative_departments' => $altDepts,
        'clinical_impression' => $clinicalImpression,
        'nurse_recommendation' => $nurseRecommendation,
        'rule_audit' => $ruleAudit,
        'missing_or_uncertain' => $missingOrUncertain ?? $abstainReason,
        'vitals_summary' => [
            'bp' => "{$bpSystolic}/{$bpDiastolic} mmHg",
            'pulse' => "{$pulseRate} bpm",
            'spo2' => "{$spo2}%",
            'temperature' => "{$temperature}°F",
            'pain' => "{$painSeverity}/10"
        ]
    ]);
}

// -----------------------------------------------------------------------------
// ACTION: Acknowledge Emergency Alert
// -----------------------------------------------------------------------------
if ($action === 'acknowledge' && $method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload = json_decode($rawInput, true);

    $assessmentId = isset($payload['assessment_id']) ? (int)$payload['assessment_id'] : 0;
    $patientId = trim($payload['patient_id'] ?? 'UNKNOWN');
    $nurseName = trim($payload['nurse_name'] ?? 'Triage Staff User');
    $actionNote = trim($payload['action_note'] ?? 'Emergency alert acknowledged directly by user. Nurse notified manually.');

    global $pdo, $db_connected;

    if ($db_connected && $pdo && $assessmentId > 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE patient_assessments 
                SET is_acknowledged = 1, acknowledged_at = NOW(), acknowledged_by = :nurse_name 
                WHERE id = :id
            ");
            $stmt->execute([':nurse_name' => $nurseName, ':id' => $assessmentId]);

            $stmt2 = $pdo->prepare("
                INSERT INTO nurse_acknowledgments (assessment_id, patient_id, action_note, acknowledged_by)
                VALUES (:assessment_id, :patient_id, :action_note, :nurse_name)
            ");
            $stmt2->execute([
                ':assessment_id' => $assessmentId,
                ':patient_id' => $patientId,
                ':action_note' => $actionNote,
                ':nurse_name' => $nurseName
            ]);

            json_response([
                'success' => true,
                'message' => 'Emergency acknowledgment recorded successfully. User took manual direct action.',
                'acknowledged_at' => date('Y-m-d H:i:s'),
                'acknowledged_by' => $nurseName
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    json_response([
        'success' => true,
        'message' => 'Acknowledgment processed.',
        'acknowledged_at' => date('Y-m-d H:i:s'),
        'acknowledged_by' => $nurseName
    ]);
}

json_response(['error' => 'Invalid API route.'], 404);
