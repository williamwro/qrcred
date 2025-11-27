<?php

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["id_profissional"]) && isset($_POST["especialidades"])) {
    try {
        $id_profissional = $_POST["id_profissional"];
        $especialidades = json_decode($_POST["especialidades"], true);
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        // Primeiro, remover todas as especialidades existentes do profissional
        $query_delete = "DELETE FROM sind.profissionais_especialidade WHERE id_profissional = ?";
        $stmt_delete = $pdo->prepare($query_delete);
        $stmt_delete->execute([$id_profissional]);
        
        // Depois, inserir as novas especialidades
        if (!empty($especialidades)) {
            $query_insert = "INSERT INTO sind.profissionais_especialidade (id_profissional, id_especialidade) VALUES (?, ?)";
            $stmt_insert = $pdo->prepare($query_insert);
            
            foreach ($especialidades as $id_especialidade) {
                $stmt_insert->execute([$id_profissional, $id_especialidade]);
            }
        }
        
        // Confirmar transação
        $pdo->commit();
        echo 'sucesso';
        
    } catch(PDOException $e) {
        // Reverter transação em caso de erro
        $pdo->rollback();
        echo "Erro: " . $e->getMessage();
    }
} else {
    echo "Dados incompletos";
}

?> 