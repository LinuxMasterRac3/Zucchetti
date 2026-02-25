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
        $sql = "SELECT * FROM ruoli WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Role not found']);
        }
    } else {
        $sql = "SELECT * FROM ruoli";
        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data);
    }
}

function handlePost($conn, $input) {
    if (!isset($input['nome'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Name is required']);
        return;
    }

    $nome = $input['nome'];
    $descrizione = $input['descrizione'] ?? '';
    $max_prenotazioni = intval($input['max_prenotazioni'] ?? 1);

    $sql = "INSERT INTO ruoli (nome, descrizione, max_prenotazioni) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $nome, $descrizione, $max_prenotazioni);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(['message' => 'Role created', 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Error creating role: ' . $conn->error]);
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

    if (isset($input['nome'])) {
        $updates[] = "nome = ?";
        $types .= "s";
        $params[] = $input['nome'];
    }
    if (isset($input['descrizione'])) {
        $updates[] = "descrizione = ?";
        $types .= "s";
        $params[] = $input['descrizione'];
    }
    if (isset($input['max_prenotazioni'])) {
        $updates[] = "max_prenotazioni = ?";
        $types .= "i";
        $params[] = intval($input['max_prenotazioni']);
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['message' => 'No fields to update']);
        return;
    }

    $types .= "i";
    $params[] = $id;

    $sql = "UPDATE ruoli SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Role updated']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Error updating role: ' . $conn->error]);
    }
}

function handleDelete($conn) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'ID is required for deletion']);
        return;
    }

    $id = intval($_GET['id']);
    $sql = "DELETE FROM ruoli WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['message' => 'Role deleted']);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Role not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Error deleting role: ' . $conn->error]);
    }
}
?>
