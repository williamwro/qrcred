<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "SELECT id, nome, endereco, numero, nascimento, cep, telres, telcom, cel, bairro, complemento, cidade, rg, cpf, email, uf, codigo, empregador
        FROM sind.associado_novo_app";

$statement = $pdo->prepare($sql);
$statement->execute();
$result = $statement->fetchAll();

$dados = array();

foreach($result as $row) {
    $sub_array = array();
    $sub_array["id"] = $row["id"];
    $sub_array["nome"] = $row["nome"];
    $sub_array["endereco"] = $row["endereco"];
    $sub_array["numero"] = $row["numero"];
    $sub_array["nascimento"] = !empty($row["nascimento"]) ? date("d/m/Y", strtotime($row["nascimento"])) : "";
    $sub_array["cep"] = $row["cep"];
    $sub_array["telres"] = $row["telres"];
    $sub_array["telcom"] = $row["telcom"];
    $sub_array["cel"] = $row["cel"];
    $sub_array["bairro"] = $row["bairro"];
    $sub_array["complemento"] = $row["complemento"];
    $sub_array["cidade"] = $row["cidade"];
    $sub_array["rg"] = $row["rg"];
    $sub_array["cpf"] = $row["cpf"];
    $sub_array["email"] = $row["email"];
    $sub_array["uf"] = $row["uf"];
    $sub_array["codigo"] = $row["codigo"];
    $sub_array["empregador"] = $row["empregador"];
    
    $sub_array["alterar"] = '<button type="button" name="update" id="'.$row["id"].'" class="btn btn-warning btn-xs update">Alterar</button>';
    $sub_array["aprovar"] = '<button type="button" name="aprovar" id="'.$row["id"].'" class="btn btn-success btn-xs aprovar">Aprovar</button>';
    
    $dados[] = $sub_array;
}

$output = array("data" => $dados);
echo json_encode($output);
?> 