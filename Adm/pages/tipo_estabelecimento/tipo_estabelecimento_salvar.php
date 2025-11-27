<?php

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["operation"])) {
    try {
        if($_POST["operation"] == "Add") {
            $query = "INSERT INTO sind.tipo_estabelecimento (nome_tipo) VALUES (?)";
            $stmt = $pdo->prepare($query);
            if($stmt->execute([$_POST["C_nome_tipo"]])) {
                echo 'cadastrado';
            }
        }
        if($_POST["operation"] == "Update") {
            $query = "UPDATE sind.tipo_estabelecimento SET nome_tipo = ? WHERE id_tipo_estabelecimento = ?";
            $stmt = $pdo->prepare($query);
            if($stmt->execute([$_POST["C_nome_tipo"], $_POST["C_id"]])) {
                echo 'atualizado';
            }
        }
    } catch(PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

?> 