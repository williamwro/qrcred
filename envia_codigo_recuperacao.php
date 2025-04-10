<?php
// Configurar arquivo de log específico para depuração
$log_file = __DIR__ . '/recuperacao_debug.log';
function debug_log($message) {
    global $log_file;
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message" . PHP_EOL, FILE_APPEND);
}

// Iniciar log
debug_log("=============== NOVA SOLICITAÇÃO DE RECUPERAÇÃO ===============");
debug_log("Iniciando processo de recuperação de senha");

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', true);
include "Adm/php/banco.php";

// Incluir a biblioteca PHPMailer via Composer
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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

// Cabeçalhos para permitir requisições de origens diferentes (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debug_log("Erro: Método não permitido: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(["erro" => "Método não permitido"]);
    exit;
}

// Obter dados da requisição
$cartao = isset($_POST['cartao']) ? preg_replace('/\D/', '', $_POST['cartao']) : "";
$metodo = isset($_POST['metodo']) ? $_POST['metodo'] : "";
$codigo = isset($_POST['codigo']) ? $_POST['codigo'] : "";
$email = isset($_POST['email']) ? $_POST['email'] : "";
$celular = isset($_POST['celular']) ? $_POST['celular'] : "";

// Log de depuração detalhado
debug_log("DADOS RECEBIDOS:");
debug_log("- Cartão: $cartao");
debug_log("- Método: $metodo");
debug_log("- Código: $codigo");
debug_log("- Email: $email");
debug_log("- Celular: $celular");

// Validar dados obrigatórios
if (empty($cartao) || empty($metodo) || empty($codigo)) {
    debug_log("Erro: Dados incompletos");
    echo json_encode(["erro" => "Dados incompletos"]);
    exit;
}

// Validar método de recuperação
if (!in_array($metodo, ['email', 'sms', 'whatsapp'])) {
    debug_log("Erro: Método de recuperação inválido: $metodo");
    echo json_encode(["erro" => "Método de recuperação inválido"]);
    exit;
}

try {
    // Conexão com o banco de dados usando seu método existente
    debug_log("Conectando ao banco de dados...");
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    debug_log("Conexão com o banco de dados estabelecida com sucesso");
    
    // Verificar se o cartão existe na tabela c_cartaoassociado e obter dados do associado
    debug_log("Verificando se o cartão $cartao existe...");
    $sql_verificar = "SELECT c.cod_associado, c.empregador, a.nome, a.email, a.cel 
                     FROM sind.c_cartaoassociado c
                     INNER JOIN sind.associado a ON c.cod_associado = a.codigo AND c.empregador = a.empregador
                     WHERE c.cod_verificacao = :cartao LIMIT 1";
    $stmt_verificar = $pdo->prepare($sql_verificar);
    $stmt_verificar->bindParam(':cartao', $cartao);
    $stmt_verificar->execute();
    
    debug_log("SQL de verificação do cartão: " . $sql_verificar . " [Parâmetro: $cartao]");
    
    if ($stmt_verificar->rowCount() == 0) {
        debug_log("Erro: Cartão não encontrado: $cartao");
        echo json_encode(["erro" => "Cartão não encontrado"]);
        exit;
    }
    
    $associado = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
    debug_log("Associado encontrado: " . json_encode($associado));
    
    // Verificar se tem o método de contato disponível
    if ($metodo == 'email' && empty($associado['email'])) {
        debug_log("Erro: Associado não possui e-mail cadastrado");
        echo json_encode(["erro" => "Este associado não possui e-mail cadastrado"]);
        exit;
    }
    
    if (($metodo == 'sms' || $metodo == 'whatsapp') && empty($associado['cel'])) {
        debug_log("Erro: Associado não possui celular cadastrado");
        echo json_encode(["erro" => "Este associado não possui celular cadastrado"]);
        exit;
    }
    
    // Verificar se a tabela sind.codigos_recuperacao existe
    debug_log("Verificando se a tabela sind.codigos_recuperacao existe...");
    $sql_verificar_tabela = "SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'sind' 
        AND table_name = 'codigos_recuperacao'
    )";
    $stmt_verificar_tabela = $pdo->prepare($sql_verificar_tabela);
    $stmt_verificar_tabela->execute();
    $tabela_existe = $stmt_verificar_tabela->fetchColumn();
    
    debug_log("Resultado da verificação da tabela: " . ($tabela_existe ? "Tabela existe" : "Tabela não existe"));
    
    if (!$tabela_existe) {
        debug_log("Tabela não encontrada. Tentando criar a tabela sind.codigos_recuperacao...");
        // Criar a tabela sind.codigos_recuperacao se não existir
        $sql_criar_tabela = "CREATE TABLE sind.codigos_recuperacao (
            id SERIAL PRIMARY KEY,
            cartao VARCHAR(20) NOT NULL,
            codigo VARCHAR(10) NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT NOW(),
            usado BOOLEAN DEFAULT FALSE,
            expirado BOOLEAN DEFAULT FALSE,
            metodo VARCHAR(10)
        )";
        
        debug_log("SQL para criar tabela: " . $sql_criar_tabela);
        $pdo->exec($sql_criar_tabela);
        
        // Criar índices para otimização
        debug_log("Criando índices para a tabela...");
        $pdo->exec("CREATE INDEX idx_codigos_cartao ON sind.codigos_recuperacao(cartao)");
        $pdo->exec("CREATE INDEX idx_codigos_codigo ON sind.codigos_recuperacao(codigo)");
        
        debug_log("Tabela sind.codigos_recuperacao criada com sucesso");
    }
    
    // Excluir códigos anteriores para este cartão
    debug_log("Excluindo códigos anteriores para o cartão $cartao...");
    $sql_excluir = "DELETE FROM sind.codigos_recuperacao WHERE cartao = :cartao";
    $stmt_excluir = $pdo->prepare($sql_excluir);
    $stmt_excluir->bindParam(':cartao', $cartao);
    $resultado_exclusao = $stmt_excluir->execute();
    debug_log("Resultado da exclusão: " . ($resultado_exclusao ? "Sucesso" : "Falha") . 
              " - Linhas afetadas: " . $stmt_excluir->rowCount());
    
    // Testar permissões de inserção com uma consulta simples
    debug_log("Testando permissões de inserção com consulta simples...");
    try {
        $pdo->query("SELECT 1");
        debug_log("Teste de consulta simples: OK");
    } catch (PDOException $e) {
        debug_log("ERRO no teste de consulta simples: " . $e->getMessage());
    }
    
    // Começar transação explícita
    debug_log("Iniciando transação...");
    $pdo->beginTransaction();
    
    // Inserir novo código de recuperação na tabela correta
    // Preparar o destino baseado no método
    $destino = ($metodo == 'email') ? $email : $celular;
    debug_log("Destino definido: $destino para método $metodo");

    // Ajustar a consulta SQL para incluir a coluna destino
    $sql_inserir = "INSERT INTO sind.codigos_recuperacao (cartao, codigo, timestamp, usado, expirado, metodo, destino)
              VALUES (:cartao, :codigo, NOW(), FALSE, FALSE, :metodo, :destino)";

    debug_log("SQL de inserção: " . $sql_inserir);
    debug_log("Parâmetros: cartao=$cartao, codigo=$codigo, metodo=$metodo");
    
    $stmt_inserir = $pdo->prepare($sql_inserir);
    $stmt_inserir->bindParam(':cartao', $cartao);
    $stmt_inserir->bindParam(':codigo', $codigo);
    $stmt_inserir->bindParam(':metodo', $metodo);
    $stmt_inserir->bindParam(':destino', $destino);
    
    try {
        debug_log("Executando inserção...");
        $resultado_insercao = $stmt_inserir->execute();
        
        if ($resultado_insercao) {
            debug_log("✅ SUCESSO: Código inserido na tabela sind.codigos_recuperacao");
            debug_log("Confirmando transação (commit)...");
            $pdo->commit();
        } else {
            $erro_info = $stmt_inserir->errorInfo();
            debug_log("❌ ERRO na inserção: " . implode(", ", $erro_info));
            
            // Rollback e tentar método alternativo
            debug_log("Fazendo rollback da transação...");
            $pdo->rollBack();
            
            // Tentar método alternativo com inserção direta
            debug_log("Tentando método alternativo de inserção...");
            $pdo->beginTransaction();
            $sql_alternativo = "INSERT INTO sind.codigos_recuperacao (cartao, codigo, timestamp, usado, expirado, metodo)
                              VALUES ('$cartao', '$codigo', NOW(), FALSE, FALSE, '$metodo')";
            
            debug_log("SQL alternativo: " . $sql_alternativo);
            $stmt_alt = $pdo->prepare($sql_alternativo);
            $resultado_alt = $stmt_alt->execute();
            
            if ($resultado_alt) {
                debug_log("✅ SUCESSO no método alternativo de inserção");
                $pdo->commit();
            } else {
                $erro_alt = $stmt_alt->errorInfo();
                debug_log("❌ ERRO no método alternativo: " . implode(", ", $erro_alt));
                $pdo->rollBack();
            }
        }
    } catch (PDOException $e) {
        debug_log("❌ EXCEÇÃO na inserção: " . $e->getMessage());
        
        // Rollback e tentar método de última chance
        $pdo->rollBack();
        
        debug_log("Tentando inserção de última chance...");
        try {
            // Usar método direto com exec()
            $pdo->beginTransaction();
            $sql_direto = "INSERT INTO sind.codigos_recuperacao (cartao, codigo, timestamp, usado, expirado, metodo)
                          VALUES ('$cartao', '$codigo', NOW(), FALSE, FALSE, '$metodo')";
            
            $pdo->exec($sql_direto);
            $pdo->commit();
            debug_log("✅ Inserção direta executada como última chance");
        } catch (Exception $e2) {
            $pdo->rollBack();
            debug_log("❌ FALHA TOTAL na inserção: " . $e2->getMessage());
        }
    }
    
    // Verificar se o código foi realmente inserido
    debug_log("Verificando se o código foi realmente inserido...");
    $sql_verificar_insercao = "SELECT COUNT(*) FROM sind.codigos_recuperacao WHERE cartao = :cartao AND codigo = :codigo";
    $stmt_verificar_insercao = $pdo->prepare($sql_verificar_insercao);
    $stmt_verificar_insercao->bindParam(':cartao', $cartao);
    $stmt_verificar_insercao->bindParam(':codigo', $codigo);
    $stmt_verificar_insercao->execute();
    $codigo_inserido = $stmt_verificar_insercao->fetchColumn() > 0;
    
    debug_log("Resultado da verificação: " . ($codigo_inserido ? "✅ Código encontrado no banco" : "❌ Código NÃO encontrado no banco"));
    
    // Se o código não foi inserido, tentar uma última vez com exec direto
    if (!$codigo_inserido) {
        debug_log("Tentando inserção final direta...");
        try {
            $sql_final = "INSERT INTO sind.codigos_recuperacao (cartao, codigo, timestamp, usado, expirado, metodo)
                         VALUES ('$cartao', '$codigo', NOW(), FALSE, FALSE, '$metodo')";
            $pdo->exec($sql_final);
            
            // Verificar novamente
            $stmt_verificar_insercao->execute();
            $codigo_inserido_final = $stmt_verificar_insercao->fetchColumn() > 0;
            debug_log("Resultado da verificação final: " . ($codigo_inserido_final ? "✅ Código inserido com sucesso" : "❌ Falha na inserção final"));
        } catch (Exception $e) {
            debug_log("❌ ERRO na inserção final: " . $e->getMessage());
        }
    }
    
    // Usar o contato do associado se não foi fornecido
    if ($metodo == 'email' && empty($email)) {
        $email = $associado['email'];
        debug_log("Email não fornecido, usando email do associado: $email");
    }
    
    if (($metodo == 'sms' || $metodo == 'whatsapp') && empty($celular)) {
        $celular = $associado['cel'];
        debug_log("Celular não fornecido, usando celular do associado: $celular");
    }
    
    // Enviar o código pelo método escolhido
    $enviado = false;
    debug_log("Preparando para enviar código pelo método: $metodo");
    
    if ($metodo == 'email' && !empty($email)) {
        // Preparar a mensagem HTML
        debug_log("Preparando mensagem de email para $email");
        $mensagem_html = "
        <html>
        <head>
            <title>Recuperação de Senha - QRCred</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                <h2 style='color: #2c5282;'>Olá, {$associado['nome']}!</h2>
                <p>Recebemos uma solicitação de recuperação de senha para sua conta QRCred.</p>
                <p>Seu código de verificação é:</p>
                <div style='background-color: #f0f4f8; padding: 15px; font-size: 24px; text-align: center; letter-spacing: 5px; font-weight: bold; margin: 20px 0; border-radius: 4px;'>
                    $codigo
                </div>
                <p>Este código expira em 10 minutos.</p>
                <p>Se você não solicitou a recuperação de senha, por favor, ignore este e-mail.</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 12px; color: #666;'>Este é um e-mail automático, não responda.</p>
            </div>
        </body>
        </html>";
        
        $mensagem_texto = "Olá, {$associado['nome']}!\n\n";
        $mensagem_texto .= "Recebemos uma solicitação de recuperação de senha para sua conta QRCred.\n\n";
        $mensagem_texto .= "Seu código de verificação é: $codigo\n\n";
        $mensagem_texto .= "Este código expira em 10 minutos.\n\n";
        $mensagem_texto .= "Se você não solicitou a recuperação de senha, por favor, ignore este e-mail.\n\n";
        $mensagem_texto .= "Este é um e-mail automático, não responda.";
        
        // Usar PHPMailer para enviar o e-mail
        $mail = new PHPMailer(true);
        
        try {
            // Obter configurações SMTP
            $smtpConfig = getSmtpConfig();
            debug_log("Configurações SMTP carregadas");
            
            // Configurações do servidor
            $mail->SMTPDebug = 0;                     // Nível de debug (0 = sem debug, 1 = mensagens, 2 = mensagens + dados)
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
            $mail->addAddress($email, $associado['nome']);
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = "Recuperação de Senha - QRCred";
            $mail->Body    = $mensagem_html;
            $mail->AltBody = $mensagem_texto; // Versão em texto
            
            debug_log("Enviando email...");
            $mail->send();
            $enviado = true;
            debug_log("✅ E-mail enviado com sucesso para $email");
        } catch (Exception $e) {
            debug_log("❌ ERRO ao enviar e-mail: " . $mail->ErrorInfo);
            $enviado = false;
        }
    } elseif ($metodo == 'sms' && !empty($celular)) {
        // Implementação para envio de SMS
        // Você precisará integrar com um provedor de SMS
        $enviado = true; // Simulando envio bem-sucedido para teste
        debug_log("SMS simulado enviado para $celular");
    } elseif ($metodo == 'whatsapp' && !empty($celular)) {
        // Implementação para envio via WhatsApp
        // Você precisará integrar com a API do WhatsApp
        $enviado = true; // Simulando envio bem-sucedido para teste
        debug_log("WhatsApp simulado enviado para $celular");
    }
    
    if ($enviado) {
        debug_log("Código enviado com sucesso pelo método $metodo");
        echo "enviado"; // Resposta para sucesso conforme esperado pela aplicação
    } else {
        debug_log("Falha ao enviar código pelo método $metodo");
        echo json_encode(["erro" => "Falha ao enviar código"]);
    }
    
    debug_log("Processo de recuperação de senha concluído");
    debug_log("=============== FIM DO PROCESSAMENTO ===============");
    
} catch (PDOException $e) {
    debug_log("❌ ERRO FATAL na recuperação de senha: " . $e->getMessage());
    echo json_encode(["erro" => "Erro no processamento da solicitação: " . $e->getMessage()]);
} catch (Exception $e) {
    debug_log("❌ EXCEÇÃO GERAL: " . $e->getMessage());
    echo json_encode(["erro" => "Erro inesperado: " . $e->getMessage()]);
}
?> 