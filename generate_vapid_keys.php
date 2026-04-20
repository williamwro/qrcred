<?php
require_once __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

try {
    $keys = VAPID::createVapidKeys();
    
    echo "✅ Chaves VAPID geradas com sucesso!\n\n";
    echo "Copie estas chaves para o vapid_config.php:\n\n";
    echo "<?php\n";
    echo "define('VAPID_PUBLIC_KEY', '{$keys['publicKey']}');\n";
    echo "define('VAPID_PRIVATE_KEY', '{$keys['privateKey']}');\n";
    echo "define('VAPID_SUBJECT', 'mailto:admin@sas.makecard.com.br');\n";
    echo "?>\n\n";
    
    echo "IMPORTANTE: Você também precisa atualizar a chave pública no frontend!\n";
    echo "Arquivo: app/lib/push-notifications.ts\n";
    echo "Linha: applicationServerKey\n";
    echo "Valor: {$keys['publicKey']}\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao gerar chaves: " . $e->getMessage() . "\n";
}
?>