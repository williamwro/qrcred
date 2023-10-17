<?PHP
date_default_timezone_set('America/Araguaina');
require '../../php/banco.php';
$_convenio    = isset($_POST['convenio']) ? strtoupper($_POST['convenio']) : "";
$_mes         = isset($_POST['mes']) ? strtoupper($_POST['mes']) : "";

if(isset($_POST['empregador']) && $_POST['empregador'] != ""){
    $_empregador = $_POST['empregador'];
}else{
    $_empregador = 0;
}

$_parcela     = isset($_POST['parcela']) ? strtoupper($_POST['parcela']) : "";
$_tipo        = isset($_POST['tipo']) ? strtoupper($_POST['tipo']) : "";
$_cod_usuario = isset($_POST['cod_usuario']) ? $_POST['cod_usuario'] : 0;
$_usuario     = isset($_POST['usuario']) ? strtoupper($_POST['usuario']) : "";
$_data2       = new DateTime();
$_data        = $_data2->format('Y-m-d h:i:s');

$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";

$sql = "INSERT INTO sind.log_rel_convenio(";
$sql .= "convenio,data,mes,empregador,parcela,tipo,cod_usuario,usuario) ";
$sql .= "VALUES(";
$sql .= ":convenio, ";
$sql .= ":data, ";
$sql .= ":mes, ";
$sql .= ":empregador, ";
$sql .= ":parcela, ";
$sql .= ":tipo, ";
$sql .= ":cod_usuario, ";
$sql .= ":usuario)";

$msg_grava_cad = "cadastrado";


try {

    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':convenio', $_convenio, PDO::PARAM_STR);
    $stmt->bindParam(':data', $_data, PDO::PARAM_STR);
    $stmt->bindParam(':mes', $_mes, PDO::PARAM_STR);
    $stmt->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
    $stmt->bindParam(':parcela', $_parcela, PDO::PARAM_STR);
    $stmt->bindParam(':tipo', $_tipo, PDO::PARAM_STR);
    $stmt->bindParam(':cod_usuario', $_cod_usuario, PDO::PARAM_INT);
    $stmt->bindParam(':usuario', $_usuario, PDO::PARAM_STR);

    $stmt->execute();

    echo $msg_grava_cad;

} catch (PDOException $erro) {
    echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
}