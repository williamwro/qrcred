<?php
include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$std = new stdClass();
$codigo = "";
$sql = $pdo->query("SELECT max(codigo) as ultimo_codigo FROM sind.convenio");
while($row = $sql->fetch()) {
    $codigo = $row['ultimo_codigo'];
    $std->codigo = $codigo + 1;
}
echo json_encode($std);
