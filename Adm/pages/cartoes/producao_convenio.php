<?PHP
    header("Content-type: application/json");
    include "../../php/banco.php";
    include "../../php/funcoes.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $mes = $_GET['mes'];
    $someArray = array();
    $i=1;
    $sql = $pdo->query("SELECT * FROM qGRUPOTOTALCONVENIO WHERE MES = '". $mes . "' ORDER BY RazaoSocial");
    while($row = $sql->fetch()) {
        $someArray[$i] = array_map("utf8_encode",$row);
        $i++;
    }
    echo json_encode($someArray);