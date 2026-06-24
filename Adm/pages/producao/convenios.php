<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";

$mes          = $_POST['mes'];
$divisao      = $_POST['divisao'];
$cod_convenio = isset($_POST['cod_convenio']) && $_POST['cod_convenio'] !== '' ? $_POST['cod_convenio'] : null;

$someArray = array();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$query = "SELECT E.codigo,
                 E.razaosocial, 
                 E.nomefantasia,
                 E.endereco, 
                 E.bairro, 
                 E.telefone,  
                 E.data_cadastro,
                 E.cidade,  
                 E.cnpj,  
                 E.email,    
                 E.contato,
                 E.cel,
                 C.mes, 
                 Sum(C.valor) AS total, 
                 E.cobranca, 
                 E.desativado
            FROM sind.conta AS C 
      INNER JOIN sind.empregador as S ON C.empregador = S.id
	  INNER JOIN sind.divisao as D ON S.id_divisao = D.id_divisao
	  INNER JOIN sind.convenio AS E ON C.convenio = E.codigo
           WHERE C.mes = '".$mes."'
             AND D.id_divisao = ".$divisao."
             AND (C.aprovado = true OR C.aprovado IS NULL)
             ".($cod_convenio !== null ? "AND E.codigo = :cod_convenio " : "")."
        GROUP BY E.razaosocial, E.nomefantasia, C.mes, E.codigo, E.cobranca, E.desativado
        ORDER BY E.razaosocial";
$statment = $pdo->prepare($query);
if ($cod_convenio !== null) {
    $statment->bindParam(':cod_convenio', $cod_convenio);
}
$statment->execute();
$result = $statment->fetchAll();
$data = array();
$linhas_filtradas = $statment->rowCount();

foreach ($result as $row){
    $sub_array = array();

    $sub_array["codigo"]        = $row["codigo"];
    $sub_array["razaosocial"]   = htmlspecialchars($row["razaosocial"] ?? '');
    $sub_array["nomefantasia"]  = htmlspecialchars($row["nomefantasia"] ?? '');
    $sub_array["endereco"]      = htmlspecialchars($row["endereco"] ?? '');
    $sub_array["bairro"]        = htmlspecialchars($row["bairro"] ?? '');
    $sub_array["telefone"]      = $row["telefone"];
    $sub_array["data_cadastro"] = $row["data_cadastro"];
    $sub_array["cidade"]        = htmlspecialchars($row["cidade"] ?? '');
    $sub_array["cnpj"]          = $row["cnpj"];
    $sub_array["email"]         = $row["email"];
    $sub_array["contato"]       = htmlspecialchars($row["contato"] ?? '');
    $sub_array["cel"]           = $row["cel"];
    $sub_array["total"]         = $row["total"];
    $sub_array["botao"]         = '<button type="button" name="update" id="'.$row["codigo"].'" class="btn btn-warning btn-xs update">Alterar</button>';
    $sub_array["botaosenha"]    = '<button type="button" name="btnsenha" id="'.$row["codigo"].'" class="btn btn-facebook btn-xs btnsenha">Senha</button>';

    $someArray["data"][] = $sub_array;

}

echo json_encode($someArray);
