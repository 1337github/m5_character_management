<?php
include 'session_handler.php'; // Für Sitzungsdaten
include 'functions.php';      // Enthält alle benötigten Berechnungsfunktionen
include 'db_connection.php';  // Datenbankverbindung

// Starte die Session nur, falls sie nicht bereits aktiv ist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debugging-Modus aktivieren (nur für Entwicklung)
$debug_mode = false;

// Charakterklassenbeschränkung nach M5:
// Elfen: nur Gl, Kr, Wa, Ba, Dr, Hx, Ma
// Gnome: nur As, Gl, Sp, Wa, Dr, Hx, Ma
// Halblinge: nur As, Hä, Sp, Wa, Ba, PB
// Zwerge: nur Hä, Kr, Ma, PB, PS

// Abenteuertypen aus der Datenbank abrufen
$sql = "SELECT id, name FROM character_type";
$result = $conn->query($sql);
if (!$result) {
    die("Fehler bei der Datenbankabfrage: " . $conn->error);
}
$adventureTypes = $result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hole die ID und den Namen des Abenteurertyps
    $abenteuertypId = $_POST['abenteuertyp'];
    $abenteuertypName = getAbenteuertypName($abenteuertypId, $conn);

    // Berechne die Werte
    $attributes = createCharacter($_POST['race']);
    $validationResult = validateAttributes($attributes);

    while ($validationResult !== true) {
        $attributes = createCharacter($_POST['race']);
        $validationResult = validateAttributes($attributes);
    }

    $age = calculateAge();
    $groesseGewicht = calculateGroesseGewicht($_POST['race'], $_POST['geschlecht'], $attributes['St']);
    $gestalt = calculateGestalt($groesseGewicht['groesse'], $groesseGewicht['gewicht']);
    $schadensbonus = calculateSchadensbonus($attributes);
    $bewegungsweite = calculateBewegungsweite($_POST['race']);
    $angriffsbonus = calculateAngriffsbonus($attributes);
    $abwehrbonus = calculateAbwehrbonus($attributes);
    $zauberbonus = calculateZauberbonus($attributes);
    $resistenzbonus = calculateResistenzbonus($attributes, $_POST['race'], $abenteuertypName);
    $haendigkeit = calculateHaendigkeit($_POST['race']);
	$skills = calculateSkills($attributes, $abwehrbonus, $resistenzbonus, $zauberbonus, $angriffsbonus, $_POST['race'], $abenteuertypName, $conn);
    $specialAbility = calculateSpecialAbility($_POST['race']);
    $lebenspunkte = berechneLebenspunkte($_POST['race']);
    $ausdauerpunkte = berechneAusdauer($abenteuertypName);
    $stand = calculateStand($abenteuertypName);
    $goettlicheGnade = calculateGoettlicheGnade();

    // Lernpunkte aus der Tabelle lernschema_start für den aktuellen Abenteuertyp laden
    $lernpunkte = [];
    $result_lernpunkte = $conn->query("SELECT * FROM lernschema_start WHERE abenteuertyp_id = $abenteuertypId");
    if ($result_lernpunkte) {
        while ($row = $result_lernpunkte->fetch_assoc()) {
            $lernpunkte[$row['bereich_id']] = $row['le'];
        }
    }

    // Debugging-Ausgabe der Attribute
    if ($debug_mode) {
        echo "<pre>Generierte Attribute: ";
        print_r($attributes);
        echo "</pre>";
    }

    // Speichere alle Werte in der Session
    saveStepData(1, [
        'spieler_name' => $_POST['spieler_name'] ?? '',
        'name' => $_POST['name'] ?? '',
        'race' => $_POST['race'] ?? '',
        'geschlecht' => $_POST['geschlecht'] ?? '',
        'abenteuertyp' => $abenteuertypName,
        'abenteuertyp_id' => $abenteuertypId,
        'attributes' => $attributes,
        'age' => $age,
        'groesse' => $groesseGewicht['groesse'],
        'gewicht' => $groesseGewicht['gewicht'],
        'gestalt' => $gestalt,
        'schadensbonus' => $schadensbonus,
        'bewegungsweite' => $bewegungsweite,
        'angriffsbonus' => $angriffsbonus,
        'abwehrbonus' => $abwehrbonus,
        'zauberbonus' => $zauberbonus,
        'resistenzbonus' => $resistenzbonus,
        'haendigkeit' => $haendigkeit,
        'skills' => $skills,
        'specialAbility' => $specialAbility,
        'lebenspunkte' => $lebenspunkte,
        'ausdauerpunkte' => $ausdauerpunkte,
        'stand' => $stand,
        'goettlicheGnade' => $goettlicheGnade,
        'lernpunkte' => $lernpunkte, // Lernpunkte speichern
    ]);

    // Weiterleitung zu Schritt 2
    header('Location: step2.php');
    exit;
}

// Lade gespeicherte Daten, falls vorhanden
$step1Data = getStepData(1);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Schritt 1: Grundwerte</title>
</head>
<body>
    <h1>Schritt 1: Grundwerte</h1>

    <?php if ($debug_mode): ?>
        <h2>Debugging: Aktuelle Session-Daten</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    <?php endif; ?>

    <form method="POST">
        <h2>Spielername</h2>
        <input type="text" name="spieler_name" value="<?= htmlspecialchars($step1Data['spieler_name'] ?? '') ?>" required>

        <h2>Name des Charakters</h2>
        <input type="text" name="name" value="<?= htmlspecialchars($step1Data['name'] ?? '') ?>" required>

        <h2>Rasse</h2>
        <select name="race" required>
            <option value="" disabled <?= empty($step1Data['race']) ? 'selected' : '' ?>>Wähle eine Rasse</option>
            <option value="Mensch" <?= ($step1Data['race'] ?? '') === 'Mensch' ? 'selected' : '' ?>>Mensch</option>
            <option value="Elf" <?= ($step1Data['race'] ?? '') === 'Elf' ? 'selected' : '' ?>>Elf</option>
            <option value="Zwerg" <?= ($step1Data['race'] ?? '') === 'Zwerg' ? 'selected' : '' ?>>Zwerg</option>
            <option value="Halbling" <?= ($step1Data['race'] ?? '') === 'Halbling' ? 'selected' : '' ?>>Halbling</option>
            <option value="Gnom" <?= ($step1Data['race'] ?? '') === 'Gnom' ? 'selected' : '' ?>>Gnom</option>
        </select>

        <h2>Geschlecht</h2>
        <select name="geschlecht" required>
            <option value="" disabled <?= empty($step1Data['geschlecht']) ? 'selected' : '' ?>>Wähle ein Geschlecht</option>
            <option value="männlich" <?= ($step1Data['geschlecht'] ?? '') === 'männlich' ? 'selected' : '' ?>>Männlich</option>
            <option value="weiblich" <?= ($step1Data['geschlecht'] ?? '') === 'weiblich' ? 'selected' : '' ?>>Weiblich</option>
            <option value="unbestimmt" <?= ($step1Data['geschlecht'] ?? '') === 'unbestimmt' ? 'selected' : '' ?>>Unbestimmt</option>
        </select>

        <h2>Abenteuertyp</h2>
        <select name="abenteuertyp" required>
            <option value="" disabled <?= empty($step1Data['abenteuertyp']) ? 'selected' : '' ?>>Wähle einen Abenteuertyp</option>
            <?php foreach ($adventureTypes as $type): ?>
                <option value="<?= $type['id'] ?>" <?= ($step1Data['abenteuertyp_id'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($type['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <br><br>
        <button type="submit">Weiter</button>
    </form>
</body>
</html>
