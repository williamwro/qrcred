<?PHP
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$tem_cadastro_conta = false;
$ultimo_codigo = '';
$mes = '';
$controle_loop = 0;
$contador = 1;
$numero_inicio=0;
foreach($_POST['data'] as $nome_campo => $valor){
    $numero_inicio = $nome_campo;
    break;
}
$std = new stdClass();

//$_valorx        = $arr['valor'] ? str_replace('.','',$arr['valor']) : "";
//$_valor         = $arr['valor'] ? str_replace(',','.',$_valorx) : "";

$sql = "SELECT DISTINCT conta.associado, 
                   conta.valor, 
                   empregador.abreviacao, 
                   conta.lancamento, 
                   conta.data, 
                   conta.mes, 
                   conta.parcela, 
                   empregador.Id, 
                   empregador.nome, 
                   usuarios.username, 
                   associado.nome AS nome_associado, 
                   convenio.razaosocial, 
                   convenio.nomeFantasia,
                   conta.hora,
                   conta.descricao,
                   situacao_conta.descri as situacao
              FROM sind.convenio RIGHT JOIN 
                   (sind.associado RIGHT JOIN 
                   (sind.usuarios RIGHT JOIN 
                   (sind.empregador RIGHT JOIN 
                   (sind.situacao_conta RIGHT JOIN
                   sind.conta ON 
                   conta.id_situacao = situacao_conta.id_situacao) ON
                   empregador.Id = conta.empregador) ON 
                   usuarios.codigo = conta.funcionario) ON 
                   associado.codigo = conta.associado AND associado.empregador = conta.empregador) ON 
                   convenio.codigo = conta.convenio
            WHERE convenio.razaosocial = '" . $_POST["data"][$numero_inicio]["nome_convenio"] . "' AND 
                  conta.valor          =  " . $_POST["data"][$numero_inicio]['valor'] . "  AND
                  conta.data           = '" . $_POST["data"][$numero_inicio]["data"] . "' AND
                  conta.hora           = '" . $_POST["data"][$numero_inicio]["hora"] . "' AND
                  conta.descricao      = '" . $_POST["data"][$numero_inicio]["descricao"] . "' AND
                  conta.associado      = '" . $_POST["data"][$numero_inicio]["associado"] . "';";
$statment = $pdo->prepare($sql);
$statment->execute();
$result = $statment->fetchAll();
foreach ($result as $row) {
    $sub_array = array();
    $sub_array["registro"]      = $row['lancamento'];
    $sub_array["nome_convenio"] = $row['razaosocial'];
    $sub_array["valor"]         = $row['valor'];
    $sub_array["data"]          = date('d/m/Y', strtotime($row['data']));
    $sub_array["hora"]          = substr($row['hora'], -8);
    $sub_array["mes"]           = $row['mes'];
    if ($row['parcela'] == null) {
        $sub_array["parcela"]   = '';
    } else {
        $sub_array["parcela"]   = $row['parcela'];
    }
    $sub_array["descricao"]     = $row['descricao'];
    $someArray['data'][] = array_map("utf8_encode", $sub_array);
}
$pp = json_encode($someArray);
echo $pp;