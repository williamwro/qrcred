<?PHP
header("Content-type: application/json");
ini_set('display_errors', true);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

// Log de início
error_log("=== ANTECIPAÇÃO APP - INÍCIO ===");
error_log("POST recebido: " . json_encode($_POST));

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
        
        error_log("RESULTADO VERIFICAÇÃO SENHA: " . ($senha_correta ? "CORRETA" : "INCORRETA"));
        
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
                
                error_log("DADOS PROCESSADOS: valor={$valor_pedido}, taxa={$taxa}, valor_descontar={$valor_descontar}, mes={$mes_corrente}");
                
                try {
                    // *** VERIFICAÇÃO RIGOROSA DE DUPLICAÇÃO ***
                    // Verificar múltiplas condições para detectar duplicação
                    
                    // 1. Verificar solicitação idêntica nos últimos 5 minutos
                    $sql_duplicacao1 = "SELECT id, data_solicitacao, 
                                               EXTRACT(EPOCH FROM (NOW() - data_solicitacao)) as segundos_diferenca
                                       FROM sind.antecipacao 
                                       WHERE matricula = ? 
                                       AND empregador = ? 
                                       AND ABS(valor - ?) < 0.01
                                       AND mes = ?
                                       AND data_solicitacao > NOW() - INTERVAL '5 minutes'
                                       ORDER BY data_solicitacao DESC 
                                       LIMIT 3";
                    
                    $stmt_dup1 = $pdo->prepare($sql_duplicacao1);
                    $stmt_dup1->bindParam(1, $matricula, PDO::PARAM_STR);
                    $stmt_dup1->bindParam(2, $empregador, PDO::PARAM_INT);
                    $stmt_dup1->bindParam(3, $valor_pedido, PDO::PARAM_STR);
                    $stmt_dup1->bindParam(4, $mes_corrente, PDO::PARAM_STR);
                    $stmt_dup1->execute();
                    
                    $duplicacoes = $stmt_dup1->fetchAll(PDO::FETCH_ASSOC);
                    
                    error_log("VERIFICAÇÃO DUPLICAÇÃO: encontradas " . count($duplicacoes) . " solicitações similares");
                    
                    if (!empty($duplicacoes)) {
                        foreach ($duplicacoes as $dup) {
                            error_log("DUPLICAÇÃO ENCONTRADA: ID={$dup['id']}, Data={$dup['data_solicitacao']}, Diferença={$dup['segundos_diferenca']}s");
                        }
                        
                        // Se encontrou duplicação nos últimos 2 minutos, bloquear
                        $duplicacao_recente = false;
                        foreach ($duplicacoes as $dup) {
                            if ($dup['segundos_diferenca'] < 120) { // 2 minutos
                                $duplicacao_recente = true;
                                break;
                            }
                        }
                        
                        if ($duplicacao_recente) {
                            error_log("DUPLICAÇÃO RECENTE DETECTADA - BLOQUEANDO");
                            $response = array(
                                "success" => "true", 
                                "message" => "Sua solicitação já foi processada. Aguarde a análise.",
                                "duplicate_prevented" => true,
                                "original_id" => $duplicacoes[0]['id'],
                                "debug_info" => "Duplicação detectada nos últimos 2 minutos"
                            );
                            $someArray = array_map(function($value) {
                                return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
                            }, $response);
                            echo json_encode($someArray);
                            exit;
                        }
                    }
                    
                    // 2. Verificar se há muitas solicitações do mesmo usuário nos últimos 10 minutos
                    $sql_flood = "SELECT COUNT(*) as total 
                                 FROM sind.antecipacao 
                                 WHERE matricula = ? 
                                 AND empregador = ? 
                                 AND data_solicitacao > NOW() - INTERVAL '10 minutes'";
                    
                    $stmt_flood = $pdo->prepare($sql_flood);
                    $stmt_flood->bindParam(1, $matricula, PDO::PARAM_STR);
                    $stmt_flood->bindParam(2, $empregador, PDO::PARAM_INT);
                    $stmt_flood->execute();
                    
                    $flood_result = $stmt_flood->fetch(PDO::FETCH_ASSOC);
                    $total_recentes = $flood_result['total'];
                    
                    error_log("VERIFICAÇÃO FLOOD: {$total_recentes} solicitações nos últimos 10 minutos");
                    
                    if ($total_recentes >= 5) {
                        error_log("FLOOD DETECTADO - MUITAS SOLICITAÇÕES");
                        $response = array(
                            "success" => "false", 
                            "message" => "Muitas solicitações recentes. Aguarde alguns minutos antes de tentar novamente.",
                            "flood_detected" => true,
                            "total_recent" => $total_recentes
                        );
                        $someArray = array_map(function($value) {
                            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
                        }, $response);
                        echo json_encode($someArray);
                        exit;
                    }
                    
                    // *** INSERIR NOVA SOLICITAÇÃO ***
                    error_log("INSERINDO NOVA SOLICITAÇÃO: matricula={$matricula}, valor={$valor_pedido}");

                    // Preparando a consulta SQL com RETURNING para confirmar inserção
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
                        error_log("INSERÇÃO REALIZADA COM SUCESSO: ID={$resultado['id']}, Data={$resultado['data_solicitacao']}");
                        
                        $response = array(
                            "success" => "true", 
                            "message" => "Dados inseridos com sucesso!",
                            "id" => $resultado['id'],
                            "data_solicitacao" => $resultado['data_solicitacao'],
                            "duplicate_prevented" => false,
                            "debug_info" => "Nova inserção realizada"
                        );
                    } else {
                        error_log("ERRO: INSERÇÃO NÃO RETORNOU ID");
                        $response = array(
                            "success" => "false", 
                            "message" => "Erro ao inserir dados - não foi possível confirmar a inserção."
                        );
                    }
                    
                    $someArray = array_map(function($value) {
                        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
                    }, $response);
                    echo json_encode($someArray);

                } catch (PDOException $e) {
                    error_log("ERRO PDO: " . $e->getMessage());
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
            error_log("ERRO: SENHA INCORRETA");
            $response = array("success" => "false", "message" => "Erro: Senha incorreta.");
            $someArray = array_map(function($value) {
                return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
            }, $response);
            echo json_encode($someArray);
        }
    } catch (PDOException $e) {
        error_log("ERRO CONEXÃO: " . $e->getMessage());
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

error_log("=== ANTECIPAÇÃO APP - FIM ===");
?>
