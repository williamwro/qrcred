<?php
/**
 * Debug Específico das Chaves VAPID
 * Diagnostica problemas com autenticação VAPID
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔍 Debug - Chaves VAPID</h1>\n";
echo "<pre>\n";

echo "=====================================\n";
echo "1. VERIFICANDO CHAVES VAPID\n";
echo "=====================================\n";

// Verificar se as constantes estão definidas
if (defined('VAPID_PUBLIC_KEY')) {
    echo "✅ VAPID_PUBLIC_KEY definida: " . substr(VAPID_PUBLIC_KEY, 0, 20) . "...\n";
} else {
    echo "❌ VAPID_PUBLIC_KEY NÃO DEFINIDA\n";
}

if (defined('VAPID_PRIVATE_KEY')) {
    echo "✅ VAPID_PRIVATE_KEY definida: " . substr(VAPID_PRIVATE_KEY, 0, 20) . "...\n";
} else {
    echo "❌ VAPID_PRIVATE_KEY NÃO DEFINIDA\n";
}

// Definir as chaves diretamente para teste
define('TEST_VAPID_PUBLIC_KEY', 'BM7z6QhdLZUACWiMZvwVb6JL2Qtvr2zFOOFqqi5E5yhFeZWj2k1YewWgAxXidqbGmcznD5LcfRComGe8h6TOAHM');
define('TEST_VAPID_PRIVATE_KEY', 'MSA8Clt7h_bbUhLq9Sbh6zPjXCzwZvecNHCqexeJPu8');

echo "\n📝 Chaves de teste:\n";
echo "Pública: " . TEST_VAPID_PUBLIC_KEY . "\n";
echo "Privada: " . TEST_VAPID_PRIVATE_KEY . "\n";

echo "\n=====================================\n";
echo "2. VALIDANDO FORMATO DAS CHAVES\n";
echo "=====================================\n";

// Verificar tamanho das chaves
$publicKeyLength = strlen(TEST_VAPID_PUBLIC_KEY);
$privateKeyLength = strlen(TEST_VAPID_PRIVATE_KEY);

echo "Tamanho chave pública: {$publicKeyLength} chars\n";
echo "Tamanho chave privada: {$privateKeyLength} chars\n";

if ($publicKeyLength === 87) {
    echo "✅ Tamanho da chave pública OK\n";
} else {
    echo "❌ Tamanho da chave pública incorreto (esperado: 87)\n";
}

if ($privateKeyLength === 43) {
    echo "✅ Tamanho da chave privada OK\n";
} else {
    echo "❌ Tamanho da chave privada incorreto (esperado: 43)\n";
}

echo "\n=====================================\n";
echo "3. TESTANDO DECODIFICAÇÃO BASE64URL\n";
echo "=====================================\n";

function base64url_decode($data) {
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $data = str_pad($data, strlen($data) % 4, '=', STR_PAD_RIGHT);
    return base64_decode($data);
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

try {
    $decodedPrivateKey = base64url_decode(TEST_VAPID_PRIVATE_KEY);
    echo "✅ Chave privada decodificada: " . strlen($decodedPrivateKey) . " bytes\n";
    
    $decodedPublicKey = base64url_decode(TEST_VAPID_PUBLIC_KEY);
    echo "✅ Chave pública decodificada: " . strlen($decodedPublicKey) . " bytes\n";
} catch (Exception $e) {
    echo "❌ Erro na decodificação: {$e->getMessage()}\n";
}

echo "\n=====================================\n";
echo "4. TESTANDO GERAÇÃO JWT SIMPLES\n";
echo "=====================================\n";

function generateSimpleJWT($privateKey) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'ES256']);
    $payload = json_encode([
        'aud' => 'https://fcm.googleapis.com',
        'exp' => time() + 3600,
        'sub' => 'mailto:test@example.com'
    ]);
    
    $headerEncoded = base64url_encode($header);
    $payloadEncoded = base64url_encode($payload);
    
    $dataToSign = $headerEncoded . '.' . $payloadEncoded;
    
    echo "Dados para assinar: " . substr($dataToSign, 0, 50) . "...\n";
    echo "Tamanho: " . strlen($dataToSign) . " chars\n";
    
    return $dataToSign;
}

try {
    $jwtData = generateSimpleJWT(TEST_VAPID_PRIVATE_KEY);
    echo "✅ JWT base gerado com sucesso\n";
} catch (Exception $e) {
    echo "❌ Erro na geração JWT: {$e->getMessage()}\n";
}

echo "\n=====================================\n";
echo "5. VERIFICANDO EXTENSÕES PHP\n";
echo "=====================================\n";

$extensions = ['openssl', 'json', 'curl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ {$ext} disponível\n";
    } else {
        echo "❌ {$ext} NÃO disponível\n";
    }
}

echo "\n=====================================\n";
echo "6. TESTE SIMPLES DE AUTENTICAÇÃO\n";
echo "=====================================\n";

echo "🔑 Testando autenticação VAPID simples...\n";

// Simular dados de subscription
$testSubscription = [
    'endpoint' => 'https://fcm.googleapis.com/fcm/send/test123',
    'keys' => [
        'p256dh' => 'test_p256dh_key',
        'auth' => 'test_auth_key'
    ]
];

echo "Endpoint de teste: {$testSubscription['endpoint']}\n";

// Extrair serviço do endpoint
$parsedUrl = parse_url($testSubscription['endpoint']);
$audience = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
echo "Audience extraída: {$audience}\n";

echo "\n=====================================\n";
echo "7. DIAGNÓSTICO FINAL\n";
echo "=====================================\n";

echo "📊 RESUMO:\n";
echo "- Chaves VAPID definidas: " . (defined('VAPID_PRIVATE_KEY') ? 'SIM' : 'NÃO') . "\n";
echo "- Formato das chaves: " . (($publicKeyLength === 87 && $privateKeyLength === 43) ? 'OK' : 'PROBLEMA') . "\n";
echo "- Extensões PHP: " . (extension_loaded('openssl') ? 'OK' : 'PROBLEMA') . "\n";

echo "\n🔧 POSSÍVEIS PROBLEMAS:\n";
echo "1. ❌ Chave VAPID privada não está sendo lida pelo script\n";
echo "2. ❌ Implementação JWT está incorreta\n";
echo "3. ❌ Chaves não coincidem entre frontend e backend\n";
echo "4. ❌ Problema na assinatura ECDSA\n";

echo "\n💡 PRÓXIMAS AÇÕES:\n";
echo "1. Verificar se send_push_notification_app.php está lendo as constantes\n";
echo "2. Verificar implementação da função generateJWT\n";
echo "3. Testar com biblioteca externa de JWT\n";

echo "\n❌ DELETE este arquivo após usar!\n";
echo "</pre>\n";
?> 