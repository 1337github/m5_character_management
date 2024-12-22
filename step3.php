<?php
require_once 'session_handler.php';
require_once 'db_connection.php';
require_once 'functions.php';

// Sicherstellen, dass die Session-Variablen für die Charaktererstellung verfügbar sind
if (!getStepData(1)) {
    die("Session-Daten sind nicht verfügbar. Bitte starte den Prozess erneut.");
}

$step1Data = getStepData(1);
// Daten aus der Session und der Datenbank laden
$abenteuertypId = $step1Data['abenteuertyp_id'];

// Lernpunkte aus der Tabelle lernschema_start für den aktuellen Abenteuertyp laden
if (!isset($_SESSION['character_creation']['lernpunkte'])) {
    $lernpunkte = [];
    $result_lernpunkte = $conn->query("SELECT * FROM lernschema_start WHERE abenteuertyp_id = $abenteuertypId");
    if ($result_lernpunkte) {
        while ($row = $result_lernpunkte->fetch_assoc()) {
            $lernpunkte[$row['bereich_id']] = $row['le'];
        }
    }
    $_SESSION['character_creation']['lernpunkte'] = $lernpunkte;
} else {
    $lernpunkte = $_SESSION['character_creation']['lernpunkte'];
}

/**
 * Filtert Fertigkeiten basierend auf Rassenbeschränkungen.
 *
 * @param array $fertigkeiten Die Liste aller Fertigkeiten.
 * @param string $race Die Rasse des Charakters (z. B. 'Elf', 'Gnom', 'Halbling').
 * @return array Gefilterte Liste der Fertigkeiten.
 */
function filterSkillsByRace(array $fertigkeiten, string $race): array {
    // Einschränkungen für Fertigkeiten basierend auf Rasse
    $restrictions = [
        'Elf' => ['Fälschen', 'Fechten', 'Gassenwissen', 'Gerätekunde', 'Geschäftssinn', 
                  'Glücksspiel', 'Meucheln', 'Schlösser öffnen', 'Stehlen'],
        'Gnom' => ['Stockwaffen', 'Zweihandschlagwaffen', 'Zweihandschwerter'],
        'Halbling' => ['Stockwaffen', 'Zweihandschlagwaffen', 'Zweihandschwerter']
    ];

    // Wenn keine Einschränkungen definiert sind, gib die Fertigkeiten unverändert zurück
    if (!isset($restrictions[$race])) {
        return $fertigkeiten;
    }

    // Filtere die Fertigkeiten basierend auf den Einschränkungen
    return array_filter($fertigkeiten, function ($fertigkeit) use ($restrictions, $race) {
        return !in_array($fertigkeit['name'], $restrictions[$race]);
    });
}

// Fertigkeiten aus der Tabelle fertigkeiten laden
$fertigkeiten = [];
$result_fertigkeiten = $conn->query("SELECT * FROM fertigkeiten WHERE startfertigkeit = 1");
if ($result_fertigkeiten) {
    while ($row = $result_fertigkeiten->fetch_assoc()) {
        $fertigkeiten[] = $row;
    }
}

// Zusätzliche LE für den Stand des Charakters
$zusatz_le = getStepData(2)['zusatz_le'] ?? 2;

