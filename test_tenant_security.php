<?php
/**
 * ============================================================================
 * TESTE DE SEGURANÇA MULTI-TENANT
 * ============================================================================
 * Arquivo para testar o middleware de segurança
 * Acesso: http://seu-servidor/qrcred/test_tenant_security.php
 * ============================================================================
 */

session_start();

// DEBUG: Verificar conteúdo da sessão
error_log("DEBUG SESSION: " . print_r($_SESSION, true));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Segurança Multi-tenant - QRCred</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        h2 {
            color: #764ba2;
            margin-top: 30px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        pre {
            background: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            overflow-x: auto;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            margin: 5px;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        .badge-warning {
            background: #ffc107;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background: #667eea;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 Teste de Segurança Multi-tenant - QRCred</h1>
        
        <!-- DEBUG: Conteúdo da Sessão -->
        <div class="test-section" style="background: #fff3cd; border-color: #ffc107;">
            <h2>🐛 DEBUG: Conteúdo da $_SESSION</h2>
            <?php
            echo '<pre style="background: #f8f9fa; padding: 15px; border-radius: 5px;">';
            echo 'Session ID: ' . session_id() . "\n\n";
            echo 'Conteúdo de $_SESSION:' . "\n";
            print_r($_SESSION);
            echo '</pre>';
            
            if (empty($_SESSION)) {
                echo '<div class="error">';
                echo '<strong>⚠️ PROBLEMA IDENTIFICADO:</strong> $_SESSION está vazia!<br>';
                echo 'Isso significa que os dados não foram armazenados no login ou a sessão foi perdida.';
                echo '</div>';
            }
            ?>
        </div>
        
        <?php
        try {
            require_once 'Adm/php/tenant_security.php';
            
            echo '<div class="success">';
            echo '<strong>✅ Middleware carregado com sucesso!</strong>';
            echo '</div>';
            
            $tenantSec = new TenantSecurity();
            
            // TESTE 1: Dados do Usuário Autenticado
            echo '<div class="test-section">';
            echo '<h2>📋 Teste 1: Usuário Autenticado</h2>';
            $usuario = $tenantSec->getUsuarioAutenticado();
            
            if ($usuario['codigo']) {
                echo '<div class="success">';
                echo '<strong>✅ Usuário autenticado encontrado</strong>';
                echo '</div>';
                
                echo '<table>';
                echo '<tr><th>Campo</th><th>Valor</th></tr>';
                echo '<tr><td><strong>Código</strong></td><td>' . htmlspecialchars($usuario['codigo']) . '</td></tr>';
                echo '<tr><td><strong>Username</strong></td><td>' . htmlspecialchars($usuario['username']) . '</td></tr>';
                echo '<tr><td><strong>Divisão</strong></td><td>' . htmlspecialchars($usuario['divisao']) . '</td></tr>';
                echo '</table>';
            } else {
                echo '<div class="error">';
                echo '<strong>❌ Nenhum usuário autenticado</strong><br>';
                echo 'Faça login primeiro para testar o middleware.';
                echo '</div>';
            }
            echo '</div>';
            
            // TESTE 2: Divisão Autenticada
            echo '<div class="test-section">';
            echo '<h2>🏢 Teste 2: Divisão Autenticada</h2>';
            $divisaoAuth = $tenantSec->getDivisaoAutenticada();
            
            if ($divisaoAuth) {
                echo '<div class="info">';
                echo '<strong>Divisão Autenticada:</strong> <span class="badge badge-success">' . htmlspecialchars($divisaoAuth) . '</span>';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '<strong>❌ Divisão não encontrada</strong>';
                echo '</div>';
            }
            echo '</div>';
            
            // TESTE 3: Validação de Acesso
            echo '<div class="test-section">';
            echo '<h2>🔐 Teste 3: Validação de Acesso</h2>';
            
            if ($divisaoAuth) {
                // Teste 3.1: Acesso à própria divisão (deve permitir)
                echo '<h3>3.1 - Acesso à própria divisão (ID: ' . $divisaoAuth . ')</h3>';
                if ($tenantSec->validateAccess($divisaoAuth)) {
                    echo '<div class="success">';
                    echo '<strong>✅ PERMITIDO</strong> - Acesso à divisão ' . $divisaoAuth . ' foi autorizado';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<strong>❌ NEGADO</strong> - Acesso à divisão ' . $divisaoAuth . ' foi bloqueado (ERRO!)';
                    echo '</div>';
                }
                
                // Teste 3.2: Acesso a outra divisão (deve bloquear)
                $outraDivisao = ($divisaoAuth == 1) ? 2 : 1;
                echo '<h3>3.2 - Tentativa de acesso cross-tenant (ID: ' . $outraDivisao . ')</h3>';
                if ($tenantSec->validateAccess($outraDivisao)) {
                    echo '<div class="error">';
                    echo '<strong>⚠️ PERMITIDO</strong> - Usuário tem permissão multi-divisão ou é admin';
                    echo '</div>';
                } else {
                    echo '<div class="success">';
                    echo '<strong>✅ BLOQUEADO</strong> - Tentativa de acesso cross-tenant foi bloqueada corretamente';
                    echo '</div>';
                }
            } else {
                echo '<div class="error">';
                echo '<strong>❌ Não é possível testar validação sem divisão autenticada</strong>';
                echo '</div>';
            }
            echo '</div>';
            
            // TESTE 4: Método getSecureDivisao
            echo '<div class="test-section">';
            echo '<h2>🛡️ Teste 4: Método getSecureDivisao()</h2>';
            
            if ($divisaoAuth) {
                // Simular $_POST
                $_POST['divisao'] = $divisaoAuth;
                $divisaoSegura = $tenantSec->getSecureDivisao();
                
                echo '<p><strong>Divisão solicitada via POST:</strong> ' . $divisaoAuth . '</p>';
                echo '<p><strong>Divisão retornada (segura):</strong> <span class="badge badge-success">' . $divisaoSegura . '</span></p>';
                
                if ($divisaoSegura == $divisaoAuth) {
                    echo '<div class="success">';
                    echo '<strong>✅ CORRETO</strong> - Divisão retornada corresponde à autenticada';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<strong>❌ ERRO</strong> - Divisão retornada não corresponde';
                    echo '</div>';
                }
            }
            echo '</div>';
            
            // TESTE 5: Verificar Logs
            echo '<div class="test-section">';
            echo '<h2>📊 Teste 5: Logs de Segurança</h2>';
            
            require_once 'Adm/php/banco.php';
            $pdo = Banco::conectar_postgres();
            
            $sqlLogs = "SELECT 
                            data_hora,
                            username,
                            divisao_usuario,
                            divisao_tentada,
                            bloqueado,
                            motivo
                        FROM sind.tenant_security_log
                        WHERE data_hora > NOW() - INTERVAL '1 hour'
                        ORDER BY data_hora DESC
                        LIMIT 10";
            
            $stmtLogs = $pdo->query($sqlLogs);
            $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($logs) > 0) {
                echo '<p><strong>Últimos 10 logs (última hora):</strong></p>';
                echo '<table>';
                echo '<tr>';
                echo '<th>Data/Hora</th>';
                echo '<th>Usuário</th>';
                echo '<th>Div. Usuário</th>';
                echo '<th>Div. Tentada</th>';
                echo '<th>Status</th>';
                echo '<th>Motivo</th>';
                echo '</tr>';
                
                foreach ($logs as $log) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($log['data_hora']) . '</td>';
                    echo '<td>' . htmlspecialchars($log['username']) . '</td>';
                    echo '<td>' . htmlspecialchars($log['divisao_usuario']) . '</td>';
                    echo '<td>' . htmlspecialchars($log['divisao_tentada']) . '</td>';
                    
                    if ($log['bloqueado'] == 't' || $log['bloqueado'] == true) {
                        echo '<td><span class="badge badge-danger">BLOQUEADO</span></td>';
                    } else {
                        echo '<td><span class="badge badge-success">PERMITIDO</span></td>';
                    }
                    
                    echo '<td>' . htmlspecialchars($log['motivo']) . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            } else {
                echo '<div class="info">';
                echo '<strong>ℹ️ Nenhum log encontrado na última hora</strong>';
                echo '</div>';
            }
            echo '</div>';
            
            // TESTE 6: Verificar Tabelas
            echo '<div class="test-section">';
            echo '<h2>🗄️ Teste 6: Verificação de Tabelas</h2>';
            
            $tabelas = [
                'tenant_security_log',
                'tenant_security_config',
                'usuario_divisao_permitida',
                'tenant_access_stats'
            ];
            
            echo '<table>';
            echo '<tr><th>Tabela</th><th>Status</th><th>Registros</th></tr>';
            
            foreach ($tabelas as $tabela) {
                $sqlCheck = "SELECT COUNT(*) as total FROM sind.$tabela";
                try {
                    $stmtCheck = $pdo->query($sqlCheck);
                    $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                    
                    echo '<tr>';
                    echo '<td><strong>' . $tabela . '</strong></td>';
                    echo '<td><span class="badge badge-success">✅ Existe</span></td>';
                    echo '<td>' . $result['total'] . '</td>';
                    echo '</tr>';
                } catch (Exception $e) {
                    echo '<tr>';
                    echo '<td><strong>' . $tabela . '</strong></td>';
                    echo '<td><span class="badge badge-danger">❌ Não existe</span></td>';
                    echo '<td>-</td>';
                    echo '</tr>';
                }
            }
            
            echo '</table>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>❌ ERRO ao carregar middleware:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '<br><br><strong>Stack Trace:</strong><br>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
        }
        ?>
        
        <div class="test-section">
            <h2>📚 Próximos Passos</h2>
            <ol>
                <li>✅ Verificar se todas as tabelas foram criadas</li>
                <li>✅ Confirmar que usuário está autenticado</li>
                <li>✅ Testar validação de acesso</li>
                <li>⏳ Executar SQL de criação das tabelas (se necessário)</li>
                <li>⏳ Proteger endpoints críticos gradualmente</li>
                <li>⏳ Monitorar logs de segurança</li>
            </ol>
        </div>
        
        <div class="info" style="margin-top: 30px;">
            <strong>📖 Documentação:</strong> Consulte o arquivo 
            <code>IMPLEMENTACAO_SEGURANCA_MULTITENANT.md</code> para instruções completas.
        </div>
    </div>
</body>
</html>
