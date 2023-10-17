<?PHP
require '../../php/banco.php';
$_codigo = isset($_POST['C_codigo_categoria']) ? $_POST['C_codigo_categoria'] : 0;
$_nome   = isset($_POST['C_nome_categoria']) ? strtoupper($_POST['C_nome_categoria']) : "";
$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";
if(isset($_POST["operation"])) {
    if($_POST["operation"] == "Update") {

        $sql = "UPDATE sind.categoriaconvenio SET ";
        $sql .= "nome = :nome ";
        $sql .= "WHERE codigo = :codigo";

        $msg_grava_cad = "atualizado";

    }elseif($_POST["operation"] == "Add") {

        $sql = "INSERT INTO sind.categoriaconvenio(";
        $sql .= "nome) ";
        $sql .= "VALUES(";
        $sql .= ":nome)";

        $msg_grava_cad = "cadastrado";

    }
    try {
        $stmt = $pdo->prepare($sql);
        if($_POST['operation'] == 'Update') {
            $stmt->bindParam(':codigo', $_codigo, PDO::PARAM_INT);
        }
        $stmt->bindParam(':nome', $_nome, PDO::PARAM_STR);

        $stmt->execute();

        echo $msg_grava_cad;

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
}