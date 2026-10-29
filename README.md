# TriNova — OPD Triage Assistant
### *AI-Assisted Patient Prioritization (v0.3 Professional Safety Engine)*

TriNova is an outpatient department (OPD) clinical decision-support and prioritization dashboard. It evaluates physiological vitals and patient presentation using a **high emergent-case recall protocol**, a three-way safety determination (**PRIORITIZE | FLAG | ABSTAIN**), and dynamic urgency-ranked queuing backed by local MySQL persistence.

---

## ⚠️ Important Clinical Safety & High-Recall Notice

> **EDUCATIONAL PROTOTYPE ONLY**  
> TriNova v0.3 is an experimental healthcare proof-of-concept.
> - **Emergent-Case Recall Matters:** Missing a red-flag emergency carries catastrophic clinical risk compared to ordinary classification variance. TriNova's safety checks aggressively detect red flags to ensure zero missed emergencies.
> - **Three-Way Safety Determination:** The system determines whether to **PRIORITIZE** (score based on verified data), **FLAG** (trigger immediate emergency alert), or **ABSTAIN** (refrain from automated prediction when data is contradictory, missing, or unreliable, requiring manual human clinical review).
> - **Final Decision Authority:** **Qualified clinicians make all final care decisions.** This software is NOT a certified medical device and is NOT for live unsupervised triage.

---

## 🩺 5-Level Priority Classification (User-Dictated Convention)

TriNova implements a 5-level ascending urgency scale where **Level 5 is the Highest Priority**:

| Category | Level | Priority Meaning | Protocol & Queue Impact |
| :--- | :---: | :--- | :--- |
| **Highest Priority** | **Level 5** | Possible immediately life-threatening emergency; acute resuscitation required. | **Jumps to Queue Rank #1 ★ TOP PRIORITY**. Immediate nurse review required. |
| **High Priority** | **Level 4** | Potentially serious condition; severe pain ($\ge 8$), high fever, borderline vitals. | Time-critical nurse and clinician review ($< 15$ min). |
| **Prompt Attention** | **Level 3** | Urgent presentation; moderate pain (5–7), low-grade febrile vitals. | Prompt specialty clinical assessment ($< 30$ min). |
| **Routine Priority Review** | **Level 2** | Standard localized complaints (e.g. ankle sprains, minor injuries, stable vitals). | Assessment follows standard ambulatory queue ($< 60$ min). |
| **Lowest Priority** | **Level 1** | Minimal symptoms, chronic medication renewal, routine checkup. | Standard outpatient clinician review ($< 120$ min). |

---

## 🛡️ Three-Way Safety Determination Engine

For every incoming assessment, the engine evaluates whether to:

1. **FLAG (Emergency)**:
   * Triggers when critical vitals or clinical red flags are detected (e.g., Chest pain in patients aged 40+, acute stroke signs, $\text{SpO}_2 < 90\%$, $\text{BP} \ge 180/120\text{ mmHg}$, pulse $\ge 135$ or $< 45\text{ bpm}$, temp $\ge 104^\circ\text{F}$, syncope, or anaphylaxis).
   * Automatically locks patient into **Level 5 — Highest Priority**, promoting them to **Queue Rank #1**.
   * Informs the staff: *"Immediate review required — notify the nurse directly."*

2. **ABSTAIN (Human Clinical Review)**:
   * Triggers when data is missing, contradictory, or physiologically implausible (e.g., Systolic BP $\le$ Diastolic BP, or contradictory pain/vitals).
   * Refrains from automated machine categorization to prevent false reassurance.
   * Prompts: *"SYSTEM ABSTAINED: Data contradictory or unreliable. Qualified clinician must conduct immediate bedside manual triage."*

3. **PRIORITIZE (Standard Safety Categorization)**:
   * When data is verified, reliable, and free of acute red flags, assigns appropriate priority level (Level 1 to Level 4) and routes to the matching specialty department.

---

## 🚀 Setup & Execution Guide (XAMPP on Windows)

The application is deployed inside your local XAMPP `htdocs` directory:  
`c:\xampp\htdocs\trinova\`

### 1. Start Services in XAMPP Control Panel
1. Open the **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`).
2. Start **Apache** and **MySQL**.
   * *Note: The database `trinova_db` and tables `patient_assessments` and `nurse_acknowledgments` are automatically provisioned by `db.php` upon your first visit.*

### 2. Launch the Application
Open any web browser and navigate to:
```
http://localhost/trinova/
```

---

## 📂 Project Architecture

```
c:\xampp\htdocs\trinova\
├── db.php            # Safe PDO connection & automatic database/table migration
├── schema.sql        # Standalone SQL schema definition for trinova_db
├── api.php           # REST API (PRIORITIZE | FLAG | ABSTAIN safety logic, queue sorting)
├── index.php         # Clean hospital command cockpit & live prioritized triage queue
├── style.css         # Polished blue, white, and teal design system with rank badges
├── script.js         # Client-side intake handling, strict validation, and queue updates
└── README.md         # Setup instructions, safety philosophy & architecture guide
```

---

## 🧪 Verified Queue Prioritization Walkthrough

### Scenario Walkthrough:
1. **Patient 1 (David Miller, Age 29)** arrives with an ankle sprain:
   * Input: *Twisted right ankle, painful swelling, BP 120/78, Pulse 74*
   * Result: **Level 2 — Routine Priority Review**, Orthopaedics. Assigned Queue Rank #1 (initially).
2. **Patient 2 (Emma Watson, Age 34)** arrives with mild cold/runny nose:
   * Input: *Runny nose, sneezing for 2 days, BP 116/75, Pulse 72*
   * Result: **Level 1 — Lowest Priority**, ENT. Assigned Queue Rank #2.
3. **Patient 3 (Michael Scott, Age 45)** arrives with acute chest pain:
   * Input: *Severe crushing chest pain radiating to left arm, cold sweats, BP 168/102, Pulse 112*
   * Result: **FLAGGED AS EMERGENCY**, **Level 5 — Highest Priority**, Cardiology.
   * **Dynamic Priority Queue Result:** **Michael Scott automatically jumps to Queue Rank #1 ★ TOP PRIORITY**, while David Miller (Ankle) and Emma Watson (ENT) are queued after to be treated once the emergency is stabilized.

---

## 🗺️ Phase 2 Roadmap

- **Clinical NLP Classifier:** Transition from heuristic keyword matching to a fine-tuned clinical transformer (ClinicalBERT) for complaint context interpretation.
- **Biometric Telemetry Streaming:** Real-time Bluetooth/MQTT vital monitor ingestion.
- **EHR / FHIR HL7 Integration:** Export assessment encounters into hospital Electronic Health Record systems.
