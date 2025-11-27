<?php
//convenio_redefinir_senha.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');


// Se for uma solicitação OPTIONS, retorne apenas os cabeçalhos
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// Configurações de depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);


// Função para escrever no log
function escreverLog($mensagem, $tipo = 'INFO') {
    $dataHora = date('Y-m-d H:i:s');
    $diretorioLog = './logs';
    
    if (!file_exists($diretorioLog)) {
        mkdir($diretorioLog, 0775, true);
    }
    
    $arquivoLog = $diretorioLog . '/recuperacao_senha_convenio_' . date('Y-m-d') . '.log';
    $textoLog = "[$dataHora][$tipo] $mensagem" . PHP_EOL;
    file_put_contents($arquivoLog, $textoLog, FILE_APPEND);
}


// Início do log
escreverLog("=== INÍCIO DA REDEFINIÇÃO DE SENHA ===", 'DEBUG');


// Obter dados com base no método da requisição
$dados = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Primeiro, tentar obter dados do JSON
    $jsonInput = file_get_contents('php://input');
    if (!empty($jsonInput)) {
        $jsonData = json_decode($jsonInput, true);
        if ($jsonData !== null) {
            $dados = $jsonData;
            escreverLog("Dados recebidos via JSON: " . json_encode($dados), 'DEBUG');
        }
    }
    
    // Se não conseguir obter por JSON, tentar $_POST
    if (empty($dados) && !empty($_POST)) {
        $dados = $_POST;
        escreverLog("Dados recebidos via POST: " . json_encode($dados), 'DEBUG');
    }
}


// Verificar se temos os dados necessários
if (empty($dados) || !isset($dados['email']) || !isset($dados['senha']) || !isset($dados['codigo'])) {
    escreverLog("Parâmetros inválidos: " . json_encode($_POST) . " / JSON: " . file_get_contents('php://input'), 'ERRO');
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}


$email = trim($dados['email']);
$senha = $dados['senha'];
$codigo = trim($dados['codigo']);


// Validar senha
if (strlen($senha) < 6) {
    escreverLog("Senha muito curta para email: $email", 'ERRO');
    echo json_encode(['success' => false, 'message' => 'A senha deve ter no mínimo 6 caracteres']);
    exit;
}


try {
    // Conexão com o banco de dados 
    include "Adm/php/banco.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se o código existe e é válido
    $queryCodigo = "SELECT * FROM sind.codigos_recuperacao_convenio 
                   WHERE codigo = :codigo 
                   AND identificador = :email 
                   AND data_expiracao > NOW() 
                   AND utilizado = FALSE";
    
    escreverLog("Verificando código de recuperação: $codigo para email: $email", 'DEBUG');
    
    $stmtCodigo = $pdo->prepare($queryCodigo);
    $stmtCodigo->bindParam(':codigo', $codigo);
    $stmtCodigo->bindParam(':email', $email);
    $stmtCodigo->execute();
    
    $codigoRecuperacao = $stmtCodigo->fetch(PDO::FETCH_ASSOC);
    
    if (!$codigoRecuperacao) {
        // Consulta adicional para depuração
        $queryDebug = "SELECT * FROM sind.codigos_recuperacao_convenio 
                WHERE identificador = :email ORDER BY data_criacao DESC LIMIT 5";
        $stmtDebug = $pdo->prepare($queryDebug);
        $stmtDebug->bindParam(':email', $email);
        $stmtDebug->execute();
        $codigosDebug = $stmtDebug->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($codigosDebug) > 0) {
            escreverLog("Códigos recentes para este email: " . json_encode($codigosDebug), 'DEBUG');
        }
        
        escreverLog("Código inválido ou expirado: $codigo para email $email na redefinição", 'ERRO');
        echo json_encode(['success' => false, 'message' => 'Código de verificação inválido ou expirado']);
        exit;
    }
    
    // Criar hash MD5 da senha (conforme o padrão atual da tabela)
    $senhaMD5 = md5($senha);
    
    // Buscar registro do usuário pelo email
    $queryBuscarUsuario = "SELECT sc.* FROM sind.c_senhaconvenio sc 
                          INNER JOIN sind.convenio c ON sc.cod_convenio = c.codigo
                          WHERE c.email = :email";
    $stmtBuscarUsuario = $pdo->prepare($queryBuscarUsuario);
    $stmtBuscarUsuario->bindParam(':email', $email);
    $stmtBuscarUsuario->execute();
    
    $usuarioConvenio = $stmtBuscarUsuario->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuarioConvenio) {
        escreverLog("Usuário não encontrado para redefinição: $email", 'ERRO');
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit;
    }
    
    escreverLog("Atualizando senha para email: $email", 'INFO');
    
    // Atualizar senha do usuário - tanto os campos criptografados quanto os de texto
    $queryAtualizarSenha = "UPDATE sind.c_senhaconvenio 
                           SET senha = :senha_md5, password = :senha_texto 
                           WHERE usuario_texto = :usuario_texto";
    $stmtAtualizarSenha = $pdo->prepare($queryAtualizarSenha);
    $stmtAtualizarSenha->bindParam(':senha_md5', $senhaMD5);
    $stmtAtualizarSenha->bindParam(':senha_texto', $senha);
    $stmtAtualizarSenha->bindParam(':usuario_texto', $usuarioConvenio['usuario_texto']);
    $stmtAtualizarSenha->execute();
    
    // Marcar código como utilizado
    $queryMarcarUtilizado = "UPDATE sind.codigos_recuperacao_convenio 
                            SET utilizado = TRUE, data_utilizacao = NOW() 
                            WHERE codigo = :codigo AND identificador = :email";
    $stmtMarcarUtilizado = $pdo->prepare($queryMarcarUtilizado);
    $stmtMarcarUtilizado->bindParam(':codigo', $codigo);
    $stmtMarcarUtilizado->bindParam(':email', $email);
    $stmtMarcarUtilizado->execute();
    
    escreverLog("Código marcado como utilizado: $codigo", 'DEBUG');
    
    // Buscar dados do convênio para email
    $queryConvenio = "SELECT c.* FROM sind.c_senhaconvenio sc 
                     INNER JOIN sind.convenio c ON sc.cod_convenio = c.codigo
                     WHERE c.email = :email";
    $stmtConvenio = $pdo->prepare($queryConvenio);
    $stmtConvenio->bindParam(':email', $email);
    $stmtConvenio->execute();
    $convenio = $stmtConvenio->fetch(PDO::FETCH_ASSOC);
    
    // Enviar email de confirmação
    if ($convenio && !empty($convenio['email'])) {
        $para = $convenio['email'];
        $assunto = 'Senha alterada com sucesso - QRCred';
        
        $mensagem = "
        <html>
        <head>
          <title>Senha alterada - QRCred</title>
        </head>
        <body>
          <p>Olá, {$convenio['razaosocial']}!</p>
          <p>Sua senha foi alterada com sucesso no sistema QRCred.</p>
          <p>Se você não realizou esta alteração, entre em contato com o suporte imediatamente.</p>
          <p>Atenciosamente,<br>Equipe QRCred</p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: QRCred <noreply@qrcred.com.br>\r\n";
        
        mail($para, $assunto, $mensagem, $headers);
        
        escreverLog("Email de confirmação enviado para: {$convenio['email']}", 'INFO');
    }
    
    escreverLog("Senha alterada com sucesso para email: $email", 'INFO');
    
    echo json_encode([
        'success' => true,
        'message' => 'Senha alterada com sucesso'
    ]);
    
} catch (PDOException $e) {
    escreverLog("Erro no banco de dados: " . $e->getMessage(), 'ERRO');
    echo json_encode(['success' => false, 'message' => 'Erro interno no servidor']);
} finally {
    escreverLog("=== FIM DA REDEFINIÇÃO DE SENHA ===", 'DEBUG');
}
?>
