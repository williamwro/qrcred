<?PHP
    header("Content-type: application/json");
    include "../../php/banco.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $divisao  = $_GET['divisao'];
    $someArray = array();
    $i=0;
    $sql = $pdo->query("SELECT id, lote, data AS datalote FROM sind.lotes_cartao WHERE id_divisao = ".$divisao." ORDER BY id");
    $i++;
    while($row = $sql->fetch()) {
        $someArray[$i] = array_map(function($value) {
            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
        }, $row);
        $i++;
    }
    echo json_encode($someArray);