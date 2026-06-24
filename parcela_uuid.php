<?PHP
header("Content-type: application/json");
require 'Adm/php/banco.php';
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$lancamento = (int)$_POST['lancamento'];
$someArray = array();
$someArray2 = array();
$i=0;
$i2=0;
$stmt_first = $pdo->prepare("SELECT * FROM sind.conta WHERE lancamento = :lancamento");
$stmt_first->bindParam(':lancamento', $lancamento, PDO::PARAM_INT);
$stmt_first->execute();
$i++;
while($row = $stmt_first->fetch()) {
        $someArray[$i] = array_map(function($value) {
            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
        }, $row);
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
        $someArray2[$i2] = array_map(function($value) {
            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
        }, $row);
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
        $someArray2[$i2] = array_map(function($value) {
            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
        }, $row);
        $i2++;
    }
}



echo json_encode($someArray2);