<?php
// Avvia sessione per autenticazione
session_start();

// Gestione CORS — accetta sia Vue dev server (:5173) che Docker (:8080)
$allowed_origins = ['http://localhost:5173', 'http://localhost:8080'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:5173");
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Gestione preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Credenziali DB — variabili d'ambiente (Docker) con fallback (XAMPP)
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'z_volta_db';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

/**
 * Verifica che l'utente sia autenticato.
 * Restituisce i dati utente dalla sessione o termina con 401.
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['message' => 'Non autenticato. Effettuare il login.']);
        exit();
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'id_ruolo' => $_SESSION['id_ruolo'],
        'ruolo_nome' => $_SESSION['ruolo_nome']
    ];
}

/**
 * Verifica che l'utente abbia il ruolo richiesto.
 */
function requireRole($ruolo_nome) {
    $user = requireAuth();
    if ($user['ruolo_nome'] !== $ruolo_nome) {
        http_response_code(403);
        echo json_encode(['message' => 'Accesso negato. Ruolo richiesto: ' . $ruolo_nome]);
        exit();
    }
    return $user;
}
?>
