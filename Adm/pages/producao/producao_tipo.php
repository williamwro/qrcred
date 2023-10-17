<?PHP
    header("Content-type: application/json");
    include "../../php/banco.php";
    include "../../php/funcoes.php";
    $someArray = array();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $i=1;
    $sql = $pdo->query("SELECT * FROM sind.tipoconvenio ORDER BY nome");
    while($row = $sql->fetch()) {
        $someArray[$i] = array_map("utf8_encode",$row);
        $i++;
    }
    echo json_encode($someArray);