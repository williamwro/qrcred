<?PHP
error_reporting(E_ALL ^ E_NOTICE);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

require "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$_limite = 0;
$_limite_hidden = 0;

$_usuario_cod       = $_POST['usuario_cod'];
$_divisao           = isset($_POST['divisao']) ? $_POST['divisao'] : 0;
$_matricula         = isset($_POST['C_matricula_antecipacao']) ? $_POST['C_matricula_antecipacao'] : "";
$_empregador        = isset($_POST['C_id_empregador_antecipacao']) ? $_POST['C_id_empregador_antecipacao'] : 0;
$_mes               = isset($_POST['C_mes']) ? $_POST['C_mes'] : "";
$valor_pedido = str_replace('.','',$_POST['C_valor_antecipacao']);
$valor_pedido = str_replace(',','.',$valor_pedido);
$_valor             = $valor_pedido;
$data               = new DateTime();
if($_POST['C_aprovado'] == "1"){
    $_data_aprovacao    = null;
}else {
    $_data_aprovacao    = $data->format('Y-m-d');
}
if($_POST['C_aprovado'] == "2"){
    $_aprovado      = true;
}else if($_POST['C_aprovado'] == "3"){
    $_aprovado      = false;
}else{
    $_aprovado      = null;
}

$stmt = new stdClass();

$msg_grava_cad="";

    $sql = "UPDATE sind.antecipacao SET ";
    $sql .= "aprovado = :aprovado, ";
    $sql .= "data_aprovacao = :data_aprovacao ";
    $sql .= "WHERE matricula = '" . $_matricula ."' ";
    $sql .= "AND empregador = " . $_empregador ." ";
    $sql .= "AND mes = '" . $_mes ."' ";
    $sql .= "AND valor = '" . $_valor  ."'";

    $msg_grava_cad = "atualizado";
    try {

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':aprovado', $_aprovado, PDO::PARAM_BOOL);       //1
        $stmt->bindParam(':data_aprovacao', $_data_aprovacao, PDO::PARAM_STR);       //1
        
        $stmt->execute();

        $data2      = new DateTime();
        $data       = $data2->format('Y-m-d h:i:s');
        
        echo $msg_grava_cad;

    } catch (PDOException $erro) {
        if($erro->getCode() === '42501'){
            $msg_grava_cad = "Seu usuario não tem permissão!";
        }else{
            $msg_grava_cad = "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
        }
        echo $msg_grava_cad;
    }