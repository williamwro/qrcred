<?php
/**
 * Limpar Subscriptions Antigas
 * Remove subscriptions com dados incompletos
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🗑️ Limpar Subscriptions Antigas</h1>\n";
echo "<pre>\n";

try {
    require_once 'Adm/php/banco.php';
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=====================================\n";
    echo "LIMPEZA DE SUBSCRIPTIONS ANTIGAS\n";
    echo "=====================================\n";
    
    // Verificar subscriptions antes da limpeza
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM sind.push_subscriptions");
    $totalBefore = $countStmt->fetch()['total'];
    echo "Total de subscriptions antes: {$totalBefore}\n";
    
    // Contar subscriptions com problemas
    $problemStmt = $pdo->query("
        SELECT COUNT(*) as problemas 
        FROM sind.push_subscriptions 
        WHERE p256dh IS NULL OR p256dh = '' OR auth IS NULL OR auth = ''
    ");
    $totalProblemas = $problemStmt->fetch()['problemas'];
    echo "Subscriptions com problemas: {$totalProblemas}\n\n";
    
    if ($totalProblemas > 0) {
        echo "🗑️ Removendo subscriptions com dados incompletos...\n";
        
        $deleteStmt = $pdo->prepare("
            DELETE FROM sind.push_subscriptions 
            WHERE p256dh IS NULL OR p256dh = '' OR auth IS NULL OR auth = ''
        ");
        $deleteStmt->execute();
        $deletedCount = $deleteStmt->rowCount();
        
        echo "✅ Removidas: {$deletedCount} subscriptions\n";
    } else {
        echo "ℹ️ Nenhuma subscription com problemas encontrada.\n";
    }
    
    // Verificar após limpeza
    $countStmt->execute();
    $totalAfter = $countStmt->fetch()['total'];
    echo "Total de subscriptions após limpeza: {$totalAfter}\n\n";
    
    if ($totalAfter > 0) {
        echo "📋 Subscriptions restantes:\n";
        $remainingStmt = $pdo->query("
            SELECT id, user_card, 
                   CASE WHEN p256dh IS NOT NULL AND p256dh != '' THEN 'OK' ELSE 'VAZIO' END as p256dh_status,
                   CASE WHEN auth IS NOT NULL AND auth != '' THEN 'OK' ELSE 'VAZIO' END as auth_status,
                   created_at
            FROM sind.push_subscriptions 
            ORDER BY id DESC
        ");
        
        while ($row = $remainingStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- ID {$row['id']}: User {$row['user_card']}, p256dh: {$row['p256dh_status']}, auth: {$row['auth_status']}\n";
        }
    } else {
        echo "✨ Tabela limpa! Todas as subscriptions foram removidas.\n";
    }
    
    echo "\n=====================================\n";
    echo "PRÓXIMOS PASSOS\n";
    echo "=====================================\n";
    echo "1. 🔑 Configure a chave VAPID privada no send_push_notification_app.php:\n";
    echo "   define('VAPID_PRIVATE_KEY', 'gdc9W5SDjkTwr7l_fa-TE6D53VfXs_S3cBSeq2OrF4o');\n\n";
    echo "2. 📱 Reative notificações no app móvel\n";
    echo "   (isso criará uma nova subscription com dados corretos)\n\n";
    echo "3. 🧪 Execute o teste final novamente\n\n";
    
    echo "❌ DELETE este arquivo após usar!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n";
}

echo "</pre>\n";
?> 