<?PHP
    header("Content-type: application/json");
    include "../../php/banco.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $someArray = array();
    $i=1;
    $sql = $pdo->query("SELECT * FROM sind.tipo_associado");
    while($row = $sql->fetch()) {
        $someArray[$i] = array_map(function($value) {
    return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
}, $row);
        $i++;
    }
    echo json_encode($someArray);
