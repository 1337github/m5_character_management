<?php
require_once 'session_handler.php';
require_once 'db_connection.php';
require_once 'functions.php';

// Sicherstellen, dass die Session-Daten verfügbar sind
if (!getStepData(1)) {
    die("Session-Daten sind nicht verfügbar. Bitte starte den Prozess erneut.");
}

// Charakterdaten laden
$characterData = getStepData(1);
$abenteuertypId = $characterData['abenteuertyp_id'];
$abenteuertypName = $characterData['abenteuertyp'];

// Überprüfen, ob der Charakter Zauberer oder zauberkundiger Kämpfer ist
$isMagicUser = isMagicUser($abenteuertypId, $conn);

if (!$isMagicUser) {
    // Weiterleitung, falls der Charakter keine Zauber auswählen kann
    header('Location: step6.php');
    exit;
}

// Typische Zauber aus der Datenbank abrufen
$sql = "
    SELECT z.id, z.name 
    FROM zauber z
    JOIN typical_spells ts ON ts.spell_id = z.id
    WHERE ts.abenteuer_id = $abenteuertypId
";
$result = $conn->query($sql);
if (!$result) {
    die("Fehler bei der Datenbankabfrage: " . $conn->error);
}

$typicalSpells = $result->fetch_all(MYSQLI_ASSOC);

// POST-Logik: Zauber speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedSpells = $_POST['selected_spells'] ?? [];
    saveStepData(5, ['typical_spells' => $selectedSpells]);

    // Weiterleitung zu Schritt 6
    header('Location: step6.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Schritt 5: Typische Zauber auswählen</title>
</head>
<body>
    <h1>Schritt 5: Typische Zauber auswählen</h1>

    <?php if (!empty($typicalSpells)): ?>
        <form method="POST">
            <h2>Wähle deine typischen Zauber</h2>
            <?php foreach ($typicalSpells as $spell): ?>
                <div>
                    <label>
                        <input type="checkbox" name="selected_spells[]" value="<?= htmlspecialchars($spell['id']) ?>">
                        <?= htmlspecialchars($spell['name']) ?>
                    </label>
                </div>
            <?php endforeach; ?>
            <button type="submit">Weiter</button>
        </form>
    <?php else: ?>
        <p>Für diesen Abenteuertyp stehen keine typischen Zauber zur Verfügung.</p>
        <a href="step6.php">Weiter</a>
    <?php endif; ?>
</body>
</html>
