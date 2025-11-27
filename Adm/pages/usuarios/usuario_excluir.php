<?PHP
require '../../php/banco.php';
include "../../php/funcoes.php";

if(isset($_POST['cod_associado'])){
    $cod_associado = $_POST['cod_associado'];

    $stmt = new stdClass();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $msg_grava_cad="";

    try {
        $sql = "DELETE FROM sind.usuarios WHERE codigo = :codigo";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':codigo', $cod_associado, PDO::PARAM_INT);
        $stmt->execute();

        $sql = "DELETE FROM sind.usuarios_menu WHERE codigo_usuario = :codigo";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':codigo', $cod_associado, PDO::PARAM_INT);
        $stmt->execute();

        $msg = 'excluido';
        $arr = array('Resultado'=>$msg);
        $someArray = // Substituir utf8_encode() depreciado por mb_convert_encoding()
array_map(function($value) {
    return is_string($value) ? (mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1')) : $value;
}, $arr);
        echo json_encode($someArray);

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
}