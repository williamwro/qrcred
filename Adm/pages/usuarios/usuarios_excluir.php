<?PHP
require '../../php/banco.php';
include "../../php/funcoes.php";
if(isset($_POST['cod_usuario'])){
    $cod_usuario      = $_POST['cod_usuario'];

    $stmt = new stdClass();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $msg_grava_cad="";

    try {
        $sql = "DELETE FROM sind.usuarios WHERE codigo = :codigo";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':codigo', $cod_usuario, PDO::PARAM_INT);
        $stmt->execute();

        $sql = "DELETE FROM sind.usuarios_menu WHERE codigo_usuario = :codigo";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':codigo', $cod_usuario, PDO::PARAM_INT);
        $stmt->execute();

        $msg = 'excluido';
        $arr = array('Resultado'=>$msg);
        $someArray = array_map("utf8_encode",$arr);
        echo json_encode($someArray);

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
}