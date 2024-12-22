<?php
include 'session_handler.php';
include 'functions.php';
include 'db_connection.php';

// Sicherstellen, dass die Session aktiv ist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Schritt 1 Daten laden, um Abenteuertyp und Lernpunkte zu ermitteln
$step1Data = getStepData(1);
$abenteuertypId = $step1Data['abenteuertyp_id'] ?? null;

// Lernpunkte aus der Session oder der Tabelle laden
if (!isset($_SESSION['character_creation']['lernpunkte_zauber'])) {
    $lernpunkte = 0;

    if ($abenteuertypId) {
        $sql = "SELECT le FROM lernschema_start WHERE abenteuertyp_id = ? AND bereich_id = 16";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $abenteuertypId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $lernpunkte = $row['le'] ?? 0;
        $stmt->close();
    }

    $_SESSION['character_creation']['lernpunkte_zauber'] = $lernpunkte;
} else {
    $lernpunkte = $_SESSION['character_creation']['lernpunkte_zauber'];
}

// Alle Zauber mit `startzauber = 1` aus der Datenbank laden
$sql = "SELECT id, name, start_le FROM zauber WHERE startzauber = 1";
$result = $conn->query($sql);
if (!$result) {
    die("Fehler bei der Abfrage der Zauber: " . $conn->error);
}
$startzauber = $result->fetch_all(MYSQLI_ASSOC);

// Gekaufte Zauber aus der Session laden
$gekaufteZauber = getStepData(6)['gekaufte_zauber'] ?? [];

// POST-Logik: Zauber kaufen oder rückgängig machen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['zauber_id'])) {
        $zauberId = (int)$_POST['zauber_id'];
        $zauber = array_filter($startzauber, fn($z) => $z['id'] == $zauberId);
        if ($zauber) {
            $zauber = array_values($zauber)[0];

            // Prüfen, ob der Zauber bereits gekauft wurde
            if (!in_array($zauber, $gekaufteZauber, true)) {
                if ($zauber['start_le'] <= $lernpunkte) {
                    // Lernpunkte reduzieren und Zauber hinzufügen
                    $lernpunkte -= $zauber['start_le'];
                    $gekaufteZauber[] = $zauber;
                }
            }
        }
    }

    if (isset($_POST['undo_zauber_id'])) {
        $undoZauberId = (int)$_POST['undo_zauber_id'];
        $key = array_search($undoZauberId, array_column($gekaufteZauber, 'id'));
        if ($key !== false) {
            $zauber = $gekaufteZauber[$key];
            unset($gekaufteZauber[$key]);
            $lernpunkte += $zauber['start_le']; // Lernpunkte zurückerstatten
        }
    }

    // Lernpunkte in der Session speichern
    $_SESSION['character_creation']['lernpunkte_zauber'] = $lernpunkte;

    // Session aktualisieren
    saveStepData(6, [
        'gekaufte_zauber' => array_values($gekaufteZauber)
    ]);
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Schritt 6: Zauber kaufen</title>
</head>
<body>
    <h1>Schritt 6: Zauber kaufen</h1>
    <h2>Verfügbare Lernpunkte: <?= htmlspecialchars($lernpunkte) ?> LE</h2>

    <h3>Verfügbare Zauber</h3>
    <form method="POST">
        <?php foreach ($startzauber as $zauber): ?>
            <?php
            $bereitsGekauft = in_array($zauber, $gekaufteZauber, true);
            if (!$bereitsGekauft && $zauber['start_le'] <= $lernpunkte):
            ?>
                <div>
                    <label>
                        <?= htmlspecialchars($zauber['name']) ?> (Kosten: <?= htmlspecialchars($zauber['start_le']) ?> LE)
                    </label>
                    <button type="submit" name="zauber_id" value="<?= $zauber['id'] ?>">Kaufen</button>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </form>

    <h3>Gekaufte Zauber</h3>
    <form method="POST">
        <?php if (!empty($gekaufteZauber)): ?>
            <?php foreach ($gekaufteZauber as $zauber): ?>
                <div>
                    <label>
                        <?= htmlspecialchars($zauber['name']) ?> (Kosten: <?= htmlspecialchars($zauber['start_le']) ?> LE)
                    </label>
                    <button type="submit" name="undo_zauber_id" value="<?= $zauber['id'] ?>">Rückgängig</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Es wurden noch keine Zauber gekauft.</p>
        <?php endif; ?>
    </form>

    <form method="POST" action="step7.php">
        <button type="submit">Weiter</button>
    </form>
</body>
</html>
