<?php
header('Content-Type: application/json; charset=utf-8');

// Incluir conexão com banco de dados
include "../Adm/php/banco.php";
    
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Receber dados do POST
    $id_solicitacao = isset($_POST['id_solicitacao']) ? intval($_POST['id_solicitacao']) : 0;
    $empregador_id = isset($_POST['empregador_id']) ? intval($_POST['empregador_id']) : 0;

    // Validar dados
    if ($id_solicitacao <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ID da solicitação inválido.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($empregador_id <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Empregador não identificado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verificar se a solicitação existe e pertence ao empregador
    $sql_verifica = "SELECT sb.id, sb.id_situacao, sb.cod_verificacao, sb.id_associado,
                            a.nome as nome_associado
                     FROM sind.solicitacao_bloqueio sb
                     INNER JOIN sind.associado a ON a.id = sb.id_associado
                     WHERE sb.id = :id_solicitacao 
                     AND sb.id_empregador = :empregador_id";
    
    $stmt_verifica = $pdo->prepare($sql_verifica);
    $stmt_verifica->bindParam(':id_solicitacao', $id_solicitacao, PDO::PARAM_INT);
    $stmt_verifica->bindParam(':empregador_id', $empregador_id, PDO::PARAM_INT);
    $stmt_verifica->execute();
    
    $solicitacao = $stmt_verifica->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitacao) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Solicitação não encontrada ou você não tem permissão para cancelá-la.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verificar se a solicitação está pendente (id_situacao = 1 ou NULL)
    if ($solicitacao['id_situacao'] != 1 && $solicitacao['id_situacao'] !== null) {
        $situacao_texto = '';
        if ($solicitacao['id_situacao'] == 2) {
            $situacao_texto = 'aprovada';
        } elseif ($solicitacao['id_situacao'] == 3) {
            $situacao_texto = 'reprovada';
        } else {
            $situacao_texto = 'processada';
        }
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Não é possível cancelar esta solicitação pois ela já foi ' . $situacao_texto . '.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Cancelar a solicitação (deletar o registro)
    $sql_delete = "DELETE FROM sind.solicitacao_bloqueio 
                   WHERE id = :id_solicitacao 
                   AND id_empregador = :empregador_id 
                   AND (id_situacao = 1 OR id_situacao IS NULL)";
    
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->bindParam(':id_solicitacao', $id_solicitacao, PDO::PARAM_INT);
    $stmt_delete->bindParam(':empregador_id', $empregador_id, PDO::PARAM_INT);
    $stmt_delete->execute();
    
    if ($stmt_delete->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Solicitação cancelada com sucesso.',
            'cod_verificacao' => $solicitacao['cod_verificacao'],
            'nome_associado' => $solicitacao['nome_associado']
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Não foi possível cancelar a solicitação. Tente novamente.'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro no banco de dados: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao processar: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
