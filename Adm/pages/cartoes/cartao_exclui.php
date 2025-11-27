<?php
header("Content-type: application/json");
require '../../php/banco.php';

$someArray = array();

try {
    if (!isset($_POST['cartao'])) {
        echo json_encode(['resultado' => 'erro', 'mensagem' => 'Parâmetro cartão não informado.']);
        exit;
    }

    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Garantir que os valores são do tipo correto
    $_cartao = isset($_POST['cartao']) ? trim((string)$_POST['cartao']) : '';
    $_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // IF de segurança: impedir exclusão sem parâmetros essenciais
    if (empty($_cartao) || $_id <= 0) {
        echo json_encode(['resultado' => 'erro_parametros', 'mensagem' => 'Parâmetros inválidos para exclusão (cartão/id obrigatórios).']);
        exit;
    }

    // Antes de excluir, verificar dependências em sind.conta e sind.antecipacao
    // Obter id_associado e id_divisao do cartão
    $sqlInfo = "SELECT id_associado, id_divisao FROM sind.c_cartaoassociado WHERE cod_verificacao = :cartao";
    $stmtInfo = $pdo->prepare($sqlInfo);
    $stmtInfo->bindParam(':cartao', $_cartao, PDO::PARAM_STR);
    $stmtInfo->execute();
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
    
    // Conversão explícita para int para evitar operações com strings
    $idAssociado = isset($info['id_associado']) ? (int)$info['id_associado'] : 0;
    $idDivisao = isset($info['id_divisao']) ? (int)$info['id_divisao'] : 0;

    // Contar quantos cartões o associado possui
    $totalCartoes = 0;
    if ($idAssociado > 0 && $idDivisao > 0) {
        $sqlCountCartoes = "SELECT COUNT(*) FROM sind.c_cartaoassociado WHERE id_associado = :id_associado AND id_divisao = :divisao";
        $stmtCountCartoes = $pdo->prepare($sqlCountCartoes);
        $stmtCountCartoes->execute([':id_associado' => $idAssociado, ':divisao' => $idDivisao]);
        $totalCartoes = (int)$stmtCountCartoes->fetchColumn();
    }

    // Verificar se existem lançamentos em outras tabelas
    $temConta = false;
    $temAntecipacao = false;
    
    if ($idAssociado > 0 && $idDivisao > 0) {
        $sqlConta = "SELECT 1 FROM sind.conta WHERE id_associado = :id_associado AND divisao = :divisao LIMIT 1";
        $stmtConta = $pdo->prepare($sqlConta);
        $stmtConta->execute([':id_associado' => $idAssociado, ':divisao' => $idDivisao]);
        $temConta = (bool)$stmtConta->fetchColumn();

        $sqlAnt = "SELECT 1 FROM sind.antecipacao WHERE id_associado = :id_associado AND divisao = :divisao LIMIT 1";
        $stmtAnt = $pdo->prepare($sqlAnt);
        $stmtAnt->execute([':id_associado' => $idAssociado, ':divisao' => $idDivisao]);
        $temAntecipacao = (bool)$stmtAnt->fetchColumn();
    }

    // Regra de negócio:
    // - Se o associado tem apenas 1 cartão E tem lançamentos: BLOQUEAR exclusão
    // - Se o associado tem mais de 1 cartão: PERMITIR exclusão mesmo com lançamentos
    if ($totalCartoes == 1 && ($temConta || $temAntecipacao)) {
        $someArray = array(
            'cartao' => $_cartao, 
            'resultado' => 'bloqueado_por_dependencias', 
            'mensagem' => 'Não é possível excluir o único cartão do associado pois existem lançamentos vinculados (conta/antecipação).'
        );
        echo json_encode($someArray);
        exit;
    }

    $sql = "DELETE FROM sind.c_cartaoassociado WHERE cod_verificacao = :cartao AND id_associado = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':cartao', $_cartao, PDO::PARAM_STR);
    $stmt->bindParam(':id', $_id, PDO::PARAM_INT);
    $stmt->execute();

    $someArray = array('cartao' => $_cartao, 'resultado' => 'excluido');
    
} catch (PDOException $e) {
    $someArray = array(
        'resultado' => 'erro_banco', 
        'mensagem' => 'Erro ao excluir cartão: ' . $e->getMessage()
    );
} catch (Exception $e) {
    $someArray = array(
        'resultado' => 'erro', 
        'mensagem' => 'Erro inesperado: ' . $e->getMessage()
    );
}

echo json_encode($someArray);





