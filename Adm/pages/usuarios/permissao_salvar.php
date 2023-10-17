<?PHP
require '../../php/banco.php';
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
header('Content-Type: text/html; charset=utf-8');
$_codigo_menu    = $_POST['codigo_menu'];
$_codigo_usuario = $_POST['codigo_usuario'];
$_status         = $_POST['status'];

$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";
try {

    $sql = "UPDATE sind.usuarios_menu SET ";
    $sql .= "status = :status ";
    $sql .= "WHERE codigo_usuario = " . $_codigo_usuario ." AND id_menu = ".$_codigo_menu;

    $msg_grava_cad = "atualizado";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':status', $_status,PDO::PARAM_STR);

    $arr = array('codigo_usuario' =>$_codigo_usuario,'id_menu'=>$_codigo_menu,'resultado'=>$msg_grava_cad);
    $stmt->execute();

    $someArray = array_map("utf8_encode",$arr);
    echo json_encode($someArray);

} catch (PDOException $erro) {
    echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();

}
