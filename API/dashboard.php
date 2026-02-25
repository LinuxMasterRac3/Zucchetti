<?php
/**
 * API DASHBOARD
 * 
 * GET /dashboard.php → Restituisce informazioni utente e statistiche.
 * Richiede autenticazione.
 */
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit();
}

$currentUser = requireAuth();
$userId = $currentUser['id'];
$ruolo = $currentUser['ruolo_nome'];

// 1. Dati profilo completi
$sqlProfile = "SELECT u.id, u.nome, u.cognome, u.username, r.nome AS ruolo, r.max_prenotazioni,
                      c.nome AS coordinatore_nome, c.cognome AS coordinatore_cognome
               FROM utenti u
               JOIN ruoli r ON u.id_ruolo = r.id
               LEFT JOIN utenti c ON u.id_coordinatore = c.id
               WHERE u.id = ?";
$stmt = $conn->prepare($sqlProfile);
$stmt->bind_param("i", $userId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if ($profile['coordinatore_nome']) {
    $profile['coordinatore'] = $profile['coordinatore_nome'] . ' ' . $profile['coordinatore_cognome'];
} else {
    $profile['coordinatore'] = null;
}
unset($profile['coordinatore_nome'], $profile['coordinatore_cognome']);

// 2. Conteggio prenotazioni attive dell'utente
$sqlActiveCount = "SELECT COUNT(*) AS attive FROM prenotazioni 
                   WHERE id_utente = ? AND stato_prenotazione = 'attiva'";
$stmt = $conn->prepare($sqlActiveCount);
$stmt->bind_param("i", $userId);
$stmt->execute();
$activeCount = $stmt->get_result()->fetch_assoc()['attive'];

// 3. Prossime prenotazioni (da oggi in poi)
$sqlNext = "SELECT p.id, p.data_prenotazione, p.ora_inizio, p.ora_fine, p.stato_prenotazione,
                   p.modifiche_counter, a.codice_univoco, t.codice_tipo, t.descrizione AS tipo_descrizione
            FROM prenotazioni p
            JOIN assets a ON p.id_asset = a.id
            JOIN tipi_asset t ON a.id_tipo = t.id
            WHERE p.id_utente = ? AND p.stato_prenotazione = 'attiva' AND p.data_prenotazione >= CURDATE()
            ORDER BY p.data_prenotazione ASC, p.ora_inizio ASC
            LIMIT 10";
$stmt = $conn->prepare($sqlNext);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$nextBookings = [];
while ($row = $result->fetch_assoc()) {
    $nextBookings[] = $row;
}

// 4. Statistiche aggiuntive per gestore
$stats = null;
if ($ruolo === 'gestore') {
    // Totale utenti per ruolo
    $sqlUsersByRole = "SELECT r.nome AS ruolo, COUNT(u.id) AS totale
                       FROM utenti u JOIN ruoli r ON u.id_ruolo = r.id
                       GROUP BY r.nome";
    $result = $conn->query($sqlUsersByRole);
    $usersByRole = [];
    while ($row = $result->fetch_assoc()) {
        $usersByRole[$row['ruolo']] = intval($row['totale']);
    }

    // Assets per stato
    $sqlAssetsByStatus = "SELECT stato_attuale, COUNT(*) AS totale FROM assets GROUP BY stato_attuale";
    $result = $conn->query($sqlAssetsByStatus);
    $assetsByStatus = [];
    while ($row = $result->fetch_assoc()) {
        $assetsByStatus[$row['stato_attuale']] = intval($row['totale']);
    }

    // Prenotazioni oggi
    $sqlToday = "SELECT COUNT(*) AS totale FROM prenotazioni 
                 WHERE data_prenotazione = CURDATE() AND stato_prenotazione = 'attiva'";
    $todayCount = $conn->query($sqlToday)->fetch_assoc()['totale'];

    $stats = [
        'utenti_per_ruolo' => $usersByRole,
        'assets_per_stato' => $assetsByStatus,
        'prenotazioni_oggi' => intval($todayCount)
    ];
}

// 5. Dipendenti del coordinatore
$team = null;
if ($ruolo === 'coordinatore') {
    $sqlTeam = "SELECT u.id, u.nome, u.cognome, u.username FROM utenti u WHERE u.id_coordinatore = ?";
    $stmt = $conn->prepare($sqlTeam);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $team = [];
    while ($row = $result->fetch_assoc()) {
        $team[] = $row;
    }
}

$response = [
    'profile' => $profile,
    'prenotazioni_attive' => intval($activeCount),
    'prossime_prenotazioni' => $nextBookings
];

if ($stats !== null) {
    $response['statistiche'] = $stats;
}
if ($team !== null) {
    $response['team'] = $team;
}

echo json_encode($response);
$conn->close();
?>
