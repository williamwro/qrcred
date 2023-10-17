<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST["cod_convenio"])){
    $std = new stdClass();
    $cod_convenio = $_POST["cod_convenio"];
    $card1 = $_POST["card1"];
    $card2 = $_POST["card2"];
    $card3 = $_POST["card3"];
    $card4 = $_POST["card4"];
    $card5 = $_POST["card5"];
    $card6 = $_POST["card6"];
    $query = "SELECT * FROM sind.associado WHERE codigo = '".$cod_convenio."' 
                            AND associado.codigo <> '".$card1."' 
                            AND associado.codigo <> '".$card2."' 
                            AND associado.codigo <> '".$card3."' 
                            AND associado.codigo <> '".$card4."' 
                            AND associado.codigo <> '".$card5."' 
                            AND associado.codigo <> '".$card6."'";
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();
    $salario='';

    foreach ($result as $row){
        $std->codigo          = $row["codigo"];
        $std->nome            = $row["nome"];
        $std->endereco        = $row["endereco"];
        $std->numero          = $row["numero"];
        $std->nascimento      = date('d/m/Y', strtotime($row["nascimento"]));
        $std->salario         = (float)str_replace('.',',',$row["salario"]);
        $std->Limite          = (float)str_replace('.',',',$row["limite"]);
        $std->Empregador      = (int)$row["empregador"];
        $std->CEP             = $row["cep"];
        $std->TelRes          = $row["telres"];
        $std->TelCom          = $row["telcom"];
        $std->Cel             = $row["cel"];
        $std->Bairro          = $row["bairro"];
        $std->Complemento     = $row["complemento"];
        $std->Cidade          = $row["cidade"];
        $std->rg              = $row["rg"];
        $std->cpf             = $row["cpf"];
        $std->funcao          = (int)$row['funcao'];
        if($row['filiado'] == true){
            $std->filiado = true;//checked
        }else{
            $std->filiado = false;//Unchecked
        }
        $std->obs             = $row["obs"];
        $std->id_situacao     = (int)$row["id_situacao"];
        $std->data_filiacao   = date('d/m/Y', strtotime($row["data_filiacao"]));
        $std->data_desfiliacao = date('d/m/Y', strtotime($row["data_desfiliacao"]));
        $std->email           = $row["email"];
        $std->tipo            = (int)$row["tipo"];
        $std->codigo_isa      = $row["codigo_isa"];
        $std->parcelas_permitidas = (int)$row["parcelas_permitidas"];
    }
    $xxx = json_encode($std);
    echo $xxx;
}