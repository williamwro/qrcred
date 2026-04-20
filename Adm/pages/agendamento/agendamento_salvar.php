<?PHP
error_reporting(E_ALL ^ E_NOTICE);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

require "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$_usuario_cod       = $_POST['usuario_cod'];
$_divisao           = isset($_POST['divisao']) ? $_POST['divisao'] : 0;
$_operation         = isset($_POST['operation']) ? $_POST['operation'] : "";
$_id                = isset($_POST['C_id_agendamento']) ? $_POST['C_id_agendamento'] : 0;
$_cod_associado     = isset($_POST['C_cod_associado_agendamento']) ? $_POST['C_cod_associado_agendamento'] : "";
$_id_empregador     = isset($_POST['C_id_empregador_agendamento']) ? $_POST['C_id_empregador_agendamento'] : null;
$_cod_convenio      = isset($_POST['C_cod_convenio_agendamento']) ? $_POST['C_cod_convenio_agendamento'] : "";
$_status            = isset($_POST['C_status_agendamento']) ? $_POST['C_status_agendamento'] : "1";
$_profissional      = isset($_POST['C_profissional_agendamento']) ? $_POST['C_profissional_agendamento'] : "";
$_especialidade     = isset($_POST['C_especialidade_agendamento']) ? $_POST['C_especialidade_agendamento'] : "";
$_convenio_nome     = isset($_POST['C_convenio_nome_agendamento']) ? $_POST['C_convenio_nome_agendamento'] : "";

// TRATAMENTO ESPECÍFICO PARA CAMPO DATETIME-LOCAL data_solicitacao
$_data_solicitacao = null;
if (isset($_POST['C_data_solicitacao_agendamento'])) {
    $data_solicitacao_raw = trim($_POST['C_data_solicitacao_agendamento']);
    
    // DEBUG ESPECÍFICO PARA CAMPO DATA_SOLICITACAO
    error_log("========== DEBUG DATA_SOLICITACAO ==========");
    error_log("POST C_data_solicitacao_agendamento valor bruto: '" . $data_solicitacao_raw . "'");
    
    // Se o campo não está vazio, processar
    if (!empty($data_solicitacao_raw)) {
        // CONVERSÃO PARA FORMATO TIMESTAMP
        if (strpos($data_solicitacao_raw, 'T') !== false) {
            // Formato ISO: 2025-07-11T17:46 -> converter para 2025-07-11 17:46:00
            $data_solicitacao_formatted = str_replace('T', ' ', $data_solicitacao_raw);
            // Se não tem segundos, adicionar :00
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $data_solicitacao_formatted)) {
                $data_solicitacao_formatted .= ':00';
            }
            error_log("Formato ISO detectado - convertendo: '" . $data_solicitacao_formatted . "'");
        } else if (strpos($data_solicitacao_raw, ' ') !== false) {
            // Formato padrão: 2025-07-11 17:46:00 -> manter como está
            $data_solicitacao_formatted = $data_solicitacao_raw;
            error_log("Formato padrão detectado - mantendo: '" . $data_solicitacao_formatted . "'");
        } else {
            // Apenas data: 2025-07-11 -> adicionar horário atual
            $data_solicitacao_formatted = $data_solicitacao_raw . ' ' . date('H:i:s');
            error_log("Apenas data detectada - adicionando horário atual: '" . $data_solicitacao_formatted . "'");
        }
        
        try {
            // Tentar criar DateTime para validar
            $datetime = new DateTime($data_solicitacao_formatted);
            $_data_solicitacao = $datetime->format('Y-m-d H:i:s');
            error_log("DateTime criado com sucesso: '" . $_data_solicitacao . "'");
        } catch (Exception $e) {
            error_log("Erro no DateTime: " . $e->getMessage());
            $_data_solicitacao = null;
        }
    } else {
        $_data_solicitacao = null;
        error_log("Campo data_solicitacao vazio, mantendo como NULL");
    }
} else {
    $_data_solicitacao = null;
    error_log("Campo data_solicitacao não foi enviado, mantendo como NULL");
}

