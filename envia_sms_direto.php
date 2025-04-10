<?php
// Configurar arquivo de log específico para depuração
$log_file = __DIR__ . '/sms_debug.log';
function debug_log($message) {
    global $log_file;
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message" . PHP_EOL, FILE_APPEND);
}

// Iniciar log
debug_log("=============== NOVA SOLICITAÇÃO DE ENVIO SMS/WHATSAPP ===============");

// Configurações de erro e cabeçalhos
error_reporting(E_ALL);
ini_set('display_errors', false);

// Cabeçalhos para permitir requisições de origens diferentes (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Carregar configurações da API de SMS (arquivo externo para separar credenciais)
function getApiSmsConfig() {
    $config_file = __DIR__ . '/sms_api_config.php';
    if (file_exists($config_file)) {
        return include($config_file);
    }
    
    // Configuração padrão se não existir arquivo externo
    return [
        'api_key' => 'sua_chave_api',
        'token' => 'chave_segura_123',
        'sender_id' => 'QRCred',
        'api_endpoint_sms' => 'https://api.sms.com.br/send',
        'api_endpoint_whatsapp' => 'https://api.whatsapp.com/send',
        'teste_mode' => true,
        'teste_numero' => '5511999999999' // Número para testes
    ];
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debug_log("Erro: Método não permitido: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(["erro" => "Método não permitido"]);
    exit;
}

// Obter dados da requisição
$celular = isset($_POST['celular']) ? $_POST['celular'] : "";
$mensagem = isset($_POST['mensagem']) ? $_POST['mensagem'] : "";
$whatsapp = isset($_POST['whatsapp']) && ($_POST['whatsapp'] === 'true' || $_POST['whatsapp'] === '1');
$sms = isset($_POST['sms']) && ($_POST['sms'] === 'true' || $_POST['sms'] === '1');
$token = isset($_POST['token']) ? $_POST['token'] : "";
$teste = isset($_POST['teste']) && ($_POST['teste'] === 'true' || $_POST['teste'] === '1');

// Carregar configurações
$config = getApiSmsConfig();

// Log detalhado
debug_log("DADOS RECEBIDOS:");
debug_log("- Celular: $celular");
debug_log("- Mensagem: $mensagem");
debug_log("- WhatsApp: " . ($whatsapp ? "Sim" : "Não"));
debug_log("- SMS: " . ($sms ? "Sim" : "Não"));
debug_log("- Teste: " . ($teste ? "Sim" : "Não"));

// Validar token de segurança (opcional)
if ($config['token'] && $token !== $config['token']) {
    debug_log("Erro: Token inválido");
    echo json_encode(["erro" => "Token de acesso inválido"]);
    exit;
}

// Validar dados obrigatórios
if (empty($celular) || empty($mensagem)) {
    debug_log("Erro: Dados incompletos (celular ou mensagem não fornecidos)");
    echo json_encode(["erro" => "Dados incompletos"]);
    exit;
}

// Verificar se é para enviar por SMS ou WhatsApp
if (!$sms && !$whatsapp) {
    // Se nenhum método específico for definido, usar SMS como padrão
    $sms = true;
    debug_log("Nenhum método especificado, usando SMS como padrão");
}

// Formatar o celular (remover caracteres não numéricos)
$celular = preg_replace('/\D/', '', $celular);

// Garantir que o celular tenha o prefixo do Brasil (55)
if (!preg_match('/^55/', $celular)) {
    $celular = "55" . $celular;
    debug_log("Adicionando prefixo 55 ao celular: $celular");
}

// Se estiver em modo de teste, usar o número de teste
if ($config['teste_mode'] || $teste) {
    debug_log("Modo de teste ativado. Usando número de teste.");
    $celular_original = $celular;
    $celular = $config['teste_numero'];
    debug_log("Número original: $celular_original, Número de teste: $celular");
}

// Função para enviar SMS usando API externa
function enviarSMS($celular, $mensagem, $config) {
    debug_log("Enviando SMS para $celular");
    
    // Para fins de demonstração e testes, vamos apenas simular o envio
    if ($config['teste_mode']) {
        debug_log("SIMULAÇÃO DE SMS: Enviando para $celular: '$mensagem'");
        return ["success" => true, "message" => "SMS simulado com sucesso"];
    }
    
    // Aqui você implementaria a chamada real para a API de SMS
    // Exemplo com cURL:
    try {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $config['api_endpoint_sms'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'to' => $celular,
                'message' => $mensagem,
                'from' => $config['sender_id']
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['api_key']
            ],
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            debug_log("Erro cURL ao enviar SMS: $err");
            return ["success" => false, "error" => "Falha na requisição: $err"];
        }
        
        debug_log("Resposta da API de SMS: $response");
        return json_decode($response, true);
        
    } catch (Exception $e) {
        debug_log("Exceção ao enviar SMS: " . $e->getMessage());
        return ["success" => false, "error" => "Exceção: " . $e->getMessage()];
    }
}

// Função para enviar WhatsApp
function enviarWhatsApp($celular, $mensagem, $config) {
    debug_log("Enviando WhatsApp para $celular");
    
    // Para fins de demonstração e testes, vamos apenas simular o envio
    if ($config['teste_mode']) {
        debug_log("SIMULAÇÃO DE WHATSAPP: Enviando para $celular: '$mensagem'");
        return ["success" => true, "message" => "WhatsApp simulado com sucesso"];
    }
    
    // Aqui você implementaria a chamada real para a API de WhatsApp
    // Exemplo com cURL para WhatsApp Business API:
    try {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $config['api_endpoint_whatsapp'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'phone' => $celular,
                'body' => $mensagem
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['api_key']
            ],
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            debug_log("Erro cURL ao enviar WhatsApp: $err");
            return ["success" => false, "error" => "Falha na requisição: $err"];
        }
        
        debug_log("Resposta da API de WhatsApp: $response");
        return json_decode($response, true);
        
    } catch (Exception $e) {
        debug_log("Exceção ao enviar WhatsApp: " . $e->getMessage());
        return ["success" => false, "error" => "Exceção: " . $e->getMessage()];
    }
}

// Executar o envio baseado no método selecionado
$resultado = null;

if ($whatsapp) {
    debug_log("Método selecionado: WhatsApp");
    $resultado = enviarWhatsApp($celular, $mensagem, $config);
} else {
    debug_log("Método selecionado: SMS");
    $resultado = enviarSMS($celular, $mensagem, $config);
}

// Verificar o resultado e retornar a resposta apropriada
if ($resultado && isset($resultado['success']) && $resultado['success']) {
    debug_log("Envio bem-sucedido!");
    debug_log("=============== FIM DE PROCESSAMENTO - SUCESSO ===============");
    echo "enviado"; // Formato esperado pela aplicação para sucesso
} else {
    $erro = isset($resultado['error']) ? $resultado['error'] : "Erro desconhecido";
    debug_log("Falha no envio: $erro");
    debug_log("=============== FIM DE PROCESSAMENTO - FALHA ===============");
    echo json_encode(["erro" => $erro]);
}
?> 