<?php
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$origem = $_POST['origem'];
$mes = $_POST['mes'];
$empregador = $_POST['empregador'];
function getFileNames($directoryPath) {
    $fileNames = array();
    $contents = scandir($directoryPath,1);

    foreach($contents as $content) {
        if(is_file($directoryPath . DIRECTORY_SEPARATOR . $content)) {
            array_push($fileNames, $content);
        }
    }
    return $fileNames;
}
$someArray = array();
if(!$mes) {
    $row = $pdo->query("SELECT abreviacao FROM sind.mes_corrente")->fetch();
    $mescorrente = $row["abreviacao"];
}else{
    $mescorrente = $mes;
}
$mescorrente = str_replace('/','-',$mescorrente);
//$origem = 'C:\xampp\htdocs\sind\Adm\pages\arquivos\\'.str_replace('/','\\',$_POST['origem'].$mescorrente.'\\'.$empregador);
$origem = '/home/makecard/public_html/sind/Adm/pages/arquivos/'.$_POST['origem'].$mescorrente.'/'.$empregador;
$arquivo = getFileNames($origem);
if ($arquivo){
    $origem = $origem ."/". $arquivo[0];
    $someArray[0] = $origem;
    $size_arq = filesize($origem);
    array_push($someArray,$size_arq);
    array_push($someArray,'/Adm/pages/arquivos/'.$_POST['origem'].$mescorrente.'/'.$empregador.'/'.$arquivo[0]);
    echo json_encode($someArray);
}else{
    $arr = array('result' =>"nao achou");
    echo json_encode($arr);
}