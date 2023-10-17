<?PHP
    header("Content-type: application/json");
    include "Adm/php/banco.php";
    include "Adm/php/funcoes.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_POST['convenio'])) {
    $convenio = $_POST['convenio'];
}else{
    $convenio = "";
}
if(isset($_POST['mes'])) {
    $mes = $_POST['mes'];
}else{
    $mes = "";
}
$std = new stdClass();
$someArray = array();

$query = $pdo->query("SELECT DISTINCT associado.codigo AS associado, associado.nome,convenio.nomefantasia,
                                      convenio.razaosocial, conta.valor, conta.mes,convenio.cnpj,
                                      conta.parcela, conta.data as dia, conta.hora, conta.convenio,
                                      empregador.id AS id_empregador, empregador.nome AS nome_empregador, 
                                      divisao.id_divisao, divisao.nome AS nome_divisao, conta.uri_cupom,
                                      conta.lancamento
                                 FROM sind.divisao 
                           INNER JOIN (sind.empregador 
                           INNER JOIN ((sind.tipoconvenio 
                           INNER JOIN sind.convenio 
                                   ON tipoconvenio.codigo = convenio.tipo OR convenio.tipo = 0) 
                           INNER JOIN (sind.associado 
                           INNER JOIN sind.conta 
                                   ON associado.codigo = conta.associado AND associado.empregador = conta.empregador) 
                                   ON convenio.codigo = conta.convenio) 
                                   ON (conta.empregador = empregador.id) 
                                  AND (empregador.id = associado.empregador)) 
                                   ON divisao.id_divisao = empregador.divisao
                                WHERE conta.convenio = ".$convenio." AND conta.mes = '".$mes."' ORDER BY associado.nome ASC, conta.lancamento ASC");
while($row = $query->fetch()) {
    $someArray[] = array_map("utf8_encode",$row);
}
echo json_encode($someArray);