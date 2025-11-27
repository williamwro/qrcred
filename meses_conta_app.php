<?PHP
    header("Content-type: application/json");
    include "Adm/php/banco.php";
    include "Adm/php/funcoes.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $divisao = $_POST['divisao'];

$someArray = array();
    $query = $pdo->prepare('SELECT abreviacao,data,completo,periodo FROM sind.meses_conta WHERE divisao = ? ORDER BY data desc LIMIT 32');
    $query->execute(array($divisao));
    while($row = $query->fetch()) {

        $someArray[] = array_map(function($value) {
    return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
}, $row);

    }

echo json_encode($someArray);