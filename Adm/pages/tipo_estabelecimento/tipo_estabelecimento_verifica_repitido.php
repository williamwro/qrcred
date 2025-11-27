<?php

// Database connection
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["C_nome_tipo"])) {
    try {
        $query = "SELECT COUNT(*) as total FROM sind.tipo_estabelecimento WHERE UPPER(nome_tipo) = UPPER(?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_POST["C_nome_tipo"]]);
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