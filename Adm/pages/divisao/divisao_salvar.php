<?PHP
require '../../php/banco.php';
$_codigo = isset($_POST['C_codigo']) ? $_POST['C_codigo'] : 0;
$_nome   = isset($_POST['C_nome_divisao']) ? strtoupper($_POST['C_nome_divisao']) : "";
$_cidade = isset($_POST['C_cidade']) ? strtoupper($_POST['C_cidade']) : "";

$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";
if(isset($_POST["operation"])) {
    if($_POST["operation"] == "Update") {

        $sql = "UPDATE sind.divisao SET ";
        $sql .= "nome = :nome, ";
        $sql .= "cidade = :cidade ";
        $sql .= "WHERE id_divisao = " . $_codigo;

        $msg_grava_cad = "atualizado";

    }elseif($_POST["operation"] == "Add") {

        $sql = "INSERT INTO sind.divisao(";
        $sql .= "nome,cidade) ";
        $sql .= "VALUES(";
        $sql .= ":nome, ";
        $sql .= ":cidade)";

        $msg_grava_cad = "cadastrado";

    }
    try {

        $stmt = $pdo->prepare($sql);

        //if($_POST['operation'] == 'Add') {
        //    $stmt->bindParam(':codigo', $_codigo, PDO::PARAM_STR);
        //}
        $stmt->bindParam(':nome', $_nome, PDO::PARAM_STR);
        $stmt->bindParam(':cidade', $_cidade, PDO::PARAM_STR);


        $stmt->execute();

        echo $msg_grava_cad;

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();

    }
}