<?php
header('Content-Type: application/json; charset=utf-8');
include "../../php/banco.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Receber dados do POST
    $id_empregador = isset($_POST['id_empregador']) ? (int)$_POST['id_empregador'] : 0;
    $id_associado = isset($_POST['id_associado']) ? (int)$_POST['id_associado'] : 0;
    $usuario_cod = isset($_POST['usuario_cod']) ? $_POST['usuario_cod'] : '';
    $divisao = isset($_POST['divisao']) ? (int)$_POST['divisao'] : 0;

    // Validar dados obrigatórios
    if ($id_associado == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ID do associado é obrigatório.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Buscar o número do cartão (cod_verificacao) da tabela sind.c_cartaoassociado
    $sqlCartao = "SELECT cod_verificacao 
                  FROM sind.c_cartaoassociado 
                  WHERE id_associado = :id_associado 
                  AND id_divisao = :divisao
                  ORDER BY id DESC 
                  LIMIT 1";
    
    $stmtCartao = $pdo->prepare($sqlCartao);
    $stmtCartao->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
    $stmtCartao->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $stmtCartao->execute();
    $cartao = $stmtCartao->fetch(PDO::FETCH_ASSOC);

    if (!$cartao || empty($cartao['cod_verificacao'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Associado não possui cartão cadastrado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $cod_verificacao = $cartao['cod_verificacao'];

    // Inserir na tabela sind.solicitacao_bloqueio
    $sql = "INSERT INTO sind.solicitacao_bloqueio (id_empregador, id_associado, cod_verificacao, data_hora, id_divisao) 
            VALUES (:id_empregador, :id_associado, :cod_verificacao, NOW(), :divisao)
            RETURNING id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_empregador', $id_empregador, PDO::PARAM_INT);
    $stmt->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
    $stmt->bindParam(':cod_verificacao', $cod_verificacao, PDO::PARAM_STR);
    $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_inserido = $result['id'];

    echo json_encode([
        'status' => 'success',
        'message' => 'Solicitação de bloqueio registrada com sucesso.',
        'id' => $id_inserido,
        'cod_verificacao' => $cod_verificacao
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao processar a solicitação: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
