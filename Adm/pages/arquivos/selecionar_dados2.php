<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$mes        = $_POST["mes"];
$empregador = $_POST["empregador"];
$tipo       = $_POST["tipo"];
$divisao    = $_POST["divisao"];
$card1 = $_POST["card1"];
$card2 = $_POST["card2"];
$card3 = $_POST["card3"];
$card4 = $_POST["card4"];
$card5 = $_POST["card5"];
$card6 = $_POST["card6"];

if (isset($_POST["empregador"]) and $_POST["mes"] != "" and $tipo === "0" ) {
    $query = "SELECT SUM(conta.valor) AS total, convenio.tipo AS tipoconvenio, tipoconvenio.categoria as codrg, 
                    conta.associado, associado.nome, tipoconvenio.nome AS nome_tipo
            FROM sind.tipoconvenio 
      INNER JOIN (sind.convenio 
      INNER JOIN (sind.associado 
      INNER JOIN sind.conta 
              ON (associado.empregador = conta.empregador) 
             AND (associado.codigo = conta.associado)) 
              ON convenio.codigo = conta.convenio) 
              ON tipoconvenio.Codigo = convenio.tipo
           WHERE conta.mes = '" . $mes . "'
             AND conta.empregador = " . $empregador . "
             AND associado.id_divisao = " . $divisao . "
             AND associado.codigo <> '".$card1."' 
             AND associado.codigo <> '".$card2."' 
             AND associado.codigo <> '".$card3."'
             AND associado.codigo <> '".$card4."' 
             AND associado.codigo <> '".$card5."' 
             AND associado.codigo <> '".$card6."'
        GROUP BY convenio.tipo, conta.associado, associado.nome, tipoconvenio.nome, tipoconvenio.categoria 
        ORDER BY conta.associado,tipoconvenio.categoria";
}else{
    $query = "SELECT SUM(conta.valor) AS total, convenio.tipo AS tipoconvenio, tipoconvenio.categoria as codrg, 
                    conta.associado, associado.nome, tipoconvenio.nome AS nome_tipo
            FROM sind.tipoconvenio 
      INNER JOIN (sind.convenio 
      INNER JOIN (sind.associado 
      INNER JOIN sind.conta 
              ON (associado.empregador = conta.empregador) 
             AND (associado.codigo = conta.associado)) 
              ON convenio.codigo = conta.convenio) 
              ON tipoconvenio.Codigo = convenio.tipo
           WHERE conta.mes = '" . $mes . "'
             AND conta.empregador = " . $empregador . "
             AND convenio.tipo = ". $tipo ."
             AND associado.id_divisao = " . $divisao . "
             AND associado.codigo <> '".$card1."'
             AND associado.codigo <> '".$card2."'
             AND associado.codigo <> '".$card3."'
             AND associado.codigo <> '".$card4."' 
             AND associado.codigo <> '".$card5."'       
             AND associado.codigo <> '".$card6."'          
        GROUP BY convenio.tipo, conta.associado, associado.nome, tipoconvenio.nome, tipoconvenio.categoria 
        ORDER BY conta.associado,tipoconvenio.categoria";
}
$someArray = array();
$i=1;
$sql = $pdo->query($query);

while($row = $sql->fetch()) {

    $sub_array = array();
    $sub_array["associado"] = $row['associado'];
    $sub_array["nome"]      = trim($row['nome']);
    $sub_array["tipo"]      = $row['tipoconvenio'];
    $sub_array["nome_tipo"] = $row['nome_tipo'];
    $sub_array["codrg"]     = $row['codrg'];
    $sub_array["total"]     = $row['total'];

    $someArray[] = $sub_array;
}
$aux = json_encode($someArray);
echo json_encode($someArray);