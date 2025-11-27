<?PHP
header('Content-Type: application/json');
ini_set('display_errors', true);
error_reporting(E_ALL);

include "../../php/banco.php";
include "../../php/funcoes.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se foi enviado um timestamp para comparação
    $lastCheck = isset($_POST['last_check']) ? $_POST['last_check'] : '1970-01-01 00:00:00';
    error_log("CHECK_NEW_DATA - Timestamp recebido: " . $lastCheck);
    
    // Buscar quantos registros novos há desde o último check
    $queryNewCount = "SELECT COUNT(*) as new_records_count
                      FROM sind.associados_sasmais 
                      WHERE data_hora > :last_check";
    
    error_log("CHECK_NEW_DATA - Query de contagem: " . $queryNewCount);
    error_log("CHECK_NEW_DATA - Parâmetro last_check: " . $lastCheck);
    
    $stmtNewCount = $pdo->prepare($queryNewCount);
    $stmtNewCount->bindParam(':last_check', $lastCheck, PDO::PARAM_STR);
    $stmtNewCount->execute();
    $newCountResult = $stmtNewCount->fetch(PDO::FETCH_ASSOC);
    
    error_log("CHECK_NEW_DATA - Novos registros encontrados: " . $newCountResult['new_records_count']);
    
    // Buscar a data/hora mais recente da tabela e total de registros
    $queryLatest = "SELECT MAX(data_hora) as latest_update, COUNT(*) as total_records
                    FROM sind.associados_sasmais";
    $stmtLatest = $pdo->prepare($queryLatest);
    $stmtLatest->execute();
    $latestResult = $stmtLatest->fetch(PDO::FETCH_ASSOC);
    
    error_log("CHECK_NEW_DATA - Latest update na tabela: " . $latestResult['latest_update']);
    error_log("CHECK_NEW_DATA - Total de registros: " . $latestResult['total_records']);
    
    $newRecordsCount = intval($newCountResult['new_records_count']);
    error_log("CHECK_NEW_DATA - Resultado final - has_new_data: " . ($newRecordsCount > 0 ? 'true' : 'false'));
    
    $response = [
        'has_new_data' => $newRecordsCount > 0,
        'latest_update' => $latestResult['latest_update'],
        'total_records' => $latestResult['total_records'],
        'new_records_count' => $newRecordsCount,
        'last_check' => $lastCheck,
        'current_time' => date('Y-m-d H:i:s')
    ];
    
    // Definir mensagem baseada no resultado
    if ($newRecordsCount > 0) {
        $response['message'] = "Novos dados encontrados! {$newRecordsCount} novo(s) registro(s).";
    } else {
        $response['message'] = 'Nenhum dado novo encontrado.';
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Erro ao verificar dados: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Erro geral: ' . $e->getMessage()
    ]);
}
?> 