<?php
require '../../php/banco.php';
date_default_timezone_set('America/Sao_Paulo');
$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$numero = "";
$numero = mt_rand(0, 9999999999);