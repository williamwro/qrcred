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
$_id                = isset($_POST['C_id_assinatura']) ? $_POST['C_id_assinatura'] : 0;
$_codigo            = isset($_POST['C_codigo_assinatura']) ? $_POST['C_codigo_assinatura'] : "";
$_nome              = isset($_POST['C_nome_assinatura']) ? $_POST['C_nome_assinatura'] : "";
$_celular           = isset($_POST['C_celular_assinatura']) ? $_POST['C_celular_assinatura'] : "";
$_cel_informado     = isset($_POST['C_cel_informado_assinatura']) ? $_POST['C_cel_informado_assinatura'] : "";
$_email             = isset($_POST['C_email_assinatura']) ? $_POST['C_email_assinatura'] : "";
$_cpf               = isset($_POST['C_cpf_assinatura']) ? $_POST['C_cpf_assinatura'] : "";
$_event             = "doc_signed"; // Sempre definir como doc_signed ao salvar via modal
$_doc_token         = isset($_POST['C_doc_token_assinatura']) ? $_POST['C_doc_token_assinatura'] : "";
$_doc_name          = isset($_POST['C_doc_name_assinatura']) ? $_POST['C_doc_name_assinatura'] : "";
$_signed_at         = isset($_POST['C_signed_at_assinatura']) ? $_POST['C_signed_at_assinatura'] : "";
$_limite            = isset($_POST['C_limite_assinatura']) ? str_replace(['.', ','], ['', '.'], $_POST['C_limite_assinatura']) : 0;
$_chave_pix         = isset($_POST['C_chave_pix_assinatura']) ? $_POST['C_chave_pix_assinatura'] : "";

// Tratamento do campo has_signed
if (isset($_POST['C_has_signed']) && $_POST['C_has_signed'] == "1") {
    $_has_signed = true;
} else if (isset($_POST['C_has_signed']) && $_POST['C_has_signed'] == "2") {
    $_has_signed = false;
} else {
    $_has_signed = null;
}

// Campos automáticos - sempre true quando salvar via modal
$_autorizado = true;
$_aceitou_termo = true;

$data = new DateTime();
$_data_hora = $data->format('Y-m-d H:i:s.u'); // Formato com microssegundos

$stmt = new stdClass();
$msg_grava_cad = "";

// Verificar se o campo reprovar foi marcado (ANTES de usar)
$reprovar = null;
if (isset($_POST['C_reprovar'])) {
    if ($_POST['C_reprovar'] == "1") {
        $reprovar = true;  // Reprovar = Sim
    } else if ($_POST['C_reprovar'] == "2" || $_POST['C_reprovar'] == "0") {
        $reprovar = false; // Reprovar = Não (aceita tanto 2 quanto 0)
    }
}

// DEBUG: Log inicial para verificar recebimento dos dados
error_log("DEBUG INICIAL - Operation: " . $_operation);
error_log("DEBUG INICIAL - ID: " . $_id);
error_log("DEBUG INICIAL - Campo C_reprovar recebido: " . (isset($_POST['C_reprovar']) ? $_POST['C_reprovar'] : 'NOT SET'));
error_log("DEBUG INICIAL - Variável reprovar: " . ($reprovar === true ? 'TRUE (Reprovar = Sim)' : ($reprovar === false ? 'FALSE (Reprovar = Não)' : 'NULL (Não definido)')));

