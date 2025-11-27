<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST["cod_categoria"])){
    $std = new stdClass();
    $cod_categoria = $_POST["cod_categoria"];
    $nome = $_POST["nome"];

    $query = "SELECT id,nome
                FROM sind.funcao WHERE id = ".$cod_categoria;
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();

    foreach ($result as $row){
        $std->codigo = $row["id"];
        $std->nome   = mb_convert_encoding($row["nome"], 'UTF-8', 'ISO-8859-1');
    }
    echo json_encode($std);
}