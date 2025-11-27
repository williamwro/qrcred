<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST["cod_associado"])){
    $std = new stdClass();
    $cod_associado = $_POST["cod_associado"];
    $id_empregador = $_POST["id_empregador"];
    $id_associado = $_POST["id_associado"];
    $query = "SELECT * FROM sind.associado WHERE codigo = '".$cod_associado."' AND empregador = ".$id_empregador." AND id = ".$id_associado;
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();
    foreach ($result as $row){
        $std->nome      = $row["nome"];
        $std->id_divisao = $row["id_divisao"];
    }
    $std->matricula     = $cod_associado;
    $std->id_empregador = $id_empregador;
    $query = "SELECT * FROM sind.c_senhaassociado WHERE cod_associado = '".$cod_associado."' AND id_empregador = ".$id_empregador." AND id_associado = ".$id_associado;
    $statment_senha = $pdo->prepare($query);
    $statment_senha->execute();
    $result = $statment_senha->fetchAll();
    $std->existesenha        = "nao";
    foreach ($result as $row){
        $std->senha          = $row["senha"];
        $std->existesenha    = "sim";
    }
    echo json_encode($std);
}