try {
    if($_operation == "Add") {
        // INSERT - removidos campos valor_aprovado e data_pgto, usando data_hora atual com microssegundos
        $sql = "INSERT INTO sind.associados_sasmais (codigo, nome, celular, data_hora, autorizado, aceitou_termo, event, doc_token, doc_name, signed_at, name, email, cpf, has_signed, cel_informado, limite, chave_pix)
                VALUES (:codigo, :nome, :celular, :data_hora, :autorizado, :aceitou_termo, :event, :doc_token, :doc_name, :signed_at, :name, :email, :cpf, :has_signed, :cel_informado, :limite, :chave_pix)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':codigo', $_codigo, PDO::PARAM_STR);
        $stmt->bindParam(':nome', $_nome, PDO::PARAM_STR);
        $stmt->bindParam(':celular', $_celular, PDO::PARAM_STR);
        $stmt->bindParam(':data_hora', $_data_hora, PDO::PARAM_STR);
        $stmt->bindParam(':autorizado', $_autorizado, PDO::PARAM_BOOL);
        $stmt->bindParam(':aceitou_termo', $_aceitou_termo, PDO::PARAM_BOOL);
        $stmt->bindParam(':event', $_event, PDO::PARAM_STR);
        $stmt->bindParam(':doc_token', $_doc_token, PDO::PARAM_STR);
        $stmt->bindParam(':doc_name', $_doc_name, PDO::PARAM_STR);
        $stmt->bindParam(':signed_at', $_signed_at, PDO::PARAM_STR);
        $stmt->bindParam(':name', $_nome, PDO::PARAM_STR); // usando o mesmo nome
        $stmt->bindParam(':email', $_email, PDO::PARAM_STR);
        $stmt->bindParam(':cpf', $_cpf, PDO::PARAM_STR);
        $stmt->bindParam(':has_signed', $_has_signed, PDO::PARAM_BOOL);
        $stmt->bindParam(':cel_informado', $_cel_informado, PDO::PARAM_STR);
        $stmt->bindParam(':limite', $_limite, PDO::PARAM_STR);
        $stmt->bindParam(':chave_pix', $_chave_pix, PDO::PARAM_STR);
        
        $stmt->execute();
        $msg_grava_cad = "cadastrado";
        
    } else {
        // UPDATE
        error_log("DEBUG UPDATE - REPROVAÇÃO: " . ($reprovar === true ? 'SIM' : ($reprovar === false ? 'NÃO' : 'NULL')));
        
        if ($reprovar === true) {
            error_log("DEBUG UPDATE - EXECUTANDO REPROVAÇÃO!");
            // Se marcado para reprovar, setar reprovado = true e atualizar data_hora
            $sql = "UPDATE sind.associados_sasmais SET 
                    codigo = :codigo,
                    nome = :nome,
                    celular = :celular,
                    data_hora = :data_hora,
                    autorizado = :autorizado,
                    aceitou_termo = :aceitou_termo,
                    event = :event,
                    doc_token = :doc_token,
                    doc_name = :doc_name,
                    signed_at = :signed_at,
                    name = :name,
                    email = :email,
                    cpf = :cpf,
                    has_signed = :has_signed,
                    cel_informado = :cel_informado,
                    limite = :limite,
                    chave_pix = :chave_pix,
                    reprovado = true
                    WHERE id = :id";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $_id, PDO::PARAM_INT);
            $stmt->bindParam(':codigo', $_codigo, PDO::PARAM_STR);
            $stmt->bindParam(':nome', $_nome, PDO::PARAM_STR);
            $stmt->bindParam(':celular', $_celular, PDO::PARAM_STR);
            $stmt->bindParam(':data_hora', $_data_hora, PDO::PARAM_STR);
            $stmt->bindParam(':autorizado', $_autorizado, PDO::PARAM_BOOL);
            $stmt->bindParam(':aceitou_termo', $_aceitou_termo, PDO::PARAM_BOOL);
            $stmt->bindParam(':event', $_event, PDO::PARAM_STR);
            $stmt->bindParam(':doc_token', $_doc_token, PDO::PARAM_STR);
            $stmt->bindParam(':doc_name', $_doc_name, PDO::PARAM_STR);
            $stmt->bindParam(':signed_at', $_signed_at, PDO::PARAM_STR);
            $stmt->bindParam(':name', $_nome, PDO::PARAM_STR);
            $stmt->bindParam(':email', $_email, PDO::PARAM_STR);
            $stmt->bindParam(':cpf', $_cpf, PDO::PARAM_STR);
            $stmt->bindParam(':has_signed', $_has_signed, PDO::PARAM_BOOL);
            $stmt->bindParam(':cel_informado', $_cel_informado, PDO::PARAM_STR);
            $stmt->bindParam(':limite', $_limite, PDO::PARAM_STR);
            $stmt->bindParam(':chave_pix', $_chave_pix, PDO::PARAM_STR);
            
        } else if ($reprovar === false) {
            error_log("DEBUG UPDATE - EXECUTANDO REMOÇÃO DE REPROVAÇÃO!");
            // Se marcado para NÃO reprovar, setar reprovado = false e atualizar data_hora
            $sql = "UPDATE sind.associados_sasmais SET 
                    codigo = :codigo,
                    nome = :nome,
                    celular = :celular,
                    data_hora = :data_hora,
                    autorizado = :autorizado,
                    aceitou_termo = :aceitou_termo,
                    event = :event,
                    doc_token = :doc_token,
                    doc_name = :doc_name,
                    signed_at = :signed_at,
                    name = :name,
                    email = :email,
                    cpf = :cpf,
                    has_signed = :has_signed,
                    cel_informado = :cel_informado,
                    limite = :limite,
                    chave_pix = :chave_pix,
                    reprovado = false
                    WHERE id = :id";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $_id, PDO::PARAM_INT);
            $stmt->bindParam(':codigo', $_codigo, PDO::PARAM_STR);
            $stmt->bindParam(':nome', $_nome, PDO::PARAM_STR);
            $stmt->bindParam(':celular', $_celular, PDO::PARAM_STR);
            $stmt->bindParam(':data_hora', $_data_hora, PDO::PARAM_STR);
            $stmt->bindParam(':autorizado', $_autorizado, PDO::PARAM_BOOL);
            $stmt->bindParam(':aceitou_termo', $_aceitou_termo, PDO::PARAM_BOOL);
            $stmt->bindParam(':event', $_event, PDO::PARAM_STR);
            $stmt->bindParam(':doc_token', $_doc_token, PDO::PARAM_STR);
            $stmt->bindParam(':doc_name', $_doc_name, PDO::PARAM_STR);
            $stmt->bindParam(':signed_at', $_signed_at, PDO::PARAM_STR);
            $stmt->bindParam(':name', $_nome, PDO::PARAM_STR);
            $stmt->bindParam(':email', $_email, PDO::PARAM_STR);
            $stmt->bindParam(':cpf', $_cpf, PDO::PARAM_STR);
            $stmt->bindParam(':has_signed', $_has_signed, PDO::PARAM_BOOL);
            $stmt->bindParam(':cel_informado', $_cel_informado, PDO::PARAM_STR);
            $stmt->bindParam(':limite', $_limite, PDO::PARAM_STR);
            $stmt->bindParam(':chave_pix', $_chave_pix, PDO::PARAM_STR);
            
            // DEBUG ANTES DO EXECUTE (UPDATE REMOÇÃO REPROVAÇÃO)
            // Removido log de data_pgto pois não é usado neste SQL
            
        } else {
            error_log("DEBUG UPDATE - EXECUTANDO APROVAÇÃO NORMAL!");
            // Se não está reprovando, verificar se tem valor aprovado > 0 para limpar reprovado
            $limpar_reprovado = (floatval($_valor_aprovado) > 0);
            
            $sql = "UPDATE sind.associados_sasmais SET 
                    codigo = :codigo,
                    nome = :nome,
                    celular = :celular,
                    autorizado = :autorizado,
                    aceitou_termo = :aceitou_termo,
                    event = :event,
                    doc_token = :doc_token,
                    doc_name = :doc_name,
                    signed_at = :signed_at,
                    name = :name,
                    email = :email,
                    cpf = :cpf,
                    has_signed = :has_signed,
                    cel_informado = :cel_informado,
                    limite = :limite,
                    valor_aprovado = :valor_aprovado,
                    data_pgto = :data_pgto,
                    chave_pix = :chave_pix";
                    
            // Se tem valor aprovado > 0, limpar reprovação
            if ($limpar_reprovado) {
                $sql .= ", reprovado = false";
                error_log("DEBUG UPDATE - Limpando reprovação (valor aprovado > 0)");
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $_id, PDO::PARAM_INT);
            $stmt->bindParam(':codigo', $_codigo, PDO::PARAM_STR);
            $stmt->bindParam(':nome', $_nome, PDO::PARAM_STR);
            $stmt->bindParam(':celular', $_celular, PDO::PARAM_STR);
            $stmt->bindParam(':autorizado', $_autorizado, PDO::PARAM_BOOL);
            $stmt->bindParam(':aceitou_termo', $_aceitou_termo, PDO::PARAM_BOOL);
            $stmt->bindParam(':event', $_event, PDO::PARAM_STR);
            $stmt->bindParam(':doc_token', $_doc_token, PDO::PARAM_STR);
            $stmt->bindParam(':doc_name', $_doc_name, PDO::PARAM_STR);
            $stmt->bindParam(':signed_at', $_signed_at, PDO::PARAM_STR);
            $stmt->bindParam(':name', $_nome, PDO::PARAM_STR);
            $stmt->bindParam(':email', $_email, PDO::PARAM_STR);
            $stmt->bindParam(':cpf', $_cpf, PDO::PARAM_STR);
            $stmt->bindParam(':has_signed', $_has_signed, PDO::PARAM_BOOL);
            $stmt->bindParam(':cel_informado', $_cel_informado, PDO::PARAM_STR);
            $stmt->bindParam(':limite', $_limite, PDO::PARAM_STR);
            $stmt->bindParam(':valor_aprovado', $_valor_aprovado, PDO::PARAM_STR);
            // Bind específico para campo TIMESTAMP (UPDATE APROVAÇÃO NORMAL)
            if ($_data_pgto === null) {
                $stmt->bindValue(':data_pgto', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':data_pgto', $_data_pgto, PDO::PARAM_STR);
            }
            $stmt->bindParam(':chave_pix', $_chave_pix, PDO::PARAM_STR);
        }
        
        // DEBUG ANTES DO EXECUTE (UPDATE APROVAÇÃO NORMAL)
        error_log("DEBUG UPDATE APROVAÇÃO NORMAL - data_pgto antes do execute: " . ($_data_pgto === null ? 'NULL' : "'" . $_data_pgto . "'"));
        error_log("DEBUG SQL COMPLETO: " . $sql);
        error_log("DEBUG PARÂMETROS: ID=" . $_id . ", data_pgto=" . ($_data_pgto === null ? 'NULL' : $_data_pgto));
        
        // Capturar erros específicos do PDO durante execução
        try {
            $stmt->execute();
            error_log("✅ Execute() executado sem erros PDO");
        } catch (PDOException $pdo_error) {
            error_log("❌ ERRO PDO no execute(): " . $pdo_error->getMessage());
            error_log("❌ SQLSTATE: " . $pdo_error->getCode());
            error_log("❌ Info adicional: " . print_r($stmt->errorInfo(), true));
            throw $pdo_error; // Re-throw para não quebrar o fluxo normal
        }
        $linhas_afetadas = $stmt->rowCount();
        error_log("DEBUG UPDATE - Query executada com sucesso! Linhas afetadas: " . $linhas_afetadas);
        error_log("DEBUG UPDATE - Campo data_pgto enviado para o banco: " . ($_data_pgto === null ? 'NULL' : "'" . $_data_pgto . "'"));
        
        // VERIFICAÇÃO PÓS-EXECUÇÃO: Confirmar se data_pgto foi realmente gravado
        if ($_data_pgto !== null && $_id) {
            try {
                $verify_sql = "SELECT data_pgto FROM sind.associados_sasmais WHERE id = :id";
                $verify_stmt = $pdo->prepare($verify_sql);
                $verify_stmt->bindParam(':id', $_id, PDO::PARAM_INT);
                $verify_stmt->execute();
                $resultado = $verify_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($resultado) {
                    error_log("VERIFICAÇÃO PÓS-EXECUÇÃO - data_pgto no banco: " . ($resultado['data_pgto'] ? "'" . $resultado['data_pgto'] . "'" : 'NULL'));
                    if ($resultado['data_pgto'] === null) {
                        error_log("⚠️ PROBLEMA: data_pgto foi enviado mas está NULL no banco!");
                    } else {
                        error_log("✅ SUCESSO: data_pgto foi gravado corretamente no banco");
                    }
                } else {
                    error_log("❌ ERRO: Registro não encontrado para verificação");
                }
            } catch (Exception $e) {
                error_log("❌ ERRO na verificação pós-execução: " . $e->getMessage());
            }
        }
        
        $msg_grava_cad = "atualizado";
    }
    
    error_log("DEBUG FINAL - Resultado: " . $msg_grava_cad);
    echo $msg_grava_cad;

} catch (PDOException $erro) {
    if($erro->getCode() === '42501'){
        $msg_grava_cad = "Seu usuario não tem permissão!";
    }else if($erro->getCode() === '23505'){
        $msg_grava_cad = "Código já existe!";
    }else{
        $msg_grava_cad = "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
    echo $msg_grava_cad;
}
?> 