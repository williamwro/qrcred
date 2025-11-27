<?php
/**
 * Debug Push Subscriptions
 * Verifica e corrige dados de subscriptions incompletos
 */

// Incluir conexão com banco
require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Push Subscriptions</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .btn { padding: 8px 15px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .truncate { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔍 Debug Push Subscriptions</h1>";

try {
    // Conectar ao banco PostgreSQL
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='box info'>
    <h2>📊 ANÁLISE COMPLETA DAS SUBSCRIPTIONS</h2>
    <strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "<br>
    <strong>User Card:</strong> 8029774802
    </div>";
    
    // 1. Verificar estrutura da tabela
    echo "<h2>📋 1. ESTRUTURA DA TABELA</h2>";
    
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable, column_default 
        FROM information_schema.columns 
        WHERE table_schema = 'sind' 
        AND table_name = 'push_subscriptions' 
        ORDER BY ordinal_position
    ");
    
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Nullable</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['column_name']}</td>";
        echo "<td>{$col['data_type']}</td>";
        echo "<td>{$col['is_nullable']}</td>";
        echo "<td>" . ($col['column_default'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Verificar todas as subscriptions
    echo "<h2>📱 2. TODAS AS SUBSCRIPTIONS</h2>";
    
    $stmt = $pdo->query("
        SELECT id, user_card, endpoint, p256dh, auth, settings, is_active, created_at
        FROM sind.push_subscriptions 
        ORDER BY created_at DESC
    ");
    
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='box'><strong>Total:</strong> " . count($subscriptions) . " subscriptions encontradas</div>";
    
    if (!empty($subscriptions)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>User Card</th><th>Endpoint</th><th>P256dh</th><th>Auth</th><th>Active</th><th>Created</th><th>Status</th></tr>";
        
        foreach ($subscriptions as $sub) {
            $isComplete = !empty($sub['endpoint']) && !empty($sub['p256dh']) && !empty($sub['auth']);
            $statusClass = $isComplete ? 'success' : 'error';
            $statusText = $isComplete ? '✅ Completa' : '❌ Incompleta';
            
            echo "<tr>";
            echo "<td>{$sub['id']}</td>";
            echo "<td>{$sub['user_card']}</td>";
            echo "<td class='truncate'>" . (empty($sub['endpoint']) ? '❌ VAZIO' : '✅ ' . substr($sub['endpoint'], 0, 50) . '...') . "</td>";
            echo "<td>" . (empty($sub['p256dh']) ? '❌ VAZIO' : '✅ ' . substr($sub['p256dh'], 0, 20) . '...') . "</td>";
            echo "<td>" . (empty($sub['auth']) ? '❌ VAZIO' : '✅ ' . substr($sub['auth'], 0, 20) . '...') . "</td>";
            echo "<td>" . ($sub['is_active'] ? '✅' : '❌') . "</td>";
            echo "<td>" . date('d/m H:i', strtotime($sub['created_at'])) . "</td>";
            echo "<td class='{$statusClass}'>{$statusText}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Focar no usuário específico
    echo "<h2>🎯 3. SUBSCRIPTION DO USUÁRIO 8029774802</h2>";
    
    $stmt = $pdo->prepare("
        SELECT * FROM sind.push_subscriptions 
        WHERE user_card = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute(['8029774802']);
    $userSubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($userSubs)) {
        echo "<div class='box error'>❌ Nenhuma subscription encontrada para este usuário!</div>";
        echo "<div class='box info'>
        <h4>💡 SOLUÇÃO:</h4>
        1. Abra o app no celular<br>
        2. Vá para Dashboard<br>
        3. Ative as notificações push<br>
        4. Execute este debug novamente
        </div>";
    } else {
        foreach ($userSubs as $sub) {
            echo "<div class='box'>";
            echo "<h4>Subscription ID: {$sub['id']}</h4>";
            echo "<strong>User Card:</strong> {$sub['user_card']}<br>";
            echo "<strong>Created:</strong> {$sub['created_at']}<br>";
            echo "<strong>Active:</strong> " . ($sub['is_active'] ? '✅ Sim' : '❌ Não') . "<br>";
            
            // Verificar cada campo
            echo "<h5>📊 Verificação de Campos:</h5>";
            echo "<strong>Endpoint:</strong> " . (empty($sub['endpoint']) ? '❌ VAZIO' : '✅ OK (' . strlen($sub['endpoint']) . ' chars)') . "<br>";
            echo "<strong>P256dh:</strong> " . (empty($sub['p256dh']) ? '❌ VAZIO' : '✅ OK (' . strlen($sub['p256dh']) . ' chars)') . "<br>";
            echo "<strong>Auth:</strong> " . (empty($sub['auth']) ? '❌ VAZIO' : '✅ OK (' . strlen($sub['auth']) . ' chars)') . "<br>";
            
            if (!empty($sub['settings'])) {
                echo "<strong>Settings:</strong> {$sub['settings']}<br>";
            }
            
            $isComplete = !empty($sub['endpoint']) && !empty($sub['p256dh']) && !empty($sub['auth']);
            
            if ($isComplete) {
                echo "<div class='success'><strong>✅ SUBSCRIPTION COMPLETA</strong></div>";
            } else {
                echo "<div class='error'><strong>❌ SUBSCRIPTION INCOMPLETA</strong></div>";
                echo "<div class='box warning'>";
                echo "<h5>🔧 CAMPOS FALTANDO:</h5>";
                if (empty($sub['endpoint'])) echo "• Endpoint<br>";
                if (empty($sub['p256dh'])) echo "• P256dh key<br>";
                if (empty($sub['auth'])) echo "• Auth key<br>";
                echo "</div>";
            }
            
            echo "</div>";
        }
    }
    
    // 4. Ações de limpeza
    echo "<h2>🧹 4. AÇÕES DE LIMPEZA</h2>";
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as count FROM sind.push_subscriptions 
        WHERE endpoint IS NULL OR endpoint = '' 
        OR p256dh IS NULL OR p256dh = ''
        OR auth IS NULL OR auth = ''
    ");
    $incompleteCount = $stmt->fetch()['count'];
    
    echo "<div class='box'>";
    echo "<h4>📊 Subscriptions Incompletas: {$incompleteCount}</h4>";
    
    if ($incompleteCount > 0) {
        echo "<div class='warning'>";
        echo "<p>🚨 Existem {$incompleteCount} subscriptions com dados incompletos que podem causar falhas.</p>";
        echo "</div>";
        
        echo "<form method='post' style='display: inline;'>";
        echo "<input type='hidden' name='action' value='clean_incomplete'>";
        echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Tem certeza que deseja remover as subscriptions incompletas?\")'>🗑️ Limpar Subscriptions Incompletas</button>";
        echo "</form>";
    } else {
        echo "<div class='success'>✅ Todas as subscriptions estão completas!</div>";
    }
    echo "</div>";
    
    // Processar ação de limpeza
    if ($_POST['action'] == 'clean_incomplete') {
        echo "<div class='box'>";
        echo "<h4>🧹 LIMPANDO SUBSCRIPTIONS INCOMPLETAS...</h4>";
        
        $stmt = $pdo->prepare("
            DELETE FROM sind.push_subscriptions 
            WHERE endpoint IS NULL OR endpoint = '' 
            OR p256dh IS NULL OR p256dh = ''
            OR auth IS NULL OR auth = ''
        ");
        
        if ($stmt->execute()) {
            $deletedCount = $stmt->rowCount();
            echo "<div class='success'>✅ {$deletedCount} subscriptions incompletas removidas!</div>";
            echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
        } else {
            echo "<div class='error'>❌ Erro ao remover subscriptions</div>";
        }
        
        echo "</div>";
    }
    
    // 5. Próximos passos
    echo "<h2>🎯 5. PRÓXIMOS PASSOS</h2>";
    echo "<div class='box info'>";
    
    if (empty($userSubs)) {
        echo "<h4>❌ USUÁRIO SEM SUBSCRIPTION:</h4>";
        echo "1. <strong>Abra o app</strong> no celular<br>";
        echo "2. <strong>Vá para Dashboard</strong><br>";
        echo "3. <strong>Ative as notificações</strong> (botão no topo)<br>";
        echo "4. <strong>Execute este debug</strong> novamente<br>";
    } else {
        $hasComplete = false;
        foreach ($userSubs as $sub) {
            if (!empty($sub['endpoint']) && !empty($sub['p256dh']) && !empty($sub['auth'])) {
                $hasComplete = true;
                break;
            }
        }
        
        if ($hasComplete) {
            echo "<h4>✅ PRONTO PARA TESTE:</h4>";
            echo "1. <strong>Execute test_push_final.php</strong> novamente<br>";
            echo "2. <strong>Verificar se notificação chega</strong> no celular<br>";
            echo "3. <strong>Sistema deve funcionar</strong> agora!<br>";
        } else {
            echo "<h4>🔧 SUBSCRIPTION INCOMPLETA:</h4>";
            echo "1. <strong>Desative e reative</strong> as notificações no app<br>";
            echo "2. <strong>Limpe subscriptions incompletas</strong> (botão acima)<br>";
            echo "3. <strong>Registre nova subscription</strong> completa<br>";
        }
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ ERRO</h3>";
    echo "<strong>Erro:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr><div class='box warning'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após usar!</strong><br>";
echo "📁 Arquivo: debug_subscriptions.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 