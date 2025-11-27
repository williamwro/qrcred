<?php

include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["id_profissional"])) {
    try {
        $pdo->beginTransaction();
        
        // Primeiro deletar as especialidades do profissional
        $query1 = "DELETE FROM sind.profissionais_especialidade WHERE id_profissional = ?";
        $stmt1 = $pdo->prepare($query1);
        $stmt1->execute([$_POST["id_profissional"]]);
        
        // Depois deletar o profissional
        $query2 = "DELETE FROM sind.profissionais WHERE id_profissional = ?";
        $stmt2 = $pdo->prepare($query2);
        $stmt2->execute([$_POST["id_profissional"]]);
        
        $pdo->commit();
        echo "deletado";
        
    } catch(PDOException $e) {
        $pdo->rollback();
        echo "erro: " . $e->getMessage();
    }
}

?> 