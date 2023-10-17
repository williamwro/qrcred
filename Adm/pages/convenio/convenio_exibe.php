<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST["cod_convenio"])){
    $std = new stdClass();
    $cod_convenio = (int)$_POST["cod_convenio"];
    $someArray = array();
    $query = "SELECT * FROM sind.convenio WHERE codigo = ".$cod_convenio;
    $statment = $pdo->prepare($query);
    $statment->execute();
    $result = $statment->fetchAll();
    foreach ($result as $row){
        $sub_array = array();
        $std->codigo          = (int)$row["codigo"];
        $std->razaosocial     = htmlspecialchars($row["razaosocial"]);
        $std->nomefantasia    = htmlspecialchars($row["nomefantasia"]);
        $std->endereco        = htmlspecialchars($row["endereco"]);
        $std->numero          = htmlspecialchars($row["numero"]);
        $std->bairro          = htmlspecialchars($row["bairro"]);
        $std->telefone        = $row["telefone"];
        $std->data_cadastro   = $row["data_cadastro"];
        $std->cidade          = htmlspecialchars($row["cidade"]);
        $std->uf              = $row["uf"];
        $std->cep             = $row["cep"];
        $std->fax             = $row["fax"];
        $std->cel             = $row["cel"];
        $std->contato         = htmlspecialchars($row["contato"]);
        $std->prolabore       = $row["prolabore"];
        $std->prolabore2      = $row["prolabore2"];
        $std->cnpj            = $row["cnpj"];
        $std->cpf             = $row["cpf"];
        $std->insc            = $row["insc"];
        $std->categoria       = (int)$row['id_categoria'];
        $std->categoriarecibo = (int)$row['id_categoria_recibo'];
        $std->registro        = $row["registro"];
        $std->aceita_parce_individ  = $row["aceita_parce_individ"];
        if($row['situacao'] == "S"){
            $std->situacao = true;
        }else{
            $std->situacao = false;
        }
        if($row['divulga'] == "S"){
            $std->divulga = true;
        }else{
            $std->divulga = false;
        }
        $std->insc_mun        = $row["insc_mun"];
        $std->email           = $row["email"];
        $std->email2          = $row["email2"];
        $std->tipo            = (int)$row['tipo'];
        $std->tipoempresa     = (int)$row['tipo2'];
        if($row['cobranca'] == true){
            $std->cobranca = true;//checked
        }else{
            $std->cobranca = false;//Unchecked
        }
        if($row['desativado'] == true){
            $std->desativado = true;//checked
        }else{
            $std->desativado = false;//Unchecked
        }
        $std->parcelas       = $row['n_parcelas'];

    }
    echo json_encode($std);
}