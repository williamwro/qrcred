<?PHP
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function replace_unicode_escape_sequence($match) {
    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}
function unicode_decode($str) {
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $str);
}
$someArray = array();
$tem_cadastro_conta = false;
$ultimo_codigo = '';
$ultimo_mes = '';
$controle_loop = 0;
$sql = "SELECT MAX(codigo) as ultimo_codigo FROM CONTROLE;";
$statment = $pdo->prepare($sql);
$statment->execute();
$result = $statment->fetchAll();
foreach ($result as $row){
    $ultimo_codigo = $row['ultimo_codigo'];
}

$sql = "SELECT * FROM CONTROLE WHERE codigo = ".$ultimo_codigo.";";
$statment = $pdo->prepare($sql);
$statment->execute();
$result = $statment->fetchAll();
foreach ($result as $row){
    $ultimo_mes = $row['mes'];
}

$sql = "SELECT * FROM MESES_CONTA ORDER BY DATA;";
$statment = $pdo->prepare($sql);
$statment->execute();
$result = $statment->fetchAll();
foreach ($result as $row){
    $sub_array = array();
    if ($controle_loop == 1){ $controle_loop ++; };
    if ($ultimo_mes ==  $row['ABREVIACAO']) {
        $controle_loop =+ 1;
    }
    if($controle_loop == 2){
        $dia_controle = substr($row['PERIODO'],0,5) ."/". substr($row['ABREVIACAO'],4,4);
        $dia_controle =  date("d/m/Y", strtotime($dia_controle));
        break;
    }
}
if(isset($_POST["matricula"])){
    $std = new stdClass();
    $matricula = $_POST["matricula"];
    $empregador = $_POST["empregador"];

    $sql = "SELECT CONTA.Associado, CONTA.Valor, EMPREGADOR.ABREVIACAO, CONTA.Lancamento, CONTA.Data, CONTA.mes, CONTA.parcela, EMPREGADOR.Id
            FROM EMPREGADOR INNER JOIN CONTA ON EMPREGADOR.Id = CONTA.Empregador
            WHERE CONTA.Associado = '".$matricula."' AND EMPREGADOR.ABREVIACAO = '".$empregador."' AND CONTA.data >= #".$dia_controle."#;";
    $statment = $pdo->prepare($sql);
    $statment->execute();
    $result = $statment->fetchAll();
    foreach ($result as $row){

        $sub_array = array();

        $sub_array["Registro"]    = $row['Lancamento'];
        $sub_array["Valor"]       = utf8_encode($row['Valor']);
        $sub_array["Data"]        =  date('d/m/Y', strtotime($row['Data']));
        $sub_array["Mes"]         = $row['mes'];
        if ($row['parcela'] == null) {
            $sub_array["Parcela"] = '';
        }else{
            $sub_array["Parcela"] = $row['parcela'];
        }
        $sub_array["Empregador"]  = $row['Id'];

        $someArray["data"][] = array_map("utf8_encode",$sub_array);

    }
    echo json_encode($someArray);
}