// TRATAMENTO ESPECÍFICO PARA CAMPO DATETIME-LOCAL data_agendada
$_data_agendada = null;
if (isset($_POST['C_data_agendada_agendamento'])) {
    $data_agendada_raw = trim($_POST['C_data_agendada_agendamento']);
    
    // DEBUG ESPECÍFICO PARA CAMPO DATA_AGENDADA
    error_log("========== DEBUG DATA_AGENDADA ==========");
    error_log("POST C_data_agendada_agendamento valor bruto: '" . $data_agendada_raw . "'");
    
    // Se o campo não está vazio, processar
    if (!empty($data_agendada_raw)) {
        // CONVERSÃO PARA FORMATO TIMESTAMP
        if (strpos($data_agendada_raw, 'T') !== false) {
            // Formato ISO: 2025-07-11T17:46 -> converter para 2025-07-11 17:46:00
            $data_agendada_formatted = str_replace('T', ' ', $data_agendada_raw);
            // Se não tem segundos, adicionar :00
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $data_agendada_formatted)) {
                $data_agendada_formatted .= ':00';
            }
            error_log("Formato ISO detectado - convertendo: '" . $data_agendada_formatted . "'");
        } else if (strpos($data_agendada_raw, ' ') !== false) {
            // Formato padrão: 2025-07-11 17:46:00 -> manter como está
            $data_agendada_formatted = $data_agendada_raw;
            error_log("Formato padrão detectado - mantendo: '" . $data_agendada_formatted . "'");
        } else {
            // Apenas data: 2025-07-11 -> adicionar horário atual
            $data_agendada_formatted = $data_agendada_raw . ' ' . date('H:i:s');
            error_log("Apenas data detectada - adicionando horário atual: '" . $data_agendada_formatted . "'");
        }
        
        try {
            // Tentar criar DateTime para validar
            $datetime = new DateTime($data_agendada_formatted);
            $_data_agendada = $datetime->format('Y-m-d H:i:s');
            error_log("DateTime criado com sucesso: '" . $_data_agendada . "'");
        } catch (Exception $e) {
            error_log("Erro no DateTime: " . $e->getMessage());
            $_data_agendada = null;
        }
    } else {
        $_data_agendada = null;
        error_log("Campo data_agendada vazio, mantendo como NULL");
    }
} else {
    $_data_agendada = null;
    error_log("Campo data_agendada não foi enviado, mantendo como NULL");
}

// TRATAMENTO ESPECÍFICO PARA CAMPO DATETIME-LOCAL data_pretendida
$_data_pretendida = null;
if (isset($_POST['C_data_pretendida_agendamento'])) {
    $data_pretendida_raw = trim($_POST['C_data_pretendida_agendamento']);
    
    if (!empty($data_pretendida_raw)) {
        // CONVERSÃO PARA FORMATO TIMESTAMP
        if (strpos($data_pretendida_raw, 'T') !== false) {
            $data_pretendida_formatted = str_replace('T', ' ', $data_pretendida_raw);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $data_pretendida_formatted)) {
                $data_pretendida_formatted .= ':00';
            }
        } else {
            $data_pretendida_formatted = $data_pretendida_raw;
        }
        
        try {
            $datetime = new DateTime($data_pretendida_formatted);
            $_data_pretendida = $datetime->format('Y-m-d H:i:s');
            error_log("Data pretendida formatada: '" . $_data_pretendida . "'");
        } catch (Exception $e) {
            error_log("Erro ao formatar data_pretendida: " . $e->getMessage());
            $_data_pretendida = null;
        }
    } else {
        $_data_pretendida = null;
    }
} else {
    $_data_pretendida = null;
}

// Log dos valores recebidos
error_log("DEBUG AGENDAMENTO SALVAR - Valores recebidos:");
error_log("ID: " . $_id);
error_log("Cod Associado: " . $_cod_associado);
error_log("ID Empregador: " . $_id_empregador);
error_log("Data Solicitação: " . $_data_solicitacao);
error_log("Data Agendada: " . $_data_agendada);
error_log("Data Pretendida: " . $_data_pretendida);
error_log("Cod Convênio: " . $_cod_convenio);
error_log("Status: " . $_status);
error_log("Profissional: " . $_profissional);
error_log("Especialidade: " . $_especialidade);
error_log("Convênio Nome: " . $_convenio_nome);
error_log("Operation: " . $_operation);
error_log("Usuario Cod: " . $_usuario_cod);

$response = array();

