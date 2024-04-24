<?PHP
header("Content-type: application/json");
include "Adm/php/banco.php";
include "Adm/php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$std = new stdClass();
$someArray = array();

$query = $pdo->query("SELECT * FROM sind.mes_corrente");

while($row = $query->fetch()) {

    // Segunda consulta
    $taxa_query = $pdo->query("SELECT id, porcentagem FROM sind.taxa_antecipacao");
    $taxa_row = $taxa_query->fetch();
    // Adicionando dados da primeira consulta
    $someArray[] = array_map("utf8_encode", $row);
    // Adicionando dados da segunda consulta
    //$someArray[count($someArray) - 1]['id'] = $taxa_row['id'];
    $someArray[count($someArray) - 1]['porcentagem'] = $taxa_row['porcentagem'];

     // terceira consulta
     $email_query = $pdo->query("SELECT id, email FROM sind.email_antecipacao");
     $email_row = $email_query->fetch();
     // Adicionando dados da primeira consulta
     //$someArray[] = array_map("utf8_encode", $row);
     // Adicionando dados da segunda consulta
     //$someArray[count($someArray) - 1]['id'] = $taxa_row['id'];
     $someArray[count($someArray) - 1]['email'] = $email_row['email'];

}

echo json_encode($someArray);