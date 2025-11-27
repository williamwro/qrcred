<?php
header('Content-Type: application/json; charset=utf-8');
include "../../php/banco.php";
date_default_timezone_set('America/Sao_Paulo');

function converte_data($date) {
    return substr($date,6,4).'-'.substr($date,3,2).'-'.substr($date,0,2).' 00:00:00';
}

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $acao = isset($_POST['acao']) ? $_POST['acao'] : '';
    $usuario_global = isset($_POST['usuario_global']) ? $_POST['usuario_global'] : 'Sistema';

    if ($id == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ID da solicitação não informado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($acao !== 'aprovar' && $acao !== 'reprovar') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Ação inválida.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Buscar dados da solicitação com informações do cartão e associado
    $sqlBusca = "SELECT sb.id_associado, sb.divisao, sb.cod_verificacao,
                        ca.id as id_cartao, 
                        a.codigo as matricula, a.empregador as id_empregador
                 FROM sind.solicitacao_bloqueio sb
                 LEFT JOIN sind.c_cartaoassociado ca ON ca.id_associado = sb.id_associado AND ca.id_divisao = sb.divisao
                 LEFT JOIN sind.associado a ON a.id = sb.id_associado
                 WHERE sb.id = :id";
    $stmtBusca = $pdo->prepare($sqlBusca);
    $stmtBusca->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtBusca->execute();
    $solicitacao = $stmtBusca->fetch(PDO::FETCH_ASSOC);

    if (!$solicitacao) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Solicitação não encontrada.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();

    if ($acao === 'aprovar') {
        // Atualizar situação da solicitação para Aprovado (2)
        $sqlUpdate = "UPDATE sind.solicitacao_bloqueio 
                      SET id_situacao = 2, data_hora_resposta = NOW() 
                      WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        // Bloquear o cartão do associado (cod_situacaocartao = 2 = Bloqueado)
        if ($solicitacao['id_cartao']) {
            $sqlCartao = "UPDATE sind.c_cartaoassociado 
                          SET cod_situacaocartao = 2, data_pedido = :data_pedido
                          WHERE id = :id_cartao";
            $stmtCartao = $pdo->prepare($sqlCartao);
            $datax = date('Y-m-d');
            $stmtCartao->bindParam(':id_cartao', $solicitacao['id_cartao'], PDO::PARAM_INT);
            $stmtCartao->bindParam(':data_pedido', $datax, PDO::PARAM_STR);
            $stmtCartao->execute();

            // Preparar data e hora para o histórico
            $data2 = new DateTime();
            $data3 = $data2->format('d-m-Y');
            $data4 = new DateTime($data3);
            $data = $data4->format('d/m/Y');
            $data = converte_data($data);
            $hora = date("H:i:s");
            $hora = str_replace("00:00:00", $hora, $data);

            // Registrar no histórico de cartões
            $sql_historico = "INSERT INTO sind.c_historico_cartoes(matricula, cod_verificacao, cod_situacaocartao, data, hora, usuario, obs, id_empregador)
                              VALUES(:matricula, :cod_verificacao, :cod_situacao, :data, :hora, :usuario, :obs, :id_empregador)";
            $stmt_historico = $pdo->prepare($sql_historico);
            $stmt_historico->execute([
                ':matricula' => $solicitacao['matricula'],
                ':cod_verificacao' => $solicitacao['cod_verificacao'],
                ':cod_situacao' => 2, // Bloqueado
                ':data' => $data,
                ':hora' => $hora,
                ':usuario' => $usuario_global,
                ':obs' => 'Cartão bloqueado via solicitação de bloqueio aprovada',
                ':id_empregador' => $solicitacao['id_empregador']
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Bloqueio aprovado com sucesso. Cartão bloqueado.'
        ], JSON_UNESCAPED_UNICODE);

    } else {
        // Reprovar - apenas atualiza a situação para Reprovado (3)
        $sqlUpdate = "UPDATE sind.solicitacao_bloqueio 
                      SET id_situacao = 3, data_hora_resposta = NOW() 
                      WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Solicitação reprovada.'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao processar: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