// POST-Logik: Fertigkeit kaufen oder rückgängig machen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['fertigkeit_id'])) {
        $fertigkeit_id = (int)$_POST['fertigkeit_id'];
        $fertigkeit = array_filter($fertigkeiten, fn($f) => $f['id'] == $fertigkeit_id);

        if ($fertigkeit) {
            $fertigkeit = array_values($fertigkeit)[0]; // Das passende Array-Element holen

            // Prüfen, ob die Fertigkeit bereits gekauft wurde
            $gekaufte_fertigkeiten = getStepData(3)['fertigkeiten'] ?? [];
            $bereits_gekauft = array_filter($gekaufte_fertigkeiten, fn($f) => $f['id'] == $fertigkeit_id);

            if ($bereits_gekauft) {
                $log = getStepData(3)['log'] ?? [];
                $log[] = "Die Fertigkeit {$fertigkeit['name']} wurde bereits gekauft.";
                saveStepData(3, ['log' => $log]);
            } else {
                $bereich_id = $fertigkeit['bereich_id'];
                $verfuegbare_le = $lernpunkte[$bereich_id] ?? 0;

                // Prüfen, ob genügend Lernpunkte im Bereich oder durch Zusatzpunkte verfügbar sind
                if ($fertigkeit['start_le'] <= $verfuegbare_le || ($fertigkeit['start_le'] <= $verfuegbare_le + $zusatz_le)) {
                    // Lernpunkte reduzieren
                    $lernpunkte[$bereich_id] = max(0, $lernpunkte[$bereich_id] - $fertigkeit['start_le']);

                    // Zusätzliche Punkte anpassen, falls notwendig
                    if ($fertigkeit['start_le'] > $verfuegbare_le) {
                        $zusatz_le -= ($fertigkeit['start_le'] - $verfuegbare_le);
                    }

                    // Lernpunkte in der Session speichern
                    $_SESSION['character_creation']['lernpunkte'] = $lernpunkte;

                    // Fertigkeit zur Session hinzufügen
                    $gekaufte_fertigkeiten[] = $fertigkeit;
                    saveStepData(3, ['fertigkeiten' => $gekaufte_fertigkeiten]);

                    $log = getStepData(3)['log'] ?? [];
                    $log[] = "Fertigkeit {$fertigkeit['name']} gekauft.";
                    saveStepData(3, ['log' => $log]);
                } else {
                    $log = getStepData(3)['log'] ?? [];
                    $log[] = "Nicht genügend LE für {$fertigkeit['name']}.";
                    saveStepData(3, ['log' => $log]);
                }
            }
        }
    }

    if (isset($_POST['undo_fertigkeit_id'])) {
        $fertigkeit_id = (int)$_POST['undo_fertigkeit_id'];
        $gekaufte_fertigkeiten = getStepData(3)['fertigkeiten'] ?? [];

        // Rückgängig machen, falls die Fertigkeit existiert
        $fertigkeit_key = array_search($fertigkeit_id, array_column($gekaufte_fertigkeiten, 'id'));
        if ($fertigkeit_key !== false) {
            $fertigkeit = $gekaufte_fertigkeiten[$fertigkeit_key];
            unset($gekaufte_fertigkeiten[$fertigkeit_key]);
            saveStepData(3, ['fertigkeiten' => array_values($gekaufte_fertigkeiten)]);

            // Lernpunkte zurückerstatten
            $lernpunkte[$fertigkeit['bereich_id']] += $fertigkeit['start_le'];

            // Lernpunkte in der Session speichern
            $_SESSION['character_creation']['lernpunkte'] = $lernpunkte;

            $log = getStepData(3)['log'] ?? [];
            $log[] = "Kauf der Fertigkeit {$fertigkeit['name']} rückgängig gemacht.";
            saveStepData(3, ['log' => $log]);
        }
    }

    if (isset($_POST['confirm'])) {
        // Weiterleitung zu Schritt 4
        header('Location: step4.php');
        exit;
    }

    if (isset($_POST['back'])) {
        // Zurück zu Schritt 2
        header('Location: step2.php');
        exit;
    }
}

// Gekaufte und verfügbare Fertigkeiten trennen
$gekaufte_fertigkeiten = getStepData(3)['fertigkeiten'] ?? [];
$noch_verfuegbare_fertigkeiten = array_filter($fertigkeiten, fn($f) => !in_array($f, $gekaufte_fertigkeiten));

?>

<!DOCTYPE html>
<html>
<head>
    <title>Schritt 3: Kauf der Fertigkeiten</title>
</head>
<body>
    <h1>Schritt 3: Kauf der Fertigkeiten</h1>

    <form method="POST">
        <h2>Gekaufte Fertigkeiten</h2>
        <?php foreach ($gekaufte_fertigkeiten as $fertigkeit): ?>
            <div>
                <strong><?= htmlspecialchars($fertigkeit['name']) ?></strong>
                <button type="submit" name="undo_fertigkeit_id" value="<?= $fertigkeit['id'] ?>">Rückgängig</button>
            </div>
        <?php endforeach; ?>

        <h2>Noch verfügbare Fertigkeiten</h2>
        <?php foreach ($noch_verfuegbare_fertigkeiten as $fertigkeit): ?>
            <?php $bereich_id = $fertigkeit['bereich_id']; ?>
            <?php $verfuegbare_le = $lernpunkte[$bereich_id] ?? 0; ?>

            <?php if ($fertigkeit['start_le'] > $verfuegbare_le) continue; ?>

            <div>
                <strong><?= htmlspecialchars($fertigkeit['name']) ?></strong> (Bereich: <?= $bereich_id ?>, Kosten: <?= $fertigkeit['start_le'] ?> LE, Verfügbar: <?= $verfuegbare_le ?> LE)
                <button type="submit" name="fertigkeit_id" value="<?= $fertigkeit['id'] ?>">Kaufen</button>
            </div>
        <?php endforeach; ?>

        <button type="submit" name="back">Zurück</button>
        <button type="submit" name="confirm">Weiter</button>
    </form>

    <h2>Log</h2>
    <pre><?= print_r(getStepData(3)['log'] ?? [], true) ?></pre>
</body>
</html>
