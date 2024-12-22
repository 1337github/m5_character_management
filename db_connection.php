<?php
// Fehlerberichterstattung aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verbindung zur Datenbank herstellen
$conn = new mysqli('sql745.your-server.de', 'anwart_1', 'Trdq8jyS16KR7524', 'anwart_db1');

// Fehlerbehandlung bei der Verbindung
if ($conn->connect_error) {
    die("Verbindung zur Datenbank fehlgeschlagen: " . $conn->connect_error);
}
?>
