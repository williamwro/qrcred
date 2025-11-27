<?php

include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["id_profissional"])) {
    try {
        $query = "SELECT id, cod_convenio, cod_profissional 
                  FROM sind.convenio_especialidades 
                  WHERE cod_profissional = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_POST["id_profissional"]]);
        
        $count = $stmt->rowCount();
        
        if ($count > 0) {
            echo "vinculado";
        } else {
            echo "nao_vinculado";
        }
        
    } catch(PDOException $e) {
        echo "erro: " . $e->getMessage();
    }
}

?> 