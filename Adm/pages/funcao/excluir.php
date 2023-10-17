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
        $someArray = array_map("utf8_encode",$arr);
        echo json_encode($someArray);

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
}