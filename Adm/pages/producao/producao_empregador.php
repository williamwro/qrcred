<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 05/09/2018
 * Time: 10:18
 */
    header("Content-type: application/json");
    include "../../php/banco.php";
    include "../../php/funcoes.php";
    $divisao = $_GET['divisao'];
    $someArray = array();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $i=1;
    $sql = $pdo->query("SELECT * FROM sind.empregador WHERE divisao = " .$divisao. " ORDER BY nome");
    while($row = $sql->fetch()) {
        $someArray[$i] = array_map("utf8_encode",$row);
        $i++;
    }
    echo json_encode($someArray);