<?php
// altera_senha_associado.php
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
if (!isset($_POST['cartao']) || !isset($_POST['senha'])) {
    echo json_encode(['status' => 'erro', 'erro' => 'Parâmetros obrigatórios: cartao, senha']);
    exit;
}

$cartao = $_POST['cartao'];
$senha = $_POST['senha'];

try {
    // Conexão com o banco de dados 
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar associado pelo número do cartão
    $stmtAssociado = $pdo->prepare("
        SELECT associado.codigo, associado.empregador
        FROM sind.associado 
        INNER JOIN sind.c_cartaoassociado 
        ON associado.codigo = c_cartaoassociado.cod_associado 
        AND associado.empregador = c_cartaoassociado.empregador
        WHERE c_cartaoassociado.cod_verificacao = :cartao
    ");
    $stmtAssociado->bindParam(':cartao', $cartao);
    $stmtAssociado->execute();
    
    if ($stmtAssociado->rowCount() == 0) {
        echo json_encode(['status' => 'erro', 'erro' => 'Associado não encontrado']);
        exit;
    }
    
    $associado = $stmtAssociado->fetch(PDO::FETCH_ASSOC);
    $codigo = $associado['codigo'];
    $empregador = $associado['empregador'];
    
    // Verificar se já existe uma senha para este associado
    $stmtSenha = $pdo->prepare("
        SELECT senha FROM sind.c_senhaassociado 
        WHERE cod_associado = :codigo AND id_empregador = :empregador
    ");
    $stmtSenha->bindParam(':codigo', $codigo);
    $stmtSenha->bindParam(':empregador', $empregador);
    $stmtSenha->execute();
    
    if ($stmtSenha->rowCount() > 0) {
        // Atualizar senha existente
        $stmtUpdate = $pdo->prepare("
            UPDATE sind.c_senhaassociado 
            SET senha = :senha 
            WHERE cod_associado = :codigo AND id_empregador = :empregador
        ");
        $stmtUpdate->bindParam(':senha', $senha);
        $stmtUpdate->bindParam(':codigo', $codigo);
        $stmtUpdate->bindParam(':empregador', $empregador);
        $stmtUpdate->execute();
    } else {
        // Inserir nova senha
        $stmtInsert = $pdo->prepare("
            INSERT INTO sind.c_senhaassociado (cod_associado, id_empregador, senha) 
            VALUES (:codigo, :empregador, :senha)
        ");
        $stmtInsert->bindParam(':codigo', $codigo);
        $stmtInsert->bindParam(':empregador', $empregador);
        $stmtInsert->bindParam(':senha', $senha);
        $stmtInsert->execute();
    }
    
    echo json_encode(['status' => 'alterado']);
} catch (PDOException $e) {
    error_log("Erro de banco de dados: " . $e->getMessage());
    echo json_encode(['status' => 'erro', 'erro' => "Erro no servidor: " . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    echo json_encode(['status' => 'erro', 'erro' => "Erro inesperado: " . $e->getMessage()]);
}
?>