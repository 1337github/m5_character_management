<?php
require_once 'session_handler.php';
require_once 'db_connection.php';
require_once 'functions.php';

// Sicherstellen, dass die Session-Variablen für die Charaktererstellung verfügbar sind
if (!getStepData(3)) {
    die("Session-Daten sind nicht verfügbar. Bitte starte den Prozess erneut.");
}

// Daten aus der Session laden
$charakter = getStepData(1); // Schritt 1: Basiswerte
$gekaufteFertigkeiten = getStepData(3)['fertigkeiten'] ?? []; // Gekaufte Fertigkeiten aus Schritt 3

// Grundattribute laden
$grundattribute = $charakter['attributes'] ?? null;
if (empty($grundattribute)) {
    die("Grundattribute sind nicht in der Session gespeichert. Bitte überprüfe Schritt 1.");
}

// Sicherstellen, dass der Abenteuertyp existiert
if (!isset($charakter['abenteuertyp'])) {
    // Abenteuertyp-ID laden
    $abenteuertyp_id = $charakter['abenteuertyp_id'] ?? null;
    if ($abenteuertyp_id) {
        $query = "SELECT name FROM abenteuertypen WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $abenteuertyp_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $abenteuertypName = $row['name'];
            $charakter['abenteuertyp'] = $abenteuertypName; // In der Session speichern
            saveStepData(1, $charakter);
        } else {
            die("Abenteuertyp konnte nicht gefunden werden. Bitte überprüfe die Datenbank.");
        }
    } else {
        die("Abenteuertyp-ID fehlt. Bitte überprüfe Schritt 1.");
    }
} else {
    $abenteuertypName = $charakter['abenteuertyp'];
}

// Maximale Anzahl der Waffen basierend auf dem Abenteuertyp festlegen
$maxWeapons = getWeaponLimitByType($abenteuertypName, $conn);

// Waffenspezialisierungen laden
$waffen = [];
foreach ($gekaufteFertigkeiten as $fertigkeit) {
    $query = "SELECT * FROM weapons WHERE category = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $fertigkeit['name']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $waffen[] = $row;
    }
}

// Prüfen, ob die Mindestattribute erfüllt sind
function areAttributesSufficient($minimumAttribute, $attributes) {
    if (empty($minimumAttribute)) {
        return true;
    }

    $requirements = explode(",", $minimumAttribute);
    foreach ($requirements as $requirement) {
        preg_match('/([A-Za-z]+)(\d+)/', $requirement, $matches);
        if (!empty($matches)) {
            $attribute = $matches[1];
            $requiredValue = (int)$matches[2];
            if (($attributes[$attribute] ?? 0) < $requiredValue) {
                return false;
            }
        }
    }
    return true;
}

// POST-Logik: Waffenspezialisierung speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['selected_weapons'])) {
        $selectedWeapons = $_POST['selected_weapons'];

        // Überprüfen, ob die Anzahl der ausgewählten Waffen die Begrenzung überschreitet
        if (count($selectedWeapons) > $maxWeapons) {
            die("Du kannst maximal $maxWeapons Waffen auswählen.");
        }

        // Speichern der Waffenspezialisierungen in der Session
        saveStepData(4, ['waffenspezialisierungen' => $selectedWeapons]);

        // Weiterleitung zum nächsten Schritt
        header('Location: step5.php');
        exit;
    }

    if (isset($_POST['back'])) {
        // Zurück zu Schritt 3
        header('Location: step3.php');
        exit;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Schritt 4: Waffenspezialisierung</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }

        h1, h2 {
            color: #333;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            margin-bottom: 10px;
        }

        label {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        button {
            padding: 10px 20px;
            margin: 10px 5px;
            border: none;
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            border-radius: 4px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .info {
            font-size: 0.9em;
            color: #555;
        }

        .log {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 20px;
        }

        .disabled {
            color: #999;
        }

        .disabled input {
            pointer-events: none;
            opacity: 0.6;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const maxWeapons = <?= json_encode($maxWeapons) ?>;
            const checkboxes = document.querySelectorAll('input[name="selected_weapons[]"]');

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const selected = document.querySelectorAll('input[name="selected_weapons[]"]:checked');
                    if (selected.length > maxWeapons) {
                        alert(`Du kannst maximal ${maxWeapons} Waffen auswählen.`);
                        this.checked = false;
                    }
                });
            });
        });
    </script>
</head>
<body>
    <h1>Schritt 4: Waffenspezialisierung</h1>

    <form method="POST">
        <h2>Wähle deine Waffenspezialisierungen (maximal <?= htmlspecialchars($maxWeapons) ?>)</h2>
        <ul>
            <?php if (!empty($waffen)): ?>
                <?php foreach ($waffen as $waffe): ?>
                    <?php
                    $isSufficient = areAttributesSufficient($waffe['minimum_attribute'], $grundattribute);
                    ?>
                    <li class="<?= $isSufficient ? '' : 'disabled' ?>">
                        <label>
                            <input type="checkbox" name="selected_weapons[]" value="<?= htmlspecialchars($waffe['id']) ?>" <?= $isSufficient ? '' : 'disabled' ?>>
                            <div>
                                <strong><?= htmlspecialchars($waffe['name']) ?></strong><br>
                                <span class="info">Kategorie: <?= htmlspecialchars($waffe['category']) ?> | Schaden: <?= htmlspecialchars($waffe['damage']) ?></span>
                                <?php if (!$isSufficient): ?>
                                    <br><span class="info">Mindestattribute nicht erfüllt: <?= htmlspecialchars($waffe['minimum_attribute']) ?></span>
                                <?php endif; ?>
                            </div>
                        </label>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Keine verfügbaren Waffen gefunden.</p>
            <?php endif; ?>
        </ul>

        <div>
            <button type="submit" name="back">Zurück</button>
            <button type="submit">Weiter</button>
        </div>
    </form>

    <?php if (!empty(getStepData(4)['waffenspezialisierungen'] ?? [])): ?>
        <div class="log">
            <h3>Ausgewählte Waffenspezialisierungen:</h3>
            <ul>
                <?php foreach (getStepData(4)['waffenspezialisierungen'] as $weaponId): ?>
                    <li><?= htmlspecialchars($weaponId) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</body>
</html>
