<?php
/**
 * API PRENOTAZIONI (BOOKINGS) con logica di business completa
 * 
 * GET    /bookings.php             → Lista prenotazioni (filtrate per ruolo)
 * GET    /bookings.php?id=X        → Singola prenotazione
 * GET    /bookings.php?user_id=X   → Prenotazioni di un utente
 * GET    /bookings.php?date=Y      → Prenotazioni per data
 * GET    /bookings.php?asset_id=X  → Prenotazioni per asset
 * POST   /bookings.php             → Crea prenotazione (con controlli)
 * PUT    /bookings.php?id=X        → Modifica prenotazione (max 2 modifiche)
 * DELETE /bookings.php?id=X        → Cancella/revoca prenotazione (soft delete)
 */
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handleGet($conn);
        break;
    case 'POST':
        handlePost($conn, $input);
        break;
    case 'PUT':
        handlePut($conn, $input);
        break;
    case 'DELETE':
        handleDelete($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}

$conn->close();

// =====================================================================
// GET – Lista prenotazioni con visibilità per ruolo
// =====================================================================
function handleGet($conn) {
    $currentUser = requireAuth();
    $userId = $currentUser['id'];
    $ruolo = $currentUser['ruolo_nome'];

    // Query base con JOIN
    $baseSql = "SELECT p.id, p.id_utente, p.id_asset, p.data_prenotazione, 
                       p.ora_inizio, p.ora_fine, p.timestamp_creazione,
                       p.modifiche_counter, p.stato_prenotazione,
                       u.nome AS utente_nome, u.cognome AS utente_cognome, u.username,
                       a.codice_univoco, t.codice_tipo, t.descrizione AS tipo_descrizione
                FROM prenotazioni p
                JOIN utenti u ON p.id_utente = u.id
                JOIN assets a ON p.id_asset = a.id
                JOIN tipi_asset t ON a.id_tipo = t.id";

    // Singola prenotazione per ID
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "$baseSql WHERE p.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Verifica visibilità
            if (!canSeeBooking($conn, $currentUser, $row)) {
                http_response_code(403);
                echo json_encode(['message' => 'Non autorizzato a vedere questa prenotazione']);
                return;
            }
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Prenotazione non trovata']);
        }
        return;
    }

    // Filtri condizionali
    $conditions = [];
    $types = "";
    $params = [];

    // Filtro per utente specifico
    if (isset($_GET['user_id'])) {
        $conditions[] = "p.id_utente = ?";
        $types .= "i";
        $params[] = intval($_GET['user_id']);
    }

    // Filtro per data
    if (isset($_GET['date'])) {
        $conditions[] = "p.data_prenotazione = ?";
        $types .= "s";
        $params[] = $_GET['date'];
    }

    // Filtro per asset
    if (isset($_GET['asset_id'])) {
        $conditions[] = "p.id_asset = ?";
        $types .= "i";
        $params[] = intval($_GET['asset_id']);
    }

    // Filtro per stato
    if (isset($_GET['stato'])) {
        $conditions[] = "p.stato_prenotazione = ?";
        $types .= "s";
        $params[] = $_GET['stato'];
    }

    // VISIBILITÀ PER RUOLO
    switch ($ruolo) {
        case 'dipendente':
            // Vede solo le proprie prenotazioni
            $conditions[] = "p.id_utente = ?";
            $types .= "i";
            $params[] = $userId;
            break;
        case 'coordinatore':
            // Vede le proprie + quelle dei propri dipendenti
            $conditions[] = "(p.id_utente = ? OR p.id_utente IN (SELECT id FROM utenti WHERE id_coordinatore = ?))";
            $types .= "ii";
            $params[] = $userId;
            $params[] = $userId;
            break;
        case 'gestore':
            // Vede tutto – nessun filtro aggiuntivo
            break;
    }

    $sql = $baseSql;
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY p.data_prenotazione DESC, p.ora_inizio ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
}

