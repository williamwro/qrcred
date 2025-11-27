<?PHP
require '../../php/banco.php';
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST['cod_divisao'])){
    $cod_divisao = $_POST['cod_divisao'];
    $stmt = new stdClass();
    $msg_grava_cad="";

    try {
        $sql = "DELETE FROM sind.divisao WHERE id_divisao = :cod_divisao ";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':cod_divisao', $cod_divisao, PDO::PARAM_INT);

        $stmt->execute();

        $msg = 'excluido';
        $arr = array('Resultado'=>$msg);
        $someArray = array_map(function($value) {
            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
        }, $arr);
        echo json_encode($someArray);

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
}