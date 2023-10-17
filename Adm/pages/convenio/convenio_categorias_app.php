<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);
header("Content-type: application/json");
include_once "../../php/banco.php";
include_once "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$someArray = array();
$i=0;
$sql = $pdo->query("SELECT convenio.codigo, 
                           convenio.razaosocial, 
                           convenio.nomefantasia, 
                           convenio.endereco, 
                           convenio.numero, 
                           convenio.bairro, 
                           convenio.cidade, 
                           convenio.cep, 
                           convenio.telefone, 
                           convenio.email, 
                           categoriaconvenio.nome AS nome_categoria, 
                           categoriaconvenio.codigo AS codigo_categoria
                      FROM sind.categoriaconvenio 
                INNER JOIN sind.convenio 
                        ON categoriaconvenio.codigo = convenio.id_categoria 
                     WHERE convenio.lista_site = true 
                  ORDER BY categoriaconvenio.nome ASC; ");
while($row = $sql->fetch()) {
    $someArray["data"][] = array_map("utf8_encode",$row);
    $i++;
}
//$someArray2 = array_values( array_unique( $someArray, SORT_REGULAR ) );
// Make a JSON string from the array.
//$someArray3 = json_encode( $someArray2 );
echo json_encode($someArray);