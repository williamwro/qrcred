<?php
require_once '../../../functions.php';

header('Content-Type: application/json');

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["id"])) {
    try {
        $query = "SELECT id_especialidade, nome_especialidade, id_tipo_especialidade FROM sind.especialidade WHERE id_especialidade = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_POST["id"]]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($row);
    } catch(PDOException $e) {
        echo json_encode(["erro" => $e->getMessage()]);
    }
}

?> 