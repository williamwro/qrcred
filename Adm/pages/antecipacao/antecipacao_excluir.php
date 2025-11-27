<?PHP
error_reporting(E_ALL ^ E_NOTICE);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

require "../../php/banco.php";
include "../../php/funcoes.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Receber dados via POST
    $id_antecipacao = $_POST['id_antecipacao'] ?? 0;
    $matricula = $_POST['matricula'] ?? '';
    $empregador = $_POST['empregador'] ?? 0;
    $mes = $_POST['mes'] ?? '';
    $associado_id = $_POST['associado_id'] ?? 0;
    $associado_id_divisao = $_POST['associado_id_divisao'] ?? 0;
    
    // Validar dados obrigatórios
    if (empty($id_antecipacao) || empty($matricula) || empty($empregador) || empty($mes)) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Dados incompletos para exclusão.'
        ]);
        exit();
    }
    
    // Log da operação
    error_log("EXCLUSÃO ANTECIPAÇÃO - ID: $id_antecipacao, Matrícula: $matricula, Empregador: $empregador, Mês: $mes, Associado ID: $associado_id, Divisão: $associado_id_divisao");
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // 1. Primeiro, remover da tabela sind.conta se existir
    $sql_delete_conta = "DELETE FROM sind.conta 
                         WHERE associado = :matricula 
                         AND mes = :mes 
                         AND empregador = :empregador
                         AND id_associado = :associado_id
                         AND divisao = :associado_id_divisao";
    
    $stmt_conta = $pdo->prepare($sql_delete_conta);
    $stmt_conta->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt_conta->bindParam(':mes', $mes, PDO::PARAM_STR);
    $stmt_conta->bindParam(':empregador', $empregador, PDO::PARAM_INT);
    $stmt_conta->bindParam(':associado_id', $associado_id, PDO::PARAM_INT);
    $stmt_conta->bindParam(':associado_id_divisao', $associado_id_divisao, PDO::PARAM_INT);
    $registros_conta = $stmt_conta->execute();
    $linhas_conta_removidas = $stmt_conta->rowCount();
    
    error_log("EXCLUSÃO CONTA - Registros removidos: $linhas_conta_removidas");
    
    // 2. Excluir da tabela sind.antecipacao
    $sql_delete_antecipacao = "DELETE FROM sind.antecipacao 
                               WHERE id = :id 
                               AND matricula = :matricula 
                               AND empregador = :empregador 
                               AND mes = :mes";
    
    $stmt_antecipacao = $pdo->prepare($sql_delete_antecipacao);
    $stmt_antecipacao->bindParam(':id', $id_antecipacao, PDO::PARAM_INT);
    $stmt_antecipacao->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt_antecipacao->bindParam(':empregador', $empregador, PDO::PARAM_INT);
    $stmt_antecipacao->bindParam(':mes', $mes, PDO::PARAM_STR);
    $resultado = $stmt_antecipacao->execute();
    $linhas_antecipacao_removidas = $stmt_antecipacao->rowCount();
    
    error_log("EXCLUSÃO ANTECIPAÇÃO - Registros removidos: $linhas_antecipacao_removidas");
    
    if ($linhas_antecipacao_removidas > 0) {
        // Commit da transação
        $pdo->commit();
        
        echo json_encode([
            'status' => 'sucesso',
            'mensagem' => 'Antecipação excluída com sucesso!',
            'detalhes' => [
                'antecipacao_removida' => $linhas_antecipacao_removidas,
                'conta_removida' => $linhas_conta_removidas
            ]
        ]);
        
        error_log("EXCLUSÃO SUCESSO - Antecipação ID $id_antecipacao excluída");
        
    } else {
        // Rollback se não encontrou o registro
        $pdo->rollBack();
        
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Registro de antecipação não encontrado ou já foi excluído.'
        ]);
        
        error_log("EXCLUSÃO ERRO - Registro não encontrado: ID $id_antecipacao");
    }
    
} catch (PDOException $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("ERRO PDO EXCLUSÃO: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro ao excluir antecipação: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    // Rollback em caso de erro geral
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("ERRO GERAL EXCLUSÃO: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro interno: ' . $e->getMessage()
    ]);
}
?>
