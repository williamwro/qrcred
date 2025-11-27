<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["id"])){
    $std = new stdClass();
    $id = $_POST["id"];

    $query = "SELECT id, nome, endereco, numero, nascimento, cep, telres, telcom, cel, bairro, complemento, cidade, rg, cpf, email, uf, codigo, empregador
              FROM sind.associado_novo_app
              WHERE id = :id";
    
    $statement = $pdo->prepare($query);
    $statement->bindParam(":id", $id);
    $statement->execute();
    $result = $statement->fetchAll();

    foreach ($result as $row){
        $std->id = $row["id"];
        $std->nome = htmlspecialchars($row["nome"] ?? '');
        $std->endereco = htmlspecialchars($row["endereco"] ?? '');
        $std->numero = $row["numero"];
        $std->nascimento = !empty($row["nascimento"]) ? date('d/m/Y', strtotime($row["nascimento"])) : "";
        $std->cep = $row["cep"];
        $std->telres = $row["telres"];
        $std->telcom = $row["telcom"];
        $std->cel = $row["cel"];
        $std->bairro = htmlspecialchars($row["bairro"] ?? '');
        $std->complemento = htmlspecialchars($row["complemento"] ?? '');
        $std->cidade = htmlspecialchars($row["cidade"] ?? '');
        $std->uf = $row["uf"];
        $std->rg = $row["rg"];
        $std->cpf = $row["cpf"];
        $std->email = $row["email"];
        $std->codigo = $row["codigo"];
        $std->empregador = $row["empregador"];
    }
    
    echo json_encode($std);
}
?> 