<?PHP
require_once '../../../functions.php';
ini_set('display_errors', true);
error_reporting(E_ALL);
header("Content-type: application/json");

try {
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
                      ORDER BY categoriaconvenio.nome ASC");
    while($row = $sql->fetch()) {
        // Substituir utf8_encode() depreciado por mb_convert_encoding()
        $someArray["data"][] = array_map(function($value) {
            if (is_string($value)) {
                return mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
            }
            return $value;
        }, $row);
        $i++;
    }
    
    echo json_encode($someArray);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "error" => true,
        "message" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ));
}