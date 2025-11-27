<?php
//convenio_cadastro_email.php
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
        'from_name' => 'QRCred - Bem-vindo',
        'secure' => 'tls',
        'smtp_auth' => true,
        'smtp_ssl_enable' => true
    ];
}

// Função para escrever no log
function escreverLog($mensagem, $tipo = 'INFO') {
    $dataHora = date('Y-m-d H:i:s');
    $diretorioLog = './logs';
    
    if (!file_exists($diretorioLog)) {
        mkdir($diretorioLog, 0775, true);
    }
    
    $arquivoLog = $diretorioLog . '/cadastro_convenio_' . date('Y-m-d') . '.log';
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
$camposObrigatorios = ['email', 'razaosocial', 'usuario', 'senha'];
foreach ($camposObrigatorios as $campo) {
    if (empty($dados) || !isset($dados[$campo]) || empty(trim($dados[$campo]))) {
        escreverLog("Campo obrigatório ausente: $campo. Dados recebidos: " . json_encode($dados), 'ERRO');
        echo json_encode(['success' => false, 'message' => "Campo obrigatório ausente: $campo"]);
        exit;
    }
}

$email = trim($dados['email']);
$razaosocial = trim($dados['razaosocial']);
$usuario = trim($dados['usuario']);
$senha = trim($dados['senha']);
$cnpj = isset($dados['cnpj']) ? trim($dados['cnpj']) : '';
$telefone = isset($dados['telefone']) ? trim($dados['telefone']) : '';

try {
    // Preparar mensagem HTML moderna
    $mensagem_html = "
    <!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Bem-vindo ao QRCred</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                line-height: 1.6;
                color: #1f2937;
                background-color: #f9fafb;
            }
            
            .container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            }
            
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 40px 30px;
                text-align: center;
                color: white;
            }
            
            .logo {
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                letter-spacing: -0.5px;
            }
            
            .header-subtitle {
                font-size: 16px;
                opacity: 0.9;
                font-weight: 300;
            }
            
            .content {
                padding: 40px 30px;
            }
            
            .welcome-title {
                font-size: 28px;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 16px;
                text-align: center;
            }
            
            .welcome-subtitle {
                font-size: 18px;
                color: #6b7280;
                text-align: center;
                margin-bottom: 32px;
                font-weight: 400;
            }
            
            .success-badge {
                display: inline-flex;
                align-items: center;
                background-color: #d1fae5;
                color: #065f46;
                padding: 8px 16px;
                border-radius: 50px;
                font-size: 14px;
                font-weight: 500;
                margin-bottom: 24px;
            }
            
            .success-icon {
                width: 16px;
                height: 16px;
                margin-right: 8px;
                fill: currentColor;
            }
            
            .credentials-card {
                background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 24px;
                margin: 24px 0;
            }
            
            .credentials-title {
                font-size: 18px;
                font-weight: 600;
                color: #374151;
                margin-bottom: 16px;
                display: flex;
                align-items: center;
            }
            
            .key-icon {
                width: 20px;
                height: 20px;
                margin-right: 8px;
                fill: #6366f1;
            }
            
            .credential-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 0;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .credential-item:last-child {
                border-bottom: none;
            }
            
            .credential-label {
                font-weight: 500;
                color: #6b7280;
                font-size: 14px;
            }
            
            .credential-value {
                font-weight: 600;
                color: #1f2937;
                font-size: 16px;
                background-color: #ffffff;
                padding: 6px 12px;
                border-radius: 6px;
                border: 1px solid #d1d5db;
                font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            }
            
            .info-section {
                background-color: #eff6ff;
                border: 1px solid #bfdbfe;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
            }
            
            .info-title {
                font-weight: 600;
                color: #1e40af;
                margin-bottom: 8px;
                display: flex;
                align-items: center;
            }
            
            .info-icon {
                width: 18px;
                height: 18px;
                margin-right: 8px;
                fill: currentColor;
            }
            
            .info-text {
                color: #1e40af;
                font-size: 14px;
                line-height: 1.5;
            }
            
            .cta-button {
                display: inline-block;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                padding: 16px 32px;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                text-align: center;
                margin: 24px auto;
                display: block;
                max-width: 200px;
                transition: transform 0.2s ease;
            }
            
            .cta-button:hover {
                transform: translateY(-2px);
            }
            
            .footer {
                background-color: #f9fafb;
                padding: 30px;
                text-align: center;
                border-top: 1px solid #e5e7eb;
            }
            
            .footer-text {
                color: #6b7280;
                font-size: 14px;
                margin-bottom: 16px;
            }
            
            .footer-links {
                display: flex;
                justify-content: center;
                gap: 24px;
                margin-bottom: 16px;
            }
            
            .footer-link {
                color: #6366f1;
                text-decoration: none;
                font-size: 14px;
                font-weight: 500;
            }
            
            .footer-link:hover {
                text-decoration: underline;
            }
            
            .footer-copyright {
                color: #9ca3af;
                font-size: 12px;
            }
            
            @media (max-width: 600px) {
                .container {
                    margin: 0;
                    border-radius: 0;
                }
                
                .content, .header, .footer {
                    padding: 24px 20px;
                }
                
                .welcome-title {
                    font-size: 24px;
                }
                
                .credentials-card {
                    padding: 20px;
                }
                
                .footer-links {
                    flex-direction: column;
                    gap: 12px;
                }
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='logo'>QRCred</div>
                <div class='header-subtitle'>Sistema de Gestão de Convênios</div>
            </div>
            
            <div class='content'>
                <div style='text-align: center;'>
                    <div class='success-badge'>
                        <svg class='success-icon' viewBox='0 0 20 20'>
                            <path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'/>
                        </svg>
                        Cadastro realizado com sucesso!
                    </div>
                </div>
                
                <h1 class='welcome-title'>Bem-vindo ao QRCred!</h1>
                <p class='welcome-subtitle'>Olá <strong>$razaosocial</strong>, seu convênio foi cadastrado com sucesso em nossa plataforma.</p>
                
                <div class='credentials-card'>
                    <div class='credentials-title'>
                        <svg class='key-icon' viewBox='0 0 20 20'>
                            <path fill-rule='evenodd' d='M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z' clip-rule='evenodd'/>
                        </svg>
                        Suas Credenciais de Acesso
                    </div>
                    
                    <div class='credential-item'>
                        <span class='credential-label'>Usuário:</span>
                        <span class='credential-value'>$usuario</span>
                    </div>
                    
                    <div class='credential-item'>
                        <span class='credential-label'>Senha:</span>
                        <span class='credential-value'>$senha</span>
                    </div>
                    
                    <div class='credential-item'>
                        <span class='credential-label'>Email:</span>
                        <span class='credential-value'>$email</span>
                    </div>
                </div>
                
                <div class='info-section'>
                    <div class='info-title'>
                        <svg class='info-icon' viewBox='0 0 20 20'>
                            <path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z' clip-rule='evenodd'/>
                        </svg>
                        Informações Importantes
                    </div>
                    <div class='info-text'>
                        • Guarde suas credenciais em local seguro<br>
                        • Você pode alterar sua senha após o primeiro acesso<br>
                        • Em caso de dúvidas, entre em contato com nosso suporte<br>
                        • Acesse o sistema através do link abaixo
                    </div>
                </div>
                
                <a href='#' class='cta-button'>Acessar Sistema</a>
                
                <div style='text-align: center; margin-top: 32px;'>
                    <p style='color: #6b7280; font-size: 14px;'>
                        Se você não solicitou este cadastro, entre em contato conosco imediatamente.
                    </p>
                </div>
            </div>
            
            <div class='footer'>
                <p class='footer-text'>Obrigado por escolher o QRCred para gerenciar seu convênio!</p>
                
                <div class='footer-links'>
                    <a href='#' class='footer-link'>Central de Ajuda</a>
                    <a href='#' class='footer-link'>Suporte Técnico</a>
                    <a href='#' class='footer-link'>Política de Privacidade</a>
                </div>
                
                <p class='footer-copyright'>
                    © " . date('Y') . " QRCred. Todos os direitos reservados.
                </p>
            </div>
        </div>
    </body>
    </html>";

    // Preparar mensagem de texto simples (fallback)
    $mensagem_texto = "Bem-vindo ao QRCred!\n\n";
    $mensagem_texto .= "Olá $razaosocial,\n\n";
    $mensagem_texto .= "Seu convênio foi cadastrado com sucesso em nossa plataforma.\n\n";
    $mensagem_texto .= "SUAS CREDENCIAIS DE ACESSO:\n";
    $mensagem_texto .= "Usuário: $usuario\n";
    $mensagem_texto .= "Senha: $senha\n";
    $mensagem_texto .= "Email: $email\n\n";
    $mensagem_texto .= "INFORMAÇÕES IMPORTANTES:\n";
    $mensagem_texto .= "• Guarde suas credenciais em local seguro\n";
    $mensagem_texto .= "• Você pode alterar sua senha após o primeiro acesso\n";
    $mensagem_texto .= "• Em caso de dúvidas, entre em contato com nosso suporte\n\n";
    $mensagem_texto .= "Se você não solicitou este cadastro, entre em contato conosco imediatamente.\n\n";
    $mensagem_texto .= "Obrigado por escolher o QRCred!\n\n";
    $mensagem_texto .= "Este é um e-mail automático, não responda.";

    // Usar PHPMailer para enviar o e-mail
    $mail = new PHPMailer(true);
    
    try {
        // Obter configurações SMTP
        $smtpConfig = getSmtpConfig();
        escreverLog("Configurações SMTP carregadas para envio de boas-vindas", 'INFO');
        
        // Configurações do servidor
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = $smtpConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpConfig['username'];
        $mail->Password   = $smtpConfig['password'];
        $mail->SMTPSecure = $smtpConfig['secure'];
        $mail->Port       = $smtpConfig['port'];
        $mail->CharSet    = 'UTF-8';
        
        // Remetente e destinatários
        $mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
        $mail->addAddress($email, $razaosocial);
        
        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = "Bem-vindo ao QRCred - Suas credenciais de acesso";
        $mail->Body    = $mensagem_html;
        $mail->AltBody = $mensagem_texto;
        
        escreverLog("Enviando email de boas-vindas via PHPMailer para: $email", 'INFO');
        $mail->send();
        
        escreverLog("✅ E-mail de boas-vindas enviado com sucesso para $email (Convênio: $razaosocial)", 'INFO');
        
        echo json_encode([
            'success' => true, 
            'message' => 'Email de boas-vindas enviado com sucesso',
            'email' => $email,
            'convenio' => $razaosocial
        ]);
        
    } catch (Exception $e) {
        escreverLog("❌ ERRO ao enviar e-mail de boas-vindas: " . $mail->ErrorInfo, 'ERRO');
        echo json_encode([
            'success' => false, 
            'message' => 'Falha ao enviar email de boas-vindas: ' . $mail->ErrorInfo
        ]);
    }
    
} catch (Exception $e) {
    escreverLog("Erro geral no envio de email de boas-vindas: " . $e->getMessage(), 'ERRO');
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno no servidor: ' . $e->getMessage()
    ]);
}
?>
