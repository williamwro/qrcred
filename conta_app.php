<?PHP
    include "Adm/php/banco.php";
    include "Adm/php/funcoes.php";
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST['matricula'])) {
    $matricula = $_POST['matricula'];
}else{
    $matricula = "";
}
if(isset($_POST['empregador'])) {
    $empregador = $_POST['empregador'];
}else{
    $empregador = null;
}
if(isset($_POST['mes'])) {
    $mes = $_POST['mes'];
}else{
    $mes = "";
}
    $std = new stdClass();
    $someArray = array();

    $query = $pdo->query("SELECT associado.codigo AS associado,associado.nome, 
                                 convenio.razaosocial,convenio.nomefantasia,conta.valor,conta.mes, 
                                 conta.parcela,conta.data as dia,conta.hora,convenio.cnpj,
                                 empregador.id AS id_empregador,empregador.nome AS nome_empregador, 
                                 divisao.id_divisao,divisao.nome AS nome_divisao,conta.uri_cupom
                            FROM sind.divisao 
                      INNER JOIN (sind.empregador 
                      INNER JOIN ((sind.tipoconvenio 
                      INNER JOIN sind.convenio 
                              ON tipoconvenio.codigo = convenio.tipo) 
                      INNER JOIN (sind.associado 
                      INNER JOIN sind.conta 
                              ON associado.codigo = conta.associado AND associado.empregador = conta.empregador) 
                              ON convenio.codigo = conta.convenio) 
                              ON (conta.empregador = empregador.id) 
                             AND (empregador.id = associado.empregador)) 
                              ON divisao.id_divisao = empregador.divisao
                           WHERE associado.codigo = '".$matricula."' AND associado.empregador = ".$empregador." AND conta.mes = '".$mes."' ORDER BY conta.lancamento ASC");
    while($row = $query->fetch()) {
        $someArray[] = array_map("utf8_encode",$row);
    }
    echo json_encode($someArray);