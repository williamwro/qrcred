<?php
// Permitir acesso de qualquer origem
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");

// Sempre definir Content-Type como JSON
header("Content-Type: application/json; charset=utf-8");

// Incluir arquivo de conexão com banco
include "Adm/php/banco.php";

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Função para retornar resposta JSON padronizada
function retornarResposta($success, $data = null, $error = null) {
    $response = [
        'success' => $success,
        'data' => $data,
        'error' => $error
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Log de erro para debugging
function logError($message) {
    error_log("[consulta_pass_assoc.php] " . $message);
}

try {
    // Verificar se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        retornarResposta(false, null, 'Método não permitido. Use POST.');
    }
    
    // Verificar se os parâmetros obrigatórios foram fornecidos
    if (!isset($_POST['pass']) || !isset($_POST['matricula']) || !isset($_POST['empregador']) || !isset($_POST['id_associado'])) {
        logError('Parâmetros obrigatórios não fornecidos: ' . json_encode($_POST));
        retornarResposta(false, null, 'Parâmetros obrigatórios não fornecidos: pass, matricula, empregador, id_associado');
    }
    
    // Obter parâmetros
    $matricula = $_POST['matricula'];
    $empregador = $_POST['empregador'];
    $senha = $_POST['pass'];
    $id_associado = $_POST['id_associado'];
    
    // Validar se os parâmetros não estão vazios
    if (empty($matricula) || empty($senha) || empty($id_associado)) {
        logError('Parâmetros vazios fornecidos');
        retornarResposta(false, null, 'Matrícula, senha e ID do associado não podem estar vazios');
    }
    
    // Validar se empregador e id_associado são numéricos
    if (!is_numeric($empregador) || !is_numeric($id_associado)) {
        logError('Empregador ou ID do associado não são numéricos');
        retornarResposta(false, null, 'Empregador e ID do associado devem ser numéricos');
    }
    
    logError("Verificando senha para matrícula: $matricula, empregador: $empregador, id_associado: $id_associado");
    
    // Conectar com banco de dados
    $pdo = null;
    try {
        $pdo = Banco::conectar_postgres();
        if (!$pdo) {
            throw new Exception('Falha na conexão com banco de dados');
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        logError('Erro na conexão com banco: ' . $e->getMessage());
        retornarResposta(false, null, 'Erro na conexão com banco de dados');
    }
    
    // Preparar e executar query
    try {
        $sql = "SELECT * FROM sind.c_senhaassociado 
                WHERE cod_associado = :matricula 
                AND id_empregador = :empregador 
                AND senha = :senha 
                AND id_associado = :id_associado";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':matricula', $matricula, PDO::PARAM_STR);
        $stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
        $stmt->bindParam(':senha', $senha, PDO::PARAM_STR);
        $stmt->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
        
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            logError("Senha verificada com sucesso para matrícula: $matricula");
            retornarResposta(true, [
                'situacao' => 'certo',
                'id_associado' => $resultado['id_associado']
            ], null);
        } else {
            logError("Senha incorreta para matrícula: $matricula");
            retornarResposta(false, [
                'situacao' => 'errado'
            ], 'Senha incorreta');
        }
        
    } catch (PDOException $e) {
        logError('Erro na query: ' . $e->getMessage());
        retornarResposta(false, null, 'Erro na consulta ao banco de dados');
    }
    
} catch (Exception $e) {
    logError('Erro geral: ' . $e->getMessage());
    retornarResposta(false, null, 'Erro interno do servidor');
} finally {
    // Fechar conexão se existir
    if ($pdo) {
        $pdo = null;
    }
}
?>
