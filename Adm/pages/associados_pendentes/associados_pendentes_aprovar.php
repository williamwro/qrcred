<?PHP
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mensagem = "";

if (isset($_POST["id"])) {
    $id = $_POST["id"];
    
    try {
        // Iniciar uma transação
        $pdo->beginTransaction();
        
        // 1. Primeiro, buscar os dados do associado pendente
        $query = "SELECT id, nome, endereco, numero, nascimento, cep, telres, telcom, cel, bairro, complemento, cidade, rg, cpf, email, uf, codigo, empregador
                  FROM sind.associado_novo_app
                  WHERE id = :id";
        
        $statement = $pdo->prepare($query);
        $statement->bindParam(":id", $id);
        $statement->execute();
        $associado = $statement->fetch(PDO::FETCH_ASSOC);
        
        if ($associado) {
            // 2. Verificar se o código foi fornecido, caso contrário, gerar um novo
            $codigo = $associado['codigo'];
            if (empty($codigo)) {
                // Gerar código para o novo associado
                $sqlCodigo = "SELECT MAX(CAST(codigo AS INTEGER)) as ultimo_codigo FROM sind.associado";
                $stmtCodigo = $pdo->prepare($sqlCodigo);
                $stmtCodigo->execute();
                $ultimoCodigo = $stmtCodigo->fetch(PDO::FETCH_ASSOC);
                
                $codigo = $ultimoCodigo['ultimo_codigo'] + 1;
            }
            
            // 3. Inserir na tabela de associados
            $sqlInsert = "INSERT INTO sind.associado (
                        codigo, nome, endereco, numero, nascimento, 
                        cep, telres, telcom, cel, bairro, 
                        complemento, cidade, uf, rg, cpf, 
                        email, empregador, id_situacao, filiado, data_filiacao, id_divisao
                    ) VALUES (
                        :codigo, :nome, :endereco, :numero, :nascimento,
                        :cep, :telres, :telcom, :cel, :bairro,
                        :complemento, :cidade, :uf, :rg, :cpf,
                        :email, :empregador, 1, TRUE, CURRENT_DATE, 1
                    )";
            
            $stmtInsert = $pdo->prepare($sqlInsert);
            
            // Formatando a data de nascimento para o formato do banco
            $nascimento = null;
            if (!empty($associado["nascimento"])) {
                $nascimento = $associado["nascimento"];
            }
            
            // Empregador
            $empregador = $associado["empregador"];
            if (empty($empregador)) {
                $empregador = 1; // Valor padrão se não for fornecido
            }
            
            $stmtInsert->bindParam(':codigo', $codigo);
            $stmtInsert->bindParam(':nome', $associado["nome"]);
            $stmtInsert->bindParam(':endereco', $associado["endereco"]);
            $stmtInsert->bindParam(':numero', $associado["numero"]);
            $stmtInsert->bindParam(':nascimento', $nascimento);
            $stmtInsert->bindParam(':cep', $associado["cep"]);
            $stmtInsert->bindParam(':telres', $associado["telres"]);
            $stmtInsert->bindParam(':telcom', $associado["telcom"]);
            $stmtInsert->bindParam(':cel', $associado["cel"]);
            $stmtInsert->bindParam(':bairro', $associado["bairro"]);
            $stmtInsert->bindParam(':complemento', $associado["complemento"]);
            $stmtInsert->bindParam(':cidade', $associado["cidade"]);
            $stmtInsert->bindParam(':uf', $associado["uf"]);
            $stmtInsert->bindParam(':rg', $associado["rg"]);
            $stmtInsert->bindParam(':cpf', $associado["cpf"]);
            $stmtInsert->bindParam(':email', $associado["email"]);
            $stmtInsert->bindParam(':empregador', $empregador);
            
            $stmtInsert->execute();
            
            // 4. Excluir da tabela de associados pendentes
            $sqlDelete = "DELETE FROM sind.associado_novo_app WHERE id = :id";
            $stmtDelete = $pdo->prepare($sqlDelete);
            $stmtDelete->bindParam(':id', $id);
            $stmtDelete->execute();
            
            // 5. Commit da transação
            $pdo->commit();
            
            $mensagem = "Associado aprovado com sucesso! Código gerado: " . $codigo;
        } else {
            $pdo->rollBack();
            $mensagem = "Associado não encontrado!";
        }
    } catch(PDOException $e) {
        // Em caso de erro, reverter a transação
        $pdo->rollBack();
        $mensagem = "Erro ao aprovar o associado: " . $e->getMessage();
    }
}

echo json_encode(array("mensagem" => $mensagem));
?> 