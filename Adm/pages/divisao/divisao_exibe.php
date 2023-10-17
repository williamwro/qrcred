<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST["cod_divisao"])){
    $std = new stdClass();
    $cod_divisao = $_POST["cod_divisao"];

    $query = "SELECT id_divisao, nome, cidade
                FROM sind.divisao WHERE id_divisao = ".$cod_divisao;
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();

    foreach ($result as $row){
        $std->id_divisao = $row["id_divisao"];
        $std->nome       = utf8_encode($row["nome"]);
        $std->cidade     = utf8_encode($row["cidade"]);
    }
    echo json_encode($std);}