<?php
header("Content-type: application/json");
require '../../php/banco.php';
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg='';
if (isset($_POST['lancamento'])){
    if($_POST['lancamento'] != "") {

        $stmt = new stdClass();
        $_lancamento = (int)$_POST['lancamento'];
        $_valor = str_replace('.', '', $_POST['valor_alterado']);
        $_valor = str_replace(',', '.', $_valor);


        $sql = "UPDATE sind.conta SET valor = :valor WHERE lancamento = :lancamento";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':lancamento', $_lancamento, PDO::PARAM_INT);
        $stmt->bindParam(':valor', $_valor, PDO::PARAM_STR);

        $stmt->execute();

        $msg = 'alterado';

        $arr = array('lancamento' => $_lancamento, 'Resultado' => $msg);
        $someArray = array_map("utf8_encode", $arr);
    }else{
        $msg = 'nao alterado';
        $arr = array('lancamento' =>'','Resultado'=>$msg);
        $someArray = array_map("utf8_encode",$arr);
    }

}else{
    $msg = 'nao alterado';
    $arr = array('lancamento' =>'','Resultado'=>$msg);
    $someArray = array_map("utf8_encode",$arr);
}
echo json_encode($someArray);