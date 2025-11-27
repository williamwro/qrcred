<?php
require_once '../../../functions.php';
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["id"])) {
    try {
        $query = "DELETE FROM sind.convenio_especialidades WHERE id = ?";
        $stmt = $pdo->prepare($query);
        
        if($stmt->execute([$_POST["id"]])) {
            echo 'removida';
        } else {
            echo 'erro_remover';
        }
        
    } catch(PDOException $e) {
        echo 'erro: ' . $e->getMessage();
    }
}

?> 