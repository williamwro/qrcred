<?PHP
include 'Adm/php/banco.php';
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if(isset($_GET['mes_atual'])){
    $mes_atual = $_GET['mes_atual'];
}else if(isset($_POST['mes_atual'])){
    $mes_atual = $_POST['mes_atual'];
}
if(isset($_GET['cod_convenio'])){
    $cod_convenio = $_GET['cod_convenio'];
}else if(isset($_POST['cod_convenio'])){
    $cod_convenio = $_POST['cod_convenio'];
}
//$mes_atual = 'OUT/2017';
//$cod_convenio = 99;

$item  = 0;
$total = 0;
$someArray = array();
$query = "SELECT estornos.lancamento, 
                 estornos.associado AS matricula, 
                 estornos.valor, 
                 estornos.data, 
                 estornos.hora, 
                 estornos.mes, 
                 empregador.nome AS empregador, 
                 empregador.id AS codigo_empregador, 
                 convenio.razaosocial AS convenio, 
                 convenio.codigo AS cod_convenio, 
                 associado.nome AS associado, 
                 estornos.funcionario, 
                 estornos.parcela, 
                 estornos.descricao,
                 estornos.data_estorno,
                 to_char(estornos.hora_estorno, 'HH24:MI') as hora_estorno,
                 associado.id_divisao,
                 divisao.nome as nome_divisao,
                 estornos.data_fatura
           FROM sind.divisao 
      RIGHT JOIN (sind.associado 
      RIGHT JOIN (sind.empregador 
      RIGHT JOIN (sind.convenio 
      RIGHT JOIN sind.estornos 
      ON convenio.codigo = estornos.convenio) 
      ON empregador.id = estornos.empregador) 
      ON associado.codigo = estornos.associado AND associado.empregador = estornos.empregador)
      ON divisao.id_divisao = associado.id_divisao
      WHERE convenio.codigo = " . $cod_convenio . " 
      AND estornos.mes = '" . $mes_atual . "' 
      AND convenio.desativado = false 
      ORDER BY estornos.lancamento DESC";

$sql_conv_vendas = $pdo->query($query);

while($row_vendas = $sql_conv_vendas->fetch()) {

    $someArray['data'][] = array_map("utf8_encode",$row_vendas);

}
echo json_encode($someArray);

