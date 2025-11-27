<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Incluir arquivo de conexão com banco (ajuste o caminho conforme sua estrutura)
require_once 'Adm/php/banco.php';

try {
    // Obter dados do POST
    $cod_associado = $_POST['cod_associado'] ?? '';
    $id_empregador = $_POST['id_empregador'] ?? '';
    
    // Validar dados obrigatórios
    if (empty($cod_associado) || empty($id_empregador)) {
        throw new Exception('Código do associado e ID do empregador são obrigatórios');
    }
    
    // Conectar ao banco PostgreSQL
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Preparar query de busca
    $sql = "SELECT 
        id,
        cod_associado,
        id_empregador,
        data_solicitacao,
        data_agendada,
        cod_convenio,
        status,
        profissional,
        especialidade,
        convenio_nome
    FROM sind.agendamento 
    WHERE cod_associado = :cod_associado 
    AND id_empregador = :id_empregador
    ORDER BY data_solicitacao DESC";
    
    $stmt = $pdo->prepare($sql);
    
    // Executar consulta
    $result = $stmt->execute([
        ':cod_associado' => $cod_associado,
        ':id_empregador' => $id_empregador
    ]);
    
    if ($result) {
        // Buscar todos os resultados
        $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log da consulta
        error_log("Agendamentos encontrados para associado {$cod_associado}: " . count($agendamentos));
        
        // Retornar sucesso com os dados
        echo json_encode([
            'success' => true,
            'data' => $agendamentos,
            'total' => count($agendamentos)
        ]);
    } else {
        throw new Exception('Erro ao buscar agendamentos no banco de dados');
    }
    
} catch (Exception $e) {
    error_log("Erro ao buscar agendamentos: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
