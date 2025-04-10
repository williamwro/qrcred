<?php
// valida_codigo_recuperacao.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-type: application/json");

// Se for uma solicitação OPTIONS, retorne apenas os cabeçalhos
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "Adm/php/banco.php";

// Garantir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'erro', 'erro' => 'Método não permitido']);
    exit;
}

// Verificar parâmetros obrigatórios
if (!isset($_POST['cartao']) || !isset($_POST['codigo'])) {
    echo json_encode(['status' => 'erro', 'erro' => 'Parâmetros obrigatórios: cartao, codigo']);
    exit;
}

$cartao = $_POST['cartao'];
$codigo = $_POST['codigo'];

try {
    // Conexão com o banco de dados 
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Limpar códigos expirados
    $stmtLimpar = $pdo->prepare("SELECT sind.limpar_codigos_expirados()");
    $stmtLimpar->execute();
    
    // Verificar se o código existe e é válido
    $stmt = $pdo->prepare("
        SELECT id FROM sind.codigos_recuperacao 
        WHERE cartao = :cartao AND codigo = :codigo 
        AND expirado = FALSE AND usado = FALSE
    ");
    $stmt->bindParam(':cartao', $cartao);
    $stmt->bindParam(':codigo', $codigo);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Código válido - marcar como usado
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $id = $row['id'];
        
        $stmtUpdate = $pdo->prepare("
            UPDATE sind.codigos_recuperacao 
            SET usado = TRUE 
            WHERE id = :id
        ");
        $stmtUpdate->bindParam(':id', $id);
        $stmtUpdate->execute();
        
        // Retornar sucesso
        echo json_encode(['status' => 'valido']);
    } else {
        // Verificar se o código existe mas já expirou
        $stmtExpirado = $pdo->prepare("
            SELECT expirado FROM sind.codigos_recuperacao 
            WHERE cartao = :cartao
        ");
        $stmtExpirado->bindParam(':cartao', $cartao);
        $stmtExpirado->execute();
        
        if ($stmtExpirado->rowCount() > 0) {
            $row = $stmtExpirado->fetch(PDO::FETCH_ASSOC);
            if ($row['expirado']) {
                echo json_encode(['status' => 'erro', 'erro' => 'Código expirado. Solicite um novo código.']);
            } else {
                echo json_encode(['status' => 'erro', 'erro' => 'Código inválido ou já utilizado.']);
            }
        } else {
            echo json_encode(['status' => 'erro', 'erro' => 'Nenhum código solicitado para este cartão.']);
        }
    }
} catch (PDOException $e) {
    error_log("Erro de banco de dados: " . $e->getMessage());
    echo json_encode(['status' => 'erro', 'erro' => "Erro no servidor: " . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    echo json_encode(['status' => 'erro', 'erro' => "Erro inesperado: " . $e->getMessage()]);
}
?>