<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST["cod_convenio"])){
    $std = new stdClass();
    $cod_convenio = (int)$_POST["cod_convenio"];
    $query = "SELECT * FROM sind.c_senhaconvenio WHERE cod_convenio = ".$cod_convenio;
    $statment_senha = $pdo->prepare($query);
    $statment_senha->execute();
    $result = $statment_senha->fetchAll();
    $std->existesenha        = "nao";
    foreach ($result as $row){

        $std->usuario        = $row["usuario"];
        $std->senha          = $row["senha"];
        if ($row["usuario_texto"] == null){
            $std->usuariotexto = "";
        }else{
            $std->usuariotexto = $row["usuario_texto"];
        }

        $std->existesenha   = "sim";
    }
    $std->codigo = $cod_convenio;
    $query = "SELECT * FROM sind.convenio WHERE codigo = ".$cod_convenio;
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();
    foreach ($result as $row){
        $std->razaosocial = $row["razaosocial"];
    }
    echo json_encode($std);
}