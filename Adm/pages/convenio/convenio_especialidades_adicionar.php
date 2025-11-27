<?php
require_once '../../../functions.php';
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["cod_convenio"]) && isset($_POST["cod_profissional"])) {
    try {
        // Verificar se o profissional já está vinculado ao convênio
        $query_verifica = "SELECT COUNT(*) as total FROM sind.convenio_especialidades 
                          WHERE cod_convenio = ? AND cod_profissional = ?";
        $stmt_verifica = $pdo->prepare($query_verifica);
        $stmt_verifica->execute([$_POST["cod_convenio"], $_POST["cod_profissional"]]);
        $resultado = $stmt_verifica->fetch(PDO::FETCH_ASSOC);
        
        if($resultado['total'] > 0) {
            echo 'ja_vinculada';
        } else {
            // Inserir a nova vinculação
            $query_insert = "INSERT INTO sind.convenio_especialidades (cod_convenio, cod_profissional) 
                            VALUES (?, ?)";
            $stmt_insert = $pdo->prepare($query_insert);
            
            if($stmt_insert->execute([$_POST["cod_convenio"], $_POST["cod_profissional"]])) {
                echo 'adicionada';
            } else {
                echo 'erro_adicionar';
            }
        }
        
    } catch(PDOException $e) {
        echo 'erro: ' . $e->getMessage();
    }
}

?> 