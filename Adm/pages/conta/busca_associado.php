<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["matricula"])){
    $std = new stdClass();
    $cod_associado = $_POST["matricula"];
    $std->matricula = $cod_associado;
    $query = "SELECT ASSOCIADO.Codigo, ASSOCIADO.Nome, Empregador.ABREVIACAO FROM EMPREGADOR RIGHT JOIN ASSOCIADO ON EMPREGADOR.Id = ASSOCIADO.Empregador WHERE Codigo = '".$cod_associado."'" ;
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();
    foreach ($result as $row){
        $std->codigo = $row["Codigo"];
        $std->nome = $row["Nome"];
        $std->abreviacao = $row["ABREVIACAO"];
    }
    echo json_encode($std);
}