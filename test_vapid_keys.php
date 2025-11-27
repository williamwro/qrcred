<?php
/**
 * Teste VAPID Keys
 * Verifica se as chaves VAPID estão corretas
 */

require_once 'vapid_config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste VAPID Keys</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .code { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔑 Teste VAPID Keys</h1>";

echo "<div class='box info'>
<h2>📊 VERIFICAÇÃO DAS CHAVES VAPID</h2>
<strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "
</div>";

// 1. Verificar se constantes estão definidas
echo "<h2>📋 1. CONSTANTES DEFINIDAS</h2>";
echo "<div class='box'>";

$vapidCheck = [
    'VAPID_PUBLIC_KEY' => defined('VAPID_PUBLIC_KEY'),
    'VAPID_PRIVATE_KEY' => defined('VAPID_PRIVATE_KEY'),
    'VAPID_SUBJECT' => defined('VAPID_SUBJECT')
];

foreach ($vapidCheck as $const => $defined) {
    $status = $defined ? '✅ SIM' : '❌ NÃO';
    echo "<strong>{$const}:</strong> {$status}<br>";
    
    if ($defined) {
        $value = constant($const);
        $length = strlen($value);
        echo "<strong>Tamanho:</strong> {$length} caracteres<br>";
        echo "<strong>Início:</strong> " . substr($value, 0, 20) . "...<br>";
    }
    echo "<br>";
}

echo "</div>";

// 2. Validar formato das chaves
echo "<h2>🔍 2. VALIDAÇÃO DO FORMATO</h2>";
echo "<div class='box'>";

if (defined('VAPID_PUBLIC_KEY')) {
    $publicKey = VAPID_PUBLIC_KEY;
    echo "<h4>Chave Pública:</h4>";
    echo "<strong>Tamanho:</strong> " . strlen($publicKey) . " chars<br>";
    echo "<strong>Formato Base64:</strong> " . (preg_match('/^[A-Za-z0-9+\/]+=*$/', $publicKey) ? '✅' : '❌') . "<br>";
    echo "<strong>Decodificável:</strong> " . (base64_decode($publicKey, true) !== false ? '✅' : '❌') . "<br>";
    
    $decoded = base64_decode($publicKey, true);
    if ($decoded) {
        echo "<strong>Bytes decodificados:</strong> " . strlen($decoded) . " bytes<br>";
        echo "<strong>Esperado P-256:</strong> " . (strlen($decoded) == 65 ? '✅ 65 bytes' : '❌ ' . strlen($decoded) . ' bytes') . "<br>";
    }
} else {
    echo "<div class='error'>❌ VAPID_PUBLIC_KEY não definida</div>";
}

echo "<br>";

if (defined('VAPID_PRIVATE_KEY')) {
    $privateKey = VAPID_PRIVATE_KEY;
    echo "<h4>Chave Privada:</h4>";
    echo "<strong>Tamanho:</strong> " . strlen($privateKey) . " chars<br>";
    echo "<strong>Formato Base64:</strong> " . (preg_match('/^[A-Za-z0-9+\/]+=*$/', $privateKey) ? '✅' : '❌') . "<br>";
    echo "<strong>Decodificável:</strong> " . (base64_decode($privateKey, true) !== false ? '✅' : '❌') . "<br>";
    
    $decoded = base64_decode($privateKey, true);
    if ($decoded) {
        echo "<strong>Bytes decodificados:</strong> " . strlen($decoded) . " bytes<br>";
        echo "<strong>Esperado P-256:</strong> " . (strlen($decoded) == 32 ? '✅ 32 bytes' : '❌ ' . strlen($decoded) . ' bytes') . "<br>";
    }
} else {
    echo "<div class='error'>❌ VAPID_PRIVATE_KEY não definida</div>";
}

echo "</div>";

// 3. Teste com biblioteca web-push
echo "<h2>🧪 3. TESTE COM WEB-PUSH LIBRARY</h2>";
echo "<div class='box'>";

