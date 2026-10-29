/**
 * TriNova - OPD Triage Assistant (v0.3 Professional Safety Engine)
 * High-Recall Triage Logic: PRIORITIZE | FLAG | ABSTAIN
 * 
 * Priority Scale (User Dictated):
 *  - Level 5: Highest Priority (Emergency / Red Alert / Rank #1)
 *  - Level 4: High Priority (Very Urgent / Time-Critical)
 *  - Level 3: Prompt Attention (Urgent / Prompt Specialty)
 *  - Level 2: Routine Priority Review (Standard Ambulatory Queue)
 *  - Level 1: Lowest Priority (Minimal / Non-Urgent)
 */

document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------------------------------------
    // DOM Element Selectors
    // -------------------------------------------------------------------------
    const patientForm = document.getElementById('patientForm');
    const btnResetForm = document.getElementById('btnResetForm');
    const btnRegenId = document.getElementById('btnRegenId');
    const globalErrorBox = document.getElementById('globalErrorBox');
    const globalErrorText = document.getElementById('globalErrorText');

    // Form Inputs
    const patientIdInput = document.getElementById('patientId');
    const patientNameInput = document.getElementById('patientName');
    const patientAgeInput = document.getElementById('patientAge');
    const symptomDurationInput = document.getElementById('symptomDuration');
    const chiefComplaintInput = document.getElementById('chiefComplaint');
    const painLocationInput = document.getElementById('painLocation');
    const painSeverityInput = document.getElementById('painSeverity');
    const painDisplay = document.getElementById('painDisplay');
    const bpSystolicInput = document.getElementById('bpSystolic');
    const bpDiastolicInput = document.getElementById('bpDiastolic');
    const pulseRateInput = document.getElementById('pulseRate');
    const spo2Input = document.getElementById('spo2');
    const temperatureInput = document.getElementById('temperature');
    const medicalHistoryInput = document.getElementById('medicalHistory');

    // Cockpit Output Elements
    const cockpitEmpty = document.getElementById('cockpitEmpty');
    const cockpitResult = document.getElementById('cockpitResult');
    const safetyDecisionBar = document.getElementById('safetyDecisionBar');
    const decisionPill = document.getElementById('decisionPill');
    const decisionRationale = document.getElementById('decisionRationale');
    const emergencyPanel = document.getElementById('emergencyPanel');
    const detectedRedFlagsList = document.getElementById('detectedRedFlagsList');
    const safetyPassBox = document.getElementById('safetyPassBox');
    const priorityShowcase = document.getElementById('priorityShowcase');
    const resPatientIdentity = document.getElementById('resPatientIdentity');
    const resPriorityLabel = document.getElementById('resPriorityLabel');
    const resPrioritySubtext = document.getElementById('resPrioritySubtext');
    const resPriorityBadge = document.getElementById('resPriorityBadge');
    const resClinicalImpression = document.getElementById('resClinicalImpression');
    const resPrimaryDept = document.getElementById('resPrimaryDept');
    const resAltDepts = document.getElementById('resAltDepts');
    const resAltDeptCard = document.getElementById('resAltDeptCard');
    const resVitalsGrid = document.getElementById('resVitalsGrid');
    const resNurseRecommendation = document.getElementById('resNurseRecommendation');
    const resAuditList = document.getElementById('resAuditList');

    // Nurse Acknowledgment Elements
    const ackDot = document.getElementById('ackDot');
    const ackText = document.getElementById('ackText');
    const btnAcknowledgeAlert = document.getElementById('btnAcknowledgeAlert');

    // History Queue
    const historyTableBody = document.getElementById('historyTableBody');
    const btnRefreshHistory = document.getElementById('btnRefreshHistory');
    const btnClearQueue = document.getElementById('btnClearQueue');

    let currentAssessmentId = null;

    // -------------------------------------------------------------------------
    // 1. Patient ID Generator
    // -------------------------------------------------------------------------
    function generateNewPatientId() {
        const year = new Date().getFullYear();
        const randHex = Math.floor(Math.random() * 0xFFFF).toString(16).toUpperCase().padStart(4, '0');
        return `TRN-${year}-${randHex}`;
    }

    btnRegenId.addEventListener('click', () => {
        patientIdInput.value = generateNewPatientId();
    });

    // -------------------------------------------------------------------------
    // 2. Pain Scale Slider Handler
    // -------------------------------------------------------------------------
    const painDescriptions = {
        0: '0 - No Pain',
        1: '1 - Minimal',
        2: '2 - Mild Discomfort',
        3: '3 - Noticeable',
        4: '4 - Moderate Pain',
        5: '5 - Distracting',
        6: '6 - Distressing',
        7: '7 - Unmanageable',
        8: '8 - Severe / Intense',
        9: '9 - Excruciating',
        10: '10 - Worst Agony'
    };

    painSeverityInput.addEventListener('input', () => {
        const val = painSeverityInput.value;
        painDisplay.textContent = painDescriptions[val] || `${val}/10`;
    });

    // -------------------------------------------------------------------------
    // 3. Clear Inline Errors on Input
    // -------------------------------------------------------------------------
    [
        patientNameInput, patientAgeInput, chiefComplaintInput,
        bpSystolicInput, bpDiastolicInput, pulseRateInput,
        spo2Input, temperatureInput
    ].forEach(input => {
        input.addEventListener('input', () => clearFieldError(input));
    });

    function setFieldError(input) {
        const field = input.closest('.form-field');
        if (field) {
            field.classList.add('has-error');
        }
    }

    function clearFieldError(input) {
        const field = input.closest('.form-field');
        if (field) {
            field.classList.remove('has-error');
        }
        globalErrorBox.style.display = 'none';
    }

    function clearAllErrors() {
        document.querySelectorAll('.form-field.has-error').forEach(el => el.classList.remove('has-error'));
        globalErrorBox.style.display = 'none';
    }

    // -------------------------------------------------------------------------
    // 4. Strict Input Validation
    // -------------------------------------------------------------------------
    function validateFormStrictly() {
        clearAllErrors();
        let isValid = true;
        const errors = [];

        if (!patientNameInput.value.trim()) {
            setFieldError(patientNameInput);
            errors.push('Patient name or anonymous identifier is required.');
            isValid = false;
        }

        const age = parseInt(patientAgeInput.value, 10);
        if (patientAgeInput.value.trim() === '' || isNaN(age) || age < 0 || age > 120) {
            setFieldError(patientAgeInput);
            errors.push('Valid age (0–120) is required.');
            isValid = false;
        }

        const complaint = chiefComplaintInput.value.trim();
        if (!complaint || complaint.length < 3) {
            setFieldError(chiefComplaintInput);
            errors.push('Please describe the main issue / symptoms (at least 3 characters).');
            isValid = false;
        }

        const sbp = parseInt(bpSystolicInput.value, 10);
        if (bpSystolicInput.value.trim() === '' || isNaN(sbp) || sbp < 50 || sbp > 260) {
            setFieldError(bpSystolicInput);
            errors.push('Systolic BP is required (50–260 mmHg).');
            isValid = false;
        }

        const dbp = parseInt(bpDiastolicInput.value, 10);
        if (bpDiastolicInput.value.trim() === '' || isNaN(dbp) || dbp < 30 || dbp > 160) {
            setFieldError(bpDiastolicInput);
            errors.push('Diastolic BP is required (30–160 mmHg).');
            isValid = false;
        }

        const pulse = parseInt(pulseRateInput.value, 10);
        if (pulseRateInput.value.trim() === '' || isNaN(pulse) || pulse < 30 || pulse > 240) {
            setFieldError(pulseRateInput);
            errors.push('Pulse / Heart rate is required (30–240 bpm).');
            isValid = false;
        }

        if (!isValid) {
            globalErrorText.textContent = errors[0] || 'Please complete all required fields accurately.';
            globalErrorBox.style.display = 'flex';
        }

        return isValid;
    }

    // -------------------------------------------------------------------------
    // 5. Submit Assessment to Backend
    // -------------------------------------------------------------------------
    patientForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!validateFormStrictly()) {
            return;
        }

        const payload = {
            patient_id: patientIdInput.value.trim(),
            patient_name: patientNameInput.value.trim(),
            age: parseInt(patientAgeInput.value, 10),
            symptom_duration: symptomDurationInput.value,
            chief_complaint: chiefComplaintInput.value.trim(),
            pain_location: painLocationInput.value.trim() || 'Not specified',
            pain_severity: parseInt(painSeverityInput.value, 10) || 0,
            bp_systolic: parseInt(bpSystolicInput.value, 10),
            bp_diastolic: parseInt(bpDiastolicInput.value, 10),
            pulse_rate: parseInt(pulseRateInput.value, 10),
            spo2: parseInt(spo2Input.value, 10) || 98,
            temperature: parseFloat(temperatureInput.value) || 98.6,
            medical_history: medicalHistoryInput.value.trim() || 'None reported'
        };

        const submitBtn = document.getElementById('btnSubmitAssess');
        const origBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<span>Evaluating Safety Rules...</span>`;

        try {
            const resp = await fetch('api.php?action=assess', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await resp.json();

            if (result.success) {
                renderTriageResult(result);
                loadPrioritizedQueue();
            } else {
                globalErrorText.textContent = result.message || 'Assessment evaluation failed.';
                globalErrorBox.style.display = 'flex';
            }
        } catch (err) {
            console.error('API Error:', err);
            globalErrorText.textContent = 'Server communication error. Check local XAMPP Apache/MySQL.';
            globalErrorBox.style.display = 'flex';
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnText;
        }
    });

    btnResetForm.addEventListener('click', () => {
        patientForm.reset();
        clearAllErrors();
        patientIdInput.value = generateNewPatientId();
        painSeverityInput.value = 0;
        painDisplay.textContent = '0 - No Pain';
        spo2Input.value = 98;
        temperatureInput.value = 98.6;
        cockpitResult.style.display = 'none';
        cockpitEmpty.style.display = 'block';
    });

    // -------------------------------------------------------------------------
    // 6. Render Assessment Result Cockpit
    // -------------------------------------------------------------------------
    function renderTriageResult(data) {
        currentAssessmentId = data.assessment_id;

        cockpitEmpty.style.display = 'none';
        cockpitResult.style.display = 'flex';

        // 1. Three-Way Safety Determination Bar
        const determination = data.safety_determination || (data.is_emergency ? 'FLAG' : 'PRIORITIZE');
        decisionPill.className = 'decision-pill';

        if (determination === 'FLAG') {
            decisionPill.classList.add('pill-flag');
            decisionPill.textContent = 'FLAG (EMERGENCY)';
            decisionRationale.textContent = 'Emergent red flag detected — high recall safety protocol locked to Level 5 Highest Priority.';
        } else if (determination === 'ABSTAIN') {
            decisionPill.classList.add('pill-abstain');
            decisionPill.textContent = 'ABSTAIN (HUMAN REVIEW)';
            decisionRationale.textContent = data.abstain_reason || 'Data contradictory or unreliable. Automated scoring abstained. Bedside clinician review required.';
        } else {
            decisionPill.classList.add('pill-prioritize');
            decisionPill.textContent = 'PRIORITIZE (STANDARD)';
            decisionRationale.textContent = 'No acute red flags detected. Case categorized by verified physiological and clinical presentation.';
        }

        // 2. Potential Emergency Alert
        if (data.is_emergency) {
            emergencyPanel.style.display = 'block';
            safetyPassBox.style.display = 'none';

            detectedRedFlagsList.innerHTML = '';
            if (data.red_flags && data.red_flags.length > 0) {
                data.red_flags.forEach(flag => {
                    const li = document.createElement('li');
                    li.textContent = flag;
                    detectedRedFlagsList.appendChild(li);
                });
            } else {
                const li = document.createElement('li');
                li.textContent = data.red_flags_summary || 'Potentially life-threatening emergency detected.';
                detectedRedFlagsList.appendChild(li);
            }

            // Reset Acknowledgment state
            ackDot.className = 'ack-dot pulse-red';
            ackText.textContent = 'Immediate review required — notify the nurse directly. (Unacknowledged)';
            btnAcknowledgeAlert.style.display = 'inline-flex';
            btnAcknowledgeAlert.disabled = false;
            btnAcknowledgeAlert.className = 'btn-acknowledge';
            btnAcknowledgeAlert.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Acknowledge Immediate Alert (Log Action)
            `;
        } else {
            emergencyPanel.style.display = 'none';
            safetyPassBox.style.display = 'flex';
        }

        // 3. Priority Level Showcase (Level 5 Highest -> Level 1 Lowest)
        priorityShowcase.className = 'priority-showcase-card';
        if (data.is_abstain) {
            priorityShowcase.classList.add('theme-abstain');
        } else {
            priorityShowcase.classList.add(`theme-l${data.priority_level}`);
        }

        resPatientIdentity.textContent = `Patient: ${data.patient_name} (${data.patient_id}) • Age: ${data.age} yrs`;
        resPriorityLabel.textContent = data.priority_label;
        resPriorityBadge.textContent = data.is_abstain ? 'ABSTAIN' : `Level ${data.priority_level}`;

        const subtexts = {
            5: 'Highest Priority: Possible immediately life-threatening emergency; immediate emergency-team resuscitation.',
            4: 'High Priority: Potentially serious condition; time-critical nurse and clinician review.',
            3: 'Prompt Attention: Urgent condition requiring prompt specialty clinical assessment.',
            2: 'Routine Priority Review: Localized complaint; assessment follows standard outpatient queue.',
            1: 'Lowest Priority: Minimal or chronic presentation; standard routine workflow.'
        };
        resPrioritySubtext.textContent = data.is_abstain 
            ? 'Clinical safety protocol: Automated scoring abstained due to contradictory or unreliable measurements.' 
            : (subtexts[data.priority_level] || '');

        // 4. Clinical Problem / Impression
        resClinicalImpression.textContent = data.clinical_impression || 'Clinical assessment recorded.';

        // 5. Department Recommendation
        resPrimaryDept.textContent = data.primary_department;
        if (data.alternative_departments && data.alternative_departments.length > 0) {
            resAltDeptCard.style.display = 'block';
            resAltDepts.textContent = data.alternative_departments.join(', ');
        } else {
            resAltDeptCard.style.display = 'none';
        }

        // 6. Vitals Grid
        renderVitalsTiles(data.vitals_summary);

        // 7. Nurse Recommendation
        resNurseRecommendation.innerHTML = `<strong>Protocol Directive:</strong> ${data.nurse_recommendation}`;

        // 8. Rule Audit Trail
        resAuditList.innerHTML = '';
        if (data.rule_audit && data.rule_audit.length > 0) {
            data.rule_audit.forEach(rule => {
                const li = document.createElement('li');
                li.textContent = rule;
                resAuditList.appendChild(li);
            });
        }

        if (window.innerWidth < 1180) {
            document.getElementById('resultsCockpit').scrollIntoView({ behavior: 'smooth' });
        }
    }

    function renderVitalsTiles(vitals) {
        resVitalsGrid.innerHTML = '';
        if (!vitals) return;

        const tileSpecs = [
            { key: 'bp', label: 'Blood Pressure' },
            { key: 'pulse', label: 'Pulse Rate' },
            { key: 'spo2', label: 'Oxygen SpO2' },
            { key: 'temperature', label: 'Temperature' }
        ];

        tileSpecs.forEach(spec => {
            const val = vitals[spec.key] || 'N/A';
            let tileClass = 'tile-norm';
            let tag = 'Normal';

            if (spec.key === 'bp') {
                const parts = val.replace(' mmHg', '').split('/');
                const sbp = parseInt(parts[0], 10);
                const dbp = parseInt(parts[1], 10);
                if (sbp >= 180 || sbp < 90 || dbp >= 120) {
                    tileClass = 'tile-crit'; tag = 'Critical';
                } else if (sbp >= 140 || dbp >= 90) {
                    tileClass = 'tile-warn'; tag = 'Elevated';
                }
            } else if (spec.key === 'spo2') {
                const num = parseInt(val, 10);
                if (num < 90) { tileClass = 'tile-crit'; tag = 'Hypoxic'; }
                else if (num <= 93) { tileClass = 'tile-warn'; tag = 'Borderline'; }
            } else if (spec.key === 'pulse') {
                const num = parseInt(val, 10);
                if (num >= 135 || num < 45) { tileClass = 'tile-crit'; tag = 'Critical'; }
                else if (num >= 110) { tileClass = 'tile-warn'; tag = 'Tachycardia'; }
            } else if (spec.key === 'temperature') {
                const num = parseFloat(val);
                if (num >= 104.0) { tileClass = 'tile-crit'; tag = 'High Fever'; }
                else if (num >= 100.4) { tileClass = 'tile-warn'; tag = 'Febrile'; }
            }

            const tile = document.createElement('div');
            tile.className = `vital-tile ${tileClass}`;
            tile.innerHTML = `
                <span class="vital-tile-name">${spec.label}</span>
                <span class="vital-tile-val">${val}</span>
                <span class="vital-tile-tag">${tag}</span>
            `;
            resVitalsGrid.appendChild(tile);
        });
    }

    // -------------------------------------------------------------------------
    // 7. Nurse Acknowledgment Handler
    // -------------------------------------------------------------------------
    btnAcknowledgeAlert.addEventListener('click', async () => {
        if (!currentAssessmentId) return;

        btnAcknowledgeAlert.disabled = true;
        btnAcknowledgeAlert.textContent = 'Recording acknowledgment...';

        try {
            const resp = await fetch('api.php?action=acknowledge', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    assessment_id: currentAssessmentId,
                    patient_id: patientIdInput.value.trim(),
                    nurse_name: 'Triage Nurse Staff',
                    action_note: 'Alert acknowledged directly. Patient prioritized for immediate emergency resuscitation.'
                })
            });

            const data = await resp.json();

            if (data.success) {
                ackDot.className = 'ack-dot is-green';
                ackText.textContent = `Alert acknowledged directly at ${data.acknowledged_at} by ${data.acknowledged_by}. (Nurse directly notified)`;
                btnAcknowledgeAlert.className = 'btn-acknowledge btn-acknowledged';
                btnAcknowledgeAlert.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Alert Acknowledged (Recorded in Log)
                `;
                loadPrioritizedQueue();
            }
        } catch (err) {
            console.error('Ack error:', err);
            btnAcknowledgeAlert.disabled = false;
            btnAcknowledgeAlert.textContent = 'Retry Acknowledgment';
        }
    });

    // -------------------------------------------------------------------------
    // 8. Load & Render Live Prioritized Patient Triage Queue
    // Sorting: Level 5 (Highest Priority) ranks at the top as Rank #1!
    // -------------------------------------------------------------------------
    async function loadPrioritizedQueue() {
        try {
            const response = await fetch('api.php?action=history');
            const data = await response.json();

            if (data.success && data.records && data.records.length > 0) {
                renderQueueTable(data.records);
            } else {
                historyTableBody.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">No patients currently in triage queue. Submit an intake form to admit a patient.</td>
                    </tr>
                `;
            }
        } catch (err) {
            console.error('Queue load error:', err);
            historyTableBody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">Unable to load triage queue. Check MySQL connection.</td>
                </tr>
            `;
        }
    }

    function renderQueueTable(records) {
        historyTableBody.innerHTML = '';

        records.forEach(rec => {
            const tr = document.createElement('tr');
            
            // Queue Rank formatting
            const isTopRank = rec.queue_rank === 1 && (rec.priority_level == 5 || rec.is_emergency == 1);
            const rankBadge = isTopRank
                ? `<span class="rank-badge rank-top">Rank #1 ★ TOP PRIORITY</span>`
                : `<span class="rank-badge rank-other">Rank #${rec.queue_rank}</span>`;

            const pillClass = `pill-l${rec.priority_level}`;
            const alertTag = rec.is_emergency == 1
                ? `<span class="tag-red-flag">▲ EMERGENCY</span>`
                : `<span class="tag-routine-pass">✓ Standard</span>`;

            tr.innerHTML = `
                <td>${rankBadge}</td>
                <td><strong class="mono-input">${escapeHtml(rec.patient_id)}</strong></td>
                <td><strong>${escapeHtml(rec.patient_name)}</strong></td>
                <td>${rec.age} yrs</td>
                <td style="max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${escapeHtml(rec.chief_complaint)}">
                    ${escapeHtml(rec.chief_complaint)}
                </td>
                <td><span class="badge-priority-pill ${pillClass}">Level ${rec.priority_level} - ${escapeHtml(rec.priority_label.replace('Level ' + rec.priority_level + ' — ', ''))}</span></td>
                <td><strong>${escapeHtml(rec.primary_department)}</strong></td>
                <td class="mono-input" style="font-size: 0.78rem;">
                    BP ${rec.bp_systolic}/${rec.bp_diastolic} | HR ${rec.pulse_rate}
                </td>
                <td>${alertTag}</td>
                <td class="mono-input" style="font-size: 0.75rem;">${rec.created_at}</td>
            `;

            historyTableBody.appendChild(tr);
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    btnRefreshHistory.addEventListener('click', () => {
        loadPrioritizedQueue();
    });

    btnClearQueue.addEventListener('click', async () => {
        if (!confirm('Are you sure you want to reset the triage queue?')) return;
        try {
            await fetch('api.php?action=clear_queue', { method: 'POST' });
            loadPrioritizedQueue();
        } catch (e) {
            console.error(e);
        }
    });

    // Initial load
    loadPrioritizedQueue();
});
