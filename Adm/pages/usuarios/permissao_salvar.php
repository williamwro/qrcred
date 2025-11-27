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

    $someArray = // Substituir utf8_encode() depreciado por mb_convert_encoding()
array_map(function($value) {
    return is_string($value) ? (mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1')) : $value;
}, $arr);
    echo json_encode($someArray);

} catch (PDOException $erro) {
    echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();

}
