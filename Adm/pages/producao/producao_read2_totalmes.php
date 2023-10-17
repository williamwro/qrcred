<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao = $_POST['divisao'];

$query = "SELECT CASE WHEN left(conta.mes,3) = 'JAN' THEN right(conta.mes,4) || '-' ||'01-01'
                      WHEN left(conta.mes,3) = 'FEV' THEN right(conta.mes,4) || '-' ||'02-01'
                      WHEN left(conta.mes,3) = 'MAR' THEN right(conta.mes,4) || '-' ||'03-01'
                      WHEN left(conta.mes,3) = 'ABR' THEN right(conta.mes,4) || '-' ||'04-01'
                      WHEN left(conta.mes,3) = 'MAI' THEN right(conta.mes,4) || '-' ||'05-01'
                      WHEN left(conta.mes,3) = 'JUN' THEN right(conta.mes,4) || '-' ||'06-01'
                      WHEN left(conta.mes,3) = 'JUL' THEN right(conta.mes,4) || '-' ||'07-01'
                      WHEN left(conta.mes,3) = 'AGO' THEN right(conta.mes,4) || '-' ||'08-01'
                      WHEN left(conta.mes,3) = 'SET' THEN right(conta.mes,4) || '-' ||'09-01'
                      WHEN left(conta.mes,3) = 'OUT' THEN right(conta.mes,4) || '-' ||'10-01'
                      WHEN left(conta.mes,3) = 'NOV' THEN right(conta.mes,4) || '-' ||'11-01'
                      WHEN left(conta.mes,3) = 'DEZ' THEN right(conta.mes,4) || '-' ||'12-01'
               END AS data,
                      mes,
                     to_char(Sum(conta.valor),'99999999999999999D99') AS valor
                    FROM sind.convenio 
              INNER JOIN sind.conta 
                      ON convenio.codigo = conta.convenio
              INNER JOIN sind.empregador
			  		  ON conta.empregador = empregador.id
                   WHERE (convenio.cobranca = true AND empregador.divisao = ".$divisao.")
                GROUP BY conta.mes
                ORDER BY data;";

$someArray = array();
$statment = $pdo->query($query);
while($row = $statment->fetch()) {
    $sub_array = array();
    $sub_array["data"]   = $row["data"];
    $sub_array["mes"]   = $row["mes"];
    $sub_array["valor"] = (real)$row["valor"];

    $someArray["data"][] = array_map("utf8_encode",$sub_array);
}
$aux = json_encode($someArray);
echo $aux;