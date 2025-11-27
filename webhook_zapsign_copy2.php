<?php
/**
 * Webhook ZapSign - Recebe notificações de documentos assinados
 * Este script deve ser configurado na ZapSign como endpoint de webhook
 * URL de exemplo: https://seudominio.com/webhook_zapsign.php
 * 
 * VERSÃO COM NOTIFICAÇÕES EM TEMPO REAL - Mantém 100% compatibilidade com sistema existente
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

// Verificar se é uma requisição para reprocessar documentos pendentes
if (isset($_GET['reprocessar'])) {
    reprocessarDocumentosPendentes();
    exit;
}

// Verificar se é uma requisição de status
if (isset($_GET['status'])) {
    $statusInfo = [
        'webhook' => 'ZapSign Webhook',
        'version' => '1.3', // Incrementado para indicar versão com notificações
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'debug_url' => 'webhook_zapsign.php?debug=1',
        'features' => ['realtime_notifications' => 'enabled'], // Nova feature
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
 * NOVA FUNÇÃO: Enviar notificação em tempo real (NÃO interfere no funcionamento existente)
 */
function sendRealtimeNotification($pdo, $eventType, $recordData) {
    try {
        $notificationData = [
            'event_type' => $eventType,
            'timestamp' => time(),
            'data' => $recordData
        ];
        
        $channel = ($eventType === 'new_signature') ? 'new_assinatura_digital' : 'update_assinatura_digital';
        
        $notifyStmt = $pdo->prepare("SELECT pg_notify(?, ?)");
        $notifyStmt->execute([$channel, json_encode($notificationData)]);
        
        writeLog("NOTIFICAÇÃO ENVIADA: {$eventType} para código {$recordData['codigo']}");
        
        return true;
        
    } catch (Exception $e) {
        writeLog("ERRO NOTIFICAÇÃO (não crítico): " . $e->getMessage());
        // Retorna true mesmo com erro para não afetar fluxo principal
        return true;
    }
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
                .notification-status { background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 10px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔍 Debug Webhook ZapSign</h1>
                    <p>Monitoramento em tempo real dos dados recebidos via webhook</p>
                    <div class="notification-status">
                        <strong>🔔 Notificações em Tempo Real:</strong> Ativadas (versão 1.3)
                    </div>
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
                console.log("🔍 Debug Webhook ZapSign - Console Ativo (v1.3 com notificações)");
                console.log("📊 Logs serão exibidos aqui em tempo real");
                
                // Logs existentes
                const existingLogs = ' . json_encode($logs) . ';
                console.group("📋 Logs Existentes (" + existingLogs.length + " linhas)");
                existingLogs.forEach(log => {
                    if (log.includes("ERRO")) {
                        console.error("❌", log);
                    } else if (log.includes("SUCCESS")) {
                        console.log("✅", log);
                    } else if (log.includes("NOTIFICAÇÃO ENVIADA")) {
                        console.log("🔔", log);
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
                
                console.log("✅ Debug page carregada (v1.3). Use webhookDebug.logWebhookData(data) para logar dados manualmente.");
            </script>
        </body>
        </html>';
}

/**
 * Função para consultar dados específicos do signatário via API ZapSign
 * Endpoint: GET /api/v1/signers/{signer_token}/
 */
function consultarSignatarioZapSign($signerToken) {
    writeLog("CONSULTA SIGNATÁRIO API: Iniciando consulta para signatário {$signerToken}");
    
    if (!defined('ZAPSIGN_API_TOKEN') || empty(ZAPSIGN_API_TOKEN)) {
        writeLog("ERRO API: Token da API ZapSign não configurado");
        return null;
    }
    
    $url = ZAPSIGN_API_BASE_URL . "/signers/{$signerToken}/";
    $headers = [
        'Authorization: Bearer ' . ZAPSIGN_API_TOKEN,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        writeLog("ERRO SIGNATÁRIO API: Erro cURL - {$curlError}");
        return null;
    }
    
    if ($httpCode !== 200) {
        writeLog("ERRO SIGNATÁRIO API: Código HTTP {$httpCode} - Response: {$response}");
        return null;
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        writeLog("ERRO SIGNATÁRIO API: Resposta JSON inválida - {$response}");
        return null;
    }
    
    writeLog("SUCESSO SIGNATÁRIO API: Dados do signatário obtidos com sucesso");
    writeLog("SIGNATÁRIO API RESPONSE: " . json_encode($data, JSON_UNESCAPED_UNICODE));
    
    return $data;
}

/**
 * Função para consultar dados completos do documento via API ZapSign
 */
function consultarDocumentoZapSign($docToken) {
    writeLog("CONSULTA API: Iniciando consulta para documento {$docToken}");
    
    if (!defined('ZAPSIGN_API_TOKEN') || empty(ZAPSIGN_API_TOKEN)) {
        writeLog("ERRO API: Token da API ZapSign não configurado");
        return null;
    }
    
    $url = ZAPSIGN_API_BASE_URL . "/docs/{$docToken}/";
    $headers = [
        'Authorization: Bearer ' . ZAPSIGN_API_TOKEN,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        writeLog("ERRO API: Erro cURL - {$curlError}");
        return null;
    }
    
    if ($httpCode !== 200) {
        writeLog("ERRO API: Código HTTP {$httpCode} - Response: {$response}");
        return null;
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        writeLog("ERRO API: Resposta JSON inválida - {$response}");
        return null;
    }
    
    writeLog("SUCESSO API: Dados do documento obtidos com sucesso");
    writeLog("API RESPONSE: " . json_encode($data, JSON_UNESCAPED_UNICODE));
    
    return $data;
}

/**
 * Função utilitária para extrair Signer Token de URL ZapSign
 * URL exemplo: https://app.zapsign.com.br/verificar/92b36ec9-a449-4574-8ff0-5cc2c5ab7
 * Retorna: 92b36ec9-a449-4574-8ff0-5cc2c5ab7
 */
function extrairSignerTokenDaUrl($url) {
    // Padrão para URLs do ZapSign: .../verificar/{signer_token}
    if (preg_match('/\/verificar\/([a-f0-9\-]+)/', $url, $matches)) {
        $signerToken = $matches[1];
        writeLog("SIGNER TOKEN EXTRAÍDO DA URL: {$signerToken}");
        return $signerToken;
    }
    
    writeLog("ERRO: Não foi possível extrair signer token da URL: {$url}");
    return null;
}

/**
 * Função para extrair dados do signatário da resposta da API
 */
function extrairDadosSignatario($apiResponse, $signerToken = null) {
    if (!isset($apiResponse['signers']) || !is_array($apiResponse['signers'])) {
        writeLog("ERRO API: Nenhum signatário encontrado na resposta");
        return null;
    }
    
    // Se temos o token do signatário, buscar por ele
    if ($signerToken) {
        foreach ($apiResponse['signers'] as $signer) {
            if (($signer['token'] ?? '') === $signerToken) {
                writeLog("SUCESSO API: Signatário encontrado pelo token {$signerToken}");
                return $signer;
            }
        }
        writeLog("AVISO API: Signatário com token {$signerToken} não encontrado, usando primeiro signatário");
    }
    
    // Se não encontrou pelo token ou não foi fornecido, usar o primeiro signatário
    $signer = $apiResponse['signers'][0];
    writeLog("SUCESSO API: Usando primeiro signatário - " . ($signer['name'] ?? 'Nome não disponível'));
    
    return $signer;
}

/**
 * Função para registrar documento pendente para reprocessamento
 */
function registrarDocumentoPendente($pdo, $docToken, $docName, $evento, $dadosObtidos = false, $cpf = '', $telefone = '', $email = '', $erro = '') {
    try {
        // Verificar se já existe
        $stmt = $pdo->prepare("SELECT id FROM sind.documentos_pendentes_zapsign WHERE doc_token = :doc_token");
        $stmt->execute([':doc_token' => $docToken]);
        
        if ($stmt->fetch()) {
            // Atualizar existente
            $updateStmt = $pdo->prepare("
                UPDATE sind.documentos_pendentes_zapsign 
                SET ultima_tentativa = CURRENT_TIMESTAMP,
                    numero_tentativas = numero_tentativas + 1,
                    dados_obtidos = :dados_obtidos,
                    cpf_encontrado = :cpf,
                    telefone_encontrado = :telefone,
                    email_encontrado = :email,
                    erro_ultima_tentativa = :erro,
                    status_processamento = CASE 
                        WHEN :dados_obtidos THEN 'concluido'
                        WHEN numero_tentativas >= 10 THEN 'erro_permanente'
                        ELSE 'pendente'
                    END,
                    processar_apos = CASE 
                        WHEN :dados_obtidos THEN CURRENT_TIMESTAMP
                        ELSE CURRENT_TIMESTAMP + INTERVAL '10 minutes'
                    END
                WHERE doc_token = :doc_token
            ");
            
            $updateStmt->execute([
                ':doc_token' => $docToken,
                ':dados_obtidos' => $dadosObtidos ? 'true' : 'false',
                ':cpf' => $cpf,
                ':telefone' => $telefone,
                ':email' => $email,
                ':erro' => $erro
            ]);
            
            writeLog("PENDENTE: Documento atualizado na fila de reprocessamento");
        } else {
            // Inserir novo
            $insertStmt = $pdo->prepare("
                INSERT INTO sind.documentos_pendentes_zapsign 
                (doc_token, doc_name, evento_inicial, dados_obtidos, cpf_encontrado, telefone_encontrado, email_encontrado, erro_ultima_tentativa, processar_apos)
                VALUES (:doc_token, :doc_name, :evento, :dados_obtidos, :cpf, :telefone, :email, :erro, CURRENT_TIMESTAMP + INTERVAL '5 minutes')
            ");
            
            $insertStmt->execute([
                ':doc_token' => $docToken,
                ':doc_name' => $docName,
                ':evento' => $evento,
                ':dados_obtidos' => $dadosObtidos ? 'true' : 'false',
                ':cpf' => $cpf,
                ':telefone' => $telefone,
                ':email' => $email,
                ':erro' => $erro
            ]);
            
            writeLog("PENDENTE: Documento adicionado à fila de reprocessamento (tentativa em 2 minutos)");
        }
        
    } catch (Exception $e) {
        writeLog("ERRO PENDENTE: " . $e->getMessage());
    }
}

/**
 * Função para tentar obter dados com múltiplas tentativas
 * Usa AMBOS os endpoints: documento E signatário específico
 */
function tentarObterDados($docToken, $signerToken = null, $maxTentativas = 3) {
    for ($tentativa = 1; $tentativa <= $maxTentativas; $tentativa++) {
        writeLog("TENTATIVA {$tentativa}/{$maxTentativas}: Consultando APIs ZapSign...");
        
        // PRIMEIRA ESTRATÉGIA: Endpoint específico do signatário (pode conter CPF)
        if ($signerToken) {
            writeLog("ESTRATÉGIA 1: Consultando endpoint específico do signatário");
            $signerData = consultarSignatarioZapSign($signerToken);
            if ($signerData) {
                $cpf = $signerData['cpf'] ?? '';
                $telefone = $signerData['phone_number'] ?? '';
                $email = $signerData['email'] ?? '';
                $nome = $signerData['name'] ?? '';
                $status = $signerData['status'] ?? '';
                $signedAt = $signerData['signed_at'] ?? '';
                
                writeLog("DADOS SIGNATÁRIO API: CPF={$cpf}, Nome={$nome}, Email={$email}, Telefone={$telefone}, Status={$status}");
                
                // Verificar se obtivemos dados úteis
                if (!empty($cpf) || !empty($telefone) || !empty($email)) {
                    writeLog("SUCESSO TENTATIVA {$tentativa}: Dados obtidos do endpoint do signatário");
                    return [
                        'sucesso' => true,
                        'signer' => $signerData,
                        'tentativa' => $tentativa,
                        'fonte' => 'endpoint_signatario'
                    ];
                }
            }
        }
        
        // SEGUNDA ESTRATÉGIA: Endpoint do documento (método atual)
        writeLog("ESTRATÉGIA 2: Consultando endpoint do documento");
        $apiData = consultarDocumentoZapSign($docToken);
        if ($apiData) {
            $signer = extrairDadosSignatario($apiData, $signerToken);
            if ($signer) {
                $cpf = $signer['cpf'] ?? '';
                $telefone = $signer['phone_number'] ?? '';
                $email = $signer['email'] ?? '';
                
                writeLog("DADOS DOCUMENTO API: CPF={$cpf}, Email={$email}, Telefone={$telefone}");
                
                // Verificar se obtivemos dados úteis
                if (!empty($cpf) || !empty($telefone) || !empty($email)) {
                    writeLog("SUCESSO TENTATIVA {$tentativa}: Dados obtidos do endpoint do documento");
                    return [
                        'sucesso' => true,
                        'signer' => $signer,
                        'tentativa' => $tentativa,
                        'fonte' => 'endpoint_documento'
                    ];
                }
            }
        }
        
        if ($tentativa < $maxTentativas) {
            writeLog("TENTATIVA {$tentativa} falhou em ambas estratégias, aguardando 2 minutos...");
            sleep(120); // Aguardar 2 minutos (120 segundos) entre tentativas para dar tempo ao usuário completar o processo
        }
    }
    
    writeLog("FALHA: Todas as {$maxTentativas} tentativas falharam em ambas estratégias");
    return ['sucesso' => false, 'tentativas' => $maxTentativas];
}

/**
 * Função auxiliar para determinar o tipo do documento
 */
function determinarTipoDocumento($docToken, $docName) {
    // Tokens específicos para cada tipo de documento
    $tokenAdesao = '4bdad7db-07ae-4505-b8cb-0bee880f6fdd';
    $tokenAntecipacao = '762dbe4c-654b-432b-a7a9-38435966e0aa';
    
    // Nomes dos documentos
    $nomeAdesao = 'TERMO DE ADESÃO DO CARTÃO CONVÊNIO';
    $nomeAntecipacao = 'Contrato de Antecipação Salarial';
    
    // Verificar por token primeiro (mais preciso)
    if ($docToken === $tokenAdesao) {
        return 1; // Adesão
    } elseif ($docToken === $tokenAntecipacao) {
        return 2; // Antecipação
    } else {
        // Se não identificou pelo token, verificar pelo nome
        function normalizeText($text) {
            // Converter para UTF-8 se necessário
            if (!mb_check_encoding($text, 'UTF-8')) {
                $text = mb_convert_encoding($text, 'UTF-8', 'auto');
            }
            
            // Normalizar caracteres Unicode
            if (function_exists('normalizer_normalize')) {
                $text = normalizer_normalize($text, Normalizer::FORM_C);
            }
            
            // Remover espaços extras e converter para minúsculas
            return trim(mb_strtolower($text, 'UTF-8'));
        }
        
        $normalizedDocName = normalizeText($docName);
        $normalizedAdesao = normalizeText($nomeAdesao);
        $normalizedAntecipacao = normalizeText($nomeAntecipacao);
        
        if ($normalizedDocName === $normalizedAdesao) {
            return 1; // Adesão
        } elseif ($normalizedDocName === $normalizedAntecipacao) {
            return 2; // Antecipação
        }
    }
    
    // Padrão: tipo indefinido
    return 0;
}

/**
 * Função para atualizar registro existente com dados completos quando CPF é obtido
 * EVITA DUPLICATAS - atualiza registro existente em vez de criar novo
 */
function gravarRegistroComDadosCompletos($pdo, $docToken, $docName, $signerName, $signerEmail, $cpfLimpo, $signerPhone, $signedAt, $signerToken, $tipoDocumento) {
    try {
        writeLog("REGISTRO COMPLETO: Iniciando atualização de registro existente com dados obtidos");
        
        // Buscar dados do associado pelo CPF para obter id e id_divisao
        $associadoStmt = $pdo->prepare("SELECT id, id_divisao FROM sind.associado WHERE cpf = :cpf LIMIT 1");
        $associadoStmt->execute([':cpf' => $cpfLimpo]);
        $associadoData = $associadoStmt->fetch(PDO::FETCH_ASSOC);
        
        $idAssociado = $associadoData['id'] ?? null;
        $idDivisao = $associadoData['id_divisao'] ?? null;
        
        // Primeiro, buscar registro existente por doc_token
        $searchStmt = $pdo->prepare("
            SELECT id, codigo 
            FROM " . TABLE_NAME . " 
            WHERE doc_token = :doc_token 
            ORDER BY data_hora DESC 
            LIMIT 1
        ");
        
        $searchStmt->execute([':doc_token' => $docToken]);
        $registroExistente = $searchStmt->fetch();
        
        if ($registroExistente) {
            // ATUALIZAR registro existente
            $registroId = $registroExistente['id'];
            $codigoExistente = $registroExistente['codigo'];
            
            writeLog("REGISTRO COMPLETO: Encontrado registro existente ID: {$registroId}, Código: {$codigoExistente}");
            
            $updateStmt = $pdo->prepare("
                UPDATE " . TABLE_NAME . " 
                SET 
                    nome = :nome,
                    celular = :celular,
                    autorizado = :autorizado,
                    aceitou_termo = :aceitou_termo,
                    event = :event,
                    signed_at = :signed_at,
                    name = :name,
                    email = :email,
                    cpf = :cpf,
                    has_signed = :has_signed,
                    cel_informado = :cel_informado,
                    id_associado = :id_associado,
                    id_divisao = :id_divisao,
                    data_hora = NOW()::timestamp(0)
                WHERE id = :id
            ");
            
            $updateStmt->execute([
                ':id' => $registroId,
                ':nome' => $signerName,
                ':celular' => $signerPhone ?? '',
                ':autorizado' => 1, // Autorizado pois dados foram obtidos
                ':aceitou_termo' => 1, // Termo aceito
                ':event' => 'doc_signed', // ✅ MARCAR COMO DOC_SIGNED quando CPF encontrado
                ':signed_at' => $signedAt,
                ':name' => $signerName,
                ':email' => $signerEmail,
                ':cpf' => $cpfLimpo,
                ':has_signed' => 1, // ✅ MARCAR COMO ASSINADO quando CPF encontrado
                ':cel_informado' => $signerPhone ?? '', // ✅ GRAVAR CELULAR RETORNADO PELO WEBHOOK
                ':id_associado' => $idAssociado,
                ':id_divisao' => $idDivisao
            ]);
            
            writeLog("REGISTRO COMPLETO: Registro atualizado com sucesso - ID: {$registroId}");
            writeLog("CAMPOS ATUALIZADOS: event=doc_signed, has_signed=true, cel_informado={$signerPhone}, cpf={$cpfLimpo}");
            
            // Enviar notificação como se fosse assinatura concluída
            sendRealtimeNotification($pdo, 'signature_completed', [
                'id' => $registroId,
                'codigo' => $codigoExistente,
                'nome' => $signerName,
                'celular' => $signerPhone ?? '',
                'email' => $signerEmail,
                'cpf' => $cpfLimpo,
                'autorizado' => true,
                'aceitou_termo' => true,
                'has_signed' => true,
                'event' => 'doc_signed',
                'doc_token' => $docToken,
                'doc_name' => $docName,
                'signed_at' => $signedAt,
                'data_hora' => date('Y-m-d H:i:s'),
                'origem' => 'api_zapsign_updated'
            ]);
            
            writeLog("REGISTRO COMPLETO: Notificação enviada para registro atualizado");
            
            // ✅ EXECUTAR APROVAÇÃO AUTOMÁTICA quando CPF está disponível
            if (!empty($cpfLimpo)) {
                executarAprovacaoAutomatica($pdo, $registroId, $cpfLimpo);
            }
            
            return $registroId;
            
        } else {
            // Se não encontrou registro existente, criar novo (caso raro)
            writeLog("REGISTRO COMPLETO: Nenhum registro existente encontrado, criando novo");

            // Tentativa extra: obter CPF correto via API ZapSign usando doc_token
            $codigoParaInserir = null;
            try {
                $apiData = consultarDocumentoZapSign($docToken);
                $cpfApi = '';
                // Tentar extrair CPF do signatário específico (se token disponível)
                if (!empty($signerToken)) {
                    $signerApi = extrairDadosSignatario($apiData, $signerToken);
                    if (is_array($signerApi) && !empty($signerApi['cpf'])) {
                        $cpfApi = preg_replace('/\D+/', '', $signerApi['cpf']);
                    }
                }
                // Fallback: pegar primeiro signatário com CPF
                if (empty($cpfApi) && is_array($apiData) && isset($apiData['signers']) && is_array($apiData['signers'])) {
                    foreach ($apiData['signers'] as $sg) {
                        if (!empty($sg['cpf'])) {
                            $cpfApi = preg_replace('/\D+/', '', $sg['cpf']);
                            break;
                        }
                    }
                }
                if (!empty($cpfApi)) {
                    writeLog("REGISTRO COMPLETO: CPF obtido via API ZapSign: {$cpfApi}");
                    $cpfLimpo = $cpfApi;
                    // Buscar associado por CPF para obter codigo, id e id_divisao
                    $associadoStmtFix = $pdo->prepare("SELECT codigo, id, id_divisao FROM sind.associado WHERE cpf = :cpf LIMIT 1");
                    $associadoStmtFix->execute([':cpf' => $cpfLimpo]);
                    $assocFix = $associadoStmtFix->fetch(PDO::FETCH_ASSOC);
                    if ($assocFix) {
                        $codigoParaInserir = $assocFix['codigo'];
                        $idAssociado = $assocFix['id'];
                        $idDivisao = $assocFix['id_divisao'];
                        writeLog("REGISTRO COMPLETO: Associado localizado via CPF da API - codigo={$codigoParaInserir}, id={$idAssociado}, divisao={$idDivisao}");
                    } else {
                        writeLog("REGISTRO COMPLETO: CPF obtido via API não encontrado na base de associado");
                    }
                } else {
                    writeLog("REGISTRO COMPLETO: Não foi possível obter CPF via API ZapSign");
                }
            } catch (Exception $zapEx) {
                writeLog("AVISO: Falha ao consultar API ZapSign para obter CPF: " . $zapEx->getMessage());
            }

            $codigoCompleto = 'webhook_signed_' . substr($docToken, 0, 8) . '_' . substr($cpfLimpo, -4) . '_' . uniqid();
            
            $insertStmt = $pdo->prepare("
                INSERT INTO " . TABLE_NAME . " 
                (codigo, nome, celular, data_hora, autorizado, aceitou_termo, event, doc_token, doc_name, signed_at, name, email, cpf, has_signed, cel_informado, tipo, id_associado, id_divisao)
                VALUES 
                (:codigo, :nome, :celular, NOW()::timestamp(0), :autorizado, :aceitou_termo, :event, :doc_token, :doc_name, :signed_at, :name, :email, :cpf, :has_signed, :cel_informado, :tipo, :id_associado, :id_divisao)
            ");
            
            $insertStmt->execute([
                ':codigo' => (!empty($codigoParaInserir) ? $codigoParaInserir : $codigoCompleto),
                ':nome' => $signerName,
                ':celular' => $signerPhone ?? '',
                ':autorizado' => 1, // Autorizado pois dados foram obtidos
                ':aceitou_termo' => 1, // Termo aceito
                ':event' => 'doc_signed', // ✅ MARCAR COMO DOC_SIGNED quando CPF encontrado
                ':doc_token' => $docToken,
                ':doc_name' => $docName,
                ':signed_at' => $signedAt,
                ':name' => $signerName,
                ':email' => $signerEmail,
                ':cpf' => $cpfLimpo,
                ':has_signed' => 1, // ✅ MARCAR COMO ASSINADO quando CPF encontrado
                ':cel_informado' => $signerPhone ?? '', // ✅ GRAVAR CELULAR RETORNADO PELO WEBHOOK
                ':tipo' => $tipoDocumento, // ✅ GRAVAR TIPO DO DOCUMENTO NA INSERÇÃO (1=adesão, 2=antecipação)
                ':id_associado' => $idAssociado,
                ':id_divisao' => $idDivisao
            ]);
            
            $novoId = $pdo->lastInsertId();
            writeLog("REGISTRO COMPLETO: Novo registro criado - ID: {$novoId}, Código: {$codigoCompleto}");
            
            // ✅ EXECUTAR APROVAÇÃO AUTOMÁTICA quando CPF está disponível
            if (!empty($cpfLimpo)) {
                executarAprovacaoAutomatica($pdo, $novoId, $cpfLimpo);
            }
            
            return $novoId;
        }
        
    } catch (Exception $e) {
        writeLog("ERRO REGISTRO COMPLETO: " . $e->getMessage());
        return false;
    }
}

/**
 * Função para aprovação automática quando dados são inseridos na tabela sind.associados_sasmais
 * Executa automaticamente: Valor Aprovado = 550,00, Data Pgto = data/hora atual, atualiza código
 */
function executarAprovacaoAutomatica($pdo, $registroId, $cpfLimpo) {
    try {
        writeLog("APROVAÇÃO AUTOMÁTICA: Iniciando para registro ID: {$registroId}, CPF: {$cpfLimpo}");
        
        // 1. Buscar dados do associado na tabela sind.associado pelo CPF
        $sqlAssociado = "SELECT codigo, nome, endereco, numero, nascimento, salario, limite, empregador, cep, telres, telcom, cel, bairro, id, complemento, cidade, foto, rg, cpf, funcao, filiado, obs, id_situacao, data_filiacao, data_desfiliacao, email, tipo, codigo_isa, parcelas_permitidas, uf, celwatzap, token_associado, cartao_entregue, data_entreg_cartao, ultimo_mes, id_divisao, id_secretaria, localizacao, token_message
                         FROM sind.associado 
                         WHERE cpf = :cpf 
                         LIMIT 1";
        
        $stmtAssociado = $pdo->prepare($sqlAssociado);
        $stmtAssociado->execute([':cpf' => $cpfLimpo]);
        $associado = $stmtAssociado->fetch(PDO::FETCH_ASSOC);
        
        if (!$associado) {
            writeLog("APROVAÇÃO AUTOMÁTICA: Associado não encontrado na tabela sind.associado para CPF: {$cpfLimpo}");
            return false;
        }
        
        $codigoAssociado = $associado['codigo'];
        $idAssociado = $associado['id'];
        $iddivisao = $associado['id_divisao'];
        writeLog("APROVAÇÃO AUTOMÁTICA: Associado encontrado - Código: {$codigoAssociado}, Nome: {$associado['nome']}");
        
        // 2. Atualizar registro na tabela sind.associados_sasmais com aprovação automática
        $valorAprovado = '550.00'; // Valor fixo de R$ 550,00
        $dataPgto = date('Y-m-d H:i:s'); // Data e hora atual
        $limite = '2000.00'; // Limite fixo de R$ 2000,00
        
        $sqlUpdate = "UPDATE " . TABLE_NAME . " 
                      SET codigo = :codigo,
                          valor_aprovado = :valor_aprovado,
                          data_pgto = :data_pgto,
                          autorizado = :autorizado,
                          aceitou_termo = :aceitou_termo,
                          event = :event,
                          has_signed = :has_signed,
                          limite = :limite,
                          id_divisao = :id_divisao,
                          id_associado = :id_associado
                      WHERE id = :id";
        
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':codigo' => $codigoAssociado,
            ':valor_aprovado' => $valorAprovado,
            ':data_pgto' => $dataPgto,
            ':autorizado' => true,
            ':aceitou_termo' => true,
            ':event' => 'doc_signed',
            ':has_signed' => true,
            ':limite' => $limite,
            ':id' => $registroId,
            ':id_divisao' => $iddivisao,
            ':id_associado' => $idAssociado
        ]);
        
        writeLog("APROVAÇÃO AUTOMÁTICA: Registro atualizado com sucesso");
        writeLog("APROVAÇÃO AUTOMÁTICA: Código: {$codigoAssociado}, Valor: {$valorAprovado}, Data: {$dataPgto}");
        writeLog("APROVAÇÃO AUTOMÁTICA: Campos adicionais - autorizado: true, aceitou_termo: true, event: doc_signed, has_signed: true, limite: {$limite}");
        
        return true;
        
    } catch (Exception $e) {
        writeLog("ERRO APROVAÇÃO AUTOMÁTICA: " . $e->getMessage());
        return false;
    }
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
 * Função para reprocessar documentos pendentes
 * Consulta a API ZapSign para obter dados completos dos signatários
 */
function reprocessarDocumentosPendentes() {
    writeLog("=== INICIANDO REPROCESSAMENTO DE DOCUMENTOS PENDENTES ===");
    
    try {
        // Conectar ao banco
        include "Adm/php/banco.php";
        $pdo = Banco::conectar_postgres();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Buscar documentos pendentes que precisam ser reprocessados
        $stmt = $pdo->prepare("
            SELECT doc_token, doc_name, evento_inicial, numero_tentativas, erro_ultima_tentativa, 
                   cpf_encontrado, telefone_encontrado, email_encontrado
            FROM sind.documentos_pendentes_zapsign 
            WHERE status_processamento = 'pendente' 
            AND dados_obtidos = false 
            AND processar_apos <= CURRENT_TIMESTAMP
            AND numero_tentativas < 10
            ORDER BY data_criacao ASC
            LIMIT 5
        ");
        
        $stmt->execute();
        $documentosPendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $processados = 0;
        $sucessos = 0;
        $erros = [];
        
        foreach ($documentosPendentes as $doc) {
            $docToken = $doc['doc_token'];
            $docName = $doc['doc_name'];
            $tentativas = $doc['numero_tentativas'];
            
            // Verificar se já temos dados salvos anteriormente
            $cpfEncontrado = $doc['cpf_encontrado'] ?? '';
            $telefoneEncontrado = $doc['telefone_encontrado'] ?? '';
            $emailEncontrado = $doc['email_encontrado'] ?? '';
            
            writeLog("REPROCESSANDO: {$docToken} (tentativa " . ($tentativas + 1) . ")");
            writeLog("DADOS SALVOS: CPF={$cpfEncontrado}, Email={$emailEncontrado}, Telefone={$telefoneEncontrado}");
            
            // Se já temos dados salvos e são válidos, usar eles diretamente
            if (!empty($cpfEncontrado) || !empty($telefoneEncontrado) || !empty($emailEncontrado)) {
                writeLog("DADOS JÁ DISPONÍVEIS: Usando dados salvos da tabela documentos_pendentes_zapsign");
                
                $cpf = $cpfEncontrado;
                $email = $emailEncontrado;
                $telefone = $telefoneEncontrado;
                $signerName = 'Nome do webhook'; // Nome será atualizado quando disponível
                
                writeLog("DADOS REUTILIZADOS: CPF={$cpf}, Email={$email}, Telefone={$telefone}");
            } else {
                // Consultar documento na API ZapSign apenas se não temos dados salvos
                writeLog("DADOS NÃO SALVOS: Consultando API ZapSign...");
                $apiData = consultarDocumentoZapSign($docToken);
                
                if ($apiData && isset($apiData['signers'])) {
                    foreach ($apiData['signers'] as $signer) {
                        $signerToken = $signer['token'] ?? '';
                        $signerName = $signer['name'] ?? '';
                        $signerStatus = $signer['status'] ?? '';
                        
                        writeLog("REPROCESSANDO SIGNATÁRIO: {$signerName} (status: {$signerStatus})");
                        
                        // Consultar dados específicos do signatário
                        $signerData = null;
                        if ($signerToken) {
                            $signerData = consultarSignatarioZapSign($signerToken);
                        }
                        
                        // Usar dados do documento se não conseguir dados específicos do signatário
                        if (!$signerData) {
                            $signerData = $signer;
                        }
                        
                        $cpf = $signerData['cpf'] ?? '';
                        $email = $signerData['email'] ?? '';
                        $telefone = $signerData['phone_number'] ?? '';
                        
                        writeLog("DADOS OBTIDOS DA API: CPF={$cpf}, Email={$email}, Telefone={$telefone}");
                        
                        // Verificar se obtivemos dados úteis
                        if (!empty($cpf) || !empty($email) || !empty($telefone)) {
                            // Dados obtidos com sucesso!
                            registrarDocumentoPendente($pdo, $docToken, $docName, $doc['evento_inicial'], true, $cpf, $telefone, $email, 'Dados obtidos no reprocessamento');
                            
                            // Criar registro completo se temos CPF
                            if (!empty($cpf)) {
                                $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
                                $signedAt = $signerData['signed_at'] ?? null;
                                
                                // Determinar tipo do documento para reprocessamento
                                $tipoDocumentoReproc = determinarTipoDocumento($docToken, $docName);
                                
                                gravarRegistroComDadosCompletos($pdo, $docToken, $docName, $signerName, $email, $cpfLimpo, $telefone, $signedAt, $signerToken, $tipoDocumentoReproc);
                                
                                writeLog("REPROCESSAMENTO SUCESSO: Registro completo criado para CPF {$cpfLimpo} - Tipo: {$tipoDocumentoReproc}");
                                
                                // ✅ EXECUTAR APROVAÇÃO AUTOMÁTICA para reprocessamento normal
                                // A função gravarRegistroComDadosCompletos já chama executarAprovacaoAutomatica internamente
                            }
                            
                            $sucessos++;
                        } else {
                            // Ainda sem dados, atualizar tentativas
                            $erro = "Dados ainda não disponíveis (tentativa " . ($tentativas + 1) . ")";
                            registrarDocumentoPendente($pdo, $docToken, $docName, $doc['evento_inicial'], false, '', '', '', $erro);
                            
                            writeLog("REPROCESSAMENTO PENDENTE: {$erro}");
                        }
                    }
                    
                    $processados++;
                } else {
                    $erro = "Erro ao consultar documento na API";
                    registrarDocumentoPendente($pdo, $docToken, $docName, $doc['evento_inicial'], false, '', '', '', $erro);
                    $erros[] = $erro . " - {$docToken}";
                    writeLog("REPROCESSAMENTO ERRO: {$erro}");
                }
            }
            
            // Processar dados já disponíveis (caso onde já temos CPF, telefone ou email salvos)
            if (!empty($cpf) || !empty($email) || !empty($telefone)) {
                // Dados já disponíveis, processar diretamente
                registrarDocumentoPendente($pdo, $docToken, $docName, $doc['evento_inicial'], true, $cpf, $telefone, $email, 'Dados reutilizados da tabela');
                
                // Criar registro completo se temos CPF
                if (!empty($cpf)) {
                    $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
                    
                    // Determinar tipo do documento para reprocessamento
                    $tipoDocumentoReproc = determinarTipoDocumento($docToken, $docName);
                    
                    gravarRegistroComDadosCompletos($pdo, $docToken, $docName, $signerName, $email, $cpfLimpo, $telefone, null, '', $tipoDocumentoReproc);
                    
                    writeLog("REPROCESSAMENTO SUCESSO (DADOS SALVOS): Registro completo criado para CPF {$cpfLimpo} - Tipo: {$tipoDocumentoReproc}");
                    
                    // ✅ EXECUTAR APROVAÇÃO AUTOMÁTICA para reprocessamento com dados salvos
                    if (!empty($cpfLimpo)) {
                        // Buscar o ID do registro recém criado
                        $searchIdStmt = $pdo->prepare("SELECT id FROM " . TABLE_NAME . " WHERE doc_token = :doc_token AND cpf = :cpf ORDER BY data_hora DESC LIMIT 1");
                        $searchIdStmt->execute([':doc_token' => $docToken, ':cpf' => $cpfLimpo]);
                        $registro = $searchIdStmt->fetch();
                        if ($registro) {
                            executarAprovacaoAutomatica($pdo, $registro['id'], $cpfLimpo);
                        }
                    }
                }
                
                $sucessos++;
                $processados++;
            }
        }
        
        $response = [
            'status' => 'sucesso',
            'mensagem' => 'Reprocessamento concluído',
            'documentos_encontrados' => count($documentosPendentes),
            'documentos_processados' => $processados,
            'sucessos' => $sucessos,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($erros)) {
            $response['erros'] = $erros;
        }
        
        writeLog("REPROCESSAMENTO CONCLUÍDO: " . json_encode($response));
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        $error = "Erro no reprocessamento: " . $e->getMessage();
        writeLog("ERRO REPROCESSAMENTO: {$error}");
        
        echo json_encode([
            'status' => 'erro',
            'mensagem' => $error,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
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
    writeLog("=== WEBHOOK ZAPSIGN INICIADO (v1.3 com notificações) ===");
    writeLog("Método: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
    // Função getallheaders() pode não existir em todas as versões do PHP
    $headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
    writeLog("Headers: " . json_encode($headers));
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

    // MODO DEBUG: Aceitar TODOS os eventos para descobrir qual é usado na assinatura
    writeLog("=== EVENTO RECEBIDO: {$event} ===");
    writeLog("STATUS DO DOCUMENTO: " . ($webhook['status'] ?? 'N/A'));
    
    // Log detalhado dos signatários para verificar status
    if (isset($webhook['signers']) && is_array($webhook['signers'])) {
        foreach ($webhook['signers'] as $index => $signer) {
            $signerStatus = $signer['status'] ?? 'N/A';
            $signerName = $signer['name'] ?? 'N/A';
            $signedAt = $signer['signed_at'] ?? 'N/A';
            writeLog("SIGNATÁRIO {$index}: {$signerName} - Status: {$signerStatus} - Assinado em: {$signedAt}");
        }
    }
    
    // Aceitar apenas eventos relacionados a documentos (filtrar eventos irrelevantes)
    $eventosRelevantes = ['doc_created', 'doc_signed', 'doc_completed', 'doc_updated', 'signer_signed', 'document_signed'];
    if (!in_array($event, $eventosRelevantes)) {
        writeLog("INFO: Evento não relacionado a documentos - {$event}");
        jsonResponse([
            'status' => 'sucesso',
            'mensagem' => 'Evento processado (não relacionado a documentos)',
            'event' => $event
        ]);
    }
    
    writeLog("PROCESSANDO EVENTO: {$event}");

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
    
    // Determinar tipo do documento baseado no token e nome
    $tipoDocumento = null;
    $documentoValido = false;
    
    // Tokens específicos para cada tipo de documento
    $tokenAdesao = '4bdad7db-07ae-4505-b8cb-0bee880f6fdd';
    $tokenAntecipacao = '762dbe4c-654b-432b-a7a9-38435966e0aa';
    
    // Nomes dos documentos
    $nomeAdesao = 'TERMO DE ADESÃO DO CARTÃO CONVÊNIO';
    $nomeAntecipacao = 'Contrato de Antecipação Salarial';
    
    writeLog("DEBUG: Identificando tipo de documento:");
    writeLog("- Token recebido: '{$docToken}'");
    writeLog("- Nome recebido: '{$docName}'");
    
    // Verificar por token primeiro (mais preciso)
    if ($docToken === $tokenAdesao) {
        $tipoDocumento = 1; // Adesão
        $documentoValido = true;
        writeLog("✅ DOCUMENTO IDENTIFICADO: Adesão (tipo=1) - baseado no token");
    } elseif ($docToken === $tokenAntecipacao) {
        $tipoDocumento = 2; // Antecipação
        $documentoValido = true;
        writeLog("✅ DOCUMENTO IDENTIFICADO: Antecipação (tipo=2) - baseado no token");
    } else {
        // Se não identificou pelo token, verificar pelo nome
        $normalizedDocName = normalizeText($docName);
        $normalizedAdesao = normalizeText($nomeAdesao);
        $normalizedAntecipacao = normalizeText($nomeAntecipacao);
        
        writeLog("DEBUG: Verificação por nome (token não reconhecido):");
        writeLog("- Normalizado recebido: '{$normalizedDocName}'");
        writeLog("- Normalizado adesão: '{$normalizedAdesao}'");
        writeLog("- Normalizado antecipação: '{$normalizedAntecipacao}'");
        
        if ($normalizedDocName === $normalizedAdesao) {
            $tipoDocumento = 1; // Adesão
            $documentoValido = true;
            writeLog("✅ DOCUMENTO IDENTIFICADO: Adesão (tipo=1) - baseado no nome");
        } elseif ($normalizedDocName === $normalizedAntecipacao) {
            $tipoDocumento = 2; // Antecipação
            $documentoValido = true;
            writeLog("✅ DOCUMENTO IDENTIFICADO: Antecipação (tipo=2) - baseado no nome");
        }
    }
    
    if (!$documentoValido) {
        writeLog("INFO: Documento ignorado - não é nem adesão nem antecipação");
        jsonResponse([
            'status' => 'sucesso',
            'mensagem' => 'Documento processado (ignorado por filtro de tipo)',
            'doc_name' => $docName,
            'doc_token' => $docToken,
            'filtro' => 'Apenas documentos de Adesão ou Antecipação são processados'
        ]);
    }
    
    writeLog("SUCCESS: Documento validado - Tipo: {$tipoDocumento} (" . ($tipoDocumento == 1 ? 'Adesão' : 'Antecipação') . ")");

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
        $signerPhone = ''; // Inicializar telefone vazio
        // Determinar se documento foi assinado baseado no evento e status do signatário
        $hasSigned = false;
        
        if ($event === 'doc_signed' || $event === 'signer_signed' || $event === 'document_signed') {
            $hasSigned = true; // Eventos de assinatura
            writeLog("EVENTO DE ASSINATURA DETECTADO: {$event}");
        } elseif ($event === 'doc_created') {
            $hasSigned = false; // Documento criado, não assinado ainda
            writeLog("EVENTO DE CRIAÇÃO DETECTADO: {$event}");
        } else {
            // Verificar status do signatário para determinar se foi assinado
            $hasSigned = ($signer['status'] ?? '') === 'signed';
            writeLog("EVENTO DESCONHECIDO: {$event} - Verificando status do signatário: " . ($signer['status'] ?? 'N/A'));
        }
        $signerSignedAt = $signer['signed_at'] ?? null;
        $signerToken = $signer['token'] ?? '';

        // Log detalhado do signer token capturado
        if (!empty($signerToken)) {
            writeLog("SIGNER TOKEN CAPTURADO: {$signerToken} (para signatário: {$signerName})");
            writeLog("URL de assinatura seria: https://app.zapsign.com.br/verificar/{$signerToken}");
        } else {
            writeLog("AVISO: Signer token vazio para signatário: {$signerName}");
        }

        // Validar dados mínimos do signatário
        if (empty($signerCpf)) {
            // Se não há CPF, tentar obter dados com múltiplas tentativas
            writeLog("AVISO: CPF vazio, iniciando sistema de tentativas múltiplas...");
            
            $resultadoTentativas = tentarObterDados($docToken, $signerToken, 4); // 4 tentativas imediatas
            
            if ($resultadoTentativas['sucesso']) {
                $apiSigner = $resultadoTentativas['signer'];
                
                // Atualizar dados do signatário com dados da API
                $signerCpf = $apiSigner['cpf'] ?? '';
                $signerName = $apiSigner['name'] ?? $signerName;
                $signerEmail = $apiSigner['email'] ?? $signerEmail;
                
                // Adicionar dados extras que podem vir da API
                $signerPhone = $apiSigner['phone_number'] ?? '';
                
                // Formatação do telefone se disponível
                if (!empty($signerPhone)) {
                    // Remove caracteres não numéricos
                    $signerPhone = preg_replace('/[^0-9]/', '', $signerPhone);
                    // Formatar como (XX) XXXXX-XXXX se tiver 11 dígitos
                    if (strlen($signerPhone) === 11) {
                        $signerPhone = '(' . substr($signerPhone, 0, 2) . ') ' . substr($signerPhone, 2, 5) . '-' . substr($signerPhone, 7);
                    }
                }
                
                writeLog("DADOS API: CPF={$signerCpf}, Nome={$signerName}, Email={$signerEmail}, Telefone={$signerPhone}");
                
                if (!empty($signerCpf)) {
                    writeLog("SUCESSO TENTATIVAS: CPF obtido após {$resultadoTentativas['tentativa']} tentativa(s) - {$signerCpf}");
                }
            } else {
                writeLog("TENTATIVAS FALHARAM: Registrando documento para reprocessamento posterior");
                // Registrar para reprocessamento automático
                registrarDocumentoPendente($pdo, $docToken, $docName, $event, false, '', '', '', 'Dados não disponíveis nas tentativas imediatas');
            }
            
            // Limpar CPF se obtido da API
            if (!empty($signerCpf)) {
                $cpfLimpo = preg_replace('/[^0-9]/', '', $signerCpf);
                writeLog("CPF LIMPO: {$cpfLimpo}");
                // Marcar como dados obtidos com sucesso
                registrarDocumentoPendente($pdo, $docToken, $docName, $event, true, $cpfLimpo, $signerPhone, $signerEmail, 'Dados obtidos com sucesso');
                
                // GRAVAR NOVO REGISTRO COMO SE FOSSE doc_signed
                writeLog("GRAVANDO NOVO REGISTRO: Simulando doc_signed com dados obtidos da API");
                gravarRegistroComDadosCompletos($pdo, $docToken, $docName, $signerName, $signerEmail, $cpfLimpo, $signerPhone, $signedAt, $signerToken, $tipoDocumento);
            } else {
                $cpfLimpo = ''; // CPF continua vazio
                writeLog("AVISO: CPF ainda vazio após tentativas, documento ficará pendente para reprocessamento");
            }
        } else {
            // Limpar CPF (remover pontos e traços)
            $cpfLimpo = preg_replace('/[^0-9]/', '', $signerCpf);
            writeLog("CPF FORNECIDO: {$cpfLimpo}");
            // Marcar como dados já disponíveis
            registrarDocumentoPendente($pdo, $docToken, $docName, $event, true, $cpfLimpo, $signerPhone, $signerEmail, 'Dados já disponíveis no webhook');
            
            // GRAVAR REGISTRO COMPLETO TAMBÉM QUANDO CPF JÁ ESTÁ DISPONÍVEL
            writeLog("GRAVANDO REGISTRO COMPLETO: CPF já disponível no webhook inicial");
            gravarRegistroComDadosCompletos($pdo, $docToken, $docName, $signerName, $signerEmail, $cpfLimpo, $signerPhone, $signedAt, $signerToken, $tipoDocumento);
        }

        try {
            // Estratégia robusta para evitar constraint violations:
            // 1. Buscar por CPF primeiro (dados do mesmo usuário)
            // 2. Se não encontrar, buscar registro reutilizável (codigo vazio)
            // 3. Se não encontrar nenhum, criar com código único
            
            $recordByCpf = null;
            
            if (!empty($cpfLimpo)) {
                writeLog("PASSO 1A: Buscando registro existente por CPF: {$cpfLimpo}");
                
                $stmtCpf = $pdo->prepare("
                    SELECT id, codigo, nome, celular 
                    FROM " . TABLE_NAME . "
                    WHERE cpf = :cpf
                    LIMIT 1
                ");
                
                $stmtCpf->execute([':cpf' => $cpfLimpo]);
                $recordByCpf = $stmtCpf->fetch();
            } else {
                writeLog("PASSO 1B: CPF vazio, buscando por token do documento e nome do signatário");
                
                $stmtToken = $pdo->prepare("
                    SELECT id, codigo, nome, celular 
                    FROM " . TABLE_NAME . "
                    WHERE doc_token = :doc_token AND name = :name
                    LIMIT 1
                ");
                
                $stmtToken->execute([':doc_token' => $docToken, ':name' => $signerName]);
                $recordByCpf = $stmtToken->fetch();
            }
            
            if ($recordByCpf) {
                writeLog("SUCESSO: Encontrado registro por CPF - ID: {$recordByCpf['id']}");
                
                // ⚠️  IMPORTANTE: Se CPF foi encontrado, gravarRegistroComDadosCompletos já atualizou 
                // os campos corretamente (event=doc_signed, has_signed=true, cel_informado=telefone)
                // Apenas atualizar campos básicos sem sobrescrever os campos já corretos
                if (!empty($cpfLimpo)) {
                    writeLog("REGISTRO COM CPF: Não sobrescrever campos já atualizados pela gravarRegistroComDadosCompletos");
                    // Apenas atualizar campos que não conflitam
                    $updateStmt = $pdo->prepare("
                        UPDATE " . TABLE_NAME . "
                        SET 
                            doc_token = :doc_token,
                            doc_name = :doc_name,
                            signed_at = :signed_at,
                            name = :name,
                            email = :email,
                            data_hora = NOW()::timestamp(0)
                        WHERE id = :id
                    ");

                    $updateStmt->execute([
                        ':doc_token' => $docToken,
                        ':doc_name' => $docName,
                        ':signed_at' => $signedAt,
                        ':name' => $signerName,
                        ':email' => $signerEmail,
                        ':id' => $recordByCpf['id']
                    ]);
                } else {
                    // CPF vazio, atualizar normalmente
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
                            data_hora = NOW()::timestamp(0)
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
                }

                writeLog("Registro atualizado com sucesso por CPF - ID: {$recordByCpf['id']}");

                // =================== NOTIFICAÇÃO EM TEMPO REAL ===================
                // Documento criado, enviando notificação de novo documento
                $notificationType = $hasSigned ? 'signature_completed' : 'document_created';
                sendRealtimeNotification($pdo, $notificationType, [
                    'id' => $recordByCpf['id'],
                    'codigo' => $recordByCpf['codigo'],
                    'nome' => $signerName,
                    'celular' => $recordByCpf['celular'],
                    'email' => $signerEmail,
                    'cpf' => $cpfLimpo,
                    'autorizado' => false,
                    'aceitou_termo' => false,
                    'has_signed' => false,
                    'event' => $event,
                    'doc_token' => $docToken,
                    'doc_name' => $docName,
                    'signed_at' => null,
                    'data_hora' => date('Y-m-d H:i:s')
                ]);
                // ===============================================================

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
                    if (!empty($cpfLimpo)) {
                        $codigoTemporario = 'webhook_' . substr($docToken, 0, 8) . '_' . substr($cpfLimpo, -4) . '_' . time();
                    } else {
                        // Para casos sem CPF, usar token do signatário
                        $codigoTemporario = 'webhook_' . substr($docToken, 0, 8) . '_' . substr($signerToken, -8) . '_' . time();
                    }
                    
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
                            data_hora = NOW()::timestamp(0)
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

                    // =================== NOTIFICAÇÃO EM TEMPO REAL ===================
                    // Documento criado, enviando notificação de novo documento
                    $notificationType = $hasSigned ? 'signature_completed' : 'document_created';
                sendRealtimeNotification($pdo, $notificationType, [
                        'id' => $emptyRecord['id'],
                        'codigo' => $codigoTemporario,
                        'nome' => $signerName,
                        'celular' => '',
                        'email' => $signerEmail,
                        'cpf' => $cpfLimpo,
                        'autorizado' => $hasSigned,
                        'aceitou_termo' => $hasSigned,
                        'has_signed' => $hasSigned,
                        'event' => $event,
                        'doc_token' => $docToken,
                        'doc_name' => $docName,
                        'signed_at' => null,
                        'data_hora' => date('Y-m-d H:i:s')
                    ]);
                    // ===============================================================

                    $processedSigners++;
                    
                } else {
                    writeLog("PASSO 3: Nenhum registro reutilizável encontrado, criando novo registro");
                    
                    // Gerar código único para novo registro
                    if (!empty($cpfLimpo)) {
                        $codigoNovo = 'webhook_' . substr($docToken, 0, 8) . '_' . substr($cpfLimpo, -4) . '_' . uniqid();
                    } else {
                        // Para casos sem CPF, usar token do signatário
                        $codigoNovo = 'webhook_' . substr($docToken, 0, 8) . '_' . substr($signerToken, -8) . '_' . uniqid();
                    }
                    
                    writeLog("Código para novo registro: {$codigoNovo}");
                    
                    // Inserir novo registro
                    $insertStmt = $pdo->prepare("
                        INSERT INTO " . TABLE_NAME . "
                        (codigo, nome, celular, data_hora, autorizado, aceitou_termo, event, doc_token, doc_name, signed_at, name, email, cpf, has_signed, cel_informado, tipo)
                        VALUES 
                        (:codigo, :nome, :celular, NOW()::timestamp(0), :autorizado, :aceitou_termo, :event, :doc_token, :doc_name, :signed_at, :name, :email, :cpf, :has_signed, :cel_informado, :tipo)
                    ");

                    $insertStmt->execute([
                        ':codigo' => $codigoNovo,
                        ':nome' => $signerName,
                        ':celular' => $signerPhone ?? '',
                        ':autorizado' => $hasSigned ? 1 : 0,
                        ':aceitou_termo' => $hasSigned ? 1 : 0,
                        ':event' => $event,
                        ':doc_token' => $docToken,
                        ':doc_name' => $docName,
                        ':signed_at' => $signedAt,
                        ':name' => $signerName,
                        ':email' => $signerEmail,
                        ':cpf' => $cpfLimpo,
                        ':has_signed' => $hasSigned ? 1 : 0,
                        ':cel_informado' => '',
                        ':tipo' => $tipoDocumento // ✅ GRAVAR TIPO DO DOCUMENTO (1=adesão, 2=antecipação)
                    ]);

                    writeLog("Novo registro criado com sucesso - Código: {$codigoNovo}");

                    // =================== NOTIFICAÇÃO EM TEMPO REAL ===================
                    // Documento criado, enviando notificação de novo documento
                    $notificationType = $hasSigned ? 'signature_completed' : 'document_created';
                sendRealtimeNotification($pdo, $notificationType, [
                        'id' => $pdo->lastInsertId(),
                        'codigo' => $codigoNovo,
                        'nome' => $signerName,
                        'celular' => '',
                        'email' => $signerEmail,
                        'cpf' => $cpfLimpo,
                        'autorizado' => $hasSigned,
                        'aceitou_termo' => $hasSigned,
                        'has_signed' => $hasSigned,
                        'event' => $event,
                        'doc_token' => $docToken,
                        'doc_name' => $docName,
                        'signed_at' => null,
                        'data_hora' => date('Y-m-d H:i:s')
                    ]);
                    // ===============================================================

                    $processedSigners++;
                }
            }

        } catch (PDOException $e) {
            $error = "Erro ao processar signatário {$signerName}: " . $e->getMessage();
            writeLog("ERRO DB: {$error}");
            $errors[] = $error;
        }
    }

    // Preparar resposta (MESMA LÓGICA ORIGINAL)
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