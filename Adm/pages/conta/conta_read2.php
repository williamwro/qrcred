<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $query = "SELECT associado.codigo, associado.nome, c_cartaoassociado.cod_verificacao, c_cartaoassociado.cod_situacaocartao, c_situacaocartao.descri AS descri_situacao, c_cartaoassociado.motivo_cancela, c_cartaoassociado.id
FROM sind.associado RIGHT JOIN (sind.c_situacaocartao RIGHT JOIN sind.c_cartaoassociado ON c_situacaocartao.id = c_cartaoassociado.cod_situacaocartao) ON associado.codigo = c_cartaoassociado.cod_associado ORDER BY associado.nome";

    $someArray = array();

    $statment = $pdo->prepare($query);

    $statment->execute();

    $result = $statment->fetchAll();

    $data = array();

    $linhas_filtradas = $statment->rowCount();

    foreach ($result as $row){
        $sub_array = array();

        $sub_array["codigo"]                = $row["codigo"];
        $sub_array["nome"]                  = $row["nome"];
        $sub_array["cod_verificacao"]       = $row["cod_verificacao"];
        $sub_array["cod_situacaocartao"]    = $row["cod_situacaocartao"];
        $sub_array["descri_situacao"]       = $row["descri_situacao"];
        $sub_array["motivo_cancela"]        = $row["motivo_cancela"];
        $sub_array["botao"]                 = '<button type="button" name="update" id="'.$row["cod_verificacao"].'" class="btn btn-warning btn-xs update">Alterar</button>';
        $sub_array["botaosenha"]            = '<button type="button" name="btnsenha" id="'.$row["cod_verificacao"].'" class="btn btn-facebook btn-xs btnsenha">Senha</button>';

        $someArray["data"][] = array_map("utf8_encode",$sub_array);

    }
    $teste = json_encode($someArray);
    echo json_encode($someArray);