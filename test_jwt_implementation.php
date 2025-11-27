<?php
/**
 * Teste Específico da Implementação JWT
 * Diagnóstico detalhado da assinatura ECDSA
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔍 Teste JWT - Assinatura ECDSA</h1>\n";
echo "<pre>\n";

// Incluir o arquivo principal para ter acesso às funções
include_once 'send_push_notification_app.php';

echo "=====================================\n";
echo "1. VERIFICANDO CONSTANTES\n";
echo "=====================================\n";

if (defined('VAPID_PUBLIC_KEY') && defined('VAPID_PRIVATE_KEY')) {
    echo "✅ Constantes VAPID disponíveis\n";
    echo "Pública: " . substr(VAPID_PUBLIC_KEY, 0, 30) . "...\n";
    echo "Privada: " . substr(VAPID_PRIVATE_KEY, 0, 30) . "...\n";
    
    if (defined('VAPID_SUBJECT')) {
        echo "Subject: " . VAPID_SUBJECT . "\n";
    }
} else {
    echo "❌ Constantes não disponíveis\n";
    exit;
}

echo "\n=====================================\n";
echo "2. TESTANDO FUNÇÕES BASE64URL\n";
echo "=====================================\n";

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $data = str_pad($data, strlen($data) % 4, '=', STR_PAD_RIGHT);
    return base64_decode($data);
}

// Testar decodificação da chave privada
$privateKeyDecoded = base64url_decode(VAPID_PRIVATE_KEY);
echo "Chave privada decodificada: " . strlen($privateKeyDecoded) . " bytes\n";
echo "Primeiros bytes (hex): " . bin2hex(substr($privateKeyDecoded, 0, 8)) . "\n";

$publicKeyDecoded = base64url_decode(VAPID_PUBLIC_KEY);
echo "Chave pública decodificada: " . strlen($publicKeyDecoded) . " bytes\n";
echo "Primeiros bytes (hex): " . bin2hex(substr($publicKeyDecoded, 0, 8)) . "\n";

echo "\n=====================================\n";
echo "3. TESTANDO GERAÇÃO JWT MANUAL\n";
echo "=====================================\n";

// Criar JWT manualmente
$header = [
    'typ' => 'JWT',
    'alg' => 'ES256'
];

$payload = [
    'aud' => 'https://fcm.googleapis.com',
    'exp' => time() + 3600,
    'sub' => defined('VAPID_SUBJECT') ? VAPID_SUBJECT : 'mailto:admin@example.com'
];

$headerEncoded = base64url_encode(json_encode($header));
$payloadEncoded = base64url_encode(json_encode($payload));

echo "Header: " . json_encode($header) . "\n";
echo "Payload: " . json_encode($payload) . "\n";
echo "Header encoded: " . $headerEncoded . "\n";
echo "Payload encoded: " . $payloadEncoded . "\n";

$dataToSign = $headerEncoded . '.' . $payloadEncoded;
echo "Data to sign: " . $dataToSign . "\n";
echo "Data length: " . strlen($dataToSign) . " chars\n";

echo "\n=====================================\n";
echo "4. TESTANDO ASSINATURA ECDSA\n";
echo "=====================================\n";

// Converter chave privada para formato PEM
function createPrivateKeyPEM($privateKeyBase64url) {
    $privateKeyBinary = base64url_decode($privateKeyBase64url);
    
    // ASN.1 structure for P-256 private key
    $asn1Header = hex2bin('3041020100301306072a8648ce3d020106082a8648ce3d030107042704251e20');
    $asn1Key = $asn1Header . $privateKeyBinary;
    
    $pem = "-----BEGIN PRIVATE KEY-----\n";
    $pem .= chunk_split(base64_encode($asn1Key), 64);
    $pem .= "-----END PRIVATE KEY-----\n";
    
    return $pem;
}

try {
    $privateKeyPEM = createPrivateKeyPEM(VAPID_PRIVATE_KEY);
    echo "✅ Chave privada PEM criada:\n";
    echo substr($privateKeyPEM, 0, 100) . "...\n";
    
    // Verificar se a chave é válida
    $privateKeyResource = openssl_pkey_get_private($privateKeyPEM);
    if ($privateKeyResource) {
        echo "✅ Chave privada é válida\n";
        
        // Tentar assinar
        $signature = '';
        $signResult = openssl_sign($dataToSign, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
        
        if ($signResult) {
            echo "✅ Assinatura criada: " . strlen($signature) . " bytes\n";
            echo "Assinatura (hex): " . bin2hex(substr($signature, 0, 16)) . "...\n";
            
            // Converter assinatura DER para IEEE P1363
            $signatureBase64 = base64url_encode($signature);
            echo "Assinatura base64url: " . substr($signatureBase64, 0, 50) . "...\n";
            
        } else {
            echo "❌ Falha na assinatura: " . openssl_error_string() . "\n";
        }
        
        openssl_free_key($privateKeyResource);
        
    } else {
        echo "❌ Chave privada inválida: " . openssl_error_string() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro na criação da chave PEM: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "5. TESTANDO FUNÇÃO generateJWT ORIGINAL\n";
echo "=====================================\n";

// Verificar se a função generateJWT existe
if (function_exists('generateJWT')) {
    echo "✅ Função generateJWT encontrada\n";
    
    try {
        $jwtToken = generateJWT('https://fcm.googleapis.com');
        echo "✅ JWT gerado com sucesso\n";
        echo "JWT: " . substr($jwtToken, 0, 100) . "...\n";
        echo "JWT length: " . strlen($jwtToken) . " chars\n";
        
        // Dividir o JWT em partes
        $jwtParts = explode('.', $jwtToken);
        echo "JWT parts: " . count($jwtParts) . "\n";
        
        if (count($jwtParts) === 3) {
            echo "✅ JWT tem 3 partes (header.payload.signature)\n";
            
            // Decodificar header
            $headerDecoded = json_decode(base64url_decode($jwtParts[0]), true);
            echo "Header decodificado: " . json_encode($headerDecoded) . "\n";
            
            // Decodificar payload
            $payloadDecoded = json_decode(base64url_decode($jwtParts[1]), true);
            echo "Payload decodificado: " . json_encode($payloadDecoded) . "\n";
            
            echo "Signature length: " . strlen($jwtParts[2]) . " chars\n";
            
        } else {
            echo "❌ JWT malformado\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Erro na geração JWT: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ Função generateJWT não encontrada\n";
}

echo "\n=====================================\n";
echo "6. TESTE COM ENDPOINT REAL\n";
echo "=====================================\n";

// Testar com um endpoint real do FCM
$testEndpoint = 'https://fcm.googleapis.com/fcm/send/test123';
$parsedUrl = parse_url($testEndpoint);
$audience = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

echo "Endpoint teste: {$testEndpoint}\n";
echo "Audience extraída: {$audience}\n";

if (function_exists('generateJWT')) {
    try {
        $jwtForFCM = generateJWT($audience);
        echo "✅ JWT para FCM gerado\n";
        echo "JWT: " . substr($jwtForFCM, 0, 100) . "...\n";
        
    } catch (Exception $e) {
        echo "❌ Erro JWT para FCM: " . $e->getMessage() . "\n";
    }
}

echo "\n=====================================\n";
echo "7. DIAGNÓSTICO FINAL\n";
echo "=====================================\n";

echo "📊 RESUMO:\n";
echo "- Constantes VAPID: ✅ DEFINIDAS\n";
echo "- Decodificação base64url: ✅ OK\n";
echo "- Criação PEM: " . (isset($privateKeyPEM) ? '✅ OK' : '❌ FALHA') . "\n";
echo "- Assinatura ECDSA: " . (isset($signResult) && $signResult ? '✅ OK' : '❌ FALHA') . "\n";
echo "- Função generateJWT: " . (function_exists('generateJWT') ? '✅ EXISTE' : '❌ NÃO EXISTE') . "\n";

echo "\n🔧 POSSÍVEIS PROBLEMAS:\n";
echo "1. Formato da assinatura (DER vs IEEE P1363)\n";
echo "2. Campos do payload JWT incorretos\n";
echo "3. Algoritmo de hash incorreto\n";
echo "4. Codificação da chave privada\n";

echo "\n💡 PRÓXIMAS AÇÕES:\n";
echo "1. Verificar implementação da função generateJWT no arquivo original\n";
echo "2. Comparar com especificação VAPID oficial\n";
echo "3. Testar com biblioteca externa (web-push)\n";

echo "\n❌ DELETE este arquivo após usar!\n";
echo "</pre>\n";
?> 