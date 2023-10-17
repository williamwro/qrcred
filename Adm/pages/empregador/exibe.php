<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST["cod_empregador"])){
    $std = new stdClass();
    $cod_empregador = $_POST["cod_empregador"];
    $nome = $_POST["nome"];
    $divisao = $_POST["divisao"];

    $query = "SELECT id,nome,responsavel,telefone,abreviacao,divisao
                FROM sind.empregador WHERE id = ".$cod_empregador;
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();

    foreach ($result as $row){
        $std->id         = $row["id"];
        $std->nome       = utf8_encode($row["nome"]);
        $std->responsavel = utf8_encode($row["responsavel"]);
        $std->telefone   = utf8_encode($row["telefone"]);
        $std->abreviacao = utf8_encode($row["abreviacao"]);
        $std->divisao    = utf8_encode($row["divisao"]);
    }
    echo json_encode($std);}