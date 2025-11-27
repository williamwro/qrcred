<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido'
    ]);
    exit();
}

try {
    // Incluir conexão com banco
    include "Adm/php/banco.php";
    
    // Conectar ao banco
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Validar parâmetros obrigatórios
    if (!isset($_POST['id']) || !isset($_POST['chave_pix'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Parâmetros obrigatórios não fornecidos (id, chave_pix)'
        ]);
        exit();
    }
    
    $id = $_POST['id'];
    $chave_pix = $_POST['chave_pix'];
    
    // Validar se o ID é numérico
    if (!is_numeric($id)) {
        echo json_encode([
            'success' => false,
            'error' => 'ID deve ser numérico'
        ]);
        exit();
    }
    
    // Validar se a chave PIX não está vazia
    if (empty(trim($chave_pix))) {
        echo json_encode([
            'success' => false,
            'error' => 'Chave PIX não pode estar vazia'
        ]);
        exit();
    }
    
    // Log dos dados recebidos
    error_log("Atualizando chave PIX - ID: $id, Nova chave: $chave_pix");
    
    // Verificar se o registro existe
    $stmt_check = $pdo->prepare("SELECT id FROM sind.antecipacao WHERE id = ?");
    $stmt_check->execute([$id]);
    
    if ($stmt_check->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Solicitação de antecipação não encontrada'
        ]);
        exit();
    }
    
    // Atualizar a chave PIX
    $stmt_update = $pdo->prepare("UPDATE sind.antecipacao SET chave_pix = ? WHERE id = ?");
    $resultado = $stmt_update->execute([$chave_pix, $id]);
    
    if ($resultado && $stmt_update->rowCount() > 0) {
        error_log("Chave PIX atualizada com sucesso - ID: $id");
        echo json_encode([
            'success' => true,
            'message' => 'Chave PIX atualizada com sucesso'
        ]);
    } else {
        error_log("Erro ao atualizar chave PIX - ID: $id");
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao atualizar chave PIX no banco de dados'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Erro de banco ao atualizar chave PIX: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro de banco de dados: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erro geral ao atualizar chave PIX: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>
