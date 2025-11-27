<?php

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["C_nome_profissional"])) {
    try {
        $nome_profissional = trim($_POST["C_nome_profissional"]);
        

        
        if($_POST["operation"] == "Add") {
            // Para inserção, verifica se já existe
            $query = "SELECT COUNT(*) as total FROM sind.profissionais WHERE UPPER(TRIM(nome_profissional)) = UPPER(?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$nome_profissional]);
        } else {
            // Para atualização, buscar todos os registros com o mesmo nome e filtrar manualmente
            $id_profissional = intval($_POST["C_idx"]);
            
            $query = "SELECT id_profissional FROM sind.profissionais WHERE UPPER(TRIM(nome_profissional)) = UPPER(?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$nome_profissional]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar quantos registros têm o mesmo nome, exceto o atual
            $total = 0;
            
            foreach ($records as $record) {
                $record_id = intval($record['id_profissional']);
                if ($record_id !== $id_profissional) {
                    $total++;
                }
            }
            
            // Criar um resultado fake para manter a estrutura
            $row = array('total' => $total);
            $stmt = null; // Não executar fetch depois
        }
        
        // Só fazer fetch se não for UPDATE (pois já processamos manualmente)
        if($_POST["operation"] == "Add") {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        // Para UPDATE, $row já foi definido acima
        

        
        if($row['total'] > 0) {
            echo 'Sim'; // Já existe
        } else {
            echo 'Nao'; // Não existe
        }
        
    } catch(PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

?> 