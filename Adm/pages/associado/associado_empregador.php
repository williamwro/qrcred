<?PHP
    header("Content-type: application/json; charset=utf-8");
    include "../../php/banco.php";
    include "../../php/funcoes.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $someArray = array();
    $i=1;
    $divisao = isset($_GET["divisaox"]) ? (int)$_GET["divisaox"] : null;
    
    if($divisao !== null && $divisao > 0) {
        $sql = $pdo->prepare("SELECT * FROM sind.empregador WHERE divisao = :divisao ORDER BY nome");
        $sql->bindParam(':divisao', $divisao, PDO::PARAM_INT);
        $sql->execute();
    } else {
        $sql = $pdo->prepare("SELECT * FROM sind.empregador ORDER BY nome");
        $sql->execute();
    }
    
    while($row = $sql->fetch()) {
        $someArray[$i] = $row;
        $i++;
    }
    echo json_encode($someArray, JSON_UNESCAPED_UNICODE);
