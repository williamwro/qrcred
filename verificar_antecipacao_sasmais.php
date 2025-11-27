<?php
// Permitir acesso de qualquer origem
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Tratar requisição OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuração de exibição de erros
ini_set('display_errors', 0);
error_reporting(0);

include "Adm/php/banco.php";

$response = new stdClass();

try {
    // Obter dados da requisição
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['codigo'])) {
        $response->status = 'erro';
        $response->mensagem = 'Código do associado é obrigatório';
        echo json_encode($response);
        exit;
    }
    
    $codigo = trim($data['codigo']);
    $id         = isset($data['id']) ? (int)$data['id'] : null;
    $id_divisao = isset($data['id_divisao']) ? (int)$data['id_divisao'] : null;
    
    // Conectar ao banco
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
     // Reaproveitar o WHERE dinâmico adicionando filtros se informados
    if ($id !== null || $id_divisao !== null) {
        // Reconstruir SQL com filtros adicionais
        $sql = "SELECT 
                    id, codigo, nome, celular, data_hora, autorizado, 
                    aceitou_termo, event, doc_token, doc_name, signed_at, 
                    name, email, cpf, has_signed, cel_informado, limite, 
                    valor_aprovado, data_pgto, chave_pix, reprovado, tipo 
                FROM sind.associados_sasmais 
                WHERE codigo = :codigo ";
        if ($id !== null) {
            $sql .= " AND id_associado = :id_associado";
        }
        if ($id_divisao !== null) {
            $sql .= " AND id_divisao = :id_divisao";
        }
        $sql .= " AND tipo = 2 ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
        if ($id !== null) {
            $stmt->bindParam(':id_associado', $id, PDO::PARAM_INT);
        }
        if ($id_divisao !== null) {
            $stmt->bindParam(':id_divisao', $id_divisao, PDO::PARAM_INT);
        }
    }
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $response->status = 'sucesso';
        $response->encontrado = true;
        $response->mensagem = 'Registro de antecipação encontrado (tipo=2)';
        $response->dados = array(
            'id' => $result['id'],
            'codigo' => $result['codigo'],
            'nome' => $result['nome'],
            'celular' => $result['celular'],
            'data_hora' => $result['data_hora'],
            'autorizado' => $result['autorizado'],
            'aceitou_termo' => $result['aceitou_termo'],
            'event' => $result['event'],
            'doc_token' => $result['doc_token'],
            'doc_name' => $result['doc_name'],
            'signed_at' => $result['signed_at'],
            'name' => $result['name'],
            'email' => $result['email'],
            'cpf' => $result['cpf'],
            'has_signed' => $result['has_signed'],
            'cel_informado' => $result['cel_informado'],
            'limite' => $result['limite'],
            'valor_aprovado' => $result['valor_aprovado'],
            'data_pgto' => $result['data_pgto'],
            'chave_pix' => $result['chave_pix'],
            'reprovado' => $result['reprovado'],
            'tipo' => $result['tipo']
        );
        
        // Verificar critérios ESPECÍFICOS da antecipação
        $has_signed = $result['has_signed'];
        $tipo = $result['tipo'];
        
        // CRITÉRIO ESPECÍFICO PARA ANTECIPAÇÃO (tipo=2):
        // 1. has_signed = true
        // 2. tipo = 2
        $hasSignedValido = ($has_signed === true || $has_signed === 't' || $has_signed === '1' || $has_signed === 1);
        $tipoValido = (intval($tipo) === 2);
        
        $response->antecipacao_aprovada = ($hasSignedValido && $tipoValido);
        $response->criterios = array(
            'tipo_correto' => $tipoValido,
            'has_signed_ok' => $hasSignedValido,
            'tipo_encontrado' => intval($result['tipo']),
            'has_signed_valor' => $has_signed
        );
        
        // Log para debug
        error_log("ANTECIPAÇÃO DEBUG - Código: {$codigo}, Tipo: {$result['tipo']}, Signed: " . ($has_signed ? 'true' : 'false') . ", Aprovada: " . ($response->antecipacao_aprovada ? 'SIM' : 'NÃO'));
        
    } else {
        $response->status = 'sucesso';
        $response->encontrado = false;
        $response->mensagem = 'Nenhum registro de antecipação encontrado (tipo=2)';
        $response->dados = null;
        $response->antecipacao_aprovada = false;
        
        // Verificar se existe algum registro para debug
        $sqlDebug = "SELECT tipo, has_signed FROM sind.associados_sasmais WHERE codigo = :codigo ORDER BY id DESC";
        $stmtDebug = $pdo->prepare($sqlDebug);
        $stmtDebug->bindParam(':codigo', $codigo, PDO::PARAM_STR);
        $stmtDebug->execute();
        $registros = $stmtDebug->fetchAll(PDO::FETCH_ASSOC);
        
        $response->debug = array(
            'registros_encontrados' => count($registros),
            'tipos_existentes' => array_column($registros, 'tipo'),
            'has_signed_existentes' => array_column($registros, 'has_signed')
        );
        
        error_log("ANTECIPAÇÃO DEBUG - Código: {$codigo} - Nenhum tipo=2. Registros: " . json_encode($registros));
    }
    
} catch (Exception $e) {
    $response->status = 'erro';
    $response->mensagem = 'Erro interno: ' . $e->getMessage();
    $response->dados = null;
    $response->antecipacao_aprovada = false;
    error_log("ANTECIPAÇÃO ERRO: " . $e->getMessage());
}

echo json_encode($response);
?>
