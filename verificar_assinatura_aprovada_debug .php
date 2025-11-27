<?php
/**
 * API DEBUG para verificar assinaturas digitais aprovadas
 * Testa múltiplas variações do tipo para encontrar o formato correto
 */

// Headers CORS
header("Access-Control-Allow-Origin: https://sasapp.tec.br");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400");
header("Content-Type: application/json; charset=UTF-8");

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$response = new stdClass();

try {
    $codigo = $_POST['codigo'] ?? '';
    $tipo = $_POST['tipo'] ?? 'antecipação';
    
    if (empty($codigo)) {
        $response->success = false;
        $response->message = "Código do associado é obrigatório";
        $response->aprovada = false;
        echo json_encode($response);
        exit;
    }
    
    error_log("=== DEBUG VERIFICAR ASSINATURA APROVADA ===");
    error_log("Código: " . $codigo);
    error_log("Tipo solicitado: " . $tipo);
    
    // Primeiro, vamos ver todas as assinaturas para este código
    $sqlTodas = "SELECT 
                    id, 
                    codigo_associado, 
                    tipo, 
                    valor_aprovado, 
                    data_pgto, 
                    data_assinatura,
                    status
                FROM sind.assinaturas_digitais 
                WHERE codigo_associado = :codigo 
                ORDER BY data_assinatura DESC";
    
    $stmtTodas = $pdo->prepare($sqlTodas);
    $stmtTodas->bindParam(':codigo', $codigo, PDO::PARAM_STR);
    $stmtTodas->execute();
    
    $todasAssinaturas = $stmtTodas->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Total de assinaturas encontradas: " . count($todasAssinaturas));
    foreach ($todasAssinaturas as $ass) {
        error_log("Assinatura: ID={$ass['id']}, Tipo='{$ass['tipo']}', Valor Aprovado='{$ass['valor_aprovado']}', Data Pgto='{$ass['data_pgto']}'");
    }
    
    // Com base nos dados reais: tipo=1, valor_aprovado="$550.00", data_pgto=null
    // Vamos verificar todas as colunas da tabela para entender a estrutura
    $sqlColunas = "SELECT column_name, data_type, is_nullable 
                   FROM information_schema.columns 
                   WHERE table_schema = 'sind' 
                   AND table_name = 'assinaturas_digitais' 
                   ORDER BY ordinal_position";
    
    $stmtColunas = $pdo->prepare($sqlColunas);
    $stmtColunas->execute();
    $colunas = $stmtColunas->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Estrutura da tabela assinaturas_digitais:");
    foreach ($colunas as $col) {
        error_log("Coluna: {$col['column_name']} | Tipo: {$col['data_type']} | Nullable: {$col['is_nullable']}");
    }
    
    // OPÇÃO 1: Considerar aprovada apenas com valor_aprovado > 0 (sem verificar data_pgto)
    $sql = "SELECT 
                id, 
                codigo_associado, 
                tipo, 
                valor_aprovado, 
                data_pgto, 
                data_hora as data_assinatura,
                has_signed
            FROM sind.assinaturas_digitais 
            WHERE codigo_associado = :codigo 
            AND valor_aprovado IS NOT NULL 
            AND valor_aprovado != '' 
            AND valor_aprovado != '0'
            AND valor_aprovado != '0.00'
            AND valor_aprovado != '$0.00'
            ORDER BY data_hora DESC 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
    $stmt->execute();
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Query executada: " . $sql);
    error_log("Resultado da consulta: " . print_r($resultado, true));
    
    $encontrouAprovada = false;
    $assinaturaAprovada = null;
    
    if ($resultado) {
        // Extrair valor numérico do valor_aprovado (remover $ e converter)
        $valorLimpo = str_replace(['$', ','], '', $resultado['valor_aprovado']);
        $valorNumerico = floatval($valorLimpo);
        
        error_log("Valor aprovado original: " . $resultado['valor_aprovado']);
        error_log("Valor numérico extraído: " . $valorNumerico);
        
        // OPÇÃO 1: Considerar aprovada apenas com valor_aprovado > 0
        if ($valorNumerico > 0) {
            $encontrouAprovada = true;
            $assinaturaAprovada = $resultado;
            error_log("✅ ANTECIPAÇÃO APROVADA! Valor: " . $valorNumerico);
            error_log("✅ Critério: apenas valor_aprovado preenchido (conforme solicitado)");
        } else {
            error_log("❌ Valor aprovado é zero ou inválido: " . $resultado['valor_aprovado']);
        }
    } else {
        error_log("❌ Nenhuma assinatura encontrada com valor_aprovado preenchido");
    }
    
    if ($encontrouAprovada && $assinaturaAprovada) {
        $response->success = true;
        $response->aprovada = true;
        $response->valor_aprovado = $assinaturaAprovada['valor_aprovado'];
        $response->data_pgto = $assinaturaAprovada['data_pgto'];
        $response->data_assinatura = $assinaturaAprovada['data_assinatura'];
        $response->tipo = $assinaturaAprovada['tipo'];
        $response->message = "Antecipação aprovada encontrada";
        $response->debug = [
            'total_assinaturas' => count($todasAssinaturas),
            'tipos_testados' => $tiposParaTestar,
            'tipo_encontrado' => $assinaturaAprovada['tipo']
        ];
        
        error_log("✅ Retornando aprovação TRUE para código: " . $codigo);
    } else {
        $response->success = true;
        $response->aprovada = false;
        $response->message = "Nenhuma antecipação aprovada encontrada";
        $response->debug = [
            'total_assinaturas' => count($todasAssinaturas),
            'tipos_testados' => $tiposParaTestar,
            'todas_assinaturas' => $todasAssinaturas
        ];
        
        error_log("❌ Nenhuma antecipação aprovada para código: " . $codigo);
    }
    
} catch (Exception $e) {
    error_log("Erro ao verificar assinatura aprovada: " . $e->getMessage());
    
    $response->success = false;
    $response->message = "Erro ao verificar aprovação: " . $e->getMessage();
    $response->aprovada = false;
    $response->debug = [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
}

echo json_encode($response);
?>
