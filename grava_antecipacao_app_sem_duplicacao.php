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
    // Verificar se todos os campos necessários foram enviados
    $campos_obrigatorios = ['matricula', 'pass', 'empregador', 'valor_pedido', 'taxa', 'valor_descontar', 'mes_corrente', 'chave_pix'];
    $campos_faltando = [];
    
    foreach ($campos_obrigatorios as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            $campos_faltando[] = $campo;
        }
    }
    
    if (!empty($campos_faltando)) {
        $response->success = false;
        $response->message = 'Campos obrigatórios faltando: ' . implode(', ', $campos_faltando);
        echo json_encode($response);
        exit;
    }
    
    // Obter dados dos campos
    $matricula = trim($_POST['matricula']);
    $pass = trim($_POST['pass']);
    $empregador = intval($_POST['empregador']);
    $valor_pedido = floatval($_POST['valor_pedido']);
    $taxa = floatval($_POST['taxa']);
    $valor_descontar = floatval($_POST['valor_descontar']);
    $mes_corrente = trim($_POST['mes_corrente']);
    $chave_pix = trim($_POST['chave_pix']);
    
    // Conectar ao banco
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Log da solicitação recebida
    error_log("ANTECIPAÇÃO RECEBIDA: matricula={$matricula}, empregador={$empregador}, valor={$valor_pedido}, mes={$mes_corrente}");
    
    // VERIFICAR DUPLICAÇÃO: Verificar se já existe uma solicitação idêntica nos últimos 2 minutos
    $sql_duplicacao = "SELECT id, data_solicitacao 
                      FROM sind.antecipacao 
                      WHERE matricula = :matricula 
                      AND empregador = :empregador 
                      AND valor = :valor_pedido 
                      AND mes = :mes_corrente
                      AND data_solicitacao > NOW() - INTERVAL '2 minutes'
                      ORDER BY data_solicitacao DESC 
                      LIMIT 1";
    
    $stmt_duplicacao = $pdo->prepare($sql_duplicacao);
    $stmt_duplicacao->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt_duplicacao->bindParam(':empregador', $empregador, PDO::PARAM_INT);
    $stmt_duplicacao->bindParam(':valor_pedido', $valor_pedido, PDO::PARAM_STR);
    $stmt_duplicacao->bindParam(':mes_corrente', $mes_corrente, PDO::PARAM_STR);
    $stmt_duplicacao->execute();
    
    $duplicacao = $stmt_duplicacao->fetch(PDO::FETCH_ASSOC);
    
    if ($duplicacao) {
        error_log("DUPLICAÇÃO DETECTADA: ID={$duplicacao['id']}, Data={$duplicacao['data_solicitacao']}");
        $response->success = true;
        $response->message = 'Sua solicitação já foi processada. Aguarde a análise.';
        $response->duplicate_prevented = true;
        $response->original_id = $duplicacao['id'];
        echo json_encode($response);
        exit;
    }
    
    // VERIFICAR SENHA: Validar a senha do associado e buscar id_divisao e id
    $sql_senha = "SELECT senha, id_divisao, id FROM sind.associado 
                  WHERE codigo = :matricula AND empregador = :empregador";
    
    $stmt_senha = $pdo->prepare($sql_senha);
    $stmt_senha->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt_senha->bindParam(':empregador', $empregador, PDO::PARAM_INT);
    $stmt_senha->execute();
    
    $associado = $stmt_senha->fetch(PDO::FETCH_ASSOC);
    
    if (!$associado) {
        $response->success = false;
        $response->message = 'Associado não encontrado.';
        echo json_encode($response);
        exit;
    }
    
    // Capturar id_divisao e id_associado
    $divisao = $associado['id_divisao'];
    $id_associado = $associado['id'];
    
    // Verificar senha (assumindo que pode ser MD5 ou texto limpo)
    $senha_correta = ($associado['senha'] === $pass || $associado['senha'] === md5($pass));
    
    if (!$senha_correta) {
        $response->success = false;
        $response->message = 'Senha incorreta.';
        echo json_encode($response);
        exit;
    }
    
    // INSERIR NOVA SOLICITAÇÃO
    $sql_insert = "INSERT INTO sind.antecipacao 
                   (matricula, empregador, mes, data_solicitacao, valor, aprovado, celular, valor_taxa, valor_a_descontar, chave_pix, id_divisao, id_associado) 
                   VALUES 
                   (:matricula, :empregador, :mes, NOW(), :valor, false, '', :taxa, :valor_descontar, :chave_pix, :divisao, :id_associado)
                   RETURNING id";
    
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt_insert->bindParam(':empregador', $empregador, PDO::PARAM_INT);
    $stmt_insert->bindParam(':mes', $mes_corrente, PDO::PARAM_STR);
    $stmt_insert->bindParam(':valor', $valor_pedido, PDO::PARAM_STR);
    $stmt_insert->bindParam(':taxa', $taxa, PDO::PARAM_STR);
    $stmt_insert->bindParam(':valor_descontar', $valor_descontar, PDO::PARAM_STR);
    $stmt_insert->bindParam(':chave_pix', $chave_pix, PDO::PARAM_STR);
    $stmt_insert->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $stmt_insert->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
    
    $stmt_insert->execute();
    $novo_id = $stmt_insert->fetchColumn();
    
    if ($novo_id) {
        error_log("ANTECIPAÇÃO GRAVADA: ID={$novo_id}, matricula={$matricula}, valor={$valor_pedido}");
        
        $response->success = true;
        $response->message = 'Solicitação de antecipação enviada com sucesso!';
        $response->id = $novo_id;
        $response->duplicate_prevented = false;
        $response->data_solicitacao = date('Y-m-d H:i:s');
    } else {
        $response->success = false;
        $response->message = 'Erro ao gravar solicitação no banco de dados.';
    }
    
} catch (Exception $e) {
    error_log("ERRO ANTECIPAÇÃO: " . $e->getMessage());
    $response->success = false;
    $response->message = 'Erro interno do servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>
