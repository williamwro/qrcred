<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");
header("Content-type: application/json");
require 'Adm/php/banco.php';
date_default_timezone_set('America/Sao_Paulo');

// Obter parâmetros da requisição
$lancamento = isset($_POST['lancamento']) ? (int)$_POST['lancamento'] : 0;
$convenio = isset($_POST['convenio']) ? (int)$_POST['convenio'] : 0;
$associado = isset($_POST['associado']) ? $_POST['associado'] : '';
$data_estorno = isset($_POST['data']) ? $_POST['data'] : '';
$mes = isset($_POST['mes']) ? $_POST['mes'] : '';
$parcela = isset($_POST['parcela']) ? $_POST['parcela'] : '';

// Validar parâmetros obrigatórios
if ($lancamento === 0 || $convenio === 0 || empty($associado) || empty($data_estorno) || empty($mes)) {
    echo json_encode([
        'success' => false,
        'message' => 'Parâmetros inválidos. Lancamento, convênio, associado, data e mês são obrigatórios.'
    ]);
    exit;
}

try {
    // Conectar ao banco de dados
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Preparar a condição WHERE
    $whereConditions = [];
    $params = [];
    
    // Campos para identificação
    $whereConditions[] = "lancamento = :lancamento";
    $params[':lancamento'] = $lancamento;
    
    $whereConditions[] = "convenio = :convenio";
    $params[':convenio'] = $convenio;
    
    $whereConditions[] = "associado = :associado";
    $params[':associado'] = $associado;
    
    $whereConditions[] = "data_estorno = :data_estorno";
    $params[':data_estorno'] = $data_estorno;
    
    $whereConditions[] = "mes = :mes";
    $params[':mes'] = $mes;
    
    if (!empty($parcela)) {
        $whereConditions[] = "parcela = :parcela";
        $params[':parcela'] = $parcela;
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    // Verificar se o estorno existe antes de excluir
    $sqlSelect = "SELECT lancamento, associado, convenio, valor, data, hora, 
                 mes, empregador, parcela, data_estorno, hora_estorno
                 FROM sind.estornos 
                 WHERE $whereClause";
    
    $stmtSelect = $pdo->prepare($sqlSelect);
    foreach ($params as $key => $value) {
        $stmtSelect->bindValue($key, $value);
    }
    $stmtSelect->execute();
    
    $estornoDetails = $stmtSelect->fetch(PDO::FETCH_ASSOC);
    
    if (!$estornoDetails) {
        echo json_encode([
            'success' => false,
            'message' => 'Estorno não encontrado com os parâmetros fornecidos.'
        ]);
        exit;
    }
    
    // Registrar o log da operação antes de excluir
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $user = isset($_POST['user']) ? $_POST['user'] : 'App';
    
    $sqlLog = "INSERT INTO sind.log_operacoes (operacao, tabela, id_registro, data, hora, usuario, convenio)
              VALUES ('Exclusão de Estorno', 'estornos', :lancamento, :data, :hora, :usuario, :convenio)";
    
    $stmtLog = $pdo->prepare($sqlLog);
    $stmtLog->bindParam(':lancamento', $lancamento, PDO::PARAM_INT);
    $stmtLog->bindParam(':data', $date);
    $stmtLog->bindParam(':hora', $time);
    $stmtLog->bindParam(':usuario', $user);
    $stmtLog->bindParam(':convenio', $convenio, PDO::PARAM_INT);
    $stmtLog->execute();
    
    // Excluir o registro da tabela de estornos usando os mesmos parâmetros da seleção
    $sqlDelete = "DELETE FROM sind.estornos WHERE $whereClause";
    
    $stmtDelete = $pdo->prepare($sqlDelete);
    foreach ($params as $key => $value) {
        $stmtDelete->bindValue($key, $value);
    }
    $stmtDelete->execute();
    
    // Verificar se o registro foi excluído
    if ($stmtDelete->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Estorno excluído com sucesso.',
            'lancamento' => $lancamento
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Falha ao excluir o estorno. Nenhum registro foi afetado.'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir estorno: ' . $e->getMessage()
    ]);
}
?> 