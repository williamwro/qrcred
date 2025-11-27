<?php

header('Content-Type: application/json');

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["id"])) {
    try {
        $query = "SELECT id_profissional, nome_profissional, contato_nome1, cel_telefone1, contato_nome2, cel_telefone2 
                  FROM sind.profissionais WHERE id_profissional = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_POST["id"]]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($row);
    } catch(PDOException $e) {
        echo json_encode(["erro" => $e->getMessage()]);
    }
}

?> 