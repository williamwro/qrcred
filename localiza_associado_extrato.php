<?PHP
include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dia = date("d");
$dia = intval($dia);
$m = 1;
if ($dia >= 4) {
    $m_p = somames(date("m/Y"), $m + 1);
} else if ($dia >= 1 && $dia <= 3) {
    $m_p = somames(date("m/Y"), $m);
}
if (isset($_POST['autorizado'])){
    $autorizado = "sim";
}else{
    $autorizado = "nao";
}
if (isset($_POST['txtCartao'])){
    $cod_cartao = $_POST['txtCartao'];
}else{
    $cod_cartao = '';
}
if (isset($_POST['txtSenhaCartao'])) {
    $SenhaCartao = $_POST['txtSenhaCartao'];
} else {
    $SenhaCartao = 0;
}
//  1 = liberado
//if (isset($_POST['m_p'])) {
//    $m_p = $_POST['m_p'];               //  2 = bloqueado
//}else{
//    $m_p = "";
//}
$limite              = '';                          //  3 = cancelado
$parcelas_permitidas = 0;                           //  4 = produção
$std = new stdClass();                              //  5 = segunda via
$contador=0;
$l_c=0;
$temdebito=false;

$sql_associado = $pdo->query("SELECT associado.codigo, associado.nome, associado.empregador, 
                                             associado.limite, associado.salario, associado.parcelas_permitidas, 
                                             c_cartaoassociado.cod_situacaocartao, c_cartaoassociado.cod_verificacao 
                                        FROM sind.associado 
                                       INNER JOIN sind.c_cartaoassociado 
                                          ON associado.codigo = c_cartaoassociado.cod_associado 
                                       INNER JOIN sind.empregador 
                                          ON associado.empregador = empregador.id
                                         AND associado.empregador = c_cartaoassociado.empregador
                                       WHERE ((c_cartaoassociado.cod_verificacao)='".$cod_cartao."')");
while($row_assoc = $sql_associado->fetch()) {
    // VERIFICA SE A SENHA ESTA CORRETA ******
    $sql_pede_senhax = $pdo->query("SELECT * FROM sind.c_senhaassociado WHERE cod_associado = '" . $row_assoc['codigo'] . "' and id_empregador=".$row_assoc['empregador']." and senha='".$SenhaCartao."'")->fetch();
    $contador=1;
    if ($sql_pede_senhax) {
        //SENHA CORRETA
        $parcelas_permitidas = $row_assoc["parcelas_permitidas"];
        if($parcelas_permitidas == 0 or $parcelas_permitidas == null) {
            $parcelas_permitidas = 20; // parcelas dos convenio
        }else {
            $parcelas_permitidas = $row_assoc["parcelas_permitidas"]; // parcelas do associado
        }
        if ($row_assoc['cod_situacaocartao'] == "1" or $row_assoc['cod_situacaocartao'] == "5" or $row_assoc['cod_situacaocartao'] == "4" or $row_assoc['cod_situacaocartao'] == "2" or $row_assoc['cod_situacaocartao'] == "6" or $row_assoc['cod_situacaocartao'] == "7") {

            $std->situacao            = 1; //1 = liberado
            $std->nome                = utf8_encode($row_assoc['nome']);
            $std->cod_cart            = $row_assoc['cod_verificacao'];
            $std->matricula           = $row_assoc['codigo'];
            $std->empregador          = $row_assoc['empregador'];
            $std->parcelas_permitidas = $parcelas_permitidas;
            $std->mes_desconto        = $m_p;
            $std->autorizado          = $autorizado;
            $std->senhacartao         = $SenhaCartao;

            $sql_debito_associado = $pdo->query("SELECT SUM(valor) AS valor1, mes FROM sind.conta WHERE mes='" . $m_p . "' AND associado='" . $row_assoc['codigo'] . "' GROUP BY mes");
            while ($row_debito = $sql_debito_associado->fetch()) {
                $l_c    = floatval($row_debito["valor1"]);
                $temdebito=true;
            }
            if ($temdebito == false) {
                $l_c = 0;
            }
            $limite = floatval($row_assoc["limite"]);
            $limite_credito = number_format((($l_c * -1) + $limite), 2, '.', ',');// Valor DISPONIVEL para compras

            $limite_credito = str_replace(",","",$limite_credito);

            $std->limite = $limite_credito;
            $std->limite_credito_     = $l_c;

        } else {

            $std->situacao            = 0; //0 = bloqueado,
            $std->nome                = $row_assoc['nome'];
            $std->cod_cart            = $row_assoc['cod_verificacao'];
            $std->matricula           = $row_assoc['codigo'];
            $std->empregador          = $row_assoc['empregador'];
            $std->parcelas_permitidas = $parcelas_permitidas;
            $std->limite              = 0;
            $std->mes_desconto        = $m_p;
            $std->limite_credito_     = $l_c;
            $std->autorizado          = $autorizado;
        }
    }else{
        //3 = SENHA ERRADA,
        $std->situacao            = 3; 
        $std->nome                = '';
        $std->cod_cart            = '';
        $std->matricula           = '';
        $std->empregador          = 0;
        $std->parcelas_permitidas = 0;
        $std->razaosocial         = '';
        $std->limite              = 0;
        $std->parcelas_permitidas = 0;
        $std->mes_desconto        = $m_p;
        $std->limite_credito_     = 0;
        $std->autorizado          = $autorizado;

    }
}
if ($contador == 0) {

    $std->situacao            = 2; //2 = nao encontrado,
    $std->nome                = '';
    $std->cod_cart            = '';
    $std->matricula           = '';
    $std->empregador          = 0;
    $std->parcelas_permitidas = 0;
    $std->razaosocial         = '';
    $std->limite              = 0;
    $std->parcelas_permitidas = 0;
    $std->mes_desconto        = $m_p;
    $std->limite_credito_     = 0;
    $std->autorizado          = $autorizado;
}
echo json_encode($std);