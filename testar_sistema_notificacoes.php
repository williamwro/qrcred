<?php
/**
 * SCRIPT DE TESTE - SISTEMA DE NOTIFICAÇÕES SASCRED
 * 
 * Este script testa todos os componentes do sistema de notificações
 * e simula cenários reais de uso para verificar funcionamento.
 */

// Verificar se é uma requisição de teste AJAX
if (isset($_GET['action'])) {
    // Apenas para requisições AJAX - definir header JSON
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_GET['action'];
    
    switch ($action) {
        case 'test_database':
            testDatabase();
            break;
        case 'test_triggers':
            testTriggers();
            break;
        case 'test_notification':
            testNotification();
            break;
        case 'simulate_signature':
            simulateSignature();
            break;
        case 'check_sse':
            checkSSE();
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Ação não reconhecida: ' . $action
            ]);
    }
    exit;
} else {
    // Mostrar página HTML apenas quando não há parâmetro action
    showTestPage();
    exit;
}

function testDatabase() {
    try {
        require_once __DIR__ . '/Adm/php/banco.php';
        $pdo = Banco::conectar_postgres();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Testar conexão
        $result = $pdo->query('SELECT NOW() as current_time')->fetch();
        
        // Testar tabela associados_sasmais
        $count = $pdo->query('SELECT COUNT(*) as total FROM sind.associados_sasmais')->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Conexão com banco de dados OK',
            'database_time' => $result['current_time'],
            'total_records' => $count['total']
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro de conexão com banco: ' . $e->getMessage()
        ]);
    }
}

function testTriggers() {
    try {
        require_once __DIR__ . '/Adm/php/banco.php';
        $pdo = Banco::conectar_postgres();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Verificar se triggers existem
        $sql = "SELECT trigger_name, event_manipulation, action_timing
                FROM information_schema.triggers 
                WHERE event_object_table = 'associados_sasmais' 
                AND event_object_schema = 'sind'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $expectedTriggers = ['trigger_notify_new_signature', 'trigger_notify_signature_update'];
        $foundTriggers = array_column($triggers, 'trigger_name');
        
        $missing = array_diff($expectedTriggers, $foundTriggers);
        
        echo json_encode([
            'success' => empty($missing),
            'message' => empty($missing) ? 'Todos os triggers estão instalados' : 'Triggers faltando: ' . implode(', ', $missing),
            'triggers_found' => $triggers,
            'missing_triggers' => $missing
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao verificar triggers: ' . $e->getMessage()
        ]);
    }
}

function testNotification() {
    try {
        require_once __DIR__ . '/Adm/php/banco.php';
        $pdo = Banco::conectar_postgres();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Enviar notificação de teste
        $testData = [
            'event_type' => 'test_notification',
            'timestamp' => time(),
            'message' => 'Teste de notificação em tempo real',
            'data' => [
                'id' => 'test_' . uniqid(),
                'codigo' => 'TEST123',
                'nome' => 'TESTE - Sistema de Notificações',
                'status' => 'teste'
            ]
        ];
        
        $stmt = $pdo->prepare("SELECT pg_notify('new_assinatura_digital', ?)");
        $result = $stmt->execute([json_encode($testData)]);
        
        echo json_encode([
            'success' => $result,
            'message' => 'Notificação de teste enviada via pg_notify',
            'test_data' => $testData
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao enviar notificação de teste: ' . $e->getMessage()
        ]);
    }
}

function simulateSignature() {
    $codigo = $_GET['codigo'] ?? 'TEST' . rand(1000, 9999);
    
    try {
        require_once __DIR__ . '/Adm/php/banco.php';
        $pdo = Banco::conectar_postgres();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Inserir registro de teste (deve disparar trigger)
        $sql = "INSERT INTO sind.associados_sasmais 
                (codigo, nome, celular, email, cpf, has_signed, autorizado, aceitou_termo, data_hora)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $codigo,
            'Teste Usuario ' . $codigo,
            '11999999999',
            'teste@teste.com',
            '12345678901',
            true,   // has_signed
            true,   // autorizado  
            true    // aceitou_termo
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Assinatura simulada com sucesso - trigger deve ter disparado',
                'codigo_usuario' => $codigo,
                'note' => 'Verifique se o app recebeu a notificação'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao inserir registro de teste'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao simular assinatura: ' . $e->getMessage()
        ]);
    }
}

