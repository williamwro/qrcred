<?PHP
require '../../php/banco.php';
include "../../php/funcoes.php";
if(isset($_POST['cod_associado']) || isset($_POST['id_empregador'])){
    $cod_associado      = $_POST['cod_associado'];
    $id_empregador      =$_POST['id_empregador'];

    $stmt = new stdClass();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $msg_grava_cad="";

    try {
        $sql = "DELETE FROM sind.associado WHERE codigo = :matricula AND empregador = :id_empregador ";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':matricula', $cod_associado, PDO::PARAM_STR);
        $stmt->bindParam(':id_empregador', $id_empregador, PDO::PARAM_INT);

        $stmt->execute();

        $msg = 'excluido';
        $arr = array('Resultado'=>$msg);
        $someArray = array_map("utf8_encode",$arr);
        echo json_encode($someArray);

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
}