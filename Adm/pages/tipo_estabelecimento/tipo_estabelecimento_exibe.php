<?php

header('Content-Type: application/json');

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["id"])) {
    try {
        $query = "SELECT id_tipo_estabelecimento, nome_tipo FROM sind.tipo_estabelecimento WHERE id_tipo_estabelecimento = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_POST["id"]]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($row);
    } catch(PDOException $e) {
        echo json_encode(["erro" => $e->getMessage()]);
    }
}

?> 