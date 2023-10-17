<?php
header("Content-type: application/json");
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dados_arquivo = array();
$caminho = $_POST['caminho'];
$someArray = array();
$file = $caminho;
$i = 0;
if (!file_exists($file))
    echo "arquivo nao existe";
else{
    $fp = fopen($file, "r");
    while (!feof($fp)){
        $current_line = fgets ($fp);
        $vetor = explode(',',$current_line);
        $i = $i + 1;
        if($i === 1598){
           $xx =  $vetor[4];
        }
        if($vetor[0] !== "" && $vetor[0] !== "\r\n"){
                $valor = floatval($vetor[4]) / 100;
                $sub_array = array();
                $sub_array["associado"] = $vetor[0];
                $sub_array["nome"] = trim($vetor[1]);
                $sub_array["tipo"] = $vetor[2];
                $sub_array["total"] = $valor;

                $someArray[] = $sub_array;
        }
    }
    fclose($fp);
    $result = json_encode($someArray);
    echo $result;
}