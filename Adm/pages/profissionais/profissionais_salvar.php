<?php

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["operation"])) {
    try {
        if($_POST["operation"] == "Add") {
            $query = "INSERT INTO sind.profissionais (nome_profissional, contato_nome1, cel_telefone1, contato_nome2, cel_telefone2) VALUES (?, ?, ?, ?, ?) RETURNING id_profissional";
            $stmt = $pdo->prepare($query);
            if($stmt->execute([$_POST["C_nome_profissional"], $_POST["C_contato_nome1"], $_POST["C_cel_telefone1"], $_POST["C_contato_nome2"], $_POST["C_cel_telefone2"]])) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo 'cadastrado|' . $row['id_profissional'];
            }
        }
        if($_POST["operation"] == "Update") {
            $query = "UPDATE sind.profissionais SET nome_profissional = ?, contato_nome1 = ?, cel_telefone1 = ?, contato_nome2 = ?, cel_telefone2 = ? WHERE id_profissional = ?";
            $stmt = $pdo->prepare($query);
            if($stmt->execute([$_POST["C_nome_profissional"], $_POST["C_contato_nome1"], $_POST["C_cel_telefone1"], $_POST["C_contato_nome2"], $_POST["C_cel_telefone2"], $_POST["C_idx"]])) {
                echo 'atualizado|' . $_POST["C_idx"];
            }
        }
    } catch(PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

?> 