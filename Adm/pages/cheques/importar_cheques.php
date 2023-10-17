<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);
header("Content-type: application/json");
include "../../php/banco.php";
include "NumeroPorExtenso.php";
$extenso = new NumeroPorExtenso;
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$total = 0;
$prolabore = 0;
$valor_prolabore = 0;
$total_liquido = 0;
$total_liquido2 = 0.00;
$extenso_aux = "";
$arrx = "";
$stmt = new stdClass();
$mes = $_POST["mes"];
$stmt = $pdo->prepare("SELECT mes FROM sind.pagamentos2 WHERE mes = ?");
$stmt->execute([$mes]);
$arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(!$arr) {
    $query = "SELECT nome_convenio,sum(valor) as total,prolabore,divisao,
                     cod_convenio,id_categoria_recibo, categoria_recibo, mes, nomefantasia
                FROM sind.qextrato 
               WHERE mes = '" . $_POST["mes"] . "' AND cobranca = true
            GROUP BY nome_convenio,divisao,prolabore,cod_convenio,id_categoria_recibo, categoria_recibo, mes, nomefantasia 
            ORDER BY nome_convenio";
    $someArray = array();
    $statment = $pdo->query($query);
    $enviado = 0;
    $pago = 0;
    $data_pgto = null;
    $prtch = false;
    $data2 = new DateTime();
    $data = $data2->format('Y-m-d');
    while ($row = $statment->fetch()) {
        $sub_array = array();
        if ($row["total"] !== null) {
            $total = number_format($row["total"], 2, '.', '');
        } else {
            $total = 0;
        }
        if ($row["prolabore"] !== null) {
            $prolabore = number_format($row["prolabore"], 2, '.', '');
        } else {
            $prolabore = 0;
        }

        $valor_prolabore = number_format(($total * $prolabore) / 100, 2, '.', '');
        $total_cobranca = number_format(($valor_prolabore), 2, '.', '');
        $total_liquido = number_format($total - $total_cobranca, 2, '.', '');
        $total_liquido2 = $total - $total_cobranca;
        $extenso_aux = htmlentities($extenso->converter($total_liquido2));

        $sql = "INSERT INTO sind.pagamentoS2(";
        $sql .= "id_convenio, mes, valor, alicota, val_alicota, liquido, extenso, data,";
        $sql .= "id_categoria_recibo, prtch, razaosocial, nomefantasia, categoria) VALUES(";
        $sql .= ":id_convenio, ";
        $sql .= ":mes, ";
        $sql .= ":valor, ";
        $sql .= ":alicota, ";
        $sql .= ":val_alicota, ";
        $sql .= ":liquido, ";
        $sql .= ":extenso, ";
        $sql .= ":data, ";
        $sql .= ":id_categoria_recibo, ";
        $sql .= ":prtch, ";
        $sql .= ":razaosocial, ";
        $sql .= ":nomefantasia, ";
        $sql .= ":categoria)";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':id_convenio', $row["cod_convenio"], PDO::PARAM_INT);
        $stmt->bindParam(':mes', $_POST["mes"], PDO::PARAM_STR);
        $stmt->bindParam(':valor', $total, PDO::PARAM_STR);
        $stmt->bindParam(':alicota', $prolabore, PDO::PARAM_STR);
        $stmt->bindParam(':val_alicota', $valor_prolabore, PDO::PARAM_STR);
        $stmt->bindParam(':liquido', $total_liquido, PDO::PARAM_STR);
        $stmt->bindParam(':extenso', $extenso_aux, PDO::PARAM_STR);
        $stmt->bindParam(':data', $data, PDO::PARAM_STR);
        $stmt->bindParam(':id_categoria_recibo', $row["id_categoria_recibo"], PDO::PARAM_INT);
        $stmt->bindParam(':prtch', $prtch, PDO::PARAM_BOOL);
        $stmt->bindParam(':razaosocial', $row["nome_convenio"], PDO::PARAM_STR);
        $stmt->bindParam(':nomefantasia', $row["nomefantasia"], PDO::PARAM_STR);
        $stmt->bindParam(':categoria', $row["categoria_recibo"], PDO::PARAM_STR);


        $stmt->execute();
        $arrx = array('resultado' => 'importado');
    }
}else{
    $arrx = array('resultado' => 'jaimportou');
}

$someArray = array_map("utf8_encode",$arrx);

$aux = json_encode($someArray);
echo $aux;