<?php
header('Content-Type: application/json; charset=utf-8');
include("../Adm/php/banco.php");

$id_associado = isset($_POST['id_associado']) ? $_POST['id_associado'] : null;
$empregador_id = isset($_POST['empregador_id']) ? $_POST['empregador_id'] : null;

if (!$id_associado) {
    echo json_encode([
        'existe_pendente' => false,
        'message' => 'ID do associado não informado'
    ]);
    exit;
}

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se existe solicitação pendente (id_situacao = 1 ou NULL) para este associado
    $sql = "SELECT id, cod_verificacao, data_hora 
            FROM sind.solicitacao_bloqueio 
            WHERE id_associado = :id_associado 
            AND (id_situacao = 1 OR id_situacao IS NULL)
            ORDER BY data_hora DESC 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
    $stmt->execute();
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        // Formatar a data
        $data_formatada = date('d/m/Y H:i', strtotime($resultado['data_hora']));
        
        echo json_encode([
            'existe_pendente' => true,
            'cod_verificacao' => $resultado['cod_verificacao'],
            'data_solicitacao' => $data_formatada
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'existe_pendente' => false
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'existe_pendente' => false,
        'error' => true,
        'message' => 'Erro ao verificar: ' . $e->getMessage()
    ]);
}
?>
