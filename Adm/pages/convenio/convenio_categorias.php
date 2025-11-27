<?PHP
    header("Content-type: application/json");
    require_once '../../../functions.php';
    include "../../php/banco.php";
    include "../../php/funcoes.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $someArray = array();
    $i=1;
    $sql_categorias = $pdo->query("SELECT * FROM sind.categoriaconvenio ORDER BY nome");
    while($row = $sql_categorias->fetch()) {
        $someArray[$i] = array_map(function($value) {
            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
        }, $row);
        $i++;
    }
    $xx = json_encode($someArray);
    echo json_encode($someArray);