<?PHP
// Script de teste para verificar se o campo reprovado funciona

require "../../php/banco.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== TESTE DO CAMPO REPROVADO ===\n\n";
    
    // 1. Verificar se a tabela existe e listar algumas colunas
    echo "1. Verificando estrutura da tabela:\n";
    $query = "SELECT column_name, data_type, is_nullable 
              FROM information_schema.columns 
              WHERE table_schema = 'sind' 
              AND table_name = 'associados_sasmais' 
              AND column_name IN ('id', 'reprovado', 'valor_aprovado')
              ORDER BY column_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $colunas = $stmt->fetchAll();
    
    foreach ($colunas as $col) {
        echo "  - {$col['column_name']}: {$col['data_type']} (nullable: {$col['is_nullable']})\n";
    }
    
    // 2. Verificar se há registros na tabela
    echo "\n2. Verificando registros na tabela:\n";
    $query = "SELECT COUNT(*) as total FROM sind.associados_sasmais";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $total = $stmt->fetchColumn();
    echo "  Total de registros: $total\n";
    
    if ($total > 0) {
        // 3. Buscar um registro para teste
        echo "\n3. Buscando registro para teste:\n";
        $query = "SELECT id, nome, reprovado, valor_aprovado 
                  FROM sind.associados_sasmais 
                  WHERE has_signed = true 
                  ORDER BY id DESC 
                  LIMIT 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $registro = $stmt->fetch();
        
        if ($registro) {
            echo "  ID: {$registro['id']}\n";
            echo "  Nome: {$registro['nome']}\n";
            echo "  Reprovado atual: " . var_export($registro['reprovado'], true) . "\n";
            echo "  Valor aprovado: {$registro['valor_aprovado']}\n";
            
            // 4. Testar UPDATE simples
            echo "\n4. Testando UPDATE do campo reprovado:\n";
            $id_teste = $registro['id'];
            
            // Primeiro, definir como true
            $query = "UPDATE sind.associados_sasmais SET reprovado = true WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id', $id_teste, PDO::PARAM_INT);
            $stmt->execute();
            $linhas_afetadas = $stmt->rowCount();
            echo "  UPDATE para true - Linhas afetadas: $linhas_afetadas\n";
            
            // Verificar se funcionou
            $query = "SELECT reprovado FROM sind.associados_sasmais WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id', $id_teste, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetchColumn();
            echo "  Valor após UPDATE: " . var_export($resultado, true) . "\n";
            
            // Agora voltar para false
            $query = "UPDATE sind.associados_sasmais SET reprovado = false WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id', $id_teste, PDO::PARAM_INT);
            $stmt->execute();
            $linhas_afetadas = $stmt->rowCount();
            echo "  UPDATE para false - Linhas afetadas: $linhas_afetadas\n";
            
            // Verificar se funcionou
            $query = "SELECT reprovado FROM sind.associados_sasmais WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id', $id_teste, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetchColumn();
            echo "  Valor após segundo UPDATE: " . var_export($resultado, true) . "\n";
            
        } else {
            echo "  Nenhum registro encontrado para teste\n";
        }
    }
    
    echo "\n=== TESTE CONCLUÍDO ===\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
}
?> 