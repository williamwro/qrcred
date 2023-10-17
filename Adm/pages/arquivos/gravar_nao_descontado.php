<?PHP
header("Content-type: application/json");
require '../../php/banco.php';
include "../../php/funcoes.php";
$dados = str_replace('[', '',$_POST['data']);
$dados2 = str_replace(']', '',$dados);
$dados3 = explode('{',$dados2);
$dados4 = str_replace('}', '',$dados3);
$dados5 = [];
$keys = array_keys($dados4);
$size = count($dados4);
for($i = 0; $i < $size; $i++){
    $key   = $keys[$i];
    $value = $dados4[$key];
    if($value !== ''){
        $dados5[$i] = explode(',',$value);
    }
}

$keys = array_keys($dados5);
$size = count($dados5);

date_default_timezone_set('America/Sao_Paulo');
$someArray = array();

function converte_data($date) {
    return substr($date,6,4).'-'.substr($date,3,2).'-'.substr($date,0,2).' 00:00:00';
}
$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$msg_grava_cad="";

if($size === 1) {

    for($i = 0; $i < $size; $i++) {

        $key = $keys[$i];
        $value = $dados5[$key];

        $matriculax = explode(':', $value[1]);
        $empregadorx = explode(':', $value[7]);
        $totalx = explode(':', $value[10]);
        $tipox = explode(':', $value[4]);
        $usuariox = explode(':', $value[8]);
        $mesx = explode(':', $value[9]);

        $matriculax = explode(':', $value[1]);
        $_matricula = str_replace('"', '', $matriculax[1]);

        $empregadorx = explode(':', $value[7]);
        $Cod_empregador = str_replace('"', '', $empregadorx[1]);
        $Cod_empregador = intval($Cod_empregador);

        $totalx = explode(':', $value[10]);
        $_valor = str_replace('"', '', $totalx[1]);

        $tipox = explode(':', $value[4]);
        $tipoy = str_replace('"', '', $tipox[1]);

        $usuariox = explode(':', $value[8]);
        $_funcionario = str_replace('"', '', $usuariox[1]);
        $_funcionario = intval($_funcionario);

        $mesx = explode(':', $value[9]);
        $_mes = str_replace('"', '', $mesx[1]);

        $_mes = somames_gravar($_mes);

        $_hora = date('H:i:s');
        $_data = date('Y-m-d') . ' 00:00:00';
        $_tipo = '';
        $_situacao = 1;
        $_obs = '';
        $_result = '';
        $_parcela = '';

        if ($tipoy === "0439") {      //farmacia não descontado
            $_convenio = 48;
        } elseif ($tipoy === "0354") { //compras não descontado
            $_convenio = 47;
        } elseif ($tipoy === "0495") {//unimed não descontado
            $_convenio = 136;
        }

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


        $arr = array('associado' => $_matricula, 'convenio' => $_convenio, 'valor' => $_valor, 'data' => $_data, 'hora' => $_hora, 'mes' => $_mes, 'empregador' => $Cod_empregador);
        $someArray["data"][1] = array_map("utf8_encode", $arr);

    }


}else{

    for($i = 0; $i < $size; $i++){
        $key   = $keys[$i];
        $value = $dados5[$key];

        $matriculax  = explode(':',$value[1]);
        $empregadorx = explode(':',$value[7]);
        $totalx      = explode(':',$value[10]);
        $tipox       = explode(':',$value[4]);
        $usuariox    = explode(':',$value[8]);
        $mesx        = explode(':',$value[9]);

        $matriculax      = explode(':',$value[1]);
        $_matricula      =  str_replace('"','',$matriculax[1]);

        $empregadorx     = explode(':',$value[7]);
        $Cod_empregador  =  str_replace('"','',$empregadorx[1]);
        $Cod_empregador  = intval($Cod_empregador);

        $totalx          = explode(':',$value[10]);
        $_valor          = str_replace('"','',$totalx[1]);

        $tipox           = explode(':',$value[4]);
        $tipoy           = str_replace('"','',$tipox[1]);

        $usuariox        = explode(':',$value[8]);
        $_funcionario    = str_replace('"','',$usuariox[1]);
        $_funcionario    = intval($_funcionario);

        $mesx            = explode(':',$value[9]);
        $_mes            = str_replace('"','',$mesx[1]);

        $_mes = somames_gravar($_mes);

        $_hora          = date('H:i:s');
        $_data          = date('Y-m-d').' 00:00:00';
        $_tipo          = '';
        $_situacao      = 1;
        $_obs           = '';
        $_result        = '';

        if($tipoy === "0439"){      //farmacia não descontado
            $_convenio = 48;
        }elseif($tipoy === "0354"){ //compras não descontado
            $_convenio = 47;
        }elseif($tipoy === "0495") {//unimed não descontado
            $_convenio = 136;
        }

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
        $stmt->bindParam(':parcela', $_result, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $_tipo, PDO::PARAM_STR);
        $stmt->bindParam(':id_situacao', $_situacao, PDO::PARAM_INT);

        $stmt->execute();

        $arr = array('associado' =>$_matricula,'convenio'=>$_convenio,'valor'=> $_valor,'data'=> $_data,'hora'=> $_hora,'mes'=> $_mes,'empregador'=> $Cod_empregador);
        $someArray["data"][$i] = array_map("utf8_encode",$arr);

    }
}
$resultado = $someArray;
$resultadow = json_encode($someArray);
echo json_encode($someArray);