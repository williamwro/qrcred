<?PHP
require '../../php/banco.php';
$_codigo        = isset($_POST['C_codigo_empregador']) ? $_POST['C_codigo_empregador'] : 0;
$_nome          = isset($_POST['C_nome_empregador']) ? strtoupper($_POST['C_nome_empregador']) : "";
$_nome_original = isset($_POST['C_nome_original']) ? strtoupper($_POST['C_nome_original']) : "";
$_telefone      = isset($_POST['C_telefone']) ? strtoupper($_POST['C_telefone']) : "";
$_responsavel   = isset($_POST['C_responsavel']) ? strtoupper($_POST['C_responsavel']) : "";
$_abreviacao    = isset($_POST['C_abreviacao']) ? strtoupper($_POST['C_abreviacao']) : "";
$_divisao       = isset($_POST['C_divisao']) ? strtoupper($_POST['C_divisao']) : 0;
$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";
if(isset($_POST["operation"])) {
    if($_POST["operation"] == "Update") {

        $sql = "UPDATE sind.empregador SET ";
        $sql .= "nome = :nome, ";
        $sql .= "telefone = :telefone, ";
        $sql .= "responsavel = :responsavel, ";
        $sql .= "abreviacao = :abreviacao, ";
        $sql .= "divisao = :divisao ";
        $sql .= "WHERE id = " . $_codigo;

        $msg_grava_cad = "atualizado";

    }elseif($_POST["operation"] == "Add") {

        $sql = "INSERT INTO sind.empregador(";
        $sql .= "nome,telefone,responsavel,abreviacao,divisao) ";
        $sql .= "VALUES(";
        $sql .= ":nome, ";
        $sql .= ":telefone, ";
        $sql .= ":responsavel, ";
        $sql .= ":abreviacao, ";
        $sql .= ":divisao)";

        $msg_grava_cad = "cadastrado";

    }
    try {

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':nome', $_nome, PDO::PARAM_STR);
        $stmt->bindParam(':telefone', $_telefone, PDO::PARAM_STR);
        $stmt->bindParam(':responsavel', $_responsavel, PDO::PARAM_STR);
        $stmt->bindParam(':abreviacao', $_abreviacao, PDO::PARAM_STR);
        $stmt->bindParam(':divisao', $_divisao, PDO::PARAM_INT);

        $stmt->execute();

        echo $msg_grava_cad;

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();

    }
}