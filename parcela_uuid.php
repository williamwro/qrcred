<?PHP
header("Content-type: application/json");
require 'Adm/php/banco.php';
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$lancamento = $_POST['lancamento'];
$someArray = array();
$someArray2 = array();
$i=0;
$i2=0;
$sql = "SELECT * FROM sind.conta WHERE lancamento = ".$lancamento;
$sql = $pdo->query($sql);
$i++;
while($row = $sql->fetch()) {
        $someArray[$i] = array_map("utf8_encode",$row);
    $i++;
}
if(isset($someArray[1]['uuid_conta']) && $someArray[1]['uuid_conta'] != ""){
    $uuid = $someArray[1]['uuid_conta'];
    $stmt = $pdo->prepare("SELECT * FROM sind.conta 
                           WHERE uuid_conta = ? AND mes
                           NOT IN(SELECT mes
                           FROM sind.controle) ORDER BY conta.lancamento ASC");
    $stmt->execute([$uuid]);

    $i2++;
    while($row = $stmt->fetch()) {
        $someArray2[$i2] = array_map("utf8_encode",$row);
        $i2++;
    }
}else{
    $lancamento = (int)$lancamento;
    $stmt = $pdo->prepare("SELECT * FROM sind.conta 
                           WHERE lancamento = :lancamento AND mes
                           NOT IN(SELECT mes
                           FROM sind.controle)");

    $stmt->bindParam(':lancamento',  $lancamento, PDO::PARAM_INT);
    $qtde_selecionados = $stmt->execute();

    $i2++;
    while($row = $stmt->fetch()) {
        $someArray2[$i2] = array_map("utf8_encode",$row);
        $i2++;
    }
}



echo json_encode($someArray2);