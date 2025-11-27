<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST["cod_divisao"])){
    $std = new stdClass();
    $cod_divisao = $_POST["cod_divisao"];

    $query = "SELECT id_divisao, nome, cidade, dia_mes_renovacao
                FROM sind.divisao WHERE id_divisao = ".$cod_divisao;
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();

    foreach ($result as $row){
        $std->id_divisao = $row["id_divisao"];
        $std->nome       = mb_convert_encoding($row["nome"], 'UTF-8', 'ISO-8859-1');
        $std->cidade     = mb_convert_encoding($row["cidade"], 'UTF-8', 'ISO-8859-1');
        $std->dia_mes_renovacao = $row["dia_mes_renovacao"];
    }
    echo json_encode($std);}