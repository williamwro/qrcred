<?php
$divisao_nome = $_POST['divisao_nome'];
$mescorrente = $_POST['mes'];
$empregador = $_POST['empregador'];
$mescorrente = str_replace('/','-',$mescorrente);
$pasta = 'uploads/'.$divisao_nome.'/'.$mescorrente.'/'.$empregador;
if (!is_dir($pasta)) {
    mkdir($pasta, 0777, true);
}
$arr = array('pasta' =>$pasta);
echo json_encode($arr);