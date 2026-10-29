
<?php
/**
 * TriNova - OPD Triage Assistant
 * AI-Assisted Patient Prioritization (v0.3 Professional Safety Engine)
 *
 * Safety Framework:
 * PRIORITIZE | FLAG | ABSTAIN
 *
 * Priority Scale:
 * Level 1 = Lowest Priority
 * Level 5 = Highest Priority
 *
 * Educational prototype only.
 * Qualified clinicians must make all final care decisions.
 */

require_once __DIR__ . '/../db.php';
$app_name = "TriNova";
$app_tagline = "OPD Triage Assistant — AI-Assisted Patient Prioritization";

try {
    $initial_patient_id = "TRN-" . date('Y') . "-" .
        strtoupper(bin2hex(random_bytes(2)));
} catch (Throwable $e) {
    http_response_code(500);
    exit("Unable to initialize the application.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name, ENT_QUOTES, 'UTF-8') ?> — OPD Triage Assistant</title>
    <meta name="description" content="TriNova educational OPD triage and patient prioritization dashboard.">
</head>
<body>
<?php
/*
 * IMPORTANT:
 * Keep your original index.php HTML, PHP dashboard,
 * CSS links, JavaScript links, and form markup here.
 *
 * This is only the PHP initialization section.
 * The complete dashboard cannot be reconstructed from
 * the opening PHP snippet alone.
 */
?>
<h1><?= htmlspecialchars($app_name, ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars($app_tagline, ENT_QUOTES, 'UTF-8') ?></p>
<p>Educational prototype only — clinical decisions require qualified professionals.</p>
</body>
</html>
