<?php
/**
 * CAPTCHA ALGORITMICO
 * Genera un'operazione matematica semplice e salva il risultato in sessione.
 * Nessuna libreria terza (come da requisiti Zucchetti).
 * 
 * GET /captcha.php → restituisce { "question": "7 + 3 = ?", "session_id": "..." }
 */
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit();
}

// Genera due numeri casuali e un'operazione
$num1 = rand(1, 20);
$num2 = rand(1, 15);
$operations = ['+', '-', '×'];
$op = $operations[array_rand($operations)];

switch ($op) {
    case '+':
        $result = $num1 + $num2;
        break;
    case '-':
        // Assicura risultato positivo
        if ($num1 < $num2) {
            $temp = $num1;
            $num1 = $num2;
            $num2 = $temp;
        }
        $result = $num1 - $num2;
        break;
    case '×':
        $num1 = rand(2, 10);
        $num2 = rand(2, 10);
        $result = $num1 * $num2;
        break;
}

// Salva in sessione
$_SESSION['captcha_result'] = $result;
$_SESSION['captcha_time'] = time();

echo json_encode([
    'question' => "$num1 $op $num2 = ?",
    'session_id' => session_id()
]);
?>
