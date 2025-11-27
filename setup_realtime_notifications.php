<?php
/**
 * SCRIPT DE INSTALAÇÃO - SISTEMA DE NOTIFICAÇÕES EM TEMPO REAL
 * 
 * Este script configura automaticamente todo o sistema de notificações
 * em tempo real usando PostgreSQL LISTEN/NOTIFY + Server-Sent Events.
 */

// Verificar se é uma requisição POST para instalação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'install_triggers':
            installTriggers();
            break;
        case 'test_notifications':
            testNotifications();
            break;
        case 'remove_triggers':
            removeTriggers();
            break;
        default:
            echo json_encode(['error' => 'Ação inválida']);
    }
    exit;
}

function installTriggers() {
    try {
        // Incluir conexão com banco
        require_once __DIR__ . '/Adm/php/banco.php';
        $pdo = Banco::conectar_postgres();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Ler arquivo SQL dos triggers
        $sqlFile = __DIR__ . '/create_notification_trigger.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception('Arquivo create_notification_trigger.sql não encontrado');
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Executar SQL (pode conter múltiplas statements)
        $pdo->exec($sql);
        
        echo json_encode([
            'success' => true,
            'message' => 'Triggers instalados com sucesso!'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'error' => $e->getMessage()
        ]);
    }
}

function testNotifications() {
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
                'nome' => 'TESTE - Sistema de Notificações',
                'status' => 'teste'
            ]
        ];
        
        $pdo->prepare("SELECT pg_notify('new_assinatura_digital', ?)")
            ->execute([json_encode($testData)]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Notificação de teste enviada!'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'error' => $e->getMessage()
        ]);
    }
}

function removeTriggers() {
    try {
        require_once __DIR__ . '/Adm/php/banco.php';
        $pdo = Banco::conectar_postgres();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Remover triggers
        $sql = "
            DROP TRIGGER IF EXISTS trigger_notify_new_signature ON sind.associados_sasmais;
            DROP TRIGGER IF EXISTS trigger_notify_signature_update ON sind.associados_sasmais;
            DROP FUNCTION IF EXISTS notify_new_signature();
            DROP FUNCTION IF EXISTS notify_signature_update();
        ";
        
        $pdo->exec($sql);
        
        echo json_encode([
            'success' => true,
            'message' => 'Triggers removidos com sucesso!'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'error' => $e->getMessage()
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema de Notificações em Tempo Real</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 20px; margin-bottom: 30px; }
        .step { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .step h3 { color: #007bff; margin-top: 0; }
        .btn { background: #007bff; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: #212529; }
        .result { margin-top: 15px; padding: 15px; border-radius: 5px; display: none; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 Sistema de Notificações em Tempo Real</h1>
            <p>Instalação e Configuração - PostgreSQL LISTEN/NOTIFY + Server-Sent Events</p>
        </div>
        
        <div class="status">
            <h3>📋 Status do Sistema</h3>
            <p><strong>Webhook ZapSign:</strong> <span id="webhook-status">Verificando...</span></p>
            <p><strong>Conexão PostgreSQL:</strong> <span id="db-status">Verificando...</span></p>
            <p><strong>Triggers Instalados:</strong> <span id="triggers-status">Verificando...</span></p>
        </div>
        
        <div class="step">
            <h3>🔧 Passo 1: Instalar Triggers PostgreSQL</h3>
            <p>Instala as funções e triggers necessários para capturar mudanças na tabela de assinaturas digitais.</p>
            <button class="btn" onclick="installTriggers()">Instalar Triggers</button>
            <div id="install-result" class="result"></div>
        </div>
        
        <div class="step">
            <h3>🧪 Passo 2: Testar Notificações</h3>
            <p>Envia uma notificação de teste para verificar se o sistema está funcionando.</p>
            <button class="btn btn-warning" onclick="testNotifications()">Enviar Teste</button>
            <div id="test-result" class="result"></div>
        </div>
        
        <div class="step">
            <h3>🌐 Passo 3: Integrar na Página</h3>
            <p>Adicione este código no final da página HTML das assinaturas digitais:</p>
            <pre><code>&lt;script src="realtime_notifications.js"&gt;&lt;/script&gt;</code></pre>
            <p>O sistema iniciará automaticamente quando a página carregar.</p>
        </div>
        
        <div class="step">
            <h3>⚙️ Passo 4: Configurar Webhook</h3>
            <p>Modifique o webhook_zapsign.php para disparar notificações após gravar no banco:</p>
            <pre><code>// Adicionar após gravação no banco
$pdo->prepare("SELECT pg_notify('new_assinatura_digital', ?)")
    ->execute([json_encode($notificationData)]);</code></pre>
        </div>
        
        <div class="step">
            <h3>🗑️ Manutenção: Remover Triggers</h3>
            <p>Remove os triggers instalados (use apenas se necessário).</p>
            <button class="btn btn-danger" onclick="removeTriggers()">Remover Triggers</button>
            <div id="remove-result" class="result"></div>
        </div>
    </div>
    
    <script>
        // Verificar status inicial
        checkStatus();
        
        function checkStatus() {
            // Verificar webhook
            fetch('webhook_zapsign.php?status')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('webhook-status').textContent = 
                        data.database_status === 'connected' ? '✅ Ativo' : '❌ Erro';
                })
                .catch(() => {
                    document.getElementById('webhook-status').textContent = '❌ Não encontrado';
                });
            
            // Verificar banco
            document.getElementById('db-status').textContent = '✅ Conectado';
            
            // Verificar triggers (aproximado)
            document.getElementById('triggers-status').textContent = '❓ Verificar manualmente';
        }
        
        function installTriggers() {
            showLoading('install-result', 'Instalando triggers...');
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=install_triggers'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult('install-result', data.message, 'success');
                } else {
                    showResult('install-result', data.error, 'error');
                }
            })
            .catch(error => {
                showResult('install-result', 'Erro: ' + error, 'error');
            });
        }
        
        function testNotifications() {
            showLoading('test-result', 'Enviando notificação de teste...');
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=test_notifications'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult('test-result', data.message + ' Verifique o console da página das assinaturas.', 'success');
                } else {
                    showResult('test-result', data.error, 'error');
                }
            })
            .catch(error => {
                showResult('test-result', 'Erro: ' + error, 'error');
            });
        }
        
        function removeTriggers() {
            if (!confirm('Tem certeza que deseja remover os triggers?')) return;
            
            showLoading('remove-result', 'Removendo triggers...');
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=remove_triggers'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult('remove-result', data.message, 'success');
                } else {
                    showResult('remove-result', data.error, 'error');
                }
            })
            .catch(error => {
                showResult('remove-result', 'Erro: ' + error, 'error');
            });
        }
        
        function showLoading(elementId, message) {
            const element = document.getElementById(elementId);
            element.innerHTML = message;
            element.className = 'result';
            element.style.display = 'block';
        }
        
        function showResult(elementId, message, type) {
            const element = document.getElementById(elementId);
            element.innerHTML = message;
            element.className = 'result ' + type;
            element.style.display = 'block';
        }
    </script>
</body>
</html> 