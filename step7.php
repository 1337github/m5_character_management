<?php
include 'session_handler.php';
include 'db_connection.php';
include 'functions.php';

// Sicherstellen, dass die Session aktiv ist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Schritt 1-Daten abrufen
$characterData = getStepData(1);
$abenteuertypId = $characterData['abenteuertyp_id'] ?? null;

// Zulässige Charaktertypen für die Spezialisierung
$allowedCharacterTypes = [17, 43, 47];

// Wenn der Charakter berechtigt ist, aber keine POST-Daten vorhanden sind
if ($abenteuertypId && in_array((int)$abenteuertypId, $allowedCharacterTypes, true)) {
    // Der Charakter ist berechtigt, bleibt auf der Seite und kann ein Gebiet auswählen
} else {
    // Der Charakter ist nicht berechtigt, direkt zu Schritt 8 weiterleiten
    header('Location: step8.php');
    exit;
}


// Verfügbare Gebiete abrufen
$query = "SELECT DISTINCT prozess FROM zauber WHERE prozess IS NOT NULL ORDER BY prozess ASC";
$result = $conn->query($query);

if (!$result) {
    die("Fehler bei der Abfrage der Gebiete: " . $conn->error);
}

$gebiete = $result->fetch_all(MYSQLI_ASSOC);

// POST-Daten verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedProzess = $_POST['prozess'] ?? null;

    // Spezialisierung speichern
    if ($selectedProzess) {
        saveStepData(7, ['prozess' => $selectedProzess]);
    }

    // Weiterleitung zu Schritt 8
    header('Location: step8.php');
    exit;
}

// Debugging: Ausgabe der Session-Daten
$debugData = [
    'Schritt 1 Daten' => $characterData,
    'Typenprüfung' => [
        'abenteuertypId' => $abenteuertypId,
        'Erlaubte Typen' => $allowedCharacterTypes,
    ],
    'Verfügbare Gebiete' => $gebiete,
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Schritt 7: Gebietsspezialisierung</title>
</head>
<body>
    <h1>Schritt 7: Gebietsspezialisierung</h1>

    <?php if (!empty($gebiete)): ?>
        <form method="POST">
            <h2>Wähle dein Gebiet:</h2>
            <?php foreach ($gebiete as $gebiet): ?>
                <div>
                    <label>
                        <input type="radio" name="prozess" value="<?= htmlspecialchars($gebiet['prozess']) ?>">
                        <?= htmlspecialchars($gebiet['prozess']) ?>
                    </label>
                </div>
            <?php endforeach; ?>
            <button type="submit">Weiter</button>
        </form>
    <?php else: ?>
        <p>Keine Gebiete verfügbar.</p>
    <?php endif; ?>

    <?php if (!empty($debugData)): ?>
        <h2>Debugging</h2>
        <pre><?= htmlspecialchars(print_r($debugData, true)) ?></pre>
    <?php endif; ?>
</body>
</html>