try {
    // Verificar se é operação de salvar (update)
    if ($_operation == "salvar" && !empty($_id)) {
        
        // Preparar query de UPDATE - Resetar flags de notificação quando data_agendada for alterada
        $query = "UPDATE sind.agendamento SET 
                    cod_associado = :cod_associado,
                    id_empregador = :id_empregador,
                    data_solicitacao = :data_solicitacao,
                    data_agendada = :data_agendada,
                    data_pretendida = :data_pretendida,
                    cod_convenio = :cod_convenio,
                    status = :status,
                    profissional = :profissional,
                    especialidade = :especialidade,
                    convenio_nome = :convenio_nome,
                    notification_sent_confirmado = false,
                    notification_sent_24h = false,
                    notification_sent_1h = false
                  WHERE id = :id";
        
        error_log("DEBUG AGENDAMENTO - Query UPDATE: " . $query);
        
        $stmt = $pdo->prepare($query);
        
        // Bind dos parâmetros
        $stmt->bindParam(':cod_associado', $_cod_associado, PDO::PARAM_STR);
        $stmt->bindParam(':id_empregador', $_id_empregador, PDO::PARAM_INT);
        $stmt->bindParam(':data_solicitacao', $_data_solicitacao, PDO::PARAM_STR);
        $stmt->bindParam(':data_agendada', $_data_agendada, PDO::PARAM_STR);
        $stmt->bindParam(':data_pretendida', $_data_pretendida, PDO::PARAM_STR);
        $stmt->bindParam(':cod_convenio', $_cod_convenio, PDO::PARAM_STR);
        $stmt->bindParam(':status', $_status, PDO::PARAM_INT);
        $stmt->bindParam(':profissional', $_profissional, PDO::PARAM_STR);
        $stmt->bindParam(':especialidade', $_especialidade, PDO::PARAM_STR);
        $stmt->bindParam(':convenio_nome', $_convenio_nome, PDO::PARAM_STR);
        $stmt->bindParam(':id', $_id, PDO::PARAM_INT);
        
        // Executar query
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Agendamento atualizado com sucesso!';
            error_log("DEBUG AGENDAMENTO - UPDATE executado com sucesso para ID: " . $_id);
            
            // NOTIFICAÇÃO PUSH IMEDIATA - Se data_agendada foi definida e status = 2 (confirmado)
            if (!empty($_data_agendada) && $_status == 2) {
                error_log(" Disparando notificação push imediata para agendamento ID: " . $_id);
                
                try {
                    // Chamar check_agendamentos_notifications_final.php via cURL
                    $notificationUrl = 'https://sas.makecard.com.br/check_agendamentos_notifications_final.php';
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $notificationUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    
                    $notificationResponse = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode === 200) {
                        error_log(" Sistema de notificação chamado com sucesso (HTTP 200)");
                        $response['notification_triggered'] = true;
                    } else {
                        error_log(" Sistema de notificação retornou HTTP {$httpCode}");
                        $response['notification_triggered'] = false;
                    }
                    
                } catch (Exception $e) {
                    error_log(" Erro ao chamar sistema de notificação: " . $e->getMessage());
                    $response['notification_error'] = $e->getMessage();
                }
            }
            
        } else {
            $response['success'] = false;
            $response['message'] = 'Nenhuma alteração foi feita no agendamento.';
            error_log("DEBUG AGENDAMENTO - UPDATE não afetou nenhuma linha para ID: " . $_id);
        }
        
    } else if ($_operation == "inserir") {
        
        // Preparar query de INSERT
        $query = "INSERT INTO sind.agendamento 
                    (cod_associado, id_empregador, data_solicitacao, data_agendada, data_pretendida, cod_convenio, status, profissional, especialidade, convenio_nome) 
                  VALUES 
                    (:cod_associado, :id_empregador, :data_solicitacao, :data_agendada, :data_pretendida, :cod_convenio, :status, :profissional, :especialidade, :convenio_nome)";
        
        error_log("DEBUG AGENDAMENTO - Query INSERT: " . $query);
        
        $stmt = $pdo->prepare($query);
        
        // Bind dos parâmetros
        $stmt->bindParam(':cod_associado', $_cod_associado, PDO::PARAM_STR);
        $stmt->bindParam(':id_empregador', $_id_empregador, PDO::PARAM_INT);
        $stmt->bindParam(':data_solicitacao', $_data_solicitacao, PDO::PARAM_STR);
        $stmt->bindParam(':data_agendada', $_data_agendada, PDO::PARAM_STR);
        $stmt->bindParam(':data_pretendida', $_data_pretendida, PDO::PARAM_STR);
        $stmt->bindParam(':cod_convenio', $_cod_convenio, PDO::PARAM_STR);
        $stmt->bindParam(':status', $_status, PDO::PARAM_INT);
        $stmt->bindParam(':profissional', $_profissional, PDO::PARAM_STR);
        $stmt->bindParam(':especialidade', $_especialidade, PDO::PARAM_STR);
        $stmt->bindParam(':convenio_nome', $_convenio_nome, PDO::PARAM_STR);
        
        // Executar query
        $stmt->execute();
        
        $new_id = $pdo->lastInsertId();
        
        if ($new_id) {
            $response['success'] = true;
            $response['message'] = 'Agendamento inserido com sucesso!';
            $response['new_id'] = $new_id;
            error_log("DEBUG AGENDAMENTO - INSERT executado com sucesso. Novo ID: " . $new_id);
        } else {
            $response['success'] = false;
            $response['message'] = 'Erro ao inserir agendamento.';
            error_log("DEBUG AGENDAMENTO - Erro no INSERT");
        }
        
    } else {
        $response['success'] = false;
        $response['message'] = 'Operação não reconhecida ou dados insuficientes.';
        error_log("DEBUG AGENDAMENTO - Operação inválida ou dados insuficientes");
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Erro no banco de dados: ' . $e->getMessage();
    error_log("DEBUG AGENDAMENTO - Exception: " . $e->getMessage());
}

// Retornar resposta JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?> 