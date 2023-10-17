<?PHP
    header("Content-type: application/json");
    include "../../php/banco.php";
    include "../../php/funcoes.php";
    //$mes = $_GET['mes'];
    $someArray = array();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $i=1;
    //$sql = $database->query("SELECT * FROM qGRUPOTOTALCONVENIO WHERE MES = '". $mes . "' AND Desativado = false ORDER BY RazaoSocial");
    $sql = $pdo->query("SELECT * FROM sind.convenio ORDER BY razaosocial");
    while($row = $sql->fetch()) {
        $someArray[$i] = array_map("utf8_encode",$row);
        $i++;
    }
    echo json_encode($someArray);