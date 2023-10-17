<?PHP
require '../../php/banco.php';
if(isset($_POST['cod_empregador'])){
    $cod_empregador = $_POST['cod_empregador'];

    $stmt = new stdClass();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $msg_grava_cad="";

    try {
        $sql = "DELETE FROM sind.empregador WHERE id = :cod_empregador ";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':cod_empregador', $cod_empregador, PDO::PARAM_INT);

        $stmt->execute();

        $msg = 'excluido';
        $arr = array('Resultado'=>$msg);
        $someArray = array_map("utf8_encode",$arr);
        echo json_encode($someArray);

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
}