<?php
/**
 * Buscar Empregadores com Produção
 * Retorna lista de empregadores que possuem lançamentos de produção no mês/convênio selecionado
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../../php/banco.php';

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $mes_atual = $_POST['mes_atual'] ?? '';
    $cod_convenio = $_POST['cod_convenio'] ?? '';
    $divisao = $_POST['divisao'] ?? '';
    $parcela = $_POST['parcela'] ?? '';
    
    if (empty($mes_atual) || empty($cod_convenio) || empty($divisao)) {
        throw new Exception('Parâmetros obrigatórios não fornecidos');
    }
    
    // Query para buscar empregadores com produção no mês
    $sql = "SELECT DISTINCT 
                e.id, 
                e.nome,
                e.abreviacao
            FROM sind.empregador e
            INNER JOIN sind.conta c ON e.id = c.empregador
            INNER JOIN sind.convenio conv ON c.convenio = conv.codigo
            WHERE c.mes = :mes_atual
              AND conv.codigo = :cod_convenio
              AND e.id_divisao = :divisao
              AND (c.aprovado = true OR c.aprovado IS NULL)";
    
    // Adicionar filtro de parcela se fornecido
    if (!empty($parcela)) {
        $sql .= " AND LEFT(c.parcela, 2) = :parcela";
    }
    
    $sql .= " ORDER BY e.nome ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':mes_atual', $mes_atual, PDO::PARAM_STR);
    $stmt->bindParam(':cod_convenio', $cod_convenio, PDO::PARAM_INT);
    $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    
    if (!empty($parcela)) {
        $stmt->bindParam(':parcela', $parcela, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $empregadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($empregadores, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
