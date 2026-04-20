<?php
/**
 * Teste Direto do send_push_notification_app.php
 * Inclui o arquivo e verifica se as constantes estão definidas corretamente
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔍 Teste Direto - Send Push Notification</h1>\n";
echo "<pre>\n";

echo "=====================================\n";
echo "1. INCLUINDO send_push_notification_app.php\n";
echo "=====================================\n";

// Verificar se o arquivo existe
$sendFile = 'send_push_notification_app.php';
if (file_exists($sendFile)) {
    echo "✅ Arquivo {$sendFile} encontrado\n";
    
    // Capturar qualquer saída do arquivo
    ob_start();
    try {
        include_once $sendFile;
        $includeOutput = ob_get_contents();
        echo "✅ Arquivo incluído com sucesso\n";
        
        if (!empty($includeOutput)) {
            echo "📋 Saída do arquivo:\n";
            echo $includeOutput;
        }
    } catch (Exception $e) {
        echo "❌ Erro ao incluir arquivo: {$e->getMessage()}\n";
    }
    ob_end_clean();
    
} else {
    echo "❌ Arquivo {$sendFile} NÃO ENCONTRADO\n";
    echo "📁 Arquivos no diretório atual:\n";
    $files = glob('*.php');
    foreach ($files as $file) {
        echo "  - {$file}\n";
    }
}

echo "\n=====================================\n";
echo "2. VERIFICANDO CONSTANTES APÓS INCLUDE\n";
echo "=====================================\n";

// Verificar constantes após include
if (defined('VAPID_PUBLIC_KEY')) {
    echo "✅ VAPID_PUBLIC_KEY definida: " . substr(VAPID_PUBLIC_KEY, 0, 30) . "...\n";
    echo "   Tamanho: " . strlen(VAPID_PUBLIC_KEY) . " chars\n";
} else {
    echo "❌ VAPID_PUBLIC_KEY ainda NÃO DEFINIDA após include\n";
}

if (defined('VAPID_PRIVATE_KEY')) {
    echo "✅ VAPID_PRIVATE_KEY definida: " . substr(VAPID_PRIVATE_KEY, 0, 30) . "...\n";
    echo "   Tamanho: " . strlen(VAPID_PRIVATE_KEY) . " chars\n";
} else {
    echo "❌ VAPID_PRIVATE_KEY ainda NÃO DEFINIDA após include\n";
}

echo "\n=====================================\n";
echo "3. LISTANDO TODAS AS CONSTANTES DEFINIDAS\n";
echo "=====================================\n";

$allConstants = get_defined_constants(true);
$userConstants = $allConstants['user'] ?? [];

echo "Total de constantes definidas pelo usuário: " . count($userConstants) . "\n";

$vapidConstants = [];
foreach ($userConstants as $name => $value) {
    if (stripos($name, 'VAPID') !== false) {
        $vapidConstants[$name] = $value;
    }
}

if (!empty($vapidConstants)) {
    echo "\n🔑 Constantes VAPID encontradas:\n";
    foreach ($vapidConstants as $name => $value) {
        $displayValue = is_string($value) ? substr($value, 0, 30) . '...' : $value;
        echo "  - {$name}: {$displayValue}\n";
    }
} else {
    echo "\n❌ Nenhuma constante VAPID encontrada\n";
}

echo "\n=====================================\n";
echo "4. TESTE MANUAL DE DEFINIÇÃO\n";
echo "=====================================\n";

// Definir as constantes manualmente e testar
if (!defined('MANUAL_VAPID_PUBLIC_KEY')) {
    define('MANUAL_VAPID_PUBLIC_KEY', 'BM7z6QhdLZUACWiMZvwVb6JL2Qtvr2zFOOFqqi5E5yhFeZWj2k1YewWgAxXidqbGmcznD5LcfRComGe8h6TOAHM');
    define('MANUAL_VAPID_PRIVATE_KEY', 'MSA8Clt7h_bbUhLq9Sbh6zPjXCzwZvecNHCqexeJPu8');
    echo "✅ Constantes manuais definidas com sucesso\n";
}

echo "🔑 Testando constantes manuais:\n";
echo "  Pública: " . substr(MANUAL_VAPID_PUBLIC_KEY, 0, 30) . "...\n";
echo "  Privada: " . substr(MANUAL_VAPID_PRIVATE_KEY, 0, 30) . "...\n";

echo "\n=====================================\n";
echo "5. SIMULANDO ENVIO DE NOTIFICAÇÃO\n";
echo "=====================================\n";

// Simular dados de teste
$testData = [
    'user_card' => '8029774802',
    'titulo' => 'Teste Direto',
    'mensagem' => 'Teste com constantes manuais',
    'tipo_notificacao' => 'teste_direto'
];

echo "📦 Dados de teste preparados:\n";
echo "  User: {$testData['user_card']}\n";
echo "  Título: {$testData['titulo']}\n";

// Testar se conseguimos fazer uma requisição básica
$url = 'https://sas.makecard.com.br/send_push_notification_app.php';
$postData = json_encode($testData);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 10
]);

echo "\n🚀 Enviando requisição de teste...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📥 Resposta HTTP: {$httpCode}\n";
if ($error) {
    echo "❌ Erro cURL: {$error}\n";
} else {
    echo "📋 Resposta: " . substr($response, 0, 200) . "...\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && isset($responseData['results']['details'][0]['http_code'])) {
        $detailCode = $responseData['results']['details'][0]['http_code'];
        echo "\n🔍 Código específico: {$detailCode}\n";
        
        if ($detailCode == 403) {
            echo "❌ Ainda HTTP 403 - problema com VAPID persiste\n";
        } else {
            echo "✅ Código diferente de 403 - progresso!\n";
        }
    }
}

echo "\n=====================================\n";
echo "6. DIAGNÓSTICO FINAL\n";
echo "=====================================\n";

echo "📊 RESUMO:\n";
echo "- Arquivo send_push_notification_app.php: " . (file_exists($sendFile) ? 'EXISTE' : 'NÃO EXISTE') . "\n";
echo "- Constantes VAPID após include: " . (defined('VAPID_PRIVATE_KEY') ? 'DEFINIDAS' : 'NÃO DEFINIDAS') . "\n";
echo "- Constantes manuais: FUNCIONAM\n";

echo "\n💡 PRÓXIMAS AÇÕES:\n";
if (!defined('VAPID_PRIVATE_KEY')) {
    echo "1. ❌ As constantes não estão sendo definidas corretamente\n";
    echo "2. 🔧 Verifique se as linhas define() estão no início do arquivo PHP\n";
    echo "3. 🔧 Verifique se não há erros de sintaxe no arquivo\n";
    echo "4. 🔧 Considere mover as definições para um arquivo separado\n";
} else {
    echo "✅ Constantes definidas corretamente!\n";
}

echo "\n❌ DELETE este arquivo após usar!\n";
echo "</pre>\n";
?> 