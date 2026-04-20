<?PHP
header('Content-Type: application/json; charset=utf-8');
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST["cod_empregador"])){
    $cod_empregador = $_POST["cod_empregador"];
    $std = new stdClass();

    $query = "SELECT id,nome,responsavel,telefone,abreviacao,id_divisao,bloqueio,usuario,senha
                FROM sind.empregador WHERE id = ".$cod_empregador;
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();

    foreach ($result as $row){
        $std->id          = $row["id"];
        $std->nome        = $row["nome"];
        $std->responsavel = $row["responsavel"];
        $std->telefone    = $row["telefone"];
        $std->abreviacao  = $row["abreviacao"];
        $std->divisao     = $row["id_divisao"];
        $std->bloqueio    = $row["bloqueio"];
        $std->usuario     = isset($row["usuario"]) ? $row["usuario"] : "";
        $std->senha       = isset($row["senha"]) ? $row["senha"] : "";
    }
    echo json_encode($std, JSON_UNESCAPED_UNICODE);
}