// =====================================================================
// POST – Crea prenotazione con controlli di business
// =====================================================================
function handlePost($conn, $input) {
    $currentUser = requireAuth();
    $userId = $currentUser['id'];
    $ruolo = $currentUser['ruolo_nome'];

    // Validazione input
    if (!isset($input['id_asset'], $input['data_prenotazione'], $input['ora_inizio'], $input['ora_fine'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Campi obbligatori: id_asset, data_prenotazione, ora_inizio, ora_fine']);
        return;
    }

    $idAsset = intval($input['id_asset']);
    $data = $input['data_prenotazione'];
    $oraInizio = $input['ora_inizio'];
    $oraFine = $input['ora_fine'];

    // Il gestore può prenotare anche per un altro utente
    $targetUserId = $userId;
    if ($ruolo === 'gestore' && isset($input['id_utente'])) {
        $targetUserId = intval($input['id_utente']);
    }

    // 1. Verifica che l'asset esista e sia prenotabile
    $sqlAsset = "SELECT a.id, a.stato_attuale, a.id_tipo, t.codice_tipo 
                 FROM assets a JOIN tipi_asset t ON a.id_tipo = t.id 
                 WHERE a.id = ?";
    $stmt = $conn->prepare($sqlAsset);
    $stmt->bind_param("i", $idAsset);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();

    if (!$asset) {
        http_response_code(404);
        echo json_encode(['message' => 'Asset non trovato']);
        return;
    }

    if ($asset['stato_attuale'] === 'non_prenotabile') {
        http_response_code(400);
        echo json_encode(['message' => 'Questo asset non è attualmente prenotabile']);
        return;
    }

    // 2. Verifica permessi per tipo asset
    $tipoAsset = $asset['codice_tipo'];
    if ($ruolo === 'dipendente' && $tipoAsset === 'C') {
        http_response_code(403);
        echo json_encode(['message' => 'I dipendenti non possono prenotare posti auto (tipo C)']);
        return;
    }

    // 3. Verifica limite prenotazioni attive
    $sqlActiveCount = "SELECT COUNT(*) AS attive FROM prenotazioni 
                       WHERE id_utente = ? AND stato_prenotazione = 'attiva' AND data_prenotazione >= CURDATE()";
    $stmt = $conn->prepare($sqlActiveCount);
    $stmt->bind_param("i", $targetUserId);
    $stmt->execute();
    $activeCount = $stmt->get_result()->fetch_assoc()['attive'];

    // Recupera limite dal ruolo dell'utente target
    $sqlLimit = "SELECT r.max_prenotazioni FROM utenti u JOIN ruoli r ON u.id_ruolo = r.id WHERE u.id = ?";
    $stmt = $conn->prepare($sqlLimit);
    $stmt->bind_param("i", $targetUserId);
    $stmt->execute();
    $maxBookings = $stmt->get_result()->fetch_assoc()['max_prenotazioni'];

    if ($activeCount >= $maxBookings) {
        http_response_code(400);
        echo json_encode([
            'message' => "Limite prenotazioni raggiunto ($activeCount/$maxBookings attive)"
        ]);
        return;
    }

    // 4. Verifica disponibilità asset nella fascia oraria
    $sqlConflict = "SELECT id FROM prenotazioni 
                    WHERE id_asset = ? AND data_prenotazione = ? AND stato_prenotazione = 'attiva'
                    AND ora_inizio < ? AND ora_fine > ?";
    $stmt = $conn->prepare($sqlConflict);
    $stmt->bind_param("isss", $idAsset, $data, $oraFine, $oraInizio);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['message' => 'Asset già occupato in questa fascia oraria']);
        return;
    }

    // 5. Crea la prenotazione
    $sqlInsert = "INSERT INTO prenotazioni (id_utente, id_asset, data_prenotazione, ora_inizio, ora_fine) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sqlInsert);
    $stmt->bind_param("iisss", $targetUserId, $idAsset, $data, $oraInizio, $oraFine);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(['message' => 'Prenotazione creata', 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Errore nella creazione: ' . $conn->error]);
    }
}

// =====================================================================
// PUT – Modifica prenotazione (max 2 modifiche)
// =====================================================================
function handlePut($conn, $input) {
    $currentUser = requireAuth();

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'ID è obbligatorio per la modifica']);
        return;
    }
    
    $id = intval($_GET['id']);

    // Recupera prenotazione attuale
    $sqlBooking = "SELECT * FROM prenotazioni WHERE id = ?";
    $stmt = $conn->prepare($sqlBooking);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['message' => 'Prenotazione non trovata']);
        return;
    }

    // Solo il proprietario o il gestore possono modificare
    if ($booking['id_utente'] != $currentUser['id'] && $currentUser['ruolo_nome'] !== 'gestore') {
        http_response_code(403);
        echo json_encode(['message' => 'Non autorizzato a modificare questa prenotazione']);
        return;
    }

    if ($booking['stato_prenotazione'] !== 'attiva') {
        http_response_code(400);
        echo json_encode(['message' => 'Solo le prenotazioni attive possono essere modificate']);
        return;
    }

    // Controllo max 2 modifiche (non per il gestore)
    if ($booking['modifiche_counter'] >= 2 && $currentUser['ruolo_nome'] !== 'gestore') {
        http_response_code(400);
        echo json_encode(['message' => 'Limite massimo di 2 modifiche raggiunto per questa prenotazione']);
        return;
    }

    // Costruzione query dinamica
    $updates = ["modifiche_counter = modifiche_counter + 1"];
    $types = "";
    $params = [];

    $fields = ['id_asset' => 'i', 'data_prenotazione' => 's', 'ora_inizio' => 's', 'ora_fine' => 's'];

    foreach ($fields as $field => $type) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $types .= $type;
            $params[] = $input[$field];
        }
    }

    if (count($updates) <= 1) {
        http_response_code(400);
        echo json_encode(['message' => 'Nessun campo da modificare']);
        return;
    }

    // Se cambia asset o orario, verifica conflitti
    $newAsset = $input['id_asset'] ?? $booking['id_asset'];
    $newDate = $input['data_prenotazione'] ?? $booking['data_prenotazione'];
    $newStart = $input['ora_inizio'] ?? $booking['ora_inizio'];
    $newEnd = $input['ora_fine'] ?? $booking['ora_fine'];

    $sqlConflict = "SELECT id FROM prenotazioni 
                    WHERE id_asset = ? AND data_prenotazione = ? AND stato_prenotazione = 'attiva'
                    AND ora_inizio < ? AND ora_fine > ? AND id != ?";
    $stmt = $conn->prepare($sqlConflict);
    $stmt->bind_param("isssi", $newAsset, $newDate, $newEnd, $newStart, $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['message' => 'Conflitto: asset già occupato nella nuova fascia oraria']);
        return;
    }

    $types .= "i";
    $params[] = $id;

    $sql = "UPDATE prenotazioni SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $remainingMods = 2 - ($booking['modifiche_counter'] + 1);
        echo json_encode([
            'message' => 'Prenotazione modificata',
            'modifiche_rimanenti' => max(0, $remainingMods)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Errore nella modifica: ' . $conn->error]);
    }
}

