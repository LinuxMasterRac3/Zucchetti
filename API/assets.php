<?php
/**
 * API ASSETS con filtri e stato derivato
 * 
 * GET    /assets.php              → Lista tutti gli asset
 * GET    /assets.php?id=X         → Singolo asset
 * GET    /assets.php?tipo=A       → Filtro per tipo (A, A2, B, C)
 * GET    /assets.php?stato=X      → Filtro per stato
 * GET    /assets.php?data=Y       → Stato derivato dalle prenotazioni attive per data
 * POST   /assets.php              → Crea asset (solo gestore)
 * PUT    /assets.php?id=X         → Modifica asset (solo gestore)
 * DELETE /assets.php?id=X         → Elimina asset (solo gestore)
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

function handleGet($conn) {
    $currentUser = requireAuth();

    // Singolo asset per ID
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT a.id, a.codice_univoco, a.id_tipo, t.codice_tipo, t.descrizione AS tipo_descrizione, a.stato_attuale 
                FROM assets a 
                JOIN tipi_asset t ON a.id_tipo = t.id 
                WHERE a.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Se richiesta data, aggiungi info prenotazione per quel giorno
            if (isset($_GET['data'])) {
                $row['prenotazione_attiva'] = getBookingForAssetOnDate($conn, $id, $_GET['data']);
            }
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Asset non trovato']);
        }
        return;
    }

    // Lista con filtri
    $sql = "SELECT a.id, a.codice_univoco, a.id_tipo, t.codice_tipo, t.descrizione AS tipo_descrizione, a.stato_attuale 
            FROM assets a 
            JOIN tipi_asset t ON a.id_tipo = t.id";
    
    $conditions = [];
    $types = "";
    $params = [];

    // Filtro per tipo asset (codice_tipo)
    if (isset($_GET['tipo'])) {
        $conditions[] = "t.codice_tipo = ?";
        $types .= "s";
        $params[] = $_GET['tipo'];
    }

    // Filtro per stato
    if (isset($_GET['stato'])) {
        $conditions[] = "a.stato_attuale = ?";
        $types .= "s";
        $params[] = $_GET['stato'];
    }

    // Filtro: dipendente non vede tipo C
    if ($currentUser['ruolo_nome'] === 'dipendente') {
        $conditions[] = "t.codice_tipo != 'C'";
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY t.codice_tipo ASC, a.codice_univoco ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $assets = [];
    
    $requestDate = $_GET['data'] ?? null;
    
    while ($row = $result->fetch_assoc()) {
        // Se richiesta una data, calcola stato "effettivo" dall'occupazione
        if ($requestDate) {
            $row['stato_effettivo'] = getEffectiveStatus($conn, $row['id'], $row['stato_attuale'], $requestDate);
        }
        $assets[] = $row;
    }
    echo json_encode($assets);
}

function handlePost($conn, $input) {
    $currentUser = requireAuth();
    
    // Solo gestore può creare asset
    if ($currentUser['ruolo_nome'] !== 'gestore') {
        http_response_code(403);
        echo json_encode(['message' => 'Solo il gestore può creare asset']);
        return;
    }

    if (!isset($input['codice_univoco'], $input['id_tipo'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Campi obbligatori: codice_univoco, id_tipo']);
        return;
    }

    $codice = $input['codice_univoco'];
    $tipo = intval($input['id_tipo']);
    $stato = $input['stato_attuale'] ?? 'disponibile';

    $sql = "INSERT INTO assets (codice_univoco, id_tipo, stato_attuale) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $codice, $tipo, $stato);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(['message' => 'Asset creato', 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Errore nella creazione: ' . $conn->error]);
    }
}

function handlePut($conn, $input) {
    $currentUser = requireAuth();

    // Solo gestore può modificare asset
    if ($currentUser['ruolo_nome'] !== 'gestore') {
        http_response_code(403);
        echo json_encode(['message' => 'Solo il gestore può modificare asset']);
        return;
    }

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'ID è obbligatorio per la modifica']);
        return;
    }
    
    $id = intval($_GET['id']);
    $updates = [];
    $types = "";
    $params = [];

    if (isset($input['codice_univoco'])) {
        $updates[] = "codice_univoco = ?";
        $types .= "s";
        $params[] = $input['codice_univoco'];
    }
    if (isset($input['id_tipo'])) {
        $updates[] = "id_tipo = ?";
        $types .= "i";
        $params[] = intval($input['id_tipo']);
    }
    if (isset($input['stato_attuale'])) {
        $updates[] = "stato_attuale = ?";
        $types .= "s";
        $params[] = $input['stato_attuale'];
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['message' => 'Nessun campo da modificare']);
        return;
    }

    $types .= "i";
    $params[] = $id;

    $sql = "UPDATE assets SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['message' => 'Asset modificato']);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Asset non trovato']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Errore nella modifica: ' . $conn->error]);
    }
}

function handleDelete($conn) {
    $currentUser = requireAuth();

    // Solo gestore può eliminare asset
    if ($currentUser['ruolo_nome'] !== 'gestore') {
        http_response_code(403);
        echo json_encode(['message' => 'Solo il gestore può eliminare asset']);
        return;
    }

    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'ID è obbligatorio per la cancellazione']);
        return;
    }

    $id = intval($_GET['id']);

    // Verifica che non ci siano prenotazioni attive
    $sqlCheck = "SELECT COUNT(*) AS attive FROM prenotazioni WHERE id_asset = ? AND stato_prenotazione = 'attiva'";
    $stmt = $conn->prepare($sqlCheck);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $activeCount = $stmt->get_result()->fetch_assoc()['attive'];

    if ($activeCount > 0) {
        http_response_code(400);
        echo json_encode(['message' => "Impossibile eliminare: ci sono $activeCount prenotazioni attive per questo asset"]);
        return;
    }

    $sql = "DELETE FROM assets WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['message' => 'Asset eliminato']);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Asset non trovato']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Errore nella cancellazione: ' . $conn->error]);
    }
}

// =====================================================================
// HELPER – Stato effettivo dell'asset per una data specifica
// =====================================================================
function getEffectiveStatus($conn, $assetId, $currentStatus, $date) {
    if ($currentStatus === 'non_prenotabile') {
        return 'non_prenotabile';
    }
    
    $sql = "SELECT COUNT(*) AS occupato FROM prenotazioni 
            WHERE id_asset = ? AND data_prenotazione = ? AND stato_prenotazione = 'attiva'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $assetId, $date);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['occupato'];
    
    return $count > 0 ? 'occupato' : 'disponibile';
}

// =====================================================================
// HELPER – Recupera prenotazione attiva per un asset in una data
// =====================================================================
function getBookingForAssetOnDate($conn, $assetId, $date) {
    $sql = "SELECT p.id, p.id_utente, u.nome, u.cognome, p.ora_inizio, p.ora_fine
            FROM prenotazioni p
            JOIN utenti u ON p.id_utente = u.id
            WHERE p.id_asset = ? AND p.data_prenotazione = ? AND p.stato_prenotazione = 'attiva'
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $assetId, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: null;
}
?>
