<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $query = "SELECT ASSOCIADO.Codigo, ASSOCIADO.Nome, ASSOCIADO.Empregador, C_CARTAOASSOCIADO.cod_verificacao, C_CARTAOASSOCIADO.data_pedido, C_CARTAOASSOCIADO.data_entrega, C_CARTAOASSOCIADO.id
FROM ASSOCIADO RIGHT JOIN C_CARTAOASSOCIADO ON (ASSOCIADO.Empregador = C_CARTAOASSOCIADO.empregador) AND (ASSOCIADO.Codigo = C_CARTAOASSOCIADO.cod_associado)
WHERE (((C_CARTAOASSOCIADO.cod_situacaocartao)=4))
ORDER BY C_CARTAOASSOCIADO.id;";

    $someArray = array();
    $i=1;
    $sql = $pdo->query($query);

    while($row = $sql->fetch()) {

        $someArray["data"][]  = array_map("utf8_encode",$row);
        $i++;
    }
    $aux = json_encode($someArray);
    echo json_encode($someArray);