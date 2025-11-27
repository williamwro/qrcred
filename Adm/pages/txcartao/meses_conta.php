<?PHP
    header("Content-type: application/json");
    include "../../php/banco.php";
    include "../../php/funcoes.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $divisao = isset($_GET['divisao']) ? $_GET['divisao'] : 1;
    $someArray = array();
    $i = 0;
    
    // Busca o mês corrente
    $row = $pdo->query("SELECT abreviacao FROM sind.mes_corrente")->fetch();
    $someArray[$i]["mes_corrente"] = $row["abreviacao"];
    
    // Busca todos os meses disponíveis para cadastro
    $sql = "SELECT * FROM sind.meses_conta WHERE status_cadastro = 1 AND divisao = ? ORDER BY data";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($divisao));
    
    $i++;
    while($row = $stmt->fetch()) {
        $someArray[$i] = array_map(function($value) {
            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
        }, $row);
        $i++;
    }

    echo json_encode($someArray);
?>
