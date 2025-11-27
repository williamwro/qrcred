<?PHP
require '../../php/banco.php';
if(isset($_POST['cod_categoria'])){
    $cod_categoria = $_POST['cod_categoria'];

    $stmt = new stdClass();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $msg_grava_cad="";

    try {
        $sql = "DELETE FROM sind.categoriaconvenio WHERE codigo = :cod_categoria ";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':cod_categoria', $cod_categoria, PDO::PARAM_INT);

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