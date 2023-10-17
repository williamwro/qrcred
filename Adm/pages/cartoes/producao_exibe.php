<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST["cod_convenio"])){
    $std = new stdClass();
    $cod_convenio = $_POST["cod_convenio"];
    $query = "SELECT * FROM ASSOCIADO WHERE Codigo = '".$cod_convenio."'";
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();
    $salario='';

    foreach ($result as $row){
        $std->codigo          = $row["Codigo"];
        $std->nome            = $row["Nome"];
        $std->endereco        = $row["Endereco"];
        $std->numero          = $row["numero"];
        $std->nascimento      = date('d/m/Y', strtotime($row["Nascimento"]));
        $std->salario         = (float)str_replace('.',',',$row["Salario"]);
        $std->Limite          = (float)str_replace('.',',',$row["Limite"]);
        $std->Empregador      = (int)$row["Empregador"];
        $std->CEP             = $row["CEP"];
        $std->TelRes          = $row["TelRes"];
        $std->TelCom          = $row["TelCom"];
        $std->Cel             = $row["Cel"];
        $std->Bairro          = $row["Bairro"];
        $std->Complemento     = $row["Complemento"];
        $std->Cidade          = $row["Cidade"];
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