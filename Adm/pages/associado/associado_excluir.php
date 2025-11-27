<?PHP
require '../../php/banco.php';
include "../../php/funcoes.php";
if(isset($_POST['cod_associado']) || isset($_POST['id_empregador'])){
    $cod_associado      = $_POST['cod_associado'];
    $id_empregador      = $_POST['id_empregador'];
    $id_associado       = $_POST['id_associado'];
    $divisao            = $_POST['divisao'];

    $stmt = new stdClass();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $msg_grava_cad="";

    try {
        $sql = "DELETE FROM sind.associado 
                WHERE codigo = :matricula 
                AND empregador = :id_empregador 
                AND id_divisao = :divisao 
                AND id = :id_associado";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':matricula', $cod_associado, PDO::PARAM_STR);
        $stmt->bindParam(':id_empregador', $id_empregador, PDO::PARAM_INT);
        $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
        $stmt->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);

        $stmt->execute();

        $msg = 'excluido';
        $arr = array('Resultado'=>$msg);
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
}