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
$mes = '';
$controle_loop = 0;
if(isset($_POST["mes"])){
    $std = new stdClass();
    $mes = $_POST["mes"];
    $matricula = $_POST["matricula"];
    $sql = "SELECT conta.associado, 
                   conta.valor, 
                   empregador.abreviacao, 
                   conta.lancamento, 
                   conta.data, 
                   conta.mes, 
                   conta.parcela, 
                   empregador.id, 
                   empregador.nome, 
                   senha.username, 
                   associado.nome AS nome_associado, 
                   convenio.razaosocial, 
                   convenio.nomeFantasia,
                   conta.hora,
                   situacao_conta.descri as situacao
              FROM sind.convenio RIGHT JOIN 
                   (sind.associado RIGHT JOIN 
                   (sind.senha RIGHT JOIN 
                   (sind.empregador RIGHT JOIN 
                   (sind.situacao_conta RIGHT JOIN
                   sind.conta ON 
                   conta.id_situacao = situacao_conta.id_situacao) ON
                   empregador.id = conta.empregador) ON 
                   senha.codigo = conta.funcionario) ON 
                   associado.codigo = conta.associado) ON 
                   convenio.codigo = conta.convenio
             WHERE conta.mes = '".$mes."' AND conta.associado = '".$matricula."' AND conta.empregador = '".$empregador."';";
    $statment = $pdo->prepare($sql);
    $statment->execute();
    $result = $statment->fetchAll();
    foreach ($result as $row){

        $sub_array = array();

        $sub_array["registro"]         = $row['lancamento'];
        $sub_array["matricula"]        = $row['associado'];
        $sub_array["associado"]        = $row['nome_associado'];
        $sub_array["valor"]            = utf8_encode($row['valor']);
        $sub_array["data"]             =  date('d/m/Y', strtotime($row['data']));
        $sub_array["hora"]             =   substr($row['hora'],-8);
        $sub_array["mes"]              = $row['mes'];
        if ($row['parcela'] == null) {
            $sub_array["parcela"]      = '';
        }else{
            $sub_array["parcela"]      = $row['parcela'];
        }
        $sub_array["id_empregador"]    = $row['id'];
        $sub_array["nome_empregador"]  = $row['nome'];
        $sub_array["razaosocial"]      = $row['razaosocial'];
        $sub_array["nomeFantasia"]     = $row['nomeFantasia'];
        $sub_array["funcionario"]      = $row['username'];
        $sub_array["situacao"]         = $row['situacao'];
        $sub_array["botaoalterar"]     = '<button type="button" name="btnalterar" id="'.$row["lancamento"].'" class="btn btn-facebook btn-xs btnalterar">Alterar</button>';
        $sub_array["botaoexcluir"]     = '<button type="button" name="btnexcluir" id="'.$row["lancamento"].'" class="btn btn-facebook btn-xs btnexcluir">Excluir</button>';


        $someArray["data"][] = array_map("utf8_encode",$sub_array);

    }
    echo json_encode($someArray);
}