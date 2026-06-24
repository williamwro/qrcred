<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
function replace_unicode_escape_sequence($match) {
    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}
function unicode_decode($str) {
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $str);
}
$divisao = $_POST["divisao"];


$query = "SELECT associado.codigo, 
                 associado.nome, 
                 associado.endereco, 
                 associado.numero, 
                 associado.nascimento,
                 associado.salario,
                 associado.limite,
                 associado.empregador AS id_empregador, 
                 associado.cep, 
                 associado.telres, 
                 associado.telcom, 
                 associado.cel, 
                 associado.bairro, 
                 associado.complemento,
                 associado.cidade,
                 associado.id_situacao,
                 associado.id_divisao,
                 associado.id,
				 empregador.id_divisao,
                 empregador.nome AS empregador, 
                 empregador.abreviacao
            FROM sind.empregador RIGHT JOIN sind.associado ON empregador.id = associado.empregador 
           WHERE associado.id_divisao = ".$divisao ."";
$someArray = array();

$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();

$linhas_filtradas = $statment->rowCount();

foreach ($result as $row){

    $sub_array = array();

    $sub_array["codigo"]        = $row["codigo"];
    $sub_array["nome"]          = mb_convert_encoding($row["nome"] ?? '', 'UTF-8', 'ISO-8859-1');
    $sub_array["endereco"]      = mb_convert_encoding($row["endereco"] ?? '', 'UTF-8', 'ISO-8859-1');
    $sub_array["numero"]        = $row["numero"];
    $sub_array["bairro"]        = mb_convert_encoding($row["bairro"] ?? '', 'UTF-8', 'ISO-8859-1');
    $sub_array["nascimento"]    = $row["nascimento"] ? date('d/m/Y', strtotime($row["nascimento"])) : '';
    $sub_array["salario"]       = (float)str_replace('.',',', $row["salario"] ?? '0');
    $sub_array["limite"]        = (float)str_replace('.',',', $row["limite"] ?? '0');
    $sub_array["empregador"]    = $row["empregador"];
    $sub_array["codempregador"] = (int)$row["id_empregador"];
    $sub_array["cep"]           = $row["cep"];
    $sub_array["telres"]        = $row["telres"];
    $sub_array["telcom"]        = $row["telcom"];
    $sub_array["cel"]           = $row["cel"];
    $sub_array["complemento"]   = mb_convert_encoding($row["complemento"] ?? '', 'UTF-8', 'ISO-8859-1');
    $sub_array["cidade"]        = $row["cidade"];
    $sub_array["id_situacao"]   = (int)$row["id_situacao"];
    $sub_array["id"]            = (int)$row["id"];
    $sub_array["abreviacao"]    = $row["abreviacao"];

    // Preservar campos numéricos antes da conversão
    $id_preservado = $sub_array["id"];
    $codempregador_preservado = $sub_array["codempregador"];
    $id_situacao_preservado = $sub_array["id_situacao"];
    
    $sub_array_convertido = array_map(function($value) {
        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
    }, $sub_array);
    
    // Restaurar campos numéricos
    $sub_array_convertido["id"] = $id_preservado;
    $sub_array_convertido["codempregador"] = $codempregador_preservado;
    $sub_array_convertido["id_situacao"] = $id_situacao_preservado;
    
    $someArray["data"][] = $sub_array_convertido;
}
echo json_encode($someArray);