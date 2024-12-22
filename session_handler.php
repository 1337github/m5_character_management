<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Speichert Daten für einen bestimmten Schritt
function saveStepData($step, $data) {
    if (!isset($_SESSION['character_creation'])) {
        $_SESSION['character_creation'] = [];
    }

    if (!isset($_SESSION['character_creation']["step_$step"])) {
        $_SESSION['character_creation']["step_$step"] = [];
    }

    $_SESSION['character_creation']["step_$step"] = array_merge($_SESSION['character_creation']["step_$step"], $data);
}

// Ruft Daten für einen bestimmten Schritt ab
function getStepData($step) {
    if (!isset($_SESSION['character_creation']["step_$step"])) {
        return [];
    }
    return $_SESSION['character_creation']["step_$step"];
}

// Löscht alle Charaktererstellungsdaten
function resetCharacterCreation() {
    unset($_SESSION['character_creation']);
}
?>
