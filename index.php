<?php
include 'session_handler.php'; // Session-Handling für Fortschritt und Zurücksetzen

// Falls "Reset" angeklickt wird, alle Daten zurücksetzen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    resetCharacterCreation();
    header('Location: index.php');
    exit;
}

// Überprüfen, ob bereits Daten für die Charaktererstellung vorliegen
$stepProgress = [
    'step1' => getStepData(1) ? '✔️' : '❌',
    'step2' => getStepData(2) ? '✔️' : '❌',
    'step3' => getStepData(3) ? '✔️' : '❌',
    'step4' => getStepData(4) ? '✔️' : '❌'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charaktererstellung</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .step-link { margin: 10px 0; display: block; }
        .status { margin-left: 10px; font-weight: bold; }
        .reset-button { margin-top: 20px; background-color: red; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .reset-button:hover { background-color: darkred; }
    </style>
</head>
<body>
    <h1>Charaktererstellung</h1>
    <p>Bitte wähle einen Schritt aus oder starte die Charaktererstellung.</p>

    <h2>Schritte:</h2>
    <ul>
        <li>
            <a class="step-link" href="step1.php">Schritt 1: Grundwerte und Boni</a>
            <span class="status"><?= $stepProgress['step1'] ?></span>
        </li>
        <li>
            <a class="step-link" href="step2.php">Schritt 2: Waffen-Spezialisierung</a>
            <span class="status"><?= $stepProgress['step2'] ?></span>
        </li>
        <li>
            <a class="step-link" href="step3.php">Schritt 3: Startfertigkeiten kaufen</a>
            <span class="status"><?= $stepProgress['step3'] ?></span>
        </li>
        <li>
            <a class="step-link" href="step4.php">Schritt 4: Ausrüstung kaufen</a>
            <span class="status"><?= $stepProgress['step4'] ?></span>
        </li>
        <li>
            <a class="step-link" href="details.php">Details: Charakterübersicht</a>
        </li>
    </ul>

    <form method="POST">
        <button type="submit" name="reset" class="reset-button">Charaktererstellung zurücksetzen</button>
    </form>
</body>
</html>
