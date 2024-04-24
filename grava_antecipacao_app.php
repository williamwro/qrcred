<?PHP
header("Content-type: application/json");
ini_set('display_errors', true);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');
// Incluindo o arquivo de conexão com o banco
include "Adm/php/banco.php";
include "Adm/php/funcoes.php";

if(isset($_POST['pass'])){
    $pass             = $_POST['pass'];
    $matricula        = $_POST['matricula'];
    $empregador       = $_POST['empregador'];  
    $senha_correta = false;  
    // Conectando ao banco de dados utilizando o PDO
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    try {
        // Preparando a consulta SQL
        $sqlsenha = "SELECT cod_associado, senha, id_empregador
                  FROM sind.c_senhaassociado
                 WHERE cod_associado = ? 
                   AND id_empregador = ? 
                   AND senha = ?";
       
        $stmt = $pdo->prepare($sqlsenha);

        // Associando os parâmetros com os placeholders na consulta preparada
        $stmt->bindParam(1, $matricula, PDO::PARAM_STR);
        $stmt->bindParam(2, $empregador, PDO::PARAM_INT);
        $stmt->bindParam(3, $pass, PDO::PARAM_STR);
      
        // Executando a consulta preparada
        $stmt->execute();
        // Faz um loop percorrendo os resultados
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $senha_correta = true;
        }
        if($senha_correta == true){

            if(isset($_POST['matricula'], $_POST['empregador'], $_POST['valor_pedido'], $_POST['taxa'], $_POST['valor_descontar'], $_POST['mes_corrente'])) {
                //  Recebendo os parâmetros via POST e Atribuindo os valores dos parâmetros
                $valor_pedido2    = preg_replace("/[^0-9]/", "", $_POST['valor_pedido']);
                $valor_pedido     = floatval($valor_pedido2)/100;
                $taxa2            = preg_replace("/[^0-9]/", "", $_POST['taxa']);
                $taxa             = floatval($taxa2)/100;
                $valor_descontar2 = preg_replace("/[^0-9]/", "", $_POST['valor_descontar']);
                $valor_descontar  = floatval($valor_descontar2)/100;
                $mes_corrente     = $_POST['mes_corrente'];
                $chave_pix        = $_POST['chave_pix'];
                try {

                        // Preparando a consulta SQL
                        $sql = "INSERT INTO sind.antecipacao (id,matricula, empregador, mes, 
                                                            data_solicitacao, valor, aprovado, 
                                                            data_aprovacao, celular, valor_taxa, 
                                                            valor_a_descontar,chave_pix)
                                VALUES (DEFAULT, ?, ?, ?, CURRENT_DATE, ?, NULL, NULL, NULL, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);

                        // Associando os parâmetros com os placeholders na consulta preparada
                        $stmt->bindParam(1, $matricula, PDO::PARAM_STR);
                        $stmt->bindParam(2, $empregador, PDO::PARAM_INT);
                        $stmt->bindParam(3, $mes_corrente, PDO::PARAM_STR);
                        $stmt->bindParam(4, $valor_pedido, PDO::PARAM_STR);
                        $stmt->bindParam(5, $taxa, PDO::PARAM_STR);
                        $stmt->bindParam(6, $valor_descontar, PDO::PARAM_STR);
                        $stmt->bindParam(7, $chave_pix , PDO::PARAM_STR);

                        // Executando a consulta preparada
                        $stmt->execute();

                        $response = array("success" => "true", "message" => "Dados inseridos com sucesso!");
                        $someArray = array_map("utf8_encode",$response);
                        echo json_encode($someArray);

                } catch (PDOException $e) {
                    $response = array("success" => "false", "message" => "Erro: Todos os campos devem ser fornecidos.");
                    $someArray = array_map("utf8_encode",$response);
                    echo json_encode($someArray);
                }
            } else {
                $response = array("success" => "false", "message" => "Erro: Dados vazios.");
                $someArray = array_map("utf8_encode",$response);
                echo json_encode($someArray);
            }
        } else {
            $response = array("success" => "false", "message" => "Erro: Senha incorreta.");
            $someArray = array_map("utf8_encode",$response);
            echo json_encode($someArray);
        }
    } catch (PDOException $e) {
        $response = array("success" => "false", "message" => "Erro ao inserir dados no banco: " . $e->getMessage());
        $someArray = array_map("utf8_encode",$response);
        echo json_encode($someArray);
    }
} 
