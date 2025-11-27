<?php
/**
 * Corrigir Estrutura das Subscriptions
 * Resolve problemas com p256dh_key vs p256dh
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔧 Corrigir Estrutura das Subscriptions</h1>\n";
echo "<pre>\n";

try {
    require_once 'Adm/php/banco.php';
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=====================================\n";
    echo "1. VERIFICANDO SUBSCRIPTIONS ATUAIS\n";
    echo "=====================================\n";
    
    // Buscar todas as subscriptions
    $stmt = $pdo->query("SELECT * FROM sind.push_subscriptions ORDER BY id DESC");
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total de subscriptions: " . count($subscriptions) . "\n\n";
    
    foreach ($subscriptions as $i => $sub) {
        echo "Subscription " . ($i + 1) . ":\n";
        echo "- ID: {$sub['id']}\n";
        echo "- User: {$sub['user_card']}\n";
        echo "- Endpoint: " . substr($sub['endpoint'], 0, 50) . "...\n";
        
        // Verificar se tem p256dh e auth
        if (isset($sub['p256dh'])) {
            echo "- p256dh: " . (empty($sub['p256dh']) ? "VAZIO" : "OK (" . strlen($sub['p256dh']) . " chars)") . "\n";
        } else {
            echo "- p256dh: COLUNA NÃO EXISTE\n";
        }
        
        if (isset($sub['auth'])) {
            echo "- auth: " . (empty($sub['auth']) ? "VAZIO" : "OK (" . strlen($sub['auth']) . " chars)") . "\n";
        } else {
            echo "- auth: COLUNA NÃO EXISTE\n";
        }
        
        echo "\n";
    }
    
    echo "=====================================\n";
    echo "2. TESTANDO DECODIFICAÇÃO DE ENDPOINT\n";
    echo "=====================================\n";
    
    if (count($subscriptions) > 0) {
        $testSub = $subscriptions[0];
        echo "Testando subscription ID: {$testSub['id']}\n";
        echo "Endpoint: {$testSub['endpoint']}\n\n";
        
        // Tentar decodificar como JSON (formato novo)
        if (!empty($testSub['endpoint']) && (strpos($testSub['endpoint'], '{') === 0 || strpos($testSub['endpoint'], 'https://') === 0)) {
            if (strpos($testSub['endpoint'], '{') === 0) {
                echo "🔍 Endpoint parece ser JSON, tentando decodificar...\n";
                $endpointData = json_decode($testSub['endpoint'], true);
                if ($endpointData) {
                    echo "✅ JSON decodificado com sucesso!\n";
                    echo "Estrutura encontrada:\n";
                    foreach ($endpointData as $key => $value) {
                        if (is_string($value)) {
                            $displayValue = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                        } else {
                            $displayValue = json_encode($value);
                        }
                        echo "  - {$key}: {$displayValue}\n";
                    }
                } else {
                    echo "❌ Erro ao decodificar JSON\n";
                }
            } else {
                echo "🔍 Endpoint parece ser URL simples: {$testSub['endpoint']}\n";
            }
        }
    }
    
    echo "\n=====================================\n";
    echo "3. VERIFICANDO NECESSIDADE DE CORREÇÃO\n";
    echo "=====================================\n";
    
    $needsFix = false;
    $emptyData = 0;
    
    foreach ($subscriptions as $sub) {
        if (empty($sub['p256dh']) || empty($sub['auth'])) {
            $needsFix = true;
            $emptyData++;
        }
    }
    
    echo "Subscriptions com dados vazios: {$emptyData}\n";
    
    if ($needsFix) {
        echo "⚠️ PROBLEMA ENCONTRADO: Subscriptions têm p256dh/auth vazios\n";
        echo "\n💡 SOLUÇÕES:\n";
        echo "1. Os dados podem estar no formato JSON dentro do endpoint\n";
        echo "2. Ou as subscriptions foram criadas antes da correção da tabela\n";
        echo "3. É necessário recriar as subscriptions no app\n\n";
        
        echo "🔧 RECOMENDAÇÃO:\n";
        echo "1. Limpe as subscriptions antigas:\n";
        echo "   DELETE FROM sind.push_subscriptions WHERE p256dh IS NULL OR p256dh = '';\n";
        echo "2. Reative notificações no app móvel\n";
        echo "3. Isso criará subscriptions com estrutura correta\n";
        
    } else {
        echo "✅ Estrutura parece OK\n";
    }
    
    echo "\n=====================================\n";
    echo "4. VERIFICAÇÃO DA CHAVE VAPID\n";
    echo "=====================================\n";
    
    $expectedPrivateKey = 'gdc9W5SDjkTwr7l_fa-TE6D53VfXs_S3cBSeq2OrF4o';
    
    echo "🔑 Chave VAPID privada esperada:\n";
    echo "{$expectedPrivateKey}\n\n";
    
    echo "📝 Para configurar no send_push_notification_app.php:\n";
    echo "Localize a linha (aproximadamente linha 24):\n";
    echo "define('VAPID_PRIVATE_KEY', 'COLOQUE_SUA_CHAVE_VAPID_PRIVADA_AQUI');\n\n";
    echo "E substitua por:\n";
    echo "define('VAPID_PRIVATE_KEY', '{$expectedPrivateKey}');\n\n";
    
    echo "=====================================\n";
    echo "5. PRÓXIMOS PASSOS\n";
    echo "=====================================\n";
    
    if ($needsFix) {
        echo "🔧 CORREÇÕES NECESSÁRIAS:\n";
        echo "1. ⚠️ Configure a chave VAPID privada no PHP\n";
        echo "2. 🗑️ Limpe subscriptions antigas (opcional)\n";
        echo "3. 📱 Reative notificações no app\n";
        echo "4. 🧪 Execute o teste novamente\n";
    } else {
        echo "✅ Estrutura OK - apenas configure a chave VAPID\n";
        echo "1. 🔑 Configure a chave VAPID privada no PHP\n";
        echo "2. 🧪 Execute o teste novamente\n";
    }
    
    echo "\n❌ DELETE este arquivo após usar!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n";
}

echo "</pre>\n";
?> 