// =====================================================================
// DELETE – Cancella o Revoca prenotazione (soft delete)
// =====================================================================
function handleDelete($conn) {
    $currentUser = requireAuth();

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'ID è obbligatorio per la cancellazione']);
        return;
    }

    $id = intval($_GET['id']);

    // Recupera prenotazione
    $sqlBooking = "SELECT * FROM prenotazioni WHERE id = ?";
    $stmt = $conn->prepare($sqlBooking);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['message' => 'Prenotazione non trovata']);
        return;
    }

    if ($booking['stato_prenotazione'] !== 'attiva') {
        http_response_code(400);
        echo json_encode(['message' => 'Solo le prenotazioni attive possono essere cancellate']);
        return;
    }

    // Determina se è cancellazione (utente) o revoca (gestore)
    if ($currentUser['ruolo_nome'] === 'gestore') {
        $nuovoStato = 'revocata';
        $messaggio = 'Prenotazione revocata dal gestore';
    } elseif ($booking['id_utente'] == $currentUser['id']) {
        $nuovoStato = 'cancellata';
        $messaggio = 'Prenotazione cancellata';
    } else {
        http_response_code(403);
        echo json_encode(['message' => 'Non autorizzato a cancellare questa prenotazione']);
        return;
    }

    // Soft delete: cambia stato
    $sql = "UPDATE prenotazioni SET stato_prenotazione = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuovoStato, $id);

    if ($stmt->execute()) {
        echo json_encode(['message' => $messaggio]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Errore nella cancellazione: ' . $conn->error]);
    }
}

// =====================================================================
// HELPER – Verifica visibilità prenotazione per ruolo
// =====================================================================
function canSeeBooking($conn, $currentUser, $booking) {
    $ruolo = $currentUser['ruolo_nome'];
    $userId = $currentUser['id'];

    switch ($ruolo) {
        case 'gestore':
            return true;
        case 'coordinatore':
            // Proprie + dipendenti assegnati
            if ($booking['id_utente'] == $userId) return true;
            $sql = "SELECT id FROM utenti WHERE id_coordinatore = ? AND id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $booking['id_utente']);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        case 'dipendente':
            return $booking['id_utente'] == $userId;
        default:
            return false;
    }
}
?>
