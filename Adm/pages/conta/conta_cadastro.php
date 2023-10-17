<?PHP
header("Content-type: application/json");
require '../../php/banco.php';
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

date_default_timezone_set('America/Sao_Paulo');
$_divisao       = isset($_POST['divisao']) ? $_POST['divisao'] : 0;
$_matricula     = isset($_POST['inputMatricula_aux']) ? $_POST['inputMatricula_aux'] : "";
$_empregador    = isset($_POST['id_empregador']) ? $_POST['id_empregador'] : "";
$_convenio      = isset($_POST['cod_convenio']) ? (int)$_POST['cod_convenio'] : 0;
$_valorx        = isset($_POST['inputValor']) ? str_replace('.','',$_POST['inputValor']) : "";
$_valor         = isset($_POST['inputValor']) ? str_replace(',','.',$_valorx) : "";
$_data          = isset($_POST['inputDataCad']) ? converte_data($_POST['inputDataCad']) : "";
$_hora          = date('H:i:s');
$_hora          = str_replace('00:00:00',$_hora,$_data);
$_mes           = isset($_POST['select_mes']) ? $_POST['select_mes'] : "";
$aux            = $_mes;
$_parcela       = isset($_POST['inputParcela']) ? $_POST['inputParcela'] : "";
$_optarcela     = isset($_POST['tipo_parcela']) ? $_POST['tipo_parcela'] : "";
$_funcionario   = isset($_POST['funcionario_cad']) ? (int)$_POST['funcionario_cad'] : 0;
$_situacao      = isset($_POST['situacao_reg']) ? (int)$_POST['situacao_reg'] : 1;
$_tipo          = isset($_POST['tipo_reg']) ? $_POST['tipo_reg'] : "";
$_obs           = isset($_POST['obsCad']) ? $_POST['obsCad'] : "";
$_nome_convenio = isset($_POST['nome_convenio']) ? $_POST['nome_convenio'] : "";
$_qtde_parcelas = 0;
$_parcela1      = 0;
$someArray = array();

$query = "SELECT * FROM sind.empregador WHERE abreviacao = '".$_empregador."' AND divisao = ".$_divisao;
$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();
$Cod_empregador = 0;
foreach ($result as $row) {
    $Cod_empregador = (int)$row["id"];
}
function converte_data($date) {
    return substr($date,6,4).'-'.substr($date,3,2).'-'.substr($date,0,2).' 00:00:00';
}
$stmt = new stdClass();

