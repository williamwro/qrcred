<?php
//convenio_recuperacao_senha.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');


// Incluir a biblioteca PHPMailer via Composer
require 'vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


// Se for uma solicitação OPTIONS, retorne apenas os cabeçalhos
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// Configurações de depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);


// Configurações do SMTP
function getSmtpConfig() {
    // Carregar do arquivo externo se existir
    $config_file = __DIR__ . '/smtp_config.php';
    if (file_exists($config_file)) {
        return include($config_file);
    }
    
    // Configurações padrão caso o arquivo não exista
    return [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'qrcredq@gmail.com',
        'password' => 'vsmn dlbl acsz zukc',
        'from_email' => 'qrcredq@gmail.com',
        'from_name' => 'QRCred - Recuperação de Senha',
        'secure' => 'tls',
        'smtp_auth' => true,                 // Autenticação SMTP habilitada
        'smtp_ssl_enable' => true            // SSL habilitado
    ];
}


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
if (empty($dados) || !isset($dados['email'])) {
    escreverLog("Parâmetros inválidos: " . json_encode($_POST) . " / JSON: " . file_get_contents('php://input'), 'ERRO');
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}


$email = trim($dados['email']);
$codigo = isset($dados['codigo']) ? $dados['codigo'] : '';
$dataExpiracao = isset($dados['dataExpiracao']) ? $dados['dataExpiracao'] : '';


// Inicializar a variável que indica se o email foi enviado como falsa
$emailJaEnviado = false;


try {
    // Conexão com o banco de dados 
    include "Adm/php/banco.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se a tabela existe, se não, criar
    $checkTableQuery = "SELECT to_regclass('sind.codigos_recuperacao_convenio')";
    $checkResult = $pdo->query($checkTableQuery)->fetchColumn();
    
    if (!$checkResult) {
        // Criar a tabela
        $createTableQuery = "
            CREATE TABLE sind.codigos_recuperacao_convenio (
                id SERIAL PRIMARY KEY,
                codigo VARCHAR(10) NOT NULL,
                destino VARCHAR(100) NOT NULL,
                origem VARCHAR(20) NOT NULL DEFAULT 'convenio',
                data_expiracao TIMESTAMP NOT NULL,
                identificador VARCHAR(50) NOT NULL,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                utilizado BOOLEAN DEFAULT FALSE,
                data_utilizacao TIMESTAMP
            );
            
            CREATE INDEX idx_codigo_convenio ON sind.codigos_recuperacao_convenio(codigo);
            CREATE INDEX idx_destino_convenio ON sind.codigos_recuperacao_convenio(destino);
            CREATE INDEX idx_identificador_convenio ON sind.codigos_recuperacao_convenio(identificador);
        ";
        
        $pdo->exec($createTableQuery);
        escreverLog("Tabela sind.codigos_recuperacao_convenio criada com sucesso", 'INFO');
    }
    
    // Buscar convênio pelo email
    $query = "SELECT sc.*, c.email, c.razaosocial FROM sind.c_senhaconvenio sc 
              INNER JOIN sind.convenio c ON sc.cod_convenio = c.codigo
              WHERE c.email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $convenio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$convenio) {
        escreverLog("Email não encontrado: $email", 'ERRO');
        echo json_encode(['success' => false, 'message' => 'Email não encontrado']);
        exit;
    }


    // Verificar se já existe um código válido e recente (criado nos últimos 60 segundos)
    $queryCodigoRecente = "SELECT * FROM sind.codigos_recuperacao_convenio 
                         WHERE identificador = :email 
                         AND data_expiracao > NOW() 
                         AND utilizado = FALSE 
                         AND data_criacao > (NOW() - INTERVAL '60 seconds')
                         ORDER BY data_criacao DESC 
                         LIMIT 1";
    
    $stmtCodigoRecente = $pdo->prepare($queryCodigoRecente);
    $stmtCodigoRecente->bindParam(':email', $email);
    $stmtCodigoRecente->execute();
    $codigoRecente = $stmtCodigoRecente->fetch(PDO::FETCH_ASSOC);
    
    // Se encontrou um código recente, usar este código
    if ($codigoRecente) {
        $codigo = $codigoRecente['codigo'];
        $dataExpiracao = $codigoRecente['data_expiracao'];
        
        escreverLog("Usando código já existente e válido: $codigo para email $email", 'INFO');
        $emailJaEnviado = true;
    } else {
        // Se não tem código recente ou não foi fornecido um código, gerar um novo
        if (empty($codigo)) {
            $codigo = mt_rand(100000, 999999);
            escreverLog("Código gerado: $codigo para email $email", 'INFO');
        }
        
        // Se a data de expiração não for fornecida, definir para 15 minutos no futuro
        if (empty($dataExpiracao)) {
            $dataExpiracao = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            escreverLog("Data de expiração gerada: $dataExpiracao para email $email", 'INFO');
        }
    
        // Verificar se já existe código pendente e removê-lo
        $query = "DELETE FROM sind.codigos_recuperacao_convenio WHERE destino = :email AND origem = 'convenio'";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':email', $convenio['email']);
        $stmt->execute();
        
        // Inserir novo código
        $query = "INSERT INTO sind.codigos_recuperacao_convenio 
                  (codigo, destino, origem, data_expiracao, identificador) 
                  VALUES (:codigo, :email, 'convenio', :data_expiracao, :email_identificador)";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':email', $convenio['email']);
        $stmt->bindParam(':data_expiracao', $dataExpiracao);
        $stmt->bindParam(':email_identificador', $email);
        $stmt->execute();
    }
    
    // Só enviar email se não for um código recém-enviado (para evitar envios duplicados)
    if (!$emailJaEnviado) {
        // Preparar mensagens HTML e texto
        $mensagem_html = "
        <html>
        <head>
            <title>Recuperação de senha - QRCred</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                <h2 style='color: #2c5282;'>Olá, {$convenio['razaosocial']}!</h2>
                <p>Recebemos uma solicitação de recuperação de senha para sua conta de convênio no sistema QRCred.</p>
                <p>Seu código de verificação é:</p>
                <div style='background-color: #f0f4f8; padding: 15px; font-size: 24px; text-align: center; letter-spacing: 5px; font-weight: bold; margin: 20px 0; border-radius: 4px;'>
                    $codigo
                </div>
                <p>Este código é válido até " . date('d/m/Y H:i', strtotime($dataExpiracao)) . ".</p>
                <p>Se você não solicitou essa recuperação, por favor, ignore este email.</p>
                <p>Atenciosamente,<br>Equipe QRCred</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 12px; color: #666;'>Este é um e-mail automático, não responda.</p>
            </div>
        </body>
        </html>";
        
        $mensagem_texto = "Olá, {$convenio['razaosocial']}!\n\n";
        $mensagem_texto .= "Recebemos uma solicitação de recuperação de senha para sua conta de convênio no sistema QRCred.\n\n";
        $mensagem_texto .= "Seu código de verificação é: $codigo\n\n";
        $mensagem_texto .= "Este código é válido até " . date('d/m/Y H:i', strtotime($dataExpiracao)) . ".\n\n";
        $mensagem_texto .= "Se você não solicitou essa recuperação, por favor, ignore este e-mail.\n\n";
        $mensagem_texto .= "Este é um e-mail automático, não responda.";
        
        // Usar PHPMailer para enviar o e-mail
        $mail = new PHPMailer(true);
        
        try {
            // Obter configurações SMTP
            $smtpConfig = getSmtpConfig();
            escreverLog("Configurações SMTP carregadas", 'INFO');
            
            // Configurações do servidor
            $mail->SMTPDebug = 0;                     // Nível de debug (0 = sem debug)
            $mail->isSMTP();                          // Usar SMTP
            $mail->Host       = $smtpConfig['host'];  // Servidor SMTP
            $mail->SMTPAuth   = true;                 // Habilitar autenticação SMTP
            $mail->Username   = $smtpConfig['username']; // SMTP username
            $mail->Password   = $smtpConfig['password']; // SMTP password
            $mail->SMTPSecure = $smtpConfig['secure'];  // Habilitar criptografia TLS ou SSL
            $mail->Port       = $smtpConfig['port'];    // Porta TCP para conexão
            $mail->CharSet    = 'UTF-8';               // Conjunto de caracteres
            
            // Remetente e destinatários
            $mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
            $mail->addAddress($convenio['email'], $convenio['razaosocial']);
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = "Código de recuperação de senha - QRCred";
            $mail->Body    = $mensagem_html;
            $mail->AltBody = $mensagem_texto; // Versão em texto
            
            escreverLog("Enviando email via PHPMailer...", 'INFO');
            $mail->send();
            $emailEnviado = true;
            escreverLog("✅ E-mail enviado com sucesso para {$convenio['email']}", 'INFO');
        } catch (Exception $e) {
            escreverLog("❌ ERRO ao enviar e-mail: " . $mail->ErrorInfo, 'ERRO');
            $emailEnviado = false;
        }
    } else {
        // Se estamos usando um código recente, considerar o email como já enviado
        $emailEnviado = true;
        escreverLog("Reutilizando código recente, email já enviado anteriormente para: {$convenio['email']}", 'INFO');
    }
    
    // Em ambos os casos (novo código ou código existente), retornar sucesso
    if ($emailEnviado) {
        // Mascara o email para exibição
        $partes = explode('@', $convenio['email']);
        $nome = $partes[0];
        $dominio = $partes[1];
        $nomeLen = strlen($nome);
        $nomeVisivel = $nomeLen <= 3 ? $nome : substr($nome, 0, 2) . str_repeat('*', $nomeLen - 3) . substr($nome, -1);
        $emailMascarado = $nomeVisivel . '@' . $dominio;
        
        escreverLog("Código enviado com sucesso para: {$convenio['email']}", 'INFO');
        
        echo json_encode([
            'success' => true, 
            'message' => 'Código enviado com sucesso', 
            'email' => $emailMascarado
        ]);
    } else {
        escreverLog("Falha ao enviar email para: {$convenio['email']}", 'ERRO');
        echo json_encode(['success' => false, 'message' => 'Falha ao enviar email']);
    }
    
} catch (PDOException $e) {
    escreverLog("Erro no banco de dados: " . $e->getMessage(), 'ERRO');
    echo json_encode(['success' => false, 'message' => 'Erro interno no servidor']);
}
?>
