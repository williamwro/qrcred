<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mensagem = "";

if (isset($_POST["id"])) {
    $id = $_POST["id"];
    $nome = $_POST["nome"];
    $endereco = $_POST["endereco"];
    $numero = $_POST["numero"];
    $complemento = $_POST["complemento"];
    $bairro = $_POST["bairro"];
    $cidade = $_POST["cidade"];
    $uf = $_POST["uf"];
    $cep = $_POST["cep"];
    
    // Converter data do formato brasileiro para o formato do banco
    $nascimento = "";
    if (!empty($_POST["nascimento"])) {
        $data = explode('/', $_POST["nascimento"]);
        if (count($data) === 3) {
            $nascimento = $data[2] . "-" . $data[1] . "-" . $data[0];
        }
    }
    
    $cpf = $_POST["cpf"];
    $rg = $_POST["rg"];
    $telres = $_POST["telres"];
    $telcom = $_POST["telcom"];
    $cel = $_POST["cel"];
    $email = $_POST["email"];
    $codigo = $_POST["codigo"];
    $empregador = $_POST["empregador"];

    try {
        $sql = "UPDATE sind.associado_novo_app 
                SET nome = :nome,
                    endereco = :endereco,
                    numero = :numero,
                    complemento = :complemento,
                    bairro = :bairro,
                    cidade = :cidade,
                    uf = :uf,
                    cep = :cep,
                    nascimento = :nascimento,
                    cpf = :cpf,
                    rg = :rg,
                    telres = :telres,
                    telcom = :telcom,
                    cel = :cel,
                    email = :email,
                    codigo = :codigo,
                    empregador = :empregador
                WHERE id = :id";

        $statement = $pdo->prepare($sql);
        $statement->bindParam(':nome', $nome);
        $statement->bindParam(':endereco', $endereco);
        $statement->bindParam(':numero', $numero);
        $statement->bindParam(':complemento', $complemento);
        $statement->bindParam(':bairro', $bairro);
        $statement->bindParam(':cidade', $cidade);
        $statement->bindParam(':uf', $uf);
        $statement->bindParam(':cep', $cep);
        $statement->bindParam(':nascimento', $nascimento);
        $statement->bindParam(':cpf', $cpf);
        $statement->bindParam(':rg', $rg);
        $statement->bindParam(':telres', $telres);
        $statement->bindParam(':telcom', $telcom);
        $statement->bindParam(':cel', $cel);
        $statement->bindParam(':email', $email);
        $statement->bindParam(':codigo', $codigo);
        $statement->bindParam(':empregador', $empregador);
        $statement->bindParam(':id', $id);

        $statement->execute();
        $mensagem = "Dados atualizados com sucesso!";
    } catch(PDOException $e) {
        $mensagem = "Erro ao atualizar os dados: " . $e->getMessage();
    }
}

echo json_encode(array("mensagem" => $mensagem));
?> 