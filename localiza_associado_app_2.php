<?php

// Permitir acesso de qualquer origem
header("Access-Control-Allow-Origin: *");

// Definir métodos HTTP permitidos
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

// Permitir headers específicos
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Definir por quanto tempo (em segundos) o navegador pode armazenar em cache os resultados da preflight request
header("Access-Control-Max-Age: 86400");

header("Content-type: application/json");

// Configurar exibição de erros para debug
ini_set('display_errors', true);
error_reporting(E_ALL);

// Função para log de debug
function logDebug($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= " - Data: " . json_encode($data);
    }
    error_log($logMessage);
}

try {
    logDebug("=== INÍCIO LOCALIZA ASSOCIADO ===");
    
    include "Adm/php/banco.php";
    
    $pdo = Banco::conectar_postgres();
    
    if (!$pdo) {
        logDebug("ERRO: Falha na conexão com banco de dados");
        throw new Exception("Erro na conexão com banco de dados");
    }
    
    // Configurar PDO para lançar exceções
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $cod_cartao = "";
    $senha = "";
    
    // Validar e sanitizar entrada
    if (isset($_POST['cartao'])) {
        $cod_cartao = trim($_POST['cartao']);
        // Remover caracteres não numéricos
        $cod_cartao = preg_replace('/\D/', '', $cod_cartao);
    }
    
    if (isset($_POST['senha'])) {
        $senha = trim($_POST['senha']);
    }
    
    logDebug("Parâmetros recebidos", [
        'cartao' => $cod_cartao,
        'senha_presente' => !empty($senha)
    ]);
    
    // Validar se cartão foi fornecido
    if (empty($cod_cartao)) {
        logDebug("ERRO: Cartão não fornecido");
        $std = new stdClass();
        $std->situacao = 3;
        $std->message = "Cartão é obrigatório";
        echo json_encode($std);
        exit;
    }
    
    // Validar formato do cartão (deve ter pelo menos 6 dígitos)
    if (strlen($cod_cartao) < 6) {
        logDebug("ERRO: Cartão com formato inválido", ['cartao' => $cod_cartao]);
        $std = new stdClass();
        $std->situacao = 3;
        $std->message = "Formato de cartão inválido";
        echo json_encode($std);
        exit;
    }
    
    $std = new stdClass();
    $contador = 0;
    
    logDebug("Executando consulta no banco", ['cartao' => $cod_cartao]);
    
    // Query preparada para evitar SQL injection
    $sql = "SELECT associado.codigo, associado.nome, 
                   associado.empregador, associado.limite, 
                   associado.salario, associado.parcelas_permitidas, 
                   associado.cpf, associado.email,
                   associado.cel, associado.cep,
                   associado.endereco, associado.numero,
                   associado.bairro, associado.cidade,
                   associado.uf, associado.celwatzap,
                   c_cartaoassociado.cod_situacaocartao, 
                   c_cartaoassociado.cod_verificacao, 
                   c_cartaoassociado.cod_situacao2,       
                   divisao.nome as nome_divisao,
                   divisao.id_divisao as id_divisao,
                   empregador.bloqueio as empregador_bloqueio,
                   associado.id as id
            FROM sind.associado 
            INNER JOIN sind.c_cartaoassociado 
                    ON associado.codigo = c_cartaoassociado.cod_associado 
                   AND associado.empregador = c_cartaoassociado.empregador
            INNER JOIN sind.divisao 
                    ON divisao.id_divisao = c_cartaoassociado.id_divisao
            INNER JOIN sind.empregador 
                    ON empregador.id = associado.empregador                                
            WHERE c_cartaoassociado.cod_verificacao = :cartao";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':cartao', $cod_cartao, PDO::PARAM_STR);
    $stmt->execute();
    
    $row_assoc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row_assoc) {
        logDebug("Associado encontrado", [
            'codigo' => $row_assoc['codigo'],
            'nome' => $row_assoc['nome'],
            'situacao_cartao' => $row_assoc['cod_situacaocartao']
        ]);
        
        $contador = 1;
        
        // Verificar situações válidas do cartão
        $situacoesValidas = ["1", "4", "5", "6", "7"];
        
        if (in_array($row_assoc['cod_situacaocartao'], $situacoesValidas)) {
            // Preencher dados do associado
            $std->nome                = $row_assoc['nome'];
            $std->cod_cart            = $row_assoc['cod_verificacao'];
            $std->matricula           = $row_assoc['codigo'];
            $std->empregador          = $row_assoc['empregador'];
            $empregador               = $row_assoc['empregador'];
            $std->parcelas_permitidas = $row_assoc["parcelas_permitidas"];
            $std->limite              = number_format(floatval($row_assoc["limite"]), 2, '.', '');
            $std->email               = $row_assoc["email"];
            $std->cpf                 = $row_assoc["cpf"];
            $std->cel                 = $row_assoc["cel"];
            $std->endereco            = $row_assoc["endereco"];
            $std->numero              = $row_assoc["numero"];
            $std->bairro              = $row_assoc["bairro"];
            $std->cep                 = $row_assoc["cep"];
            $std->cidade              = $row_assoc["cidade"];
            $std->uf                  = $row_assoc["uf"];
            $std->celwatzap           = $row_assoc["celwatzap"];
            $std->cod_situacao2       = $row_assoc["cod_situacao2"];
            $std->cod_situacaocartao  = $row_assoc["cod_situacaocartao"];
            $std->nome_divisao        = $row_assoc["nome_divisao"];
            $std->id_divisao          = $row_assoc["id_divisao"];
            $std->empregador_bloqueio  = $row_assoc["empregador_bloqueio"];
            $std->id                  = $row_assoc["id"];
            
            // Se senha foi fornecida, validar
            if (!empty($senha)) {
                logDebug("Validando senha para associado", ['codigo' => $row_assoc["codigo"]]);
                
                $sqlSenha = "SELECT * FROM sind.c_senhaassociado 
                            WHERE cod_associado = :codigo 
                              AND senha = :senha 
                              AND id_empregador = :empregador";
                
                $stmtSenha = $pdo->prepare($sqlSenha);
                $stmtSenha->bindParam(':codigo', $row_assoc["codigo"], PDO::PARAM_STR);
                $stmtSenha->bindParam(':senha', $senha, PDO::PARAM_STR);
                $stmtSenha->bindParam(':empregador', $empregador, PDO::PARAM_INT);
                $stmtSenha->execute();
                
                $row_senha = $stmtSenha->fetch(PDO::FETCH_ASSOC);
                
                if ($row_senha) {
                    $std->situacao = 1; // 1 = liberado
                    logDebug("Senha válida - acesso liberado");
                } else {
                    $std->situacao = 6; // 6 = senha errada
                    logDebug("Senha inválida");
                }
            } else {
                // Sem senha fornecida - apenas dados do associado
                $std->situacao = 1; // 1 = liberado (para recuperação de senha)
                logDebug("Consulta sem senha - dados do associado retornados");
            }
            
            $std->senha = $senha;
            
        } else {
            // Cartão bloqueado
            logDebug("Cartão bloqueado", ['situacao' => $row_assoc['cod_situacaocartao']]);
            
            $std->situacao = 0; // 0 = bloqueado
            $std->nome                = $row_assoc['nome']; // Corrigido: era 'mome'
            $std->cod_cart            = $row_assoc['cod_verificacao'];
            $std->matricula           = $row_assoc['codigo'];
            $std->empregador          = $row_assoc['empregador'];
            $std->parcelas_permitidas = $row_assoc["parcelas_permitidas"];
            $std->limite              = "";
            $std->email               = $row_assoc["email"];
            $std->cpf                 = $row_assoc["cpf"];
            $std->cel                 = $row_assoc["cel"];
            $std->endereco            = $row_assoc["endereco"];
            $std->numero              = $row_assoc["numero"];
            $std->bairro              = $row_assoc["bairro"];
            $std->cep                 = $row_assoc["cep"];
            $std->cidade              = $row_assoc["cidade"];
            $std->uf                  = $row_assoc["uf"];
            $std->celwatzap           = $row_assoc["celwatzap"];
            $std->cod_situacao2       = $row_assoc["cod_situacao2"];
            $std->cod_situacaocartao  = $row_assoc["cod_situacaocartao"];
            $std->nome_divisao        = $row_assoc["nome_divisao"];
            $std->id_divisao          = $row_assoc["id_divisao"];
            $std->empregador_bloqueio  = $row_assoc["empregador_bloqueio"];
            $std->id                  = $row_assoc["id"];
        }
        
    } else {
        // Cartão não encontrado
        logDebug("Cartão não encontrado na base de dados", ['cartao' => $cod_cartao]);
        
        $std->situacao = 3; // 3 = não encontrado
        $std->message = "Cartão não encontrado";
        $std->dados = $cod_cartao;
        
        // Limpar todos os campos
        $std->nome = '';
        $std->cod_cart = '';
        $std->matricula = '';
        $std->empregador = 0;
        $std->parcelas_permitidas = 0;
        $std->limite = '';
        $std->email = '';
        $std->cpf = '';
        $std->cel = '';
        $std->endereco = '';
        $std->numero = '';
        $std->bairro = '';
        $std->cep = '';
        $std->cidade = '';
        $std->uf = '';
        $std->celwatzap = '';
        $std->nome_divisao = '';
        $std->cod_situacao2 = '';
        $std->cod_situacaocartao = '';
        $std->id_divisao = '';
        $std->empregador_bloqueio = '';
        $std->id            = '';
    }
    
    // Adicionar informações de debug em desenvolvimento
    if (isset($_GET['debug']) || (isset($_POST['debug']) && $_POST['debug'] == '1')) {
        $std->debug = [
            'timestamp' => date('Y-m-d H:i:s'),
            'cartao_pesquisado' => $cod_cartao,
            'contador' => $contador,
            'query_executada' => true
        ];
    }
    
    logDebug("Retornando resposta", [
        'situacao' => $std->situacao,
        'nome' => $std->nome ?? '',
        'email_presente' => !empty($std->email ?? ''),
        'cel_presente' => !empty($std->cel ?? '')
    ]);
    
    // Retornar resposta JSON
    echo json_encode($std, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    logDebug("ERRO PDO", ['message' => $e->getMessage(), 'code' => $e->getCode()]);
    
    $std = new stdClass();
    $std->situacao = 4; // 4 = erro de banco
    $std->message = "Erro interno do servidor";
    $std->error = "Erro de banco de dados";
    
    if (ini_get('display_errors')) {
        $std->debug_error = $e->getMessage();
    }
    
    http_response_code(500);
    echo json_encode($std, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    logDebug("ERRO GERAL", ['message' => $e->getMessage()]);
    
    $std = new stdClass();
    $std->situacao = 5; // 5 = erro geral
    $std->message = "Erro interno do servidor";
    $std->error = $e->getMessage();
    
    http_response_code(500);
    echo json_encode($std, JSON_UNESCAPED_UNICODE);
    
} finally {
    logDebug("=== FIM LOCALIZA ASSOCIADO ===");
}

?>
