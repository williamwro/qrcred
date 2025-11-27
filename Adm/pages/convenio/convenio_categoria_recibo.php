<?PHP
    header("Content-type: application/json");
    require_once '../../../functions.php';
    include "../../php/banco.php";
    include "../../php/funcoes.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $someArray = array();
    $i=1;
    $sql = $pdo->query("SELECT * FROM sind.categoria_recibo ORDER BY nome");
    while($row = $sql->fetch()) {
        $someArray[$i] = // Substituir utf8_encode() depreciado por mb_convert_encoding()
array_map(function($value) {
    return is_string($value) ? (mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1')) : $value;
}, $row);
        $i++;
    }
    echo json_encode($someArray);