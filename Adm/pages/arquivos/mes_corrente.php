<?php
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$origem = $_POST['origem'];
$someArray = array();
$row = $pdo->query( "SELECT abreviacao FROM sind.mes_corrente" )->fetch();
$someArray["mes_corrente"] = $row["abreviacao"];
echo json_encode($someArray);