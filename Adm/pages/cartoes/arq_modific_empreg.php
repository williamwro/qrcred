<?php
// atualiza o campo empregador
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$query = "SELECT * FROM ASSOCIADO order by codigo";
$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();

foreach ($result as $row) {

    $sql = "UPDATE C_CARTAOASSOCIADO SET empregador = ". $row["Empregador"]." WHERE cod_associado ='".$row['Codigo']."'";
    $stmt = $database->prepare($sql);
    $stmt->execute();

}
