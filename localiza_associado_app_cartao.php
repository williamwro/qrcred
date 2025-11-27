<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responder a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Incluir arquivo de conexão com banco de dados
require_once 'conexao.php';

try {
    // Validar e sanitizar entrada - APENAS CARTÃO
    $cod_cartao = '';
    
    if (isset($_POST['cartao'])) {
        $cod_cartao = trim($_POST['cartao']);
        // Remover caracteres não numéricos
        $cod_cartao = preg_replace('/\D/', '', $cod_cartao);
    }
    
    // Validar se cartão foi fornecido
    if (empty($cod_cartao)) {
        echo json_encode([
            'situacao' => 3,
            'erro' => 'Cartão não informado'
        ]);
        exit;
    }
    
    // Log para debug
    error_log("Buscando associado por cartão: " . $cod_cartao);
    
    // Preparar consulta SQL para buscar associado apenas por cartão
    $sql = "SELECT 
                matricula,
                nome,
                email,
                cel,
                celwatzap,
                situacao,
                cod_cartao
            FROM associados 
            WHERE cod_cartao = ? 
            AND situacao IN (1, 2)
            LIMIT 1";
    
    $stmt = $conexao->prepare($sql);
    
    if (!$stmt) {
        error_log("Erro na preparação da query: " . $conexao->error);
        echo json_encode([
            'situacao' => 3,
            'erro' => 'Erro interno do servidor'
        ]);
        exit;
    }
    
    $stmt->bind_param("s", $cod_cartao);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $associado = $resultado->fetch_assoc();
        
        // Log para debug
        error_log("Associado encontrado: " . $associado['matricula']);
        
        // Retornar dados do associado
        echo json_encode([
            'situacao' => 1, // Sucesso
            'matricula' => $associado['matricula'],
            'nome' => $associado['nome'],
            'email' => $associado['email'],
            'cel' => $associado['cel'],
            'celwatzap' => $associado['celwatzap'],
            'cod_cartao' => $associado['cod_cartao']
        ]);
    } else {
        // Associado não encontrado
        error_log("Associado não encontrado para cartão: " . $cod_cartao);
        echo json_encode([
            'situacao' => 3, // Não encontrado
            'erro' => 'Cartão não encontrado'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Erro na consulta do associado: " . $e->getMessage());
    echo json_encode([
        'situacao' => 3,
        'erro' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conexao)) {
        $conexao->close();
    }
}
?>