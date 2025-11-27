<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mes = isset($_POST["mes"]) ? $_POST["mes"] : "";
$data_inicial = isset($_POST["data_inicial"]) ? $_POST["data_inicial"] : "";
$data_final = isset($_POST["data_final"]) ? $_POST["data_final"] : "";
$divisao = isset($_POST["divisao"]) ? $_POST["divisao"] : "";

$query = "SELECT assoc.nome, emp.abreviacao,
          div.nome AS estabelecimento,
          TO_CHAR(c.data, 'DD/MM/YYYY') AS data,
          c.mes,
          COALESCE(c.valor, 0) AS total,
          conv.razaosocial,
          conv.nomefantasia
          FROM sind.associado assoc
          INNER JOIN sind.empregador emp ON emp.id = assoc.empregador
          INNER JOIN sind.divisao div ON div.id_divisao = assoc.id_divisao
          INNER JOIN sind.conta c ON c.associado = assoc.codigo AND c.empregador = assoc.empregador
          INNER JOIN sind.convenio conv ON conv.codigo = c.convenio
          WHERE assoc.id_divisao = :divisao";

$params = array(':divisao' => $divisao);

if (!empty($mes)) {
    $query .= " AND c.mes = :mes";
    $params[':mes'] = $mes;
}

if (!empty($data_inicial) && !empty($data_final)) {
    $query .= " AND c.data BETWEEN :data_inicial AND :data_final";
    $params[':data_inicial'] = $data_inicial;
    $params[':data_final'] = $data_final;
}

$query .= " ORDER BY assoc.nome ASC, c.data DESC";

$someArray = array();
$stmt = $pdo->prepare($query);
$stmt->execute($params);

while($row = $stmt->fetch()) {
    $sub_array = array();
   
    $sub_array["nome"] = $row["nome"];
    $sub_array["abreviacao"] = $row["abreviacao"];
    $sub_array["mes"] = $row["mes"];
    $sub_array["data"] = $row["data"];
    $sub_array["total"] = $row["total"];
    $sub_array["razaosocial"] = $row["razaosocial"];
    $sub_array["nomefantasia"] = $row["nomefantasia"];
    $sub_array["estabelecimento"] = $row["estabelecimento"];

    $someArray["data"][] = $sub_array;
}

$aux = json_encode($someArray);
echo $aux;
