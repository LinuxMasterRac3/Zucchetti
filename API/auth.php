<?php

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

switch (true) {
    case ($method === 'GET' && $action === 'check'):
        handleSessionCheck();
        break;
    case ($method === 'POST' && $action === 'logout'):
        handleLogout();
        break;
    case ($method === 'POST' && $action === null):
        handleLogin($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}

$conn->close();

// =====================================================================
// HANDLERS
// =====================================================================

function handleLogin($conn) {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validazione campi obbligatori
    if (!isset($input['username'], $input['password'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Campi obbligatori: username, password']);
        return;
    }

    $username = trim($input['username']);
    $password = $input['password'];

    // Captcha è verificato lato client tramite DynamicCaptcha.vue (canvas-based)

    // 1. Validazione password (requisiti: 8+ char, maiusc, minusc, numeri, speciali)
    if (!validatePassword($password)) {
        http_response_code(400);
        echo json_encode([
            'message' => 'Password non conforme. Requisiti: minimo 8 caratteri, maiuscole, minuscole, numeri, caratteri speciali.'
        ]);
        return;
    }

    // 3. Verifica credenziali nel DB
    $sql = "SELECT u.id, u.nome, u.cognome, u.username, u.password, u.id_ruolo, u.id_coordinatore,
                   r.nome AS ruolo_nome, r.max_prenotazioni,
                   c.nome AS coordinatore_nome, c.cognome AS coordinatore_cognome
            FROM utenti u
            JOIN ruoli r ON u.id_ruolo = r.id
            LEFT JOIN utenti c ON u.id_coordinatore = c.id
            WHERE u.username = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verifica password (plain text come da requisiti – no crittografia)
        if ($password !== $row['password']) {
            http_response_code(401);
            echo json_encode(['message' => 'Credenziali errate.']);
            return;
        }

        // Login riuscito – salva in sessione
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['id_ruolo'] = $row['id_ruolo'];
        $_SESSION['ruolo_nome'] = $row['ruolo_nome'];
        $_SESSION['max_prenotazioni'] = $row['max_prenotazioni'];

        // Costruisci risposta
        $response = [
            'message' => 'Login effettuato con successo',
            'user' => [
                'id' => $row['id'],
                'nome' => $row['nome'],
                'cognome' => $row['cognome'],
                'username' => $row['username'],
                'ruolo' => $row['ruolo_nome'],
                'max_prenotazioni' => $row['max_prenotazioni'],
                'coordinatore' => null
            ]
        ];

        if ($row['coordinatore_nome']) {
            $response['user']['coordinatore'] = $row['coordinatore_nome'] . ' ' . $row['coordinatore_cognome'];
        }

        echo json_encode($response);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Credenziali errate.']);
    }
}

function handleSessionCheck() {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'authenticated' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'ruolo' => $_SESSION['ruolo_nome'],
                'max_prenotazioni' => $_SESSION['max_prenotazioni'] ?? 1
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'authenticated' => false,
            'message' => 'Sessione non attiva.'
        ]);
    }
}

function handleLogout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    echo json_encode(['message' => 'Logout effettuato con successo.']);
}

/**
 * Validazione password secondo i requisiti Zucchetti:
 * - Minimo 8 caratteri
 * - Almeno una maiuscola
 * - Almeno una minuscola
 * - Almeno un numero
 * - Almeno un carattere speciale
 */
function validatePassword($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) return false;
    return true;
}
?>
