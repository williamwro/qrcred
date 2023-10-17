<?PHP
require 'Adm/php/banco.php';
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if (isset($_POST['cod_carteira_login'])){
    $cod_cartao = $_POST['cod_carteira_login'];
}else{
    $cod_cartao = $_POST['cod_carteira'];
}
if (isset($_POST['razaosocial'])){
    $razao_social = $_POST['razaosocial'];
}else{
    $razao_social = "";
}
//  1 = liberado
if (isset($_POST['m_p'])) {
    $m_p = $_POST['m_p'];               //  2 = bloqueado
}else{
    $m_p = "";
}

$limite              = '';                          //  3 = cancelado
$parcelas_permitidas = 0;                           //  4 = produção
$std = new stdClass();                              //  5 = segunda via
$contador=0;
$l_c=0;

$sql_associado = $pdo->query("SELECT associado.codigo, 
                                                  associado.nome, 
                                                  associado.empregador, 
                                                  associado.limite, 
                                                  associado.salario, 
                                                  associado.parcelas_permitidas, 
                                                  associado.ultimo_mes, 
                                                  c_cartaoassociado.cod_situacaocartao, 
                                                  c_cartaoassociado.cod_verificacao,
                                                  c_cartaoassociado.cod_situacao2,
                                                  empregador.nome as nome_empregador
                                             FROM sind.associado 
                                       INNER JOIN sind.c_cartaoassociado 
									   		   ON associado.codigo = c_cartaoassociado.cod_associado 
									   INNER JOIN sind.empregador 
									   		   ON associado.empregador = empregador.id
                                              AND associado.empregador = c_cartaoassociado.empregador
                                            WHERE c_cartaoassociado.cod_verificacao='".$cod_cartao."'");
while($row_assoc = $sql_associado->fetch()) {
    $contador=1;
    $parcelas_permitidas = $row_assoc["parcelas_permitidas"];
    $empregador = $row_assoc["empregador"];
    if($parcelas_permitidas == 0 or $parcelas_permitidas == null) {
        $parcelas_permitidas = $_POST['parcelas_a_exibir']; // parcelas dos convenio
    }else {
        $parcelas_permitidas = $row_assoc["parcelas_permitidas"]; // parcelas do associado
    }
    if ($row_assoc['cod_situacaocartao'] == "1" or $row_assoc['cod_situacaocartao'] == "4" or $row_assoc['cod_situacaocartao'] == "5" or $row_assoc['cod_situacaocartao'] == "6" or $row_assoc['cod_situacaocartao'] == "7") {

        $std->situacao            = 1; //1 = liberado
        $std->cod_situacao2       = $row_assoc['cod_situacao2']; //2 = disponivel
        $std->nome                = utf8_encode($row_assoc['nome']);
        $std->cod_cart            = $row_assoc['cod_verificacao'];
        $std->matricula           = $row_assoc['codigo'];
        $std->empregador          = $row_assoc['empregador'];
        $std->nome_empregador     = $row_assoc['nome_empregador'];
        $std->ultimo_mes          = $row_assoc['ultimo_mes'];
        $std->parcelas_permitidas = $parcelas_permitidas;
        $std->razaosocial         = $razao_social;
        $std->mes_desconto        = $m_p;
        $l_c = 0;
        $sql_debito_associado = $pdo->query("SELECT SUM(valor) AS valor1, mes FROM sind.conta WHERE mes='" . $m_p . "' AND associado='" . $row_assoc['codigo'] . "' AND empregador = " . $empregador . " GROUP BY mes");
        while ($row_debito = $sql_debito_associado->fetch()) {
            $l_c    = floatval($row_debito["valor1"]);
        }
        $limite = floatval($row_assoc["limite"]);
        $limite_credito = number_format((($l_c * -1) + $limite), 2, '.', ',');// Valor DISPONIVEL para compras

        $limite_credito = str_replace(",","",$limite_credito);

        $std->limite = $limite_credito;
        $std->limite_credito_     = $l_c;

    }else if ($row_assoc['cod_situacaocartao'] == "8") {

        $std->situacao = 8; //1 = liberado
        $std->cod_situacao2 = $row_assoc['cod_situacao2']; //2 = disponivel
        $std->nome = utf8_encode($row_assoc['nome']);
        $std->cod_cart = $row_assoc['cod_verificacao'];
        $std->matricula = $row_assoc['codigo'];
        $std->empregador = $row_assoc['empregador'];
        $std->nome_empregador = $row_assoc['nome_empregador'];
        $std->ultimo_mes = $row_assoc['ultimo_mes'];
        $std->parcelas_permitidas = $parcelas_permitidas;
        $std->razaosocial = $razao_social;
        $std->mes_desconto = $m_p;
        $l_c = 0;
        $sql_debito_associado = $pdo->query("SELECT SUM(valor) AS valor1, mes FROM sind.conta WHERE mes='" . $m_p . "' AND associado='" . $row_assoc['codigo'] . "' AND empregador = " . $empregador . " GROUP BY mes");
        while ($row_debito = $sql_debito_associado->fetch()) {
            $l_c = floatval($row_debito["valor1"]);
        }
        $limite = floatval($row_assoc["limite"]);
        $limite_credito = number_format((($l_c * -1) + $limite), 2, '.', ',');// Valor DISPONIVEL para compras

        $limite_credito = str_replace(",", "", $limite_credito);

        $std->limite = $limite_credito;
        $std->limite_credito_ = $l_c;

    } else {

        $std->situacao            = 0; //0 = bloqueado,
        $std->cod_situacao2       = $row_assoc['cod_situacao2']; //2 = disponivel
        $std->nome                = $row_assoc['nome'];
        $std->cod_cart            = $row_assoc['cod_verificacao'];
        $std->matricula           = $row_assoc['codigo'];
        $std->empregador          = $row_assoc['empregador'];
        $std->parcelas_permitidas = $parcelas_permitidas;
        $std->razaosocial         = $razao_social;
        $std->limite              = 0;
        $std->mes_desconto        = $m_p;
        $std->limite_credito_     = $l_c;

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
}
//ATUALIZA O CAMPO MES_CORRENTE COM O MES ATUAL SEMPRE QUE CLICAR EM LOCALIZAR
$sql = "UPDATE sind.mes_corrente SET abreviacao = '". $m_p . "'";
$count = $pdo->exec($sql);
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
}
echo json_encode($std);