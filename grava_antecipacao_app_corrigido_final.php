<?PHP
header("Content-type: application/json");
ini_set('display_errors', true);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

// Log detalhado de início
error_log("=== ANTECIPAÇÃO - INÍCIO ===");
error_log("POST recebido: " . json_encode($_POST));
error_log("Timestamp: " . date('Y-m-d H:i:s'));

// Incluindo o arquivo de conexão com o banco
include "Adm/php/banco.php";
include "Adm/php/funcoes.php";

if(isset($_POST['pass'])){
    $pass             = $_POST['pass'];
    $matricula        = $_POST['matricula'];
    $empregador       = $_POST['empregador'];  
    $senha_correta = false;  
    
    error_log("VERIFICANDO SENHA: matricula={$matricula}, empregador={$empregador}");
    
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
        
        error_log("SENHA VERIFICADA: " . ($senha_correta ? "CORRETA" : "INCORRETA"));
        
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
                
                error_log("VALORES PROCESSADOS: valor_original={$_POST['valor_pedido']}, valor_float={$valor_pedido}, taxa={$taxa}, mes={$mes_corrente}");
                
                try {
                    // *** VERIFICAÇÃO ROBUSTA DE DUPLICAÇÃO ***
                    // Usar ABS para comparação de valores com tolerância
                    $sql_duplicacao = "SELECT id, data_solicitacao, valor,
                                             EXTRACT(EPOCH FROM (NOW() - data_solicitacao)) as segundos_diferenca
                                      FROM sind.antecipacao 
                                      WHERE matricula = ? 
                                      AND empregador = ? 
                                      AND ABS(valor - ?) < 0.01
                                      AND mes = ?
                                      AND data_solicitacao > NOW() - INTERVAL '3 minutes'
                                      ORDER BY data_solicitacao DESC 
                                      LIMIT 3";
                    
                    $stmt_duplicacao = $pdo->prepare($sql_duplicacao);
                    $stmt_duplicacao->bindParam(1, $matricula, PDO::PARAM_STR);
                    $stmt_duplicacao->bindParam(2, $empregador, PDO::PARAM_INT);
                    $stmt_duplicacao->bindParam(3, $valor_pedido, PDO::PARAM_STR);
                    $stmt_duplicacao->bindParam(4, $mes_corrente, PDO::PARAM_STR);
                    $stmt_duplicacao->execute();
                    
                    $duplicacoes = $stmt_duplicacao->fetchAll(PDO::FETCH_ASSOC);
                    
                    error_log("VERIFICAÇÃO DUPLICAÇÃO: encontradas " . count($duplicacoes) . " solicitações similares");
                    
                    if (!empty($duplicacoes)) {
                        foreach ($duplicacoes as $dup) {
                            error_log("SOLICITAÇÃO SIMILAR: ID={$dup['id']}, Data={$dup['data_solicitacao']}, Valor={$dup['valor']}, Diferença={$dup['segundos_diferenca']}s");
                        }
                        
                        // Se encontrou duplicação nos últimos 90 segundos, bloquear
                        $duplicacao_recente = null;
                        foreach ($duplicacoes as $dup) {
                            if ($dup['segundos_diferenca'] < 90) { // 1.5 minutos
                                $duplicacao_recente = $dup;
                                break;
                            }
                        }
                        
                        if ($duplicacao_recente) {
                            error_log("DUPLICAÇÃO RECENTE DETECTADA - BLOQUEANDO: ID={$duplicacao_recente['id']}, Diferença={$duplicacao_recente['segundos_diferenca']}s");
                            
                            $response = array(
                                "success" => "true", 
                                "message" => "Sua solicitação já foi processada. Aguarde a análise.",
                                "duplicate_prevented" => true,
                                "original_id" => $duplicacao_recente['id'],
                                "time_diff" => round($duplicacao_recente['segundos_diferenca'])
                            );
                            $someArray = array_map(function($value) {
                                return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
                            }, $response);
                            echo json_encode($someArray);
                            exit;
                        }
                    }
                    
                    // *** INSERIR NOVA SOLICITAÇÃO ***
                    error_log("INSERINDO NOVA SOLICITAÇÃO: matricula={$matricula}, empregador={$empregador}, valor={$valor_pedido}, mes={$mes_corrente}");

                    // Preparando a consulta SQL com RETURNING
                    $sql = "INSERT INTO sind.antecipacao (id,matricula, empregador, mes, 
                                                        data_solicitacao, valor, aprovado, 
                                                        data_aprovacao, celular, valor_taxa, 
                                                        valor_a_descontar,chave_pix)
                            VALUES (DEFAULT, ?, ?, ?, NOW(), ?, NULL, NULL, NULL, ?, ?, ?)
                            RETURNING id, data_solicitacao";
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
                    
                    // Obter dados da inserção
                    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($resultado && $resultado['id']) {
                        error_log("INSERÇÃO REALIZADA COM SUCESSO: ID={$resultado['id']}, Data={$resultado['data_solicitacao']}, Valor={$valor_pedido}");
                        
                        $response = array(
                            "success" => "true", 
                            "message" => "Dados inseridos com sucesso!",
                            "id" => $resultado['id'],
                            "data_solicitacao" => $resultado['data_solicitacao'],
                            "duplicate_prevented" => false
                        );
                    } else {
                        error_log("ERRO: INSERÇÃO NÃO RETORNOU DADOS");
                        $response = array("success" => "false", "message" => "Erro ao inserir dados - não foi possível confirmar a inserção.");
                    }
                    
                    $someArray = array_map(function($value) {
                        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
                    }, $response);
                    echo json_encode($someArray);

                } catch (PDOException $e) {
                    error_log("ERRO PDO ANTECIPAÇÃO: " . $e->getMessage());
                    error_log("TRACE: " . $e->getTraceAsString());
                    $response = array("success" => "false", "message" => "Erro ao inserir dados no banco: " . $e->getMessage());
                    $someArray = array_map(function($value) {
                        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
                    }, $response);
                    echo json_encode($someArray);
                }
            } else {
                error_log("ERRO: CAMPOS OBRIGATÓRIOS FALTANDO");
                $response = array("success" => "false", "message" => "Erro: Todos os campos devem ser fornecidos.");
                $someArray = array_map(function($value) {
                    return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
                }, $response);
                echo json_encode($someArray);
            }
        } else {
            error_log("ERRO: SENHA INCORRETA para matricula={$matricula}");
            $response = array("success" => "false", "message" => "Erro: Senha incorreta.");
            $someArray = array_map(function($value) {
                return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
            }, $response);
            echo json_encode($someArray);
        }
    } catch (PDOException $e) {
        error_log("ERRO CONEXÃO ANTECIPAÇÃO: " . $e->getMessage());
        $response = array("success" => "false", "message" => "Erro ao conectar com o banco: " . $e->getMessage());
        $someArray = array_map(function($value) {
            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
        }, $response);
        echo json_encode($someArray);
    }
} else {
    error_log("ERRO: PARÂMETRO 'pass' NÃO FORNECIDO");
    $response = array("success" => "false", "message" => "Erro: Parâmetro 'pass' não fornecido.");
    $someArray = array_map(function($value) {
        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
    }, $response);
    echo json_encode($someArray);
}

error_log("=== ANTECIPAÇÃO - FIM ===");
?>
