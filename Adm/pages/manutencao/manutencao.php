<?php
require '../../php/banco.php';
$abreviacao_anterior = "";
$abreviacao_mes_corrente = "";
$status_admin = "";
$std = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao = $_POST['divisao'];
$stmt = $pdo->prepare("SELECT abreviacao,id_divisao FROM sind.mes_corrente WHERE id_divisao = ?");
$stmt->execute(array($divisao));
$arr = $stmt->fetchAll();
$abreviacao_mes_corrente = $arr[0]['abreviacao'];

$stmt = $pdo->prepare("SELECT * FROM sind.meses_conta ORDER BY data");
$stmt->execute();
$arr = $stmt->fetchAll();
if(!$arr) exit();
foreach ($arr as $key => $value) {
    if($value['abreviacao'] === $abreviacao_mes_corrente) {
        $abreviacao_anterior2 = $arr[$key-2]['abreviacao'];
        $status_admin2        = $arr[$key-2]['status_cadastro'];
        $abreviacao_anterior  = $arr[$key-1]['abreviacao'];
        $status_admin         = $arr[$key-1]['status_cadastro'];
        break;
    }
}
$arr = array('abreviacao_anterior' => $abreviacao_anterior, 'abreviacao_anterior2' => $abreviacao_anterior2,'status_admin' => $status_admin,'status_admin2' => $status_admin2);
$someArray = array_map("utf8_encode",$arr);

echo json_encode($someArray);