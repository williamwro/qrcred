<?php
/**
 * ============================================================================
 * DEBUG DE SESSÃO - Diagnóstico Completo
 * ============================================================================
 */

session_start();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Debug de Sessão - QRCred</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        h2 { margin-top: 0; color: #333; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico de Sessão PHP</h1>
    
    <div class="section">
        <h2>1. Informações da Sessão</h2>
        <pre><?php
        echo "Session ID: " . session_id() . "\n";
        echo "Session Status: " . session_status() . " (1=disabled, 2=active, 3=none)\n";
        echo "Session Name: " . session_name() . "\n";
        echo "Session Save Path: " . session_save_path() . "\n";
        echo "Cookie Params: " . print_r(session_get_cookie_params(), true);
        ?></pre>
    </div>
    
    <div class="section <?php echo empty($_SESSION) ? 'error' : 'success'; ?>">
        <h2>2. Conteúdo de $_SESSION</h2>
        <?php if (empty($_SESSION)): ?>
            <p><strong>❌ $_SESSION está VAZIA!</strong></p>
            <p>Possíveis causas:</p>
            <ul>
                <li>Você não fez login ainda</li>
                <li>A sessão expirou</li>
                <li>Cookie de sessão não foi enviado pelo navegador</li>
                <li>Dados não foram salvos no login</li>
            </ul>
        <?php else: ?>
            <p><strong>✅ $_SESSION contém dados:</strong></p>
            <pre><?php print_r($_SESSION); ?></pre>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>3. Cookies Recebidos</h2>
        <pre><?php 
        if (empty($_COOKIE)) {
            echo "❌ Nenhum cookie recebido\n";
        } else {
            print_r($_COOKIE);
        }
        ?></pre>
    </div>
    
    <div class="section">
        <h2>4. Verificar Dados Esperados</h2>
        <?php
        $esperados = ['user_name', 'usuario_cod', 'divisao'];
        $faltando = [];
        
        foreach ($esperados as $key) {
            if (!isset($_SESSION[$key])) {
                $faltando[] = $key;
            }
        }
        
        if (empty($faltando)): ?>
            <p class="success">✅ Todos os dados esperados estão presentes!</p>
            <ul>
                <li><strong>user_name:</strong> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'N/A'); ?></li>
                <li><strong>usuario_cod:</strong> <?php echo htmlspecialchars($_SESSION['usuario_cod'] ?? 'N/A'); ?></li>
                <li><strong>divisao:</strong> <?php echo htmlspecialchars($_SESSION['divisao'] ?? 'N/A'); ?></li>
            </ul>
        <?php else: ?>
            <p class="error">❌ Dados faltando em $_SESSION:</p>
            <ul>
                <?php foreach ($faltando as $key): ?>
                    <li><code><?php echo $key; ?></code></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>5. Teste de Escrita na Sessão</h2>
        <?php
        $_SESSION['teste_debug'] = 'Valor de teste - ' . date('Y-m-d H:i:s');
        echo '<p>✅ Valor escrito em $_SESSION[\'teste_debug\']</p>';
        echo '<pre>Valor: ' . $_SESSION['teste_debug'] . '</pre>';
        echo '<p><em>Recarregue esta página. Se o valor persistir, a sessão está funcionando.</em></p>';
        ?>
    </div>
    
    <div class="section warning">
        <h2>6. Ações Recomendadas</h2>
        <?php if (empty($_SESSION) || !empty($faltando)): ?>
            <ol>
                <li><strong>Faça login novamente</strong> em: <a href="index.html">index.html</a></li>
                <li>Após o login, volte aqui para verificar se os dados foram salvos</li>
                <li>Se ainda não funcionar, verifique o arquivo <code>login_adm_localiza.php</code> linhas 54-57</li>
            </ol>
        <?php else: ?>
            <p>✅ Sessão está funcionando! Você pode testar o middleware agora:</p>
            <p><a href="test_tenant_security.php" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">Ir para Teste de Segurança</a></p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>7. Verificar Tabela sessoes_ativas</h2>
        <?php
        try {
            require_once 'Adm/php/banco.php';
            $pdo = Banco::conectar_postgres();
            
            $sql = "SELECT 
                        sa.session_id,
                        sa.codigo_usuario,
                        u.username,
                        u.divisao,
                        sa.is_active,
                        sa.login_time,
                        sa.last_activity
                    FROM sind.sessoes_ativas sa
                    LEFT JOIN sind.usuarios u ON sa.codigo_usuario = u.codigo
                    WHERE sa.is_active = true
                    ORDER BY sa.last_activity DESC
                    LIMIT 10";
            
            $stmt = $pdo->query($sql);
            $sessoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($sessoes) > 0) {
                echo '<p>✅ Sessões ativas encontradas no banco:</p>';
                echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">';
                echo '<tr style="background: #007bff; color: white;">';
                echo '<th>Session ID</th><th>Usuário</th><th>Divisão</th><th>Login</th><th>Última Atividade</th>';
                echo '</tr>';
                
                foreach ($sessoes as $s) {
                    $isCurrent = ($s['session_id'] == session_id());
                    $style = $isCurrent ? 'background: #d4edda; font-weight: bold;' : '';
                    
                    echo '<tr style="' . $style . '">';
                    echo '<td>' . htmlspecialchars(substr($s['session_id'], 0, 20)) . '...</td>';
                    echo '<td>' . htmlspecialchars($s['username']) . ' (ID: ' . $s['codigo_usuario'] . ')</td>';
                    echo '<td>' . htmlspecialchars($s['divisao']) . '</td>';
                    echo '<td>' . htmlspecialchars($s['login_time']) . '</td>';
                    echo '<td>' . htmlspecialchars($s['last_activity']) . '</td>';
                    echo '</tr>';
                    
                    if ($isCurrent) {
                        echo '<tr><td colspan="5" style="background: #d4edda; text-align: center;">👆 Esta é sua sessão atual</td></tr>';
                    }
                }
                
                echo '</table>';
            } else {
                echo '<p class="error">❌ Nenhuma sessão ativa encontrada no banco</p>';
            }
            
        } catch (Exception $e) {
            echo '<p class="error">❌ Erro ao consultar banco: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
    
    <hr>
    <p style="text-align: center; color: #666;">
        <small>Debug gerado em: <?php echo date('Y-m-d H:i:s'); ?></small>
    </p>
</body>
</html>
