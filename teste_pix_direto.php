<?php
// ARQUIVO DE TESTE - Copie para o servidor e acesse via navegador
// Exemplo: https://sas.makecard.com.br/teste_pix_direto.php
ini_set('display_errors', true);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

include "Adm/php/banco.php";

// Dados do usuário do log
$matricula = '023999';
$id_empregador = '19';
$id_associado = 174;
$id_divisao = 1;

echo "=== TESTE BUSCA PIX ===\n\n";
echo "Parâmetros de busca:\n";
echo "- Matrícula: $matricula\n";
echo "- ID Empregador: $id_empregador\n";
echo "- ID Associado: $id_associado\n";
echo "- ID Divisão: $id_divisao\n\n";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Teste 1: Buscar com todos os critérios
    echo "TESTE 1 - Busca com todos os critérios:\n";
    $sql1 = "SELECT codigo, nome, empregador, id, id_divisao, pix 
             FROM sind.associado 
             WHERE codigo = :matricula 
             AND empregador = :id_empregador 
             AND id = :id_associado 
             AND id_divisao = :id_divisao";
    
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt1->bindParam(':id_empregador', $id_empregador, PDO::PARAM_STR);
    $stmt1->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
    $stmt1->bindParam(':id_divisao', $id_divisao, PDO::PARAM_INT);
    $stmt1->execute();
    
    $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
    if ($result1) {
        echo "✅ Registro encontrado:\n";
        print_r($result1);
        echo "\nPIX: " . ($result1['pix'] ? $result1['pix'] : 'VAZIO ou NULL') . "\n";
    } else {
        echo "❌ Nenhum registro encontrado com todos os critérios\n";
    }
    
    // Teste 2: Buscar apenas por matrícula
    echo "\n\nTESTE 2 - Busca apenas por matrícula:\n";
    $sql2 = "SELECT codigo, nome, empregador, id, id_divisao, pix 
             FROM sind.associado 
             WHERE codigo = :matricula";
    
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt2->execute();
    
    $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "Encontrados " . count($results2) . " registros:\n";
    foreach ($results2 as $idx => $row) {
        echo "\nRegistro " . ($idx + 1) . ":\n";
        echo "- Código: " . $row['codigo'] . "\n";
        echo "- Nome: " . $row['nome'] . "\n";
        echo "- Empregador: " . $row['empregador'] . "\n";
        echo "- ID: " . $row['id'] . "\n";
        echo "- ID Divisão: " . $row['id_divisao'] . "\n";
        echo "- PIX: " . ($row['pix'] ? $row['pix'] : 'VAZIO ou NULL') . "\n";
    }
    
    // Teste 3: Verificar estrutura da tabela
    echo "\n\nTESTE 3 - Estrutura da coluna PIX:\n";
    $sql3 = "SELECT column_name, data_type, character_maximum_length, is_nullable 
             FROM information_schema.columns 
             WHERE table_schema = 'sind' 
             AND table_name = 'associado' 
             AND column_name = 'pix'";
    
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute();
    $column_info = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    if ($column_info) {
        echo "✅ Coluna PIX existe:\n";
        print_r($column_info);
    } else {
        echo "❌ Coluna PIX não encontrada na tabela!\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
} finally {
   
}

echo "\n=== FIM DO TESTE ===\n";
?>
