<?php
/**
 * Teste Manual do Webhook ZapSign
 * Simula uma requisição da ZapSign para testar o webhook
 */

// Dados de teste simulando uma requisição real da ZapSign
$dadosTeste = [
    "event_type" => "doc_created",
    "token" => "teste-" . uniqid(),
    "name" => "TERMO DE ADESÃO DO CARTÃO CONVÊNIO",
    "signed_at" => date('c'),
    "signers" => [
        [
            "name" => "Teste Usuario",
            "email" => "teste@exemplo.com",
            "cpf" => "12345678901",
            "status" => "pending",
            "signed_at" => date('c'),
            "token" => "signer-" . uniqid(),
            "phone_number" => "11999999999"
        ]
    ]
];

echo "<h1>🧪 Teste Manual do Webhook ZapSign</h1>";
echo "<h2>📋 Dados que serão enviados:</h2>";
echo "<pre>" . json_encode($dadosTeste, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

// Enviar requisição POST para o webhook
$url = 'http://localhost/qrcred/webhook_zapsign.php';
$json = json_encode($dadosTeste);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($json)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "<h2>🚀 Enviando requisição para o webhook...</h2>";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<h2>📊 Resultado:</h2>";
echo "<p><strong>Código HTTP:</strong> $httpCode</p>";

if ($curlError) {
    echo "<p style='color: red;'><strong>Erro cURL:</strong> $curlError</p>";
} else {
    echo "<p style='color: green;'><strong>Requisição enviada com sucesso!</strong></p>";
}

echo "<h3>📄 Resposta do webhook:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h2>🔍 Verificar Logs</h2>";
echo "<p><a href='webhook_zapsign.php?debug=1' target='_blank'>🔗 Abrir Debug do Webhook</a></p>";
echo "<p><a href='webhook_zapsign.php?status' target='_blank'>🔗 Status do Webhook</a></p>";

// Verificar se o registro foi criado na tabela
try {
    include "Adm/php/banco.php";
    $pdo = Banco::conectar_postgres();
    
    $stmt = $pdo->prepare("
        SELECT id, codigo, nome, celular, data_hora, event, doc_token, doc_name, cpf, has_signed 
        FROM sind.associados_sasmais 
        WHERE doc_token LIKE 'teste-%' 
        ORDER BY data_hora DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>🗄️ Registros de Teste na Tabela:</h2>";
    if ($registros) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Código</th><th>Nome</th><th>Celular</th><th>Data/Hora</th><th>Event</th><th>Doc Token</th><th>CPF</th><th>Assinado</th></tr>";
        foreach ($registros as $reg) {
            echo "<tr>";
            echo "<td>{$reg['id']}</td>";
            echo "<td>{$reg['codigo']}</td>";
            echo "<td>{$reg['nome']}</td>";
            echo "<td>{$reg['celular']}</td>";
            echo "<td>{$reg['data_hora']}</td>";
            echo "<td>{$reg['event']}</td>";
            echo "<td>" . substr($reg['doc_token'], 0, 20) . "...</td>";
            echo "<td>{$reg['cpf']}</td>";
            echo "<td>" . ($reg['has_signed'] ? 'Sim' : 'Não') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhum registro de teste encontrado na tabela.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao consultar banco: " . $e->getMessage() . "</p>";
}

echo "<h2>🧹 Limpeza</h2>";
echo "<p><a href='?limpar=1'>🗑️ Limpar registros de teste</a></p>";

// Limpar registros de teste se solicitado
if (isset($_GET['limpar'])) {
    try {
        $pdo = Banco::conectar_postgres();
        $stmt = $pdo->prepare("DELETE FROM sind.associados_sasmais WHERE doc_token LIKE 'teste-%'");
        $stmt->execute();
        $deletados = $stmt->rowCount();
        echo "<p style='color: green;'>✅ $deletados registros de teste removidos!</p>";
        echo "<script>setTimeout(() => window.location.href = window.location.pathname, 2000);</script>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro ao limpar: " . $e->getMessage() . "</p>";
    }
}
?>
