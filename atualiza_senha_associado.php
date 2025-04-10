<?php
include "Adm/php/banco.php";

// Cabeçalhos para permitir requisições de origens diferentes (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["erro" => "Método não permitido"]);
    exit;
}

// Obter dados da requisição
$cartao = isset($_POST['cartao']) ? preg_replace('/\D/', '', $_POST['cartao']) : "";
$nova_senha = isset($_POST['senha']) ? $_POST['senha'] : "";

// Validar dados obrigatórios
if (empty($cartao) || empty($nova_senha)) {
    echo json_encode(["erro" => "Dados incompletos"]);
    exit;
}

// Validar o tamanho mínimo da senha
if (strlen($nova_senha) < 4) {
    echo json_encode(["erro" => "A senha deve ter pelo menos 4 caracteres"]);
    exit;
}

// Validar o tamanho máximo da senha (conforme definido no banco como varchar(6))
if (strlen($nova_senha) > 6) {
    echo json_encode(["erro" => "A senha não pode ter mais que 6 caracteres"]);
    exit;
}

// Validar que a senha só contém dígitos numéricos
if (!preg_match('/^\d+$/', $nova_senha)) {
    echo json_encode(["erro" => "A senha deve conter apenas números"]);
    exit;
}

try {
    // Conexão com o banco de dados usando seu método existente
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar o registro do associado através do cartão
    $sql_cartao = "SELECT cod_associado, empregador 
                  FROM sind.c_cartaoassociado 
                  WHERE cod_verificacao = :cartao LIMIT 1";
    $stmt_cartao = $pdo->prepare($sql_cartao);
    $stmt_cartao->bindParam(':cartao', $cartao);
    $stmt_cartao->execute();
    
    if ($stmt_cartao->rowCount() == 0) {
        echo json_encode(["erro" => "Cartão não encontrado"]);
        exit;
    }
    
    $cartao_info = $stmt_cartao->fetch(PDO::FETCH_ASSOC);
    $matricula = $cartao_info['cod_associado'];
    $id_empregador = $cartao_info['empregador'];
    
    // Verificar se o associado passou pelo processo de validação
    $sql_validacao = "SELECT EXISTS (
        SELECT 1 FROM recuperacao_senha 
        WHERE cartao = :cartao 
        AND utilizado = TRUE
        AND expiracao > NOW() - INTERVAL '30 minutes'
    )";
    $stmt_validacao = $pdo->prepare($sql_validacao);
    $stmt_validacao->bindParam(':cartao', $cartao);
    $stmt_validacao->execute();
    $validacao_ok = $stmt_validacao->fetchColumn();
    
    if (!$validacao_ok) {
        echo json_encode(["erro" => "Processo de validação não concluído ou expirado"]);
        exit;
    }
    
    // Verificar se o registro existe na tabela de senhas
    $sql_verificar_senha = "SELECT cod FROM sind.c_senhaassociado 
                            WHERE cod_associado = :matricula AND id_empregador = :id_empregador LIMIT 1";
    $stmt_verificar_senha = $pdo->prepare($sql_verificar_senha);
    $stmt_verificar_senha->bindParam(':matricula', $matricula);
    $stmt_verificar_senha->bindParam(':id_empregador', $id_empregador);
    $stmt_verificar_senha->execute();
    
    $data_atual = date('d/m/Y');
    
    if ($stmt_verificar_senha->rowCount() > 0) {
        // Atualizar a senha existente
        $sql_atualizar = "UPDATE sind.c_senhaassociado 
                           SET senha = :senha, 
                               data = :data,
                               vezes = '0',
                               situacao = 0
                           WHERE cod_associado = :matricula AND id_empregador = :id_empregador";
    } else {
        // Inserir novo registro
        $sql_atualizar = "INSERT INTO sind.c_senhaassociado
                           (cod_associado, senha, vezes, data, situacao, id_empregador)
                           VALUES
                           (:matricula, :senha, '0', :data, 0, :id_empregador)";
    }
    
    $stmt_atualizar = $pdo->prepare($sql_atualizar);
    $stmt_atualizar->bindParam(':senha', $nova_senha);
    $stmt_atualizar->bindParam(':data', $data_atual);
    $stmt_atualizar->bindParam(':matricula', $matricula);
    $stmt_atualizar->bindParam(':id_empregador', $id_empregador);
    $stmt_atualizar->execute();
    
    if ($stmt_atualizar->rowCount() > 0) {
        // Limpar códigos de recuperação
        $sql_limpar = "DELETE FROM recuperacao_senha WHERE cartao = :cartao";
        $stmt_limpar = $pdo->prepare($sql_limpar);
        $stmt_limpar->bindParam(':cartao', $cartao);
        $stmt_limpar->execute();
        
        echo "atualizado"; // Resposta para sucesso conforme esperado pela aplicação
    } else {
        echo json_encode(["erro" => "Nenhuma alteração foi realizada"]);
    }
    
} catch (PDOException $e) {
    error_log("Erro na atualização de senha: " . $e->getMessage());
    echo json_encode(["erro" => "Erro no processamento da solicitação: " . $e->getMessage()]);
}
?>