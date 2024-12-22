<?php
include 'session_handler.php';
include 'db_connection.php';
include 'functions.php';

if (!getStepData(1)) {
    die("Session-Daten sind nicht verfügbar. Bitte starte den Prozess erneut.");
}

// Charakterdaten aus Schritt 1 laden
$characterData = getStepData(1);
$abenteuertypId = $characterData['abenteuertyp_id'];

// Typische Fertigkeiten basierend auf dem Abenteuertyp aus der Tabelle typical_skills laden
$sql = "SELECT f.* FROM fertigkeiten f 
        JOIN typical_skills ts ON f.id = ts.skill_id 
        WHERE ts.abenteuer_id = $abenteuertypId";
$result = $conn->query($sql);
if (!$result) {
    die("Fehler bei der Datenbankabfrage: " . $conn->error);
}
$fertigkeiten = $result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bestehende Session-Daten erweitern
    $sessionData = array_merge(getStepData(1) ?? [], ['typische_fertigkeiten' => $_POST['skills'] ?? []]);
    $selectedSkills = $_POST['skills'] ?? [];
    $sessionData['typische_fertigkeiten'] = $selectedSkills;
    saveStepData(2, $sessionData);


    // Weiterleitung zu Schritt 3
    header('Location: step3.php');
    exit;
}

// Vorher ausgewählte Fertigkeiten laden
$step1Data = getStepData(1);
$selectedSkills = $step1Data['typische_fertigkeiten'] ?? [];


?>

<!DOCTYPE html>
<html>
<head>
    <title>Schritt 2: Typische Fertigkeiten</title>
</head>
<body>
    <h1>Schritt 2: Typische Fertigkeiten</h1>

    <form method="POST">
        <h2>Wähle deine typischen Fertigkeiten</h2>
        <?php foreach ($fertigkeiten as $fertigkeit): ?>
            <div>
                <label>
                    <input type="radio" name="skills" value="<?= $fertigkeit['id'] ?>" <?= in_array($fertigkeit['id'], (array)$selectedSkills) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($fertigkeit['name']) ?>
                </label>
            </div>
        <?php endforeach; ?>

        <button type="submit">Weiter</button>
    </form>
</body>
</html>