function checkSSE() {
    $sseFile = __DIR__ . '/sse_notificacao_app.php';
    $jsFile = __DIR__ . '/js/sascred_notificacao_app.js';
    
    $checks = [
        'sse_file_exists' => file_exists($sseFile),
        'js_file_exists' => file_exists($jsFile),
        'sse_readable' => file_exists($sseFile) && is_readable($sseFile),
        'js_readable' => file_exists($jsFile) && is_readable($jsFile)
    ];
    
    // Testar se SSE responde (básico)
    $testUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/sse_notificacao_app.php?codigo=TEST123';
    
    echo json_encode([
        'success' => $checks['sse_file_exists'] && $checks['js_file_exists'],
        'message' => 'Verificação de arquivos SSE',
        'files_check' => $checks,
        'sse_test_url' => $testUrl,
        'note' => 'Teste o SSE abrindo a URL em uma nova aba'
    ]);
}

function showTestPage() {
    // Garantir que seja HTML quando mostrar a página
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Teste do Sistema de Notificações Sascred</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .test-item {
                margin: 15px 0;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                background-color: #f9f9f9;
            }
            .btn {
                background-color: #007bff;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                margin: 5px;
            }
            .btn:hover { background-color: #0056b3; }
            .btn.success { background-color: #28a745; }
            .btn.warning { background-color: #ffc107; color: #212529; }
            .btn.danger { background-color: #dc3545; }
            
            .result {
                margin-top: 10px;
                padding: 10px;
                border-radius: 4px;
                font-family: monospace;
                white-space: pre-wrap;
            }
            .result.success { background-color: #d4edda; border: 1px solid #c3e6cb; }
            .result.error { background-color: #f8d7da; border: 1px solid #f5c6cb; }
            .result.info { background-color: #d1ecf1; border: 1px solid #bee5eb; }
            
            .status-indicator {
                display: inline-block;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                margin-right: 8px;
            }
            .status-indicator.success { background-color: #28a745; }
            .status-indicator.error { background-color: #dc3545; }
            .status-indicator.warning { background-color: #ffc107; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧪 Teste do Sistema de Notificações Sascred</h1>
            <p>Esta página testa todos os componentes do sistema de notificações em tempo real.</p>
            
            <div class="test-item">
                <h3><span class="status-indicator" id="db-status"></span>1. Teste de Conexão com Banco de Dados</h3>
                <p>Verifica se a conexão com PostgreSQL está funcionando.</p>
                <button class="btn" onclick="runTest('test_database', 'db')">🔌 Testar Banco</button>
                <div id="db-result" class="result" style="display: none;"></div>
            </div>
            
            <div class="test-item">
                <h3><span class="status-indicator" id="triggers-status"></span>2. Verificação de Triggers</h3>
                <p>Confirma se os triggers PostgreSQL estão instalados corretamente.</p>
                <button class="btn" onclick="runTest('test_triggers', 'triggers')">⚡ Verificar Triggers</button>
                <div id="triggers-result" class="result" style="display: none;"></div>
            </div>
            
            <div class="test-item">
                <h3><span class="status-indicator" id="sse-status"></span>3. Verificação de Arquivos SSE</h3>
                <p>Verifica se os arquivos do sistema SSE estão no lugar correto.</p>
                <button class="btn" onclick="runTest('check_sse', 'sse')">📁 Verificar Arquivos</button>
                <div id="sse-result" class="result" style="display: none;"></div>
            </div>
            
            <div class="test-item">
                <h3><span class="status-indicator" id="notification-status"></span>4. Teste de Notificação</h3>
                <p>Envia uma notificação de teste via pg_notify.</p>
                <button class="btn success" onclick="runTest('test_notification', 'notification')">🔔 Testar Notificação</button>
                <div id="notification-result" class="result" style="display: none;"></div>
            </div>
            
            <div class="test-item">
                <h3><span class="status-indicator" id="signature-status"></span>5. Simulação de Assinatura Digital</h3>
                <p>Simula uma assinatura digital inserindo dados na tabela (dispara trigger real).</p>
                <input type="text" id="codigo-teste" placeholder="Código do usuário (opcional)" style="padding: 8px; margin-right: 10px;">
                <button class="btn warning" onclick="runSignatureTest()">✍️ Simular Assinatura</button>
                <div id="signature-result" class="result" style="display: none;"></div>
            </div>
            
            <div class="test-item">
                <h3>🚀 Teste Completo</h3>
                <p>Executa todos os testes em sequência.</p>
                <button class="btn danger" onclick="runAllTests()">🧪 Executar Todos os Testes</button>
                <div id="complete-result" class="result" style="display: none;"></div>
            </div>
            
            <div class="test-item">
                <h3>📊 Links Úteis</h3>
                <p>
                    <a href="exemplo_integracao_app.html" target="_blank" class="btn">📋 Abrir Exemplo de Integração</a>
                    <a href="sse_notificacao_app.php?codigo=TEST123" target="_blank" class="btn">🌊 Testar SSE Diretamente</a>
                    <a href="README_SASCRED_NOTIFICACOES.md" target="_blank" class="btn">📖 Ver Documentação</a>
                </p>
            </div>
        </div>
        
        <script>
            async function runTest(action, type) {
                const resultDiv = document.getElementById(type + '-result');
                const statusIndicator = document.getElementById(type + '-status');
                
                resultDiv.style.display = 'block';
                resultDiv.className = 'result info';
                resultDiv.textContent = 'Executando teste...';
                statusIndicator.className = 'status-indicator warning';
                
                try {
                    const response = await fetch(`?action=${action}`);
                    
                    // Verificar se a resposta é realmente JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Resposta não é JSON válido. Content-Type: ' + contentType);
                    }
                    
                    const data = await response.json();
                    
                    resultDiv.className = data.success ? 'result success' : 'result error';
                    resultDiv.textContent = JSON.stringify(data, null, 2);
                    statusIndicator.className = data.success ? 'status-indicator success' : 'status-indicator error';
                    
                } catch (error) {
                    resultDiv.className = 'result error';
                    resultDiv.textContent = 'Erro: ' + error.message;
                    statusIndicator.className = 'status-indicator error';
                }
            }
            
            async function runSignatureTest() {
                const codigo = document.getElementById('codigo-teste').value || 'TEST' + Math.floor(Math.random() * 10000);
                const action = 'simulate_signature&codigo=' + encodeURIComponent(codigo);
                await runTest(action, 'signature');
            }
            
            async function runAllTests() {
                const completeResult = document.getElementById('complete-result');
                completeResult.style.display = 'block';
                completeResult.className = 'result info';
                completeResult.textContent = 'Executando todos os testes...\n';
                
                const tests = [
                    { action: 'test_database', type: 'db', name: 'Banco de Dados' },
                    { action: 'test_triggers', type: 'triggers', name: 'Triggers' },
                    { action: 'check_sse', type: 'sse', name: 'Arquivos SSE' },
                    { action: 'test_notification', type: 'notification', name: 'Notificação' }
                ];
                
                let allSuccess = true;
                
                for (const test of tests) {
                    completeResult.textContent += `\nTestando ${test.name}...`;
                    await runTest(test.action, test.type);
                    
                    // Aguardar um pouco entre testes
                    await new Promise(resolve => setTimeout(resolve, 500));
                    
                    const indicator = document.getElementById(test.type + '-status');
                    if (!indicator.className.includes('success')) {
                        allSuccess = false;
                    }
                }
                
                completeResult.textContent += '\n\n' + (allSuccess ? 
                    '✅ TODOS OS TESTES PASSARAM!\nSistema pronto para uso.' : 
                    '❌ ALGUNS TESTES FALHARAM!\nVerifique os erros acima.');
                
                completeResult.className = allSuccess ? 'result success' : 'result error';
            }
            
            // Executar teste básico ao carregar
            window.onload = function() {
                runTest('test_database', 'db');
            };
        </script>
    </body>
    </html>
    <?php
}
?>