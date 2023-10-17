<?php
header("Content-type: application/json");
require '../../php/banco.php';
date_default_timezone_set('America/Sao_Paulo');
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
include "NumeroPorExtenso.php";
$extenso = new NumeroPorExtenso;
$mes = $_POST['mes'];
$total = 0;
$sql = "DELETE FROM sind.taxas_cheque";
$stmt = $pdo->prepare($sql);
$stmt->execute();

// LISTA AS TAXAS
$sql = "SELECT categoria,sum(val_alicota) as total
          FROM sind.pagamentos2 
         WHERE mes = '" . $mes ."'
      GROUP BY categoria";
$statment = $pdo->prepare($sql);
$statment->execute();
$result = $statment->fetchAll();
foreach ($result as $row) {

    $sql2 = "INSERT INTO sind.taxas_cheque(valor,mes,extenso)
             VALUES(:valor,:mes,:extenso)";
    $stmt = $pdo->prepare($sql2);
    $total = 0;
    $total = number_format($row['total'],2,'.', '');
    $extenso_ = $extenso->converter($total);
    $stmt->bindParam(':valor',$total,PDO::PARAM_STR);
    $stmt->bindParam(':mes',$mes,PDO::PARAM_STR);
    $stmt->bindParam(':extenso',$extenso_,PDO::PARAM_STR);

    $stmt->execute();

}