$msg_grava_cad="";
if(isset($_POST["operation"])) {

        if($_POST["operation"] == "Update") {
            $_lancamento  = isset($_POST['inputMatricula_aux']) ? (int)$_POST['inputMatricula_aux'] : "";
            $sql = "UPDATE sind.conta SET ";
            $sql .= "associado = :associado, ";
            $sql .= "convenio = :convenio, ";
            $sql .= "valor = :valor, ";
            $sql .= "data = :data, ";
            $sql .= "hora = :hora, ";
            $sql .= "descricao = :descricao, ";
            $sql .= "mes = :mes, ";
            $sql .= "funcionario = :funcionario, ";
            $sql .= "empregador = :empregador, ";
            $sql .= "parcela = :parcela, ";
            $sql .= "tipo = :tipo, ";
            $sql .= "id_situacao = :id_situacao ";
            $sql .= "WHERE lancamento = " . $_lancamento;
            $msg_grava_cad = "aualizado";

            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':associado', $_matricula, PDO::PARAM_STR);
            $stmt->bindParam(':convenio', $_convenio, PDO::PARAM_STR);
            $stmt->bindParam(':valor', $_valor, PDO::PARAM_STR);
            $stmt->bindParam(':data', $_data, PDO::PARAM_STR);
            $stmt->bindParam(':hora', $_hora, PDO::PARAM_STR);
            $stmt->bindParam(':descricao', $_obs, PDO::PARAM_STR);
            $stmt->bindParam(':mes', $_mes, PDO::PARAM_STR);
            $stmt->bindParam(':funcionario', $_funcionario, PDO::PARAM_INT);
            $stmt->bindParam(':empregador', $Cod_empregador, PDO::PARAM_INT);
            $stmt->bindParam(':parcela', $_parcela, PDO::PARAM_STR);
            $stmt->bindParam(':tipo', $_tipo, PDO::PARAM_STR);
            $stmt->bindParam(':id_situacao', $_situacao, PDO::PARAM_INT);

            $stmt->execute();

        }elseif($_POST["operation"] == "Add") {
            if($_optarcela == 'unica') {

                $sql = "INSERT INTO sind.conta(";
                $sql .= "associado, convenio, valor, data, hora, descricao, mes, funcionario, empregador, parcela, tipo, id_situacao) VALUES( ";
                $sql .= ":associado, ";
                $sql .= ":convenio, ";
                $sql .= ":valor, ";
                $sql .= ":data, ";
                $sql .= ":hora, ";
                $sql .= ":descricao, ";
                $sql .= ":mes, ";
                $sql .= ":funcionario, ";
                $sql .= ":empregador, ";
                $sql .= ":parcela, ";
                $sql .= ":tipo, ";
                $sql .= ":id_situacao)";

                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(':associado', $_matricula, PDO::PARAM_STR);
                $stmt->bindParam(':convenio', $_convenio, PDO::PARAM_INT);
                $stmt->bindParam(':valor', $_valor , PDO::PARAM_STR);
                $stmt->bindParam(':data', $_data, PDO::PARAM_STR);
                $stmt->bindParam(':hora', $_hora, PDO::PARAM_STR);
                $stmt->bindParam(':descricao', $_obs, PDO::PARAM_STR);
                $stmt->bindParam(':mes', $_mes, PDO::PARAM_STR);
                $stmt->bindParam(':funcionario', $_funcionario, PDO::PARAM_INT);
                $stmt->bindParam(':empregador', $Cod_empregador, PDO::PARAM_INT);
                $stmt->bindParam(':parcela', $_parcela, PDO::PARAM_STR);
                $stmt->bindParam(':tipo', $_tipo, PDO::PARAM_STR);
                $stmt->bindParam(':id_situacao', $_situacao, PDO::PARAM_INT);

                $stmt->execute();

                $someArray["Resultado"] = "cadastrado";
                $arr = array('associado' =>$_matricula,'convenio'=>$_convenio,'valor'=> $_valor,'data'=> $_data,'hora'=> $_hora,'descricao'=> $_obs,'mes'=> $_mes,'empregador'=> $Cod_empregador,'parcela'=> $_parcela,'tipo'=> $_tipo,'nome_convenio'=> $_nome_convenio);
                $someArray["data"][1] = array_map("utf8_encode",$arr);


            }else{
                $vetor = explode("/",$_parcela);
                $_parcela1      = (int)$vetor[0];
                $_qtde_parcelas = (int)$vetor[1];
                $aux = $_mes;
                $registros = array();
                $someArray["Resultado"] = "cadastrado";
                for($i = $_parcela1;$i <= $_qtde_parcelas;$i++){
                    $sql = "INSERT INTO sind.conta(";
                    $sql .= "associado, convenio, valor, data, hora, descricao, mes, funcionario, empregador, parcela, tipo, id_situacao) VALUES( ";
                    $sql .= ":associado, ";
                    $sql .= ":convenio, ";
                    $sql .= ":valor, ";
                    $sql .= ":data, ";
                    $sql .= ":hora, ";
                    $sql .= ":descricao, ";
                    $sql .= ":mes, ";
                    $sql .= ":funcionario, ";
                    $sql .= ":empregador, ";
                    $sql .= ":parcela, ";
                    $sql .= ":tipo, ";
                    $sql .= ":id_situacao)";

                    $stmt = $pdo->prepare($sql);
                    if ($i > 1){
                        $_soma_parcela = $i;
                    }else{
                        $_soma_parcela = 1;
                    }
                    $_parcela1_string = str_pad($_soma_parcela,2,'0',STR_PAD_LEFT);
                    $_qtde_parcelas_string = str_pad($_qtde_parcelas,2,'0',STR_PAD_LEFT);
                    $_result = $_parcela1_string."/".$_qtde_parcelas_string;

                    $stmt->bindParam(':associado', $_matricula, PDO::PARAM_STR);
                    $stmt->bindParam(':convenio', $_convenio, PDO::PARAM_INT);
                    $stmt->bindParam(':valor', $_valor , PDO::PARAM_STR);
                    $stmt->bindParam(':data', $_data, PDO::PARAM_STR);
                    $stmt->bindParam(':hora', $_hora, PDO::PARAM_STR);
                    $stmt->bindParam(':descricao', $_obs, PDO::PARAM_STR);
                    $stmt->bindParam(':mes', $_mes, PDO::PARAM_STR);
                    $stmt->bindParam(':funcionario', $_funcionario, PDO::PARAM_INT);
                    $stmt->bindParam(':empregador', $Cod_empregador, PDO::PARAM_INT);
                    $stmt->bindParam(':parcela', $_result, PDO::PARAM_STR);
                    $stmt->bindParam(':tipo', $_tipo, PDO::PARAM_STR);
                    $stmt->bindParam(':id_situacao', $_situacao, PDO::PARAM_INT);

                    $stmt->execute();

                    $arr = array('associado' =>$_matricula,'convenio'=>$_convenio,'valor'=> $_valor,'data'=> $_data,'hora'=> $_hora,'descricao'=> $_obs,'mes'=> $_mes,'empregador'=> $Cod_empregador,'parcela'=> $_result,'tipo'=> $_tipo,'nome_convenio'=> $_nome_convenio);
                    $someArray["data"][$i] = array_map("utf8_encode",$arr);

                    $_mes          = somames_gravar($aux); // soma 1 mes
                    $aux          = $_mes;
                }
            }
        }
        $resultado = $someArray;
        $resultadow = json_encode($someArray);
        echo json_encode($someArray);
}