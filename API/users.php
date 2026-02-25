<?php
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
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT u.id, u.nome, u.cognome, u.username, u.id_ruolo, r.nome as ruolo_nome, u.id_coordinatore 
                FROM utenti u 
                JOIN ruoli r ON u.id_ruolo = r.id 
                WHERE u.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
        }
    } else {
        $sql = "SELECT u.id, u.nome, u.cognome, u.username, u.id_ruolo, r.nome as ruolo_nome, u.id_coordinatore 
                FROM utenti u 
                JOIN ruoli r ON u.id_ruolo = r.id";
        $result = $conn->query($sql);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode($users);
    }
}

function handlePost($conn, $input) {
    // Basic validation
    if (!isset($input['nome'], $input['cognome'], $input['username'], $input['password'], $input['id_ruolo'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        return;
    }

    $nome = $input['nome'];
    $cognome = $input['cognome'];
    $username = $input['username'];
    $password = $input['password']; // Storing as plain text per requirements
    $id_ruolo = intval($input['id_ruolo']);
    $id_coordinatore = isset($input['id_coordinatore']) ? intval($input['id_coordinatore']) : null;

    $sql = "INSERT INTO utenti (nome, cognome, username, password, id_ruolo, id_coordinatore) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $nome, $cognome, $username, $password, $id_ruolo, $id_coordinatore);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(['message' => 'User created', 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Error creating user: ' . $conn->error]);
    }
}

function handlePut($conn, $input) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'ID is required for update']);
        return;
    }
    
    $id = intval($_GET['id']);
    $updates = [];
    $types = "";
    $params = [];

    // Fields to update
    $fields = ['nome' => 's', 'cognome' => 's', 'username' => 's', 'password' => 's', 'id_ruolo' => 'i', 'id_coordinatore' => 'i'];

    foreach ($fields as $field => $type) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $types .= $type;
            $params[] = $input[$field];
        }
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['message' => 'No fields to update']);
        return;
    }

    $types .= "i";
    $params[] = $id;

    $sql = "UPDATE utenti SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'User updated']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Error updating user: ' . $conn->error]);
    }
}

function handleDelete($conn) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'ID is required for deletion']);
        return;
    }

    $id = intval($_GET['id']);
    $sql = "DELETE FROM utenti WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['message' => 'User deleted']);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Error deleting user: ' . $conn->error]);
    }
}
?>
