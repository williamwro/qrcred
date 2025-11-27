
<?php
/**
 * API para verificar assinatura aprovada
 * Tabela correta: sind.associados_sasmais
 * Critério: has_signed = true E tipo = 2
 */

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$codigo = $_POST['codigo'] ?? '';

error_log("=== VERIFICAR ASSINATURA APROVADA ===");
error_log("Código: " . $codigo);

try {
    if (empty($codigo)) {
        echo json_encode(['success' => false, 'aprovada' => false, 'message' => 'Código obrigatório']);
        exit;
    }
    
    // Query na tabela correta: sind.associados_sasmais
    $sql = "SELECT id, codigo, nome, celular, data_hora, autorizado, aceitou_termo, event, doc_token, doc_name, 
                   signed_at, name, email, cpf, has_signed, cel_informado, limite, valor_aprovado, data_pgto, 
                   chave_pix, reprovado, tipo
            FROM sind.associados_sasmais 
            WHERE codigo = :codigo 
            ORDER BY data_hora DESC 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
    $stmt->execute();
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Resultado encontrado: " . print_r($resultado, true));
    
    $aprovada = false;
    
    if ($resultado && isset($resultado['has_signed']) && isset($resultado['tipo'])) {
        $hasSigned = $resultado['has_signed'];
        $tipo = $resultado['tipo'];
        
        error_log("Has signed: " . ($hasSigned ? 'true' : 'false'));
        error_log("Tipo: " . $tipo);
        
        // Verificar aprovação pelos campos has_signed = true E tipo = 2
        $hasSignedValido = ($hasSigned === true || $hasSigned === 't' || $hasSigned === '1' || $hasSigned === 1);
        $tipoValido = ($tipo == 2);
        
        if ($hasSignedValido && $tipoValido) {
            $aprovada = true;
            error_log("✅ ASSINATURA APROVADA! Has signed: " . $hasSigned . " e Tipo: " . $tipo);
        } else {
            error_log("❌ Assinatura não aprovada. Has signed: " . $hasSigned . " ou Tipo: " . $tipo . " não atende aos critérios");
        }
    } else {
        error_log("❌ Nenhum registro encontrado ou campos has_signed/tipo não existem");
    }
    
    $response = [
        'success' => true,
        'aprovada' => $aprovada,
        'message' => $aprovada ? 'Assinatura aprovada' : 'Assinatura não aprovada',
        'debug' => [
            'codigo' => $codigo,
            'registro_encontrado' => !!$resultado,
            'has_signed' => $resultado['has_signed'] ?? null,
            'tipo' => $resultado['tipo'] ?? null
        ]
    ];
    
    error_log("RESPOSTA FINAL: " . json_encode($response));
    
} catch (Exception $e) {
    error_log("ERRO: " . $e->getMessage());
    $response = [
        'success' => false,
        'aprovada' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>