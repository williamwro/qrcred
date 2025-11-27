<?php

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["C_nome_tipo"])) {
    try {
        // Verificar se é uma operação de update
        if(isset($_POST["operation"]) && $_POST["operation"] == "Update" && isset($_POST["C_idx"])) {
            // Para update, excluir o próprio registro da verificação
            $query = "SELECT COUNT(*) as total FROM sind.tipo_especialidade WHERE UPPER(nome_tipo) = UPPER(?) AND id_tipo_especialidade != ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$_POST["C_nome_tipo"], $_POST["C_idx"]]);
        } else {
            // Para insert, verificar normalmente
            $query = "SELECT COUNT(*) as total FROM sind.tipo_especialidade WHERE UPPER(nome_tipo) = UPPER(?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$_POST["C_nome_tipo"]]);
        }
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row['total'] > 0) {
            echo 'repitido';
        } else {
            echo 'nao repitido';
        }
    } catch(PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

?> 