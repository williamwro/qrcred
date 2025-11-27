<?php
/**
 * Webhook ZapSign - Recebe notificações de documentos assinados
 * Este script deve ser configurado na ZapSign como endpoint de webhook
 * URL de exemplo: https://seudominio.com/webhook_zapsign.php
 */

// Carregar configurações
require_once __DIR__ . '/webhook_zapsign_config.php';

// Verificar se é modo debug para console do navegador
if (isset($_GET['debug'])) {
    showDebugPage();
    exit;
}

// Configurar headers de resposta
header('Content-Type: application/json; charset=utf-8');

// Verificar se é uma requisição de status
if (isset($_GET['status'])) {
    $statusInfo = [
        'webhook' => 'ZapSign Webhook',
        'version' => '1.2',
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'debug_url' => 'webhook_zapsign.php?debug=1',
        'config' => [
            'connection_file' => 'Adm/php/banco.php',
            'connection_class' => 'Banco::conectar_postgres()',
            'table' => TABLE_NAME,
            'debug_logs' => ENABLE_DEBUG_LOGS ? 'enabled' : 'disabled'
        ]
    ];
    
    // Testar conexão com banco usando arquivo de conexão existente
    try {
        // Verificar se arquivo de conexão existe
        if (file_exists("Adm/php/banco.php")) {
            include "Adm/php/banco.php";
            /** @var PDO $pdo */
            /** @noinspection PhpUndefinedClassInspection */
            $pdo = Banco::conectar_postgres();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $statusInfo['database_status'] = 'connected';
            $statusInfo['connection_method'] = 'Sistema existente (Adm/php/banco.php)';
        } else {
            $statusInfo['database_status'] = 'error: Arquivo Adm/php/banco.php não encontrado';
        }
    } catch (Exception $e) {
        $statusInfo['database_status'] = 'error: ' . $e->getMessage();
    }
    
    echo json_encode($statusInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Função para exibir página de debug com logs no console
 */
function showDebugPage() {
    $logFile = defined('LOG_FILE') ? LOG_FILE : 'webhook_zapsign.log';
    $logs = [];
    
    if (file_exists($logFile)) {
        $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = array_slice($logs, -100); // Últimas 100 linhas
    }
    
    echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Webhook ZapSign</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; }
        .status-box { background: #e7f3ff; border: 1px solid #007bff; border-radius: 4px; padding: 15px; margin-bottom: 20px; }
        .log-container { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; max-height: 400px; overflow-y: auto; }
        .log-line { margin: 2px 0; font-size: 12px; font-family: Consolas, monospace; }
        .log-error { color: #dc3545; }
        .log-success { color: #28a745; }
        .log-info { color: #007bff; }
        .refresh-btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-bottom: 10px; }
        .refresh-btn:hover { background: #0056b3; }
        .debug-info { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Debug Webhook ZapSign</h1>
            <p>Monitoramento em tempo real dos dados recebidos via webhook</p>
        </div>
        
        <div class="debug-info">
            <h3>📋 Informações de Debug</h3>
            <p><strong>Arquivo de Log:</strong> ' . htmlspecialchars($logFile) . '</p>
            <p><strong>Status:</strong> <span id="status">Aguardando dados...</span></p>
            <p><strong>Última atualização:</strong> <span id="lastUpdate">' . date('Y-m-d H:i:s') . '</span></p>
        </div>
        
        <div class="status-box">
            <h3>🚀 Console do Navegador</h3>
            <p>Abra o console do navegador (F12) para ver os logs detalhados em tempo real.</p>
            <button class="refresh-btn" onclick="refreshLogs()">🔄 Atualizar Logs</button>
        </div>
        
        <div class="log-container">
            <h4>📄 Últimos Logs (100 linhas)</h4>';
    
    foreach ($logs as $log) {
        $class = '';
        if (strpos($log, 'ERRO') !== false) $class = 'log-error';
        elseif (strpos($log, 'SUCCESS') !== false) $class = 'log-success';
        elseif (strpos($log, 'INFO') !== false) $class = 'log-info';
        
        echo '<div class="log-line ' . $class . '">' . htmlspecialchars($log) . '</div>';
    }
    
    echo '        </div>
    </div>
    
    <script>
        console.log("🔍 Debug Webhook ZapSign - Console Ativo");
        console.log("📊 Logs serão exibidos aqui em tempo real");
        
        // Logs existentes
        const existingLogs = ' . json_encode($logs) . ';
        console.group("📋 Logs Existentes (" + existingLogs.length + " linhas)");
        existingLogs.forEach(log => {
            if (log.includes("ERRO")) {
                console.error("❌", log);
            } else if (log.includes("SUCCESS")) {
                console.log("✅", log);
            } else if (log.includes("JSON decodificado:")) {
                const jsonPart = log.substring(log.indexOf("{"));
                try {
                    const webhookData = JSON.parse(jsonPart);
                    console.group("📦 Dados do Webhook Decodificados");
                    console.log("🎯 Evento:", webhookData.event_type || webhookData.event);
                    console.log("🔑 Token:", webhookData.token || webhookData.doc_token);
                    console.log("📄 Nome do Documento:", webhookData.name || webhookData.doc_name);
                    console.log("👥 Signatários:", webhookData.signers);
                    console.log("🔗 Dados Completos:", webhookData);
                    console.groupEnd();
                } catch (e) {
                    console.log("📝", log);
                }
            } else {
                console.log("📝", log);
            }
        });
        console.groupEnd();
        
        function refreshLogs() {
            console.log("🔄 Atualizando logs...");
            document.getElementById("status").textContent = "Atualizando...";
            location.reload();
        }
        
        // Auto refresh a cada 10 segundos
        setInterval(() => {
            console.log("🔄 Auto-refresh em 10 segundos...");
            fetch(window.location.href)
                .then(response => response.text())
                .then(data => {
                    console.log("✅ Logs atualizados automaticamente");
                    document.getElementById("lastUpdate").textContent = new Date().toLocaleString();
                })
                .catch(error => {
                    console.error("❌ Erro ao atualizar:", error);
                });
        }, 10000);
        
        // Interceptar dados do webhook em tempo real (simulação)
        window.webhookDebug = {
            logWebhookData: function(data) {
                console.group("🆕 NOVO WEBHOOK RECEBIDO - " + new Date().toLocaleString());
                console.log("📊 Dados Raw:", data);
                
                if (data.event_type || data.event) {
                    console.log("🎯 Tipo de Evento:", data.event_type || data.event);
                }
                
                if (data.token || data.doc_token) {
                    console.log("🔑 Token do Documento:", data.token || data.doc_token);
                }
                
                if (data.name || data.doc_name) {
                    console.log("📄 Nome do Documento:", data.name || data.doc_name);
                }
                
                if (data.signers && Array.isArray(data.signers)) {
                    console.group("👥 Signatários (" + data.signers.length + ")");
                    data.signers.forEach((signer, index) => {
                        console.group("👤 Signatário " + (index + 1));
                        console.log("📝 Nome:", signer.name);
                        console.log("📧 Email:", signer.email);
                        console.log("🆔 CPF:", signer.cpf);
                        console.log("✍️ Status:", signer.status);
                        console.log("✅ Assinou:", signer.has_signed);
                        console.log("📅 Data Assinatura:", signer.signed_at);
                        console.log("🔗 Token:", signer.token);
                        console.groupEnd();
                    });
                    console.groupEnd();
                }
                
                console.groupEnd();
                document.getElementById("status").textContent = "Webhook recebido: " + (data.event_type || data.event);
                document.getElementById("lastUpdate").textContent = new Date().toLocaleString();
            }
        };
        
        console.log("✅ Debug page carregada. Use webhookDebug.logWebhookData(data) para logar dados manualmente.");
    </script>
</body>
</html>';
}

/**
 * Função para escrever logs
 */
function writeLog($message) {
    if (!ENABLE_DEBUG_LOGS) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    
    // Verificar tamanho do arquivo de log
    if (MAX_LOG_SIZE > 0 && file_exists(LOG_FILE) && filesize(LOG_FILE) > MAX_LOG_SIZE) {
        // Fazer backup do log atual e começar novo
        rename(LOG_FILE, LOG_FILE . '.backup.' . date('Y-m-d-H-i-s'));
    }
    
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Função para logar dados estruturados do webhook para debug
 */
function logWebhookDataForDebug($webhookData) {
    writeLog("=== DADOS COMPLETOS DO WEBHOOK ===");
    writeLog("JSON Raw: " . json_encode($webhookData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Logar cada campo individualmente para facilitar debug
    $fields = [
        'event_type', 'event', 'token', 'doc_token', 'name', 'doc_name', 
        'signed_at', 'created_at', 'updated_at', 'status', 'signers'
    ];
    
    writeLog("=== CAMPOS INDIVIDUAIS ===");
    foreach ($fields as $field) {
        if (isset($webhookData[$field])) {
            $value = is_array($webhookData[$field]) ? json_encode($webhookData[$field], JSON_UNESCAPED_UNICODE) : $webhookData[$field];
            writeLog("Campo '{$field}': {$value}");
        } else {
            writeLog("Campo '{$field}': [NÃO ENCONTRADO]");
        }
    }
    
    // Logar signatários detalhadamente
    if (isset($webhookData['signers']) && is_array($webhookData['signers'])) {
        writeLog("=== SIGNATÁRIOS DETALHADOS ===");
        foreach ($webhookData['signers'] as $index => $signer) {
            writeLog("--- Signatário " . ($index + 1) . " ---");
            writeLog("Dados completos: " . json_encode($signer, JSON_UNESCAPED_UNICODE));
            
            $signerFields = ['name', 'email', 'cpf', 'status', 'has_signed', 'signed_at', 'token', 'phone'];
            foreach ($signerFields as $field) {
                $value = isset($signer[$field]) ? $signer[$field] : '[NÃO ENCONTRADO]';
                writeLog("  {$field}: {$value}");
            }
        }
    }
    
    writeLog("=== FIM DOS DADOS DO WEBHOOK ===");
}

/**
 * Função para resposta JSON
 */
function jsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    writeLog("=== WEBHOOK ZAPSIGN INICIADO ===");
    writeLog("Método: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
    writeLog("Headers: " . json_encode(getallheaders()));
    writeLog("Query Params: " . json_encode($_GET));
    
    // Verificar se é uma requisição POST (mais tolerante)
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if (empty($method)) {
        writeLog("AVISO: Método HTTP não detectado, tentando processar mesmo assim");
    } elseif ($method !== 'POST') {
        writeLog("ERRO: Método não permitido - " . $method);
        jsonResponse([
            'status' => 'erro',
            'mensagem' => 'Apenas requisições POST são aceitas',
            'metodo_recebido' => $method
        ], 405);
    }

    // Obter o corpo da requisição
    $input = file_get_contents('php://input');
    writeLog("Corpo da requisição recebido: " . $input);

    if (empty($input)) {
        writeLog("ERRO: Corpo da requisição vazio");
        jsonResponse([
            'status' => 'erro',
            'mensagem' => 'Corpo da requisição vazio'
        ], 400);
    }

    // Decodificar JSON
    $webhookData = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        writeLog("ERRO: JSON inválido - " . json_last_error_msg());
        jsonResponse([
            'status' => 'erro',
            'mensagem' => 'JSON inválido: ' . json_last_error_msg()
        ], 400);
    }

    writeLog("JSON decodificado: " . json_encode($webhookData, JSON_UNESCAPED_UNICODE));
    
    // Log detalhado para debug no console
    logWebhookDataForDebug($webhookData);

    // Mapear campos da ZapSign para os campos esperados (compatibilidade)
    $event = $webhookData['event_type'] ?? $webhookData['event'] ?? '';
    $docToken = $webhookData['token'] ?? $webhookData['doc_token'] ?? '';
    $docName = $webhookData['name'] ?? $webhookData['doc_name'] ?? '';
    $signedAt = $webhookData['signed_at'] ?? null;

    // Validar estrutura do webhook
    if (empty($event) || empty($docToken)) {
        writeLog("ERRO: Estrutura do webhook inválida");
        writeLog("Event: '{$event}', DocToken: '{$docToken}'");
        jsonResponse([
            'status' => 'erro',
            'mensagem' => 'Estrutura do webhook inválida - event_type e token são obrigatórios',
            'recebido' => [
                'event_type' => $event,
                'token' => $docToken,
                'name' => $docName
            ]
        ], 400);
    }

    writeLog("Event: {$event}");
    writeLog("Doc Token: {$docToken}");
    writeLog("Doc Name: {$docName}");

    // Verificar se é um evento de documento assinado
    if ($event !== 'doc_signed') {
        writeLog("INFO: Evento ignorado - {$event}");
        jsonResponse([
            'status' => 'sucesso',
            'mensagem' => 'Evento processado (ignorado)',
            'event' => $event
        ]);
    }

    // Função para normalizar texto para comparação
    function normalizeText($text) {
        // Converter para UTF-8 se necessário
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8');
        }
        
        // Normalizar caracteres Unicode
        if (function_exists('normalizer_normalize')) {
            $text = normalizer_normalize($text, Normalizer::FORM_C);
        }
        
        // Remover espaços extras e converter para minúsculas
        return trim(mb_strtolower($text, 'UTF-8'));
    }
    
    // Filtrar apenas documentos com nome "Termo Adesão SasPyx"
    $expectedDocName = 'Termo Adesão SasPyx';
    $normalizedDocName = normalizeText($docName);
    $normalizedExpected = normalizeText($expectedDocName);
    
    writeLog("DEBUG: Comparação de nomes:");
    writeLog("- Recebido: '{$docName}' (bytes: " . bin2hex($docName) . ")");
    writeLog("- Esperado: '{$expectedDocName}' (bytes: " . bin2hex($expectedDocName) . ")");
    writeLog("- Normalizado recebido: '{$normalizedDocName}'");
    writeLog("- Normalizado esperado: '{$normalizedExpected}'");
    
    if ($normalizedDocName !== $normalizedExpected) {
        writeLog("INFO: Documento ignorado - nome não confere após normalização");
        jsonResponse([
            'status' => 'sucesso',
            'mensagem' => 'Documento processado (ignorado por filtro de nome)',
            'doc_name' => $docName,
            'filtro' => 'Apenas documentos "Termo Adesão SasPyx" são processados'
        ]);
    }
    
    writeLog("SUCCESS: Nome do documento validado com sucesso");

    // Validar se há signatários
    if (!isset($webhookData['signers']) || !is_array($webhookData['signers'])) {
        writeLog("ERRO: Signatários não encontrados");
        jsonResponse([
            'status' => 'erro',
            'mensagem' => 'Signatários não encontrados'
        ], 400);
    }

    // Conectar ao banco PostgreSQL usando arquivo de conexão existente
    writeLog("Conectando ao banco usando arquivo de conexão do sistema...");
    
    // Verificar se arquivo de conexão existe
    if (!file_exists("Adm/php/banco.php")) {
        writeLog("ERRO: Arquivo Adm/php/banco.php não encontrado");
        jsonResponse([
            'status' => 'erro',
            'mensagem' => 'Arquivo de conexão com banco não encontrado'
        ], 500);
    }
    
    // Incluir arquivo de conexão com banco
    include "Adm/php/banco.php";
    
    // Verificar se classe Banco existe
    if (!class_exists('Banco')) {
        writeLog("ERRO: Classe Banco não encontrada no arquivo incluído");
        jsonResponse([
            'status' => 'erro',
            'mensagem' => 'Classe de conexão com banco não encontrada'
        ], 500);
    }
    
    // Conectando ao banco de dados utilizando o PDO
    /** @var PDO $pdo */
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    writeLog("Conexão com banco estabelecida");

    // Processar cada signatário
    $processedSigners = 0;
    $errors = [];

    foreach ($webhookData['signers'] as $signer) {
        writeLog("Processando signatário: " . json_encode($signer, JSON_UNESCAPED_UNICODE));

        $signerName = $signer['name'] ?? '';
        $signerEmail = $signer['email'] ?? '';
        $signerCpf = $signer['cpf'] ?? '';
        // ZapSign envia 'status' em vez de 'has_signed'
        $hasSigned = ($signer['status'] ?? '') === 'signed' || ($signer['has_signed'] ?? false);
        $signerSignedAt = $signer['signed_at'] ?? null;
        $signerToken = $signer['token'] ?? '';

        // Validar dados mínimos do signatário
        if (empty($signerCpf)) {
            $error = "CPF do signatário não encontrado";
            writeLog("ERRO: {$error}");
            $errors[] = $error;
            continue;
        }

        // Limpar CPF (remover pontos e traços)
        $cpfLimpo = preg_replace('/[^0-9]/', '', $signerCpf);

        try {
            // Estratégia robusta para evitar constraint violations:
            // 1. Buscar por CPF primeiro (dados do mesmo usuário)
            // 2. Se não encontrar, buscar registro reutilizável (codigo vazio)
            // 3. Se não encontrar nenhum, criar com código único
            
            writeLog("PASSO 1: Buscando registro existente por CPF: {$cpfLimpo}");
            
            $stmtCpf = $pdo->prepare("
                SELECT id, codigo, nome, celular 
                FROM " . TABLE_NAME . "
                WHERE cpf = :cpf
                LIMIT 1
            ");
            
            $stmtCpf->execute([':cpf' => $cpfLimpo]);
            $recordByCpf = $stmtCpf->fetch();
            
            if ($recordByCpf) {
                writeLog("SUCESSO: Encontrado registro por CPF - ID: {$recordByCpf['id']}");
                
                // Atualizar registro existente do mesmo CPF
                $updateStmt = $pdo->prepare("
                    UPDATE " . TABLE_NAME . "
                    SET 
                        event = :event,
                        doc_token = :doc_token,
                        doc_name = :doc_name,
                        signed_at = :signed_at,
                        name = :name,
                        email = :email,
                        has_signed = :has_signed,
                        autorizado = :autorizado,
                        cel_informado = :cel_informado,
                        data_hora = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");

                $updateStmt->execute([
                    ':event' => $event,
                    ':doc_token' => $docToken,
                    ':doc_name' => $docName,
                    ':signed_at' => $signedAt,
                    ':name' => $signerName,
                    ':email' => $signerEmail,
                    ':has_signed' => $hasSigned ? 1 : 0,
                    ':autorizado' => $hasSigned ? 1 : 0,
                    ':cel_informado' => '',
                    ':id' => $recordByCpf['id']
                ]);

                writeLog("Registro atualizado com sucesso por CPF - ID: {$recordByCpf['id']}");
                $processedSigners++;
                
            } else {
                writeLog("PASSO 2: CPF não encontrado, buscando registro reutilizável (codigo vazio)");
                
                // Buscar registro com codigo vazio ou null para reutilizar
                $stmtEmpty = $pdo->prepare("
                    SELECT id, codigo, nome 
                    FROM " . TABLE_NAME . "
                    WHERE (codigo IS NULL OR codigo = '')
                    AND (cpf IS NULL OR cpf = '')
                    LIMIT 1
                ");
                
                $stmtEmpty->execute();
                $emptyRecord = $stmtEmpty->fetch();
                
                if ($emptyRecord) {
                    writeLog("SUCESSO: Encontrado registro reutilizável - ID: {$emptyRecord['id']}");
                    
                    // Gerar código temporário único para este registro
                    $codigoTemporario = 'webhook_' . substr($docToken, 0, 8) . '_' . substr($cpfLimpo, -4) . '_' . time();
                    
                    // Atualizar registro vazio com os dados da assinatura
                    $updateEmptyStmt = $pdo->prepare("
                        UPDATE " . TABLE_NAME . "
                        SET 
                            codigo = :codigo,
                            nome = :nome,
                            event = :event,
                            doc_token = :doc_token,
                            doc_name = :doc_name,
                            signed_at = :signed_at,
                            name = :name,
                            email = :email,
                            cpf = :cpf,
                            has_signed = :has_signed,
                            autorizado = :autorizado,
                            aceitou_termo = 1,
                            cel_informado = :cel_informado,
                            data_hora = CURRENT_TIMESTAMP
                        WHERE id = :id
                    ");

                    $updateEmptyStmt->execute([
                        ':codigo' => $codigoTemporario,
                        ':nome' => $signerName,
                        ':event' => $event,
                        ':doc_token' => $docToken,
                        ':doc_name' => $docName,
                        ':signed_at' => $signedAt,
                        ':name' => $signerName,
                        ':email' => $signerEmail,
                        ':cpf' => $cpfLimpo,
                        ':has_signed' => $hasSigned ? 1 : 0,
                        ':autorizado' => $hasSigned ? 1 : 0,
                        ':cel_informado' => '',
                        ':id' => $emptyRecord['id']
                    ]);

                    writeLog("Registro reutilizável atualizado com sucesso - ID: {$emptyRecord['id']}, Código: {$codigoTemporario}");
                    $processedSigners++;
                    
                } else {
                    writeLog("PASSO 3: Nenhum registro reutilizável encontrado, criando novo registro");
                    
                    // Gerar código único para novo registro
                    $codigoNovo = 'webhook_' . substr($docToken, 0, 8) . '_' . substr($cpfLimpo, -4) . '_' . uniqid();
                    
                    writeLog("Código para novo registro: {$codigoNovo}");
                    
                    // Inserir novo registro
                    $insertStmt = $pdo->prepare("
                        INSERT INTO " . TABLE_NAME . "
                        (codigo, nome, celular, data_hora, autorizado, aceitou_termo, event, doc_token, doc_name, signed_at, name, email, cpf, has_signed, cel_informado)
                        VALUES 
                        (:codigo, :nome, :celular, CURRENT_TIMESTAMP, :autorizado, :aceitou_termo, :event, :doc_token, :doc_name, :signed_at, :name, :email, :cpf, :has_signed, :cel_informado)
                    ");

                    $insertStmt->execute([
                        ':codigo' => $codigoNovo,
                        ':nome' => $signerName,
                        ':celular' => '',
                        ':autorizado' => $hasSigned ? 1 : 0,
                        ':aceitou_termo' => 1,
                        ':event' => $event,
                        ':doc_token' => $docToken,
                        ':doc_name' => $docName,
                        ':signed_at' => $signedAt,
                        ':name' => $signerName,
                        ':email' => $signerEmail,
                        ':cpf' => $cpfLimpo,
                        ':has_signed' => $hasSigned ? 1 : 0,
                        ':cel_informado' => ''
                    ]);

                    writeLog("Novo registro criado com sucesso - Código: {$codigoNovo}");
                    $processedSigners++;
                }
            }

        } catch (PDOException $e) {
            $error = "Erro ao processar signatário {$signerName}: " . $e->getMessage();
            writeLog("ERRO DB: {$error}");
            $errors[] = $error;
        }
    }

    // Preparar resposta
    if ($processedSigners > 0) {
        $response = [
            'status' => 'sucesso',
            'mensagem' => "Webhook processado com sucesso",
            'processados' => $processedSigners,
            'event' => $event,
            'doc_token' => $docToken,
            'doc_name' => $docName
        ];

        if (!empty($errors)) {
            $response['avisos'] = $errors;
        }

        writeLog("SUCCESS: " . json_encode($response));
        jsonResponse($response);

    } else {
        $response = [
            'status' => 'erro',
            'mensagem' => 'Nenhum signatário foi processado',
            'erros' => $errors
        ];

        writeLog("ERRO: " . json_encode($response));
        jsonResponse($response, 422);
    }

} catch (PDOException $e) {
    $error = "Erro de conexão com banco: " . $e->getMessage();
    writeLog("ERRO PDO: {$error}");
    
    jsonResponse([
        'status' => 'erro',
        'mensagem' => 'Erro interno do servidor (banco de dados)',
        'erro' => $error
    ], 500);

} catch (Exception $e) {
    $error = "Erro geral: " . $e->getMessage();
    writeLog("ERRO GERAL: {$error}");
    
    jsonResponse([
        'status' => 'erro',
        'mensagem' => 'Erro interno do servidor',
        'erro' => $error
    ], 500);
}
?> 