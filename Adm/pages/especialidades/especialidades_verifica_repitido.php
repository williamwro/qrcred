<?php
require_once '../../../functions.php';

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["C_nome_especialidade"])) {
    try {
        // Verificar se é uma operação de update
        if(isset($_POST["operation"]) && $_POST["operation"] == "Update" && isset($_POST["C_idx"])) {
            // Para update, excluir o próprio registro da verificação
            $query = "SELECT * FROM sind.especialidade WHERE nome_especialidade = ? AND id_especialidade != ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$_POST["C_nome_especialidade"], $_POST["C_idx"]]);
        } else {
            // Para insert, verificar normalmente
            $query = "SELECT * FROM sind.especialidade WHERE nome_especialidade = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$_POST["C_nome_especialidade"]]);
        }
        
        if($stmt->rowCount() > 0) {
                echo 'repitido';
        } else {
            echo 'nao repitido';
        }
    } catch(PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

?>