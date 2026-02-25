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
        $sql = "SELECT * FROM tipi_asset WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Asset Type not found']);
        }
    } else {
        $sql = "SELECT * FROM tipi_asset";
        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data);
    }
}

function handlePost($conn, $input) {
    if (!isset($input['codice_tipo'], $input['descrizione'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Code and description are required']);
        return;
    }

    $codice = $input['codice_tipo'];
    $descrizione = $input['descrizione'];

    $sql = "INSERT INTO tipi_asset (codice_tipo, descrizione) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $codice, $descrizione);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(['message' => 'Asset Type created', 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Error creating asset type: ' . $conn->error]);
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

    if (isset($input['codice_tipo'])) {
        $updates[] = "codice_tipo = ?";
        $types .= "s";
        $params[] = $input['codice_tipo'];
    }
    if (isset($input['descrizione'])) {
        $updates[] = "descrizione = ?";
        $types .= "s";
        $params[] = $input['descrizione'];
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['message' => 'No fields to update']);
        return;
    }

    $types .= "i";
    $params[] = $id;

    $sql = "UPDATE tipi_asset SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Asset Type updated']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Error updating asset type: ' . $conn->error]);
    }
}

function handleDelete($conn) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'ID is required for deletion']);
        return;
    }

    $id = intval($_GET['id']);
    $sql = "DELETE FROM tipi_asset WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['message' => 'Asset Type deleted']);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Asset Type not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Error deleting asset type: ' . $conn->error]);
    }
}
?>
