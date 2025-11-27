<?php
require_once '../../../functions.php';

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["operation"])) {
    try {
        if($_POST["operation"] == "Add") {
            $query = "INSERT INTO sind.especialidade (nome_especialidade, id_tipo_especialidade) VALUES (?, ?)";
            $stmt = $pdo->prepare($query);
            if($stmt->execute([$_POST["C_nome_especialidade"], $_POST["C_tipo"]])) {
                echo 'cadastrado';
            }
        }
        if($_POST["operation"] == "Update") {
            $query = "UPDATE sind.especialidade SET nome_especialidade = ?, id_tipo_especialidade = ? WHERE id_especialidade = ?";
            $stmt = $pdo->prepare($query);
            if($stmt->execute([$_POST["C_nome_especialidade"], $_POST["C_tipo"], $_POST["C_idx"]])) {
                echo 'atualizado';
            }
        }
    } catch(PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

?>