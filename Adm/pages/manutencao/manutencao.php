<?php
require '../../php/banco.php';
$abreviacao_anterior = "";
$abreviacao_mes_corrente = "";
$status_admin = "";
$divisao = 0;
$std = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao = $_POST['divisao'];
$stmt = $pdo->prepare("SELECT abreviacao,id_divisao FROM sind.mes_corrente WHERE id_divisao = ?");
$stmt->execute(array($divisao));
$arr = $stmt->fetchAll();
$abreviacao_mes_corrente = $arr[0]['abreviacao'];

$stmt = $pdo->prepare("SELECT * FROM sind.meses_conta WHERE divisao = ? ORDER BY data");
$stmt->execute(array($divisao));
$arr = $stmt->fetchAll();
if(!$arr) exit();
foreach ($arr as $key => $value) {
    if($value['abreviacao'] === $abreviacao_mes_corrente) {
        // Verificar se existem registros anteriores para evitar índices negativos
        $abreviacao_anterior2 = ($key >= 2) ? $arr[$key-2]['abreviacao'] : null;
        $status_admin2        = ($key >= 2) ? $arr[$key-2]['status_cadastro'] : null;
        $abreviacao_anterior  = ($key >= 1) ? $arr[$key-1]['abreviacao'] : null;
        $status_admin         = ($key >= 1) ? $arr[$key-1]['status_cadastro'] : 1; // Default liberado
        //$abreviacao             = $arr[$key]['abreviacao'];
        //$status_admin           = $arr[$key]['status_cadastro'];
        $divisao                = $arr[$key]['divisao'];

        break;
    }
}
$arr = array('abreviacao_anterior' => $abreviacao_anterior, 'abreviacao_anterior2' => $abreviacao_anterior2, 'status_admin' => $status_admin, 'divisao' => $divisao);
$someArray = array_map(function($value) {
    return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
}, $arr);

echo json_encode($someArray);