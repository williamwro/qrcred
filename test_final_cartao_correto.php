<?php
/**
 * Teste Final - Cartão Correto
 * Testa JOIN triplo para pegar número do cartão da tabela sind.c_cartaoassociado
 */

require_once 'Adm/php/banco.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste Final - Cartão Correto</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .code { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; font-size: 12px; }
        .button { background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .button:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<div class='container'>
<h1>🎯 Teste Final - Número do Cartão Correto</h1>";

echo "<div class='box info'>
<h2>🔧 CORREÇÃO FINAL APLICADA</h2>
<strong>Tabela correta:</strong> sind.c_cartaoassociado<br>
<strong>Campo correto:</strong> cod_verificacao<br>
<strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "
</div>";

try {
    /** @noinspection PhpUndefinedClassInspection */
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. TESTAR A ESTRUTURA DAS TABELAS
    echo "<h2>🗄️ 1. VERIFICAR ESTRUTURA DAS TABELAS</h2>";
    echo "<div class='box'>";
    
    // Verificar tabela c_cartaoassociado
    echo "<strong>Tabela sind.c_cartaoassociado:</strong><br>";
    try {
        $stmt = $pdo->query("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_schema = 'sind' AND table_name = 'c_cartaoassociado' 
            ORDER BY ordinal_position
        ");
        $cartaoColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($cartaoColumns)) {
            echo "<div class='error'>❌ Tabela sind.c_cartaoassociado não encontrada!</div>";
        } else {
            echo "✅ " . implode(', ', $cartaoColumns) . "<br>";
            
            // Verificar campos específicos
            $requiredFields = ['cod_verificacao', 'cod_associado', 'empregador', 'cod_situacaocartao'];
            foreach ($requiredFields as $field) {
                $exists = in_array($field, $cartaoColumns) ? '✅' : '❌';
                echo "{$exists} {$field}<br>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
    
    // 2. TESTAR JOIN TRIPLO
    echo "<h2>🔍 2. TESTE DO JOIN TRIPLO</h2>";
    echo "<div class='box'>";
    
    $sqlTest = "
        SELECT 
            a.id, 
            a.cod_associado, 
            a.id_empregador, 
            a.data_agendada, 
            a.profissional, 
            a.notification_sent_confirmado,
            s.nome as nome_associado,
            c.cod_verificacao as numero_cartao,
            c.cod_situacaocartao
        FROM sind.agendamento a
        INNER JOIN sind.associado s ON (
            a.cod_associado = s.codigo 
            AND a.id_empregador = s.empregador
        )
        INNER JOIN sind.c_cartaoassociado c ON (
            s.codigo = c.cod_associado 
            AND s.empregador = c.empregador
        )
        WHERE a.data_agendada IS NOT NULL
        ORDER BY a.id DESC 
        LIMIT 10
    ";
    
    try {
        $stmt = $pdo->prepare($sqlTest);
        $stmt->execute();
        $testResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='success'>✅ JOIN TRIPLO executado com sucesso!</div>";
        echo "<strong>Resultados do teste:</strong><br>";
        
        if (empty($testResults)) {
            echo "<div class='warning'>⚠️ Nenhum agendamento encontrado com JOIN triplo</div>";
            echo "<div class='info'>💡 Isso pode indicar problema na estrutura das tabelas ou dados</div>";
        } else {
            echo "<table>";
            echo "<tr>
                    <th>ID</th>
                    <th>Cod Associado</th>
                    <th>Empregador</th>
                    <th>Número Cartão</th>
                    <th>Nome</th>
                    <th>Status Cartão</th>
                    <th>Notificado</th>
                  </tr>";
            
            foreach ($testResults as $row) {
                $notificado = $row['notification_sent_confirmado'] ? '✅' : '❌';
                $statusCartao = $row['cod_situacaocartao'] == 1 ? '✅ Ativo' : '❌ Inativo';
                
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['cod_associado']}</td>";
                echo "<td>{$row['id_empregador']}</td>";
                echo "<td><strong>{$row['numero_cartao']}</strong></td>";
                echo "<td>{$row['nome_associado']}</td>";
                echo "<td>{$statusCartao}</td>";
                echo "<td>{$notificado}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<div class='success'>🎯 <strong>SUCESSO!</strong> Agora temos o número do cartão correto!</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro no JOIN triplo: " . $e->getMessage() . "</div>";
        echo "<div class='warning'>⚠️ Possíveis problemas:</div>";
        echo "1. Campos de ligação inexistentes<br>";
        echo "2. Dados inconsistentes entre tabelas<br>";
        echo "3. Estrutura de tabelas diferente do esperado<br>";
    }
    
    echo "</div>";
    
    // 3. COMPARAÇÃO EVOLUTION
    echo "<h2>📊 3. EVOLUÇÃO DA CORREÇÃO</h2>";
    echo "<div class='box'>";
    
    echo "<strong>❌ VERSÃO 1 (Incorreta):</strong><br>";
    echo "<div class='code'>user_card = agendamento.cod_associado  // ← ERRADO: código interno</div>";
    
    echo "<strong>⚠️ VERSÃO 2 (Quase correta):</strong><br>";
    echo "<div class='code'>user_card = associado.cod_cart        // ← ERRADO: campo inexistente</div>";
    
    echo "<strong>✅ VERSÃO 3 (CORRETA):</strong><br>";
    echo "<div class='code'>user_card = c_cartaoassociado.cod_verificacao  // ← CORRETO!</div>";
    
    echo "<strong>Query final:</strong><br>";
    echo "<div class='code'>SELECT a.*, s.nome, c.cod_verificacao as numero_cartao
FROM sind.agendamento a
INNER JOIN sind.associado s ON (
    a.cod_associado = s.codigo 
    AND a.id_empregador = s.empregador
)
INNER JOIN sind.c_cartaoassociado c ON (
    s.codigo = c.cod_associado 
    AND s.empregador = c.empregador
)</div>";
    
    echo "</div>";
    
    // 4. TESTE DO SISTEMA FINAL
    echo "<h2>🚀 4. TESTAR SISTEMA FINAL</h2>";
    echo "<div class='box'>";
    
    echo "<a href='check_agendamentos_notifications_final.php' class='button' target='_blank'>
          🔄 Executar Sistema Final
          </a><br><br>";
    
    // Chamar o script final
    $url = 'https://sas.makecard.com.br/check_agendamentos_notifications_final.php';
    $result = file_get_contents($url);
    
    if ($result !== false) {
        $response = json_decode($result, true);
        echo "<strong>Resultado da execução final:</strong><br>";
        echo "<div class='code'>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</div>";
        
        if ($response['success']) {
            echo "<div class='success'>✅ Sistema final executado com sucesso!</div>";
            
            if (isset($response['results']['details'])) {
                echo "<strong>📋 Detalhes dos processamentos:</strong><br>";
                echo "<table>";
                echo "<tr><th>Agendamento</th><th>Cod Associado</th><th>Cartão Final</th><th>Nome</th><th>Status</th></tr>";
                
                foreach ($response['results']['details'] as $detail) {
                    $status = $detail['success'] ? '✅ Enviado' : '❌ Erro';
                    echo "<tr>";
                    echo "<td>{$detail['agendamento_id']}</td>";
                    echo "<td>{$detail['cod_associado']}</td>";
                    echo "<td><strong>{$detail['user_card']}</strong></td>";
                    echo "<td>{$detail['nome_associado']}</td>";
                    echo "<td>{$status}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            if ($response['results']['notifications_sent'] > 0) {
                echo "<div class='success'>📱 {$response['results']['notifications_sent']} notificações enviadas com números de cartão CORRETOS!</div>";
            }
            
        } else {
            echo "<div class='error'>❌ Erro na execução: {$response['message']}</div>";
        }
    } else {
        echo "<div class='error'>❌ Erro ao chamar o script final</div>";
    }
    
    echo "</div>";
    
    // 5. INSTRUÇÕES FINAIS
    echo "<h2>🎯 5. INSTRUÇÕES FINAIS</h2>";
    echo "<div class='box'>";
    echo "<strong>Se tudo funcionou corretamente:</strong><br>";
    echo "1. ✅ Substitua o endpoint Next.js para usar versão final<br>";
    echo "2. ✅ Configure cron job com script final<br>";
    echo "3. ✅ Delete arquivos de teste<br>";
    echo "4. ✅ Sistema pronto para produção<br>";
    echo "<br>";
    echo "<strong>Comandos para produção:</strong><br>";
    echo "<div class='code'>";
    echo "# No servidor:\n";
    echo "mv check_agendamentos_notifications.php check_agendamentos_notifications_backup.php\n";
    echo "mv check_agendamentos_notifications_final.php check_agendamentos_notifications.php\n";
    echo "\n# No Next.js:\n";
    echo "// Atualizar route.ts para usar:\n";
    echo "// 'check_agendamentos_notifications.php' (que agora é a versão final)\n";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box error'>";
    echo "<h2>❌ ERRO</h2>";
    echo "Erro: " . $e->getMessage();
    echo "</div>";
}

echo "<div class='box warning'>";
echo "<h3>🗑️ LIMPEZA</h3>";
echo "❌ <strong>DELETE este arquivo após confirmar que funciona!</strong><br>";
echo "📁 Arquivo: test_final_cartao_correto.php<br>";
echo "🕒 Criado em: " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";
?> 