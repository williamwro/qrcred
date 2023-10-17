<?PHP
require '../../php/banco.php';
$_codigo = isset($_POST['C_codigo_funcao']) ? $_POST['C_codigo_funcao'] : 0;
$_nome   = isset($_POST['C_nome_funcao']) ? strtoupper($_POST['C_nome_funcao']) : "";
$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";
if(isset($_POST["operation"])) {
    if($_POST["operation"] == "Update") {

        $sql = "UPDATE sind.funcao SET ";
        $sql .= "nome = :nome ";
        $sql .= "WHERE id = :id";

        $msg_grava_cad = "atualizado";

    }elseif($_POST["operation"] == "Add") {

        $sql = "INSERT INTO sind.funcao(";
        $sql .= "nome) ";
        $sql .= "VALUES(";
        $sql .= ":nome)";

        $msg_grava_cad = "cadastrado";

    }
    try {

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':nome', $_nome, PDO::PARAM_STR);
        $stmt->bindParam(':id', $_id, PDO::PARAM_INT);

        $stmt->execute();

        echo $msg_grava_cad;

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
}