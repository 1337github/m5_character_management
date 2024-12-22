<?php
// functions.php
// Diese Datei enthält alle Funktionen, die für die Charaktererstellung und Berechnungen benötigt werden.
// Funktionen sind thematisch sortiert und mit Kommentaren versehen.

/**
 * Würfelt eine Zufallszahl zwischen min und max.
 */
function rollDice($min, $max) {
    return rand($min, $max);
}

/**
 * Würfelt mehrmals und summiert die Ergebnisse.
 */
function rollMultipleDice($times, $sides) {
    $total = 0;
    for ($i = 0; $i < $times; $i++) {
        $total += rollDice(1, $sides);
    }
    return $total;
}

function createCharacter($race) {
    $attributes = [
        'St' => rollDice(1, 100),
        'Gs' => rollDice(1, 100),
        'Gw' => rollDice(1, 100),
        'Ko' => rollDice(1, 100),
        'In_attr' => rollDice(1, 100),
        'Zt' => rollDice(1, 100),
    ];

    // Setze "Au" basierend auf der Rasse
    switch ($race) {
        case 'Elf':
            $attributes['Au'] = rollDice(81, 100); // Elfen: Mindestens 81
            break;
        case 'Gnom':
        case 'Zwerg':
            $attributes['Au'] = rollDice(1, 80); // Gnome und Zwerge: Maximal 80
            break;
        default:
            $attributes['Au'] = rollDice(1, 100); // Standardfall
            break;
    }

    return $attributes;
}


/**
 * Validiert die Summe der Basiswerte eines Charakters.
 */
function validateAttributes($attributes) {
    $sum = array_sum($attributes);
    return $sum >= 350 ? true : $sum;
}

/**
 * Validiert die Attribute basierend auf der Rasse.
 *
 * @param array $attributes Array der Charakterattribute.
 * @param string $race Die gewählte Rasse.
 * @return true|string Gibt true zurück, wenn die Attribute gültig sind, andernfalls einen Fehlerstring.
 */
function validateAttributesByRace($attributes, $race) {
    if (!isset($attributes['Au'])) {
        return "Das Attribut 'Aussehen (Au)' fehlt.";
    }

    switch ($race) {
        case 'Elf':
            if ($attributes['Au'] < 81) {
                return "Elfen müssen ein Aussehen (Au) von mindestens 81 haben.";
            }
            break;
        case 'Gnom':
        case 'Zwerg':
            if ($attributes['Au'] > 80) {
                return "Gnome und Zwerge dürfen ein Aussehen (Au) von höchstens 80 haben.";
            }
            break;
    }

    return true;
}

/**
 * Berechnet das Alter eines Charakters.
 */
function calculateAge() {
    return rollDice(1, 6) + 17;
}

/**
 * Berechnet Größe und Gewicht basierend auf Rasse, Geschlecht und Stärke.
 */
function calculateGroesseGewicht($race, $geschlecht, $st) {
    $groesse = 0;
    $gewicht = 0;

    switch ($race) {
        case 'Mensch':
            if ($geschlecht == 'männlich') {
                $groesse = rollMultipleDice(2, 20) + floor($st / 10) + 150;
                $gewicht = rollMultipleDice(4, 6) + floor($st / 10) + $groesse - 120;
            } else {
                $groesse = rollMultipleDice(2, 20) + floor($st / 10) + 140;
                $gewicht = rollMultipleDice(4, 6) - 4 + floor($st / 10) + $groesse - 120;
            }
            break;
        case 'Elf':
            $groesse = rollMultipleDice(2, 6) + floor($st / 10) + 160;
            $gewicht = rollMultipleDice(4, 6) - 8 + floor($st / 10) + $groesse - 120;
            break;
        case 'Halbling':
            $groesse = rollMultipleDice(2, 6) + floor($st / 10) + 100;
            $gewicht = rollMultipleDice(3, 6) + 3 + floor($st / 10) + $groesse - 90;
            break;
        case 'Gnom':
            $groesse = rollDice(1, 6) + floor($st / 10) + 90;
            $gewicht = rollMultipleDice(3, 6) + floor($st / 10) + $groesse - 90;
            break;
        case 'Zwerg':
            $groesse = rollDice(1, 6) + floor($st / 10) + 130;
            $gewicht = rollMultipleDice(4, 6) + floor($st / 10) + $groesse - 90;
            break;
    }

    return ['groesse' => $groesse, 'gewicht' => $gewicht];
}

/**
 * Berechnet die Gestalt eines Charakters basierend auf Größe und Gewicht.
 */
function calculateGestalt($groesse, $gewicht) {
    $verhaeltnis = $gewicht / $groesse;
    if ($verhaeltnis < 0.4) {
        return 'schlank';
    } elseif ($verhaeltnis <= 0.6) {
        return 'normal';
    } else {
        return 'breit';
    }
}

/**
 * Berechnet die Händigkeit eines Charakters.
 */
function calculateHaendigkeit($race) {
    if ($race == 'Gnom') return "Beidhänder"; // Gnome sind immer beidhändig
    $roll = rollDice(1, 20);
    if ($roll <= 15) return "Rechtshänder";
    if ($roll <= 19) return "Linkshänder";
    return "Beidhänder";
}

/**
 * Berechnet die Lebenspunkte basierend auf der Rasse.
 */
function berechneLebenspunkte($race) {
    switch ($race) {
        case 'Gnom':
        case 'Halbling':
            return rollMultipleDice(2, 3) + 8;
        case 'Zwerg':
            return rollMultipleDice(3, 3) + 12;
        default:
            return rollMultipleDice(2, 6) + 10;
    }
}

/**
 * Berechnet die Ausdauerpunkte basierend auf dem Abenteurertyp.
 */
function berechneAusdauer($abenteuertyp) {
    if (in_array($abenteuertyp, ['Barbar', 'Krieger', 'Waldläufer'])) {
        return rollMultipleDice(1, 3) + 20;
    } elseif ($abenteuertyp == 'Schamane' || $abenteuertyp == 'andere Kämpfer') {
        return rollMultipleDice(1, 3) + 15;
    } else {
        return rollMultipleDice(1, 3) + 10;
    }
}

/**
 * Berechnet den sozialen Stand basierend auf dem Abenteurertyp.
 */
function calculateStand($abenteuertyp) {
    $modifikator = 0;
    if (in_array($abenteuertyp, ['Barde', 'Priester'])) {
        $modifikator = 20;
    } elseif (in_array($abenteuertyp, ['Druide', 'Magier'])) {
        $modifikator = 10;
    } elseif (in_array($abenteuertyp, ['Assassine', 'Händler', 'Waldläufer'])) {
        $modifikator = -10;
    } elseif ($abenteuertyp === 'Spitzbube') {
        $modifikator = -20;
    }

    $stand_wert = rollDice(1, 100) + $modifikator;

    if ($stand_wert <= 10) {
        return 'unfrei';
    } elseif ($stand_wert <= 50) {
        return 'Volk';
    } elseif ($stand_wert <= 90) {
        return 'Mittelschicht';
    } else {
        return 'Adel';
    }
}

/**
 * Berechnet die Waffenspezialisierung basierend auf dem Abenteurertyp.
 */
function getWeaponSpecialization($abenteuertyp, $waffen_liste) {
    if ($abenteuertyp === 'Krieger') {
        return implode(', ', array_rand(array_flip($waffen_liste), 3));
    } elseif (in_array($abenteuertyp, ['Kämpfer', 'Zauberkundiger Kämpfer'])) {
        return $waffen_liste[array_rand($waffen_liste)];
    }
    return null;
}

/**
 * Berechnet die Göttliche Gnade (Standard 0).
 */
function calculateGoettlicheGnade() {
    return 0;
}

/**
 * Berechnet die besonderen Fähigkeiten basierend auf der Rasse.
 */
function calculateSpecialAbility($race) {
    $abilities = [
        [1, 5, 'Sehen-2'],
        [6, 10, 'Hören-2'],
        [11, 15, 'Riechen-2'],
        [16, 20, 'Sechster Sinn+2'],
        [21, 30, 'Sehen+2'],
        [31, 40, 'Hören+2'],
        [41, 50, 'Riechen+2'],
        [51, 55, 'Nachtsicht+2'],
        [56, 60, 'Gute Reflexe+9'],
        [61, 65, 'Richtungssinn+12'],
        [66, 70, 'Robustheit+9'],
        [71, 75, 'Schmerzunempfindlichkeit+9'],
        [76, 80, 'Trinken+12'],
        [81, 85, 'Wachgabe+6'],
        [86, 90, 'Wahrnehmung+8'],
        [91, 95, 'Einprägen+4'],
        [96, 99, 'Berserkergang+(18–Wk/5)'],
        [100, 100, 'Freie Wahl und zweiter Wurf']
    ];

    $roll = rollDice(1, 100);

    foreach ($abilities as $ability) {
        if ($roll >= $ability[0] && $roll <= $ability[1]) {
            return $ability[2];
        }
    }
    return '';
}

/**
 * Berechnet den Schadensbonus (SB).
 */
function calculateSchadensbonus($attributes) {
    return floor($attributes['St'] / 20) + floor($attributes['Gs'] / 30) - 3;
}

/**
 * Berechnet die Bewegungsweite (B).
 */
function calculateBewegungsweite($race) {
    switch ($race) {
        case 'Gnom':
        case 'Halbling':
            return rollMultipleDice(2, 3) + 8;
        case 'Zwerg':
            return rollMultipleDice(3, 3) + 12;
        default:
            return rollMultipleDice(4, 3) + 16;
    }
}

/**
 * Berechnet den Angriffsbonus (AnB).
 */
function calculateAngriffsbonus($attributes) {
    if ($attributes['Gs'] >= 96) return 2;
    if ($attributes['Gs'] >= 81) return 1;
    if ($attributes['Gs'] <= 5) return -2;
    if ($attributes['Gs'] <= 20) return -1;
    return 0;
}

/**
 * Berechnet den Abwehrbonus (AbB).
 */
function calculateAbwehrbonus($attributes) {
    if ($attributes['Gw'] >= 96) return 2;
    if ($attributes['Gw'] >= 81) return 1;
    if ($attributes['Gw'] <= 5) return -2;
    if ($attributes['Gw'] <= 20) return -1;
    return 0;
}

/**
 * Berechnet den Zauberbonus (ZauB).
 */
function calculateZauberbonus($attributes) {
    if ($attributes['Zt'] >= 96) return 2;
    if ($attributes['Zt'] >= 81) return 1;
    if ($attributes['Zt'] <= 5) return -2;
    if ($attributes['Zt'] <= 20) return -1;
    return 0;
}

/**
 * Berechnet den Resistenzbonus (ResB) basierend auf Rasse und Klasse.
 */
function calculateResistenzbonus($attributes, $race, $class) {
    $resist_geist = 0;
    $resist_koerper = 0;

    if ($race == 'Mensch') {
        if ($attributes['In_attr'] >= 96) $resist_geist = 2;
        elseif ($attributes['In_attr'] >= 81) $resist_geist = 1;
        elseif ($attributes['In_attr'] <= 5) $resist_geist = -2;
        elseif ($attributes['In_attr'] <= 20) $resist_geist = -1;

        if ($attributes['Ko'] >= 96) $resist_koerper = 2;
        elseif ($attributes['Ko'] >= 81) $resist_koerper = 1;
        elseif ($attributes['Ko'] <= 5) $resist_koerper = -2;
        elseif ($attributes['Ko'] <= 20) $resist_koerper = -1;

        if (in_array($class, ['Magier', 'Hexer', 'Priester'])) $resist_geist += 2;
        elseif (in_array($class, ['Krieger', 'Barbar', 'Ordenskrieger', 'Waldläufer'])) $resist_koerper += 1;
    } else {
        switch ($race) {
            case 'Elf': $resist_geist = 2; $resist_koerper = 2; break;
            case 'Gnom':
            case 'Halbling': $resist_geist = 4; $resist_koerper = 4; break;
            case 'Zwerg': $resist_geist = 3; $resist_koerper = 3; break;
        }
    }

    return ['resist_geist' => $resist_geist, 'resist_koerper' => $resist_koerper];
}

/**
 * Berechnung der Fertigkeiten basierend auf Attributen, Boni und Rasse.
 *
 * @param array $attributes Array mit den Attributen des Charakters (Stärke, Geschicklichkeit, usw.).
 * @param int $abwehrbonus Der berechnete Abwehrbonus.
 * @param array $resistenzbonus Array mit den Widerstandswerten (Geist und Körper).
 * @param int $zauberbonus Der berechnete Zauberbonus.
 * @param int $angriffsbonus Der berechnete Angriffsbonus.
 * @param string $race Die Rasse des Charakters.
 * @param string $abenteuertyp Der Name des Abenteurertyps.
 * @param mysqli $conn Datenbankverbindung.
 * @return array Array mit allen berechneten Fertigkeiten.
 */
function calculateSkills($attributes, $abwehrbonus, $resistenzbonus, $zauberbonus, $angriffsbonus, $race, $abenteuertyp, $conn) {
    // Raufen basiert auf Stärke und Gewandtheit, modifiziert durch den Angriffsbonus
    $raufen = floor(($attributes['St'] + $attributes['Gw']) / 20) + $angriffsbonus;

    // Zwerge erhalten einen Bonus von +1 auf Raufen
    if ($race == 'Zwerg') {
        $raufen += 1;
    }

    // Trinken basiert auf der Konstitution
    $trinken = floor($attributes['Ko'] / 10);

    // Wahrnehmung hat einen festen Startwert
    $wahrnehmung = 6;

    // Zaubern: Basiswert und Boni berechnen
    $zaubern = 0;

    // Prüfen, ob der Abenteuertyp magisch ist
    $stmt = $conn->prepare("SELECT magic FROM character_type WHERE name = ?");
    $stmt->bind_param("s", $abenteuertyp);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && $row['magic'] == 1) {
        // Basiswert für magische Charaktere
        $zaubern = 11;
    }

    // Zauberbonus basierend auf Zt hinzufügen
    if ($attributes['Zt'] >= 96) $zaubern += 2;
    elseif ($attributes['Zt'] >= 81) $zaubern += 1;
    elseif ($attributes['Zt'] <= 20) $zaubern -= 1;
    elseif ($attributes['Zt'] <= 5) $zaubern -= 2;

    // Rückgabe aller berechneten Fertigkeiten
    return array(
        'Abwehr' => 11 + $abwehrbonus,                  // Startwert 11 + Abwehrbonus
        'Resistenz' => 11 + $resistenzbonus['resist_geist'], // Startwert 11 + Resistenzbonus gegen Geist
        'Zaubern' => $zaubern,                         // Berechneter Zauberwert
        'Raufen' => $raufen,                           // Berechnet auf Basis von Stärke und Gewandtheit
        'Trinken' => $trinken,                         // Basierend auf Konstitution
        'Wahrnehmung' => $wahrnehmung                  // Fester Wert
    );
}


// Funktion zum Abrufen des Abenteuertyp-Namens basierend auf der ID
function getAbenteuertypName($id, $conn) {
    $stmt = $conn->prepare("SELECT name FROM character_type WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['name'] ?? 'Unbekannt';
}

// ab hier Funktionen für step2

function getWeaponLimitByType($abenteuertypName, $conn) {
    // SQL-Abfrage, um die Klasse und den Namen des Abenteurertyps aus der Datenbank zu holen
    $stmt = $conn->prepare("SELECT klasse, name FROM character_type WHERE name = ?");
    $stmt->bind_param("s", $abenteuertypName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $klasse = $row['klasse'];
        $name = $row['name'];

        // Spezialisierungslimit basierend auf Klasse und Name
        if ($klasse === 'Kämpfer' && $name === 'Krieger') {
            return 3; // Krieger dürfen 3 Waffen wählen
        } elseif ($klasse === 'Kämpfer') {
            return 1; // Standardwert für alle Kämpfer
        } elseif ($klasse === 'Zauberkundiger Kämpfer') {
            return 1; // Zauberkundige Kämpfer dürfen 1 Waffe wählen
        } elseif ($klasse === 'Zauberer') {
            return 0; // Zauberer dürfen keine Spezialisierung wählen
        }
    }

    // Standardwert, falls keine spezifische Regel zutrifft
    return 0;
}

/**
 * Liefert alle verfügbaren Waffenfertigkeiten aus der Session basierend auf dem Bereich "Waffen".
 * Einschränkungen durch den Abenteuertyp werden berücksichtigt.
 *
 * @param string $abenteuertypName Name des Abenteurertyps
 * @param mysqli $conn Aktive Datenbankverbindung
 * @return array Liste der verfügbaren Waffenfertigkeiten
 */
function getAvailableWeapons($abenteuertypName, $conn) {
    // Bereich "Waffen" aus der Tabelle `fertigkeitsbereiche` abrufen
    $bereichQuery = "SELECT id FROM fertigkeitsbereiche WHERE bereich_name = 'Waffen'";
    $bereichResult = $conn->query($bereichQuery);

    if (!$bereichResult || $bereichResult->num_rows === 0) {
        return []; // Bereich "Waffen" nicht gefunden
    }

    $bereich = $bereichResult->fetch_assoc();
    $bereich_id = $bereich['id'];

    // Gekaufte Fertigkeiten aus der Session laden
    $gekaufte_fertigkeiten = getStepData(3)['fertigkeiten'] ?? [];

    // Einschränkungen basierend auf dem Abenteuertyp laden
    $waffen_einschraenkungen = getWeaponLimitByType($abenteuertypName, $conn) ?? [];
    if (!is_array($waffen_einschraenkungen)) {
        $waffen_einschraenkungen = []; // Sicherstellen, dass ein Array verwendet wird
    }

    // Gefilterte Waffenfertigkeiten zurückgeben
    return array_filter($gekaufte_fertigkeiten, function ($fertigkeit) use ($bereich_id, $waffen_einschraenkungen) {
        return $fertigkeit['bereich_id'] === $bereich_id && !in_array($fertigkeit['id'], $waffen_einschraenkungen);
    });
}

/**
 * Prüft, ob ein Abenteuertyp ein Zauberer oder zauberkundiger Kämpfer ist.
 *
 * @param int $abenteuertypId Die ID des Abenteurertyps.
 * @param mysqli $conn Die Datenbankverbindung.
 * @return bool Gibt true zurück, wenn der Charakter zaubern kann, sonst false.
 */
function isMagicUser($abenteuertypId, $conn) {
    $stmt = $conn->prepare("SELECT magic FROM character_type WHERE id = ?");
    $stmt->bind_param("i", $abenteuertypId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['magic'] == 1; // magic = 1 bedeutet Zauberer oder zauberkundiger Kämpfer
}



function getEquipmentFromDatabase() {
    global $conn;
    $sql = "SELECT id, name, price FROM equipment"; // Ersetze 'equipment' durch den tatsächlichen Tabellennamen
    $result = $conn->query($sql);
    if (!$result) {
        die("SQL-Fehler: " . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

?>