try {
    require_once 'vendor/autoload.php';
    
    if (class_exists('Minishlink\WebPush\VAPID')) {
        echo "<h4>✅ Biblioteca web-push disponível</h4>";
        
        // Tentar validar chaves com biblioteca
        if (defined('VAPID_PUBLIC_KEY') && defined('VAPID_PRIVATE_KEY')) {
            try {
                $vapid = new \Minishlink\WebPush\VAPID();
                
                // Criar chaves de teste
                $keys = $vapid::createVapidKeys();
                echo "<strong>Chaves de teste geradas:</strong><br>";
                echo "Pública: " . substr($keys['publicKey'], 0, 30) . "...<br>";
                echo "Privada: " . substr($keys['privateKey'], 0, 30) . "...<br>";
                echo "<br>";
                
                echo "<h4>📊 COMPARAÇÃO:</h4>";
                echo "<strong>Sua chave pública atual:</strong><br>";
                echo "<div class='code'>" . VAPID_PUBLIC_KEY . "</div>";
                echo "<strong>Exemplo de chave válida:</strong><br>";
                echo "<div class='code'>" . $keys['publicKey'] . "</div>";
                
                // Verificar se as chaves atuais são válidas
                $currentPublicValid = (strlen(base64_decode(VAPID_PUBLIC_KEY, true)) == 65);
                $currentPrivateValid = (strlen(base64_decode(VAPID_PRIVATE_KEY, true)) == 32);
                
                echo "<br><h4>🎯 DIAGNÓSTICO:</h4>";
                if ($currentPublicValid && $currentPrivateValid) {
                    echo "<div class='success'>✅ Suas chaves VAPID parecem válidas no formato</div>";
                    echo "<div class='warning'>⚠️ Mas o Google FCM retorna 403 - pode ser problema de par incompatível</div>";
                    echo "<div class='info'>💡 Recomendo gerar novas chaves em par</div>";
                } else {
                    echo "<div class='error'>❌ Suas chaves VAPID têm formato incorreto</div>";
                    echo "<div class='info'>💡 Precisa gerar novas chaves válidas</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Erro ao usar biblioteca: " . $e->getMessage() . "</div>";
            }
        }
        
    } else {
        echo "<div class='error'>❌ Biblioteca web-push não disponível</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
}

echo "</div>";

// 4. Gerar novas chaves
echo "<h2>🔧 4. GERAR NOVAS CHAVES</h2>";
echo "<div class='box'>";

try {
    if (class_exists('Minishlink\WebPush\VAPID')) {
        $newKeys = \Minishlink\WebPush\VAPID::createVapidKeys();
        
        echo "<h4>🆕 NOVAS CHAVES GERADAS:</h4>";
        echo "<strong>Chave Pública:</strong><br>";
        echo "<div class='code'>" . $newKeys['publicKey'] . "</div>";
        echo "<strong>Chave Privada:</strong><br>";
        echo "<div class='code'>" . $newKeys['privateKey'] . "</div>";
        
        echo "<br><h4>📋 INSTRUÇÕES:</h4>";
        echo "1. <strong>Substitua as chaves no vapid_config.php</strong><br>";
        echo "2. <strong>Atualize a chave pública no NotificationManager.tsx</strong><br>";
        echo "3. <strong>Teste novamente</strong><br>";
        
        echo "<br><h4>📝 CÓDIGO PARA vapid_config.php:</h4>";
        echo "<div class='code'>";
        echo "define('VAPID_PUBLIC_KEY', '" . $newKeys['publicKey'] . "');\n";
        echo "define('VAPID_PRIVATE_KEY', '" . $newKeys['privateKey'] . "');\n";
        echo "define('VAPID_SUBJECT', 'mailto:admin@sas.makecard.com.br');\n";
        echo "</div>";
        
        echo "<br><h4>📝 CÓDIGO PARA NotificationManager.tsx:</h4>";
        echo "<div class='code'>";
        echo "const vapidPublicKey = '" . $newKeys['publicKey'] . "';\n";
        echo "</div>";
        
    } else {
        echo "<div class='error'>❌ Não foi possível gerar novas chaves - biblioteca não disponível</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro ao gerar chaves: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<hr><div class='box warning'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após usar!</strong><br>";
echo "📁 Arquivo: test_vapid_keys.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 