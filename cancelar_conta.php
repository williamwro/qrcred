<?PHP
require 'Adm/php/banco.php';
include "Adm/php/funcoes.php";


if(isset($_POST['nome'])){
    if($_POST['nome'] != ''){
        $C_nome = $_POST['nome'];
    }else{
        $C_nome = '';
    }
}else{
    $C_nome = '';
}
if(isset($_POST['cpf'])){
    if($_POST['cpf'] != ''){
        $C_cpf = $_POST['cpf'];
    }else{
        $C_cpf = '';
    }
}else{
    $C_cpf = '';
}
$C_email = isset($_POST['email']) ? $_POST['email'] : "";
if($_POST['motivo']  != ""){
    $C_motivo = $_POST['motivo'];
}else{
    $C_motivo = "";
}

$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";


$sql = "INSERT INTO sind.pedido_cancelamento( ";
$sql .= "nome,cpf,email,motivo) ";
$sql .= " VALUES(";
$sql .= ":nome, ";
$sql .= ":cpf, ";
$sql .= ":email, ";
$sql .= ":motivo) RETURNING lastval()";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':nome', $C_nome, PDO::PARAM_STR);
$stmt->bindParam(':cpf', $C_cpf, PDO::PARAM_STR);
$stmt->bindParam(':email', $C_email, PDO::PARAM_STR);
$stmt->bindParam(':motivo', $C_motivo, PDO::PARAM_STR);
$stmt->execute();

$msg = 'cadastrado';
$arr = array('Resultado'=>$msg);
$someArray = array_map("utf8_encode",$arr);
echo json_encode($someArray);