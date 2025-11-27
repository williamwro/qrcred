<?php
/**
 * Versão de TESTE que sempre retorna aprovada=true para código 121212
 */

// Headers CORS
header("Access-Control-Allow-Origin: https://sasapp.tec.br");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$codigo = $_POST['codigo'] ?? '';

error_log("=== TESTE FORÇADO ANTECIPAÇÃO ===");
error_log("Código: " . $codigo);

// FORÇAR APROVADA=TRUE para código 121212 para testar o frontend
if ($codigo === '121212') {
    $response = [
        'success' => true,
        'aprovada' => true, // ✅ FORÇADO PARA TRUE
        'valor_aprovado' => '$550.00',
        'data_pgto' => '2025-01-15',
        'tipo' => 'antecipacao',
        'message' => "Antecipação FORÇADA como aprovada para teste",
        'debug' => [
            'teste_forcado' => true,
            'codigo' => $codigo
        ]
    ];
    
    error_log("✅ FORÇANDO aprovada=TRUE para código 121212");
} else {
    $response = [
        'success' => true,
        'aprovada' => false,
        'message' => "Teste apenas para código 121212"
    ];
}

echo json_encode($response);
?>
