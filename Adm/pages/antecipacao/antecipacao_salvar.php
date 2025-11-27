<?PHP
error_reporting(E_ALL ^ E_NOTICE);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

require "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$_limite = 0;
$_limite_hidden = 0;

$_usuario_cod       = $_POST['usuario_cod'];
$_divisao           = isset($_POST['divisao']) ? $_POST['divisao'] : 0;
$_matricula         = isset($_POST['C_matricula_antecipacao']) ? $_POST['C_matricula_antecipacao'] : "";
$_empregador        = isset($_POST['C_id_empregador_antecipacao']) ? $_POST['C_id_empregador_antecipacao'] : 0;
$_mes               = isset($_POST['C_mes']) ? $_POST['C_mes'] : "";
$_id_antecipacao    = isset($_POST['C_id_antecipacao']) ? $_POST['C_id_antecipacao'] : 0;
$_chave_pix         = isset($_POST['C_chave_pix_antecipacao']) ? $_POST['C_chave_pix_antecipacao'] : "";
$_associado_id      = isset($_POST['C_associado_id']) ? $_POST['C_associado_id'] : 0;

// Debug dos dados recebidos
error_log("DEBUG GERAL: Dados recebidos - C_aprovado: " . ($_POST['C_aprovado'] ?? 'NÃO DEFINIDO'));
error_log("DEBUG GERAL: Matricula: {$_matricula}, Empregador: {$_empregador}, Mes: {$_mes}");
error_log("DEBUG GERAL: C_associado_id recebido: {$_associado_id}");
error_log("DEBUG GERAL: POST completo: " . print_r($_POST, true));

// ✅ BUSCAR id_associado se não foi enviado via POST
if (empty($_associado_id) && !empty($_matricula) && !empty($_empregador)) {
    error_log("DEBUG GERAL: C_associado_id está vazio, buscando na tabela sind.associado...");
    error_log("DEBUG GERAL: Critérios de busca - Matricula: {$_matricula}, Empregador: {$_empregador}, Divisao: {$_divisao}");
    
    $sql_busca_id = "SELECT id FROM sind.associado WHERE codigo = :matricula AND empregador = :empregador AND id_divisao = :divisao LIMIT 1";
    $stmt_busca = $pdo->prepare($sql_busca_id);
    $stmt_busca->bindParam(':matricula', $_matricula, PDO::PARAM_STR);
    $stmt_busca->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
    $stmt_busca->bindParam(':divisao', $_divisao, PDO::PARAM_INT);
    $stmt_busca->execute();
    $resultado_busca = $stmt_busca->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado_busca) {
        $_associado_id = $resultado_busca['id'];
        error_log("DEBUG GERAL: ✅ id_associado encontrado: {$_associado_id}");
    } else {
        error_log("DEBUG GERAL: ❌ ERRO: Não foi possível encontrar id_associado para matrícula {$_matricula}, empregador {$_empregador} e divisão {$_divisao}");
    }
}
$valor_a_descontar = $_POST['C_valor_a_descontar'];
$valor_a_descontar = str_replace(['R$', ' '], '', $valor_a_descontar); // remove "R$" e espaços normais
$valor_a_descontar = str_replace('.', '', $valor_a_descontar); // remove separador de milhar
$valor_a_descontar = str_replace(',', '.', $valor_a_descontar); // converte vírgula decimal para ponto
$valor_a_descontar = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $valor_a_descontar); // remove todos os espaços, inclusive não quebráveis, do início/fim
$_valor = $valor_a_descontar;
$data               = new DateTime();
if($_POST['C_aprovado'] == "1"){
    $_data_aprovacao    = null;
}else {
    $_data_aprovacao    = $data->format('Y-m-d');
}
if ($_POST['C_aprovado'] == "2") {
    $_aprovado = true;
} else if ($_POST['C_aprovado'] == "3") {
    $_aprovado = false;
} else {
    $_aprovado = null;
}

// Certifique-se de que o valor seja booleano ou null
$_aprovado = is_null($_aprovado) ? null : (bool)$_aprovado;

$stmt = new stdClass();

$msg_grava_cad="";

    $sql = "UPDATE sind.antecipacao SET ";
    $sql .= "aprovado = :aprovado, ";
    $sql .= "data_aprovacao = :data_aprovacao, ";
    $sql .= "chave_pix = :chave_pix ";
    $sql .= "WHERE id = :id_antecipacao";

    $msg_grava_cad = "atualizado";
    try {

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':aprovado', $_aprovado, PDO::PARAM_BOOL); // Revertido para PDO::PARAM_BOOL
        $stmt->bindParam(':data_aprovacao', $_data_aprovacao, PDO::PARAM_STR);
        $stmt->bindParam(':chave_pix', $_chave_pix, PDO::PARAM_STR);
        $stmt->bindParam(':id_antecipacao', $_id_antecipacao, PDO::PARAM_INT);
        
        // Verificar o estado atual da antecipação antes da atualização
        $sql_verifica_estado = "SELECT aprovado, matricula, empregador, mes, valor, valor_a_descontar FROM sind.antecipacao 
                               WHERE id = :id_antecipacao";
        $stmt_verifica = $pdo->prepare($sql_verifica_estado);
        $stmt_verifica->bindParam(':id_antecipacao', $_id_antecipacao, PDO::PARAM_INT);
        $stmt_verifica->execute();
        $estado_atual = $stmt_verifica->fetch(PDO::FETCH_ASSOC);
        
        $stmt->execute();

        // ✅ ATUALIZAR CAMPO APROVADO NA TABELA CONTA
        // Se status = "Aprovado" (C_aprovado = "2"), atualizar aprovado = true na tabela conta
        // Se status = "Reprovado" (C_aprovado = "3"), atualizar aprovado = false
        // Se status = "Analisando" (C_aprovado = "1"), atualizar aprovado = false
        error_log("DEBUG CONTA: ========== INICIANDO ATUALIZAÇÃO DO CAMPO APROVADO ==========");
        error_log("DEBUG CONTA: Status recebido (C_aprovado): " . $_POST['C_aprovado']);
        
        // Define o valor de aprovado baseado no status
        // Usando boolean PHP que será convertido corretamente pelo PostgreSQL
        if ($_POST['C_aprovado'] == "2") {
            $aprovado_conta = true;  // Aprovado
            error_log("DEBUG CONTA: Status = Aprovado (2) - Definindo aprovado = TRUE");
        } else {
            $aprovado_conta = false; // Analisando (1) ou Reprovado (3)
            error_log("DEBUG CONTA: Status = " . $_POST['C_aprovado'] . " - Definindo aprovado = FALSE");
        }
        
        // Primeiro, vamos verificar se existe registro na tabela conta com esses critérios
        $sql_verifica_conta = "SELECT lancamento, aprovado, valor, convenio, tipo 
                               FROM sind.conta 
                               WHERE associado = :associado 
                               AND mes = :mes 
                               AND empregador = :empregador 
                               AND divisao = :divisao
                               AND id_associado = :id_associado
                               AND valor = :valor";
        
        try {
            $stmt_verifica_conta = $pdo->prepare($sql_verifica_conta);
            $stmt_verifica_conta->bindParam(':associado', $_matricula, PDO::PARAM_STR);
            $stmt_verifica_conta->bindParam(':mes', $_mes, PDO::PARAM_STR);
            $stmt_verifica_conta->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
            $stmt_verifica_conta->bindParam(':divisao', $_divisao, PDO::PARAM_INT);
            $stmt_verifica_conta->bindParam(':id_associado', $_associado_id, PDO::PARAM_INT);
            $stmt_verifica_conta->bindParam(':valor', $_valor, PDO::PARAM_STR);
            $stmt_verifica_conta->execute();
            $registros_conta = $stmt_verifica_conta->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("DEBUG CONTA: Registros encontrados na tabela conta: " . count($registros_conta));
            foreach ($registros_conta as $reg) {
                error_log("DEBUG CONTA: - Lancamento: {$reg['lancamento']}, Tipo: {$reg['tipo']}, Valor: {$reg['valor']}, Convenio: {$reg['convenio']}, Aprovado atual: " . ($reg['aprovado'] === null ? 'NULL' : ($reg['aprovado'] === 't' ? 'true' : 'false')));
            }
        } catch (PDOException $erro_verifica) {
            error_log("DEBUG CONTA: ❌ ERRO ao verificar registros: " . $erro_verifica->getMessage());
        }
        
        // ✅ VERIFICAR SE EXISTE REGISTRO COM CONVENIO 249 (além da antecipação)
        $sql_verifica_249 = "SELECT lancamento, aprovado, valor, convenio, tipo 
                             FROM sind.conta 
                             WHERE associado = :associado 
                             AND mes = :mes 
                             AND empregador = :empregador 
                             AND divisao = :divisao
                             AND id_associado = :id_associado
                             AND convenio = 249";
        
        $tem_convenio_249 = false;
        try {
            $stmt_verifica_249 = $pdo->prepare($sql_verifica_249);
            $stmt_verifica_249->bindParam(':associado', $_matricula, PDO::PARAM_STR);
            $stmt_verifica_249->bindParam(':mes', $_mes, PDO::PARAM_STR);
            $stmt_verifica_249->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
            $stmt_verifica_249->bindParam(':divisao', $_divisao, PDO::PARAM_INT);
            $stmt_verifica_249->bindParam(':id_associado', $_associado_id, PDO::PARAM_INT);
            $stmt_verifica_249->execute();
            $registros_249 = $stmt_verifica_249->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($registros_249) == 1) {
                $tem_convenio_249 = true;
                error_log("DEBUG CONTA: ✅ Encontrado 1 registro com convenio = 249");
                error_log("DEBUG CONTA: - Lancamento: {$registros_249[0]['lancamento']}, Valor: {$registros_249[0]['valor']}, Aprovado atual: " . ($registros_249[0]['aprovado'] === null ? 'NULL' : ($registros_249[0]['aprovado'] === 't' ? 'true' : 'false')));
            } else {
                error_log("DEBUG CONTA: ℹ️ Encontrados " . count($registros_249) . " registros com convenio = 249 (não será atualizado)");
            }
        } catch (PDOException $erro_verifica_249) {
            error_log("DEBUG CONTA: ❌ ERRO ao verificar convenio 249: " . $erro_verifica_249->getMessage());
        }
        
        // Agora faz o UPDATE apenas nos registros de ANTECIPACAO
        // Usando CAST para garantir conversão correta para boolean
        $sql_update_conta = "UPDATE sind.conta 
                            SET aprovado = CAST(:aprovado AS boolean)
                            WHERE associado = :associado 
                            AND mes = :mes 
                            AND empregador = :empregador 
                            AND divisao = :divisao
                            AND id_associado = :id_associado
                            AND valor = :valor
                            AND tipo = 'ANTECIPACAO'";
        
        try {
            error_log("DEBUG CONTA: ========== INICIANDO UPDATE DA ANTECIPACAO ==========");
            error_log("DEBUG CONTA: Executando UPDATE com os seguintes parâmetros:");
            error_log("DEBUG CONTA: - aprovado: " . ($aprovado_conta ? 'TRUE (1)' : 'FALSE (0)'));
            error_log("DEBUG CONTA: - associado (matricula): '{$_matricula}'");
            error_log("DEBUG CONTA: - mes: '{$_mes}'");
            error_log("DEBUG CONTA: - empregador: {$_empregador}");
            error_log("DEBUG CONTA: - divisao: {$_divisao}");
            error_log("DEBUG CONTA: - id_associado: {$_associado_id}");
            error_log("DEBUG CONTA: - valor: '{$_valor}'");
            error_log("DEBUG CONTA: - tipo: 'ANTECIPACAO'");
            
            // Verificar se o registro existe ANTES do UPDATE
            $sql_check = "SELECT lancamento, aprovado, convenio, tipo FROM sind.conta 
                         WHERE associado = :associado 
                         AND mes = :mes 
                         AND empregador = :empregador 
                         AND divisao = :divisao
                         AND id_associado = :id_associado
                         AND valor = :valor
                         AND tipo = 'ANTECIPACAO'";
            
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->bindParam(':associado', $_matricula, PDO::PARAM_STR);
            $stmt_check->bindParam(':mes', $_mes, PDO::PARAM_STR);
            $stmt_check->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
            $stmt_check->bindParam(':divisao', $_divisao, PDO::PARAM_INT);
            $stmt_check->bindParam(':id_associado', $_associado_id, PDO::PARAM_INT);
            $stmt_check->bindParam(':valor', $_valor, PDO::PARAM_STR);
            $stmt_check->execute();
            $registro_antes = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($registro_antes) {
                error_log("DEBUG CONTA: ✅ Registro encontrado ANTES do UPDATE:");
                error_log("DEBUG CONTA:    - Lancamento: {$registro_antes['lancamento']}");
                error_log("DEBUG CONTA:    - Aprovado ANTES: " . ($registro_antes['aprovado'] === null ? 'NULL' : ($registro_antes['aprovado'] === 't' ? 'TRUE' : 'FALSE')));
                error_log("DEBUG CONTA:    - Convenio: {$registro_antes['convenio']}");
                error_log("DEBUG CONTA:    - Tipo: {$registro_antes['tipo']}");
            } else {
                error_log("DEBUG CONTA: ❌ NENHUM registro encontrado com esses critérios ANTES do UPDATE!");
            }
            
            $stmt_update_conta = $pdo->prepare($sql_update_conta);
            // Converte boolean PHP para string '1' ou '0' que o PostgreSQL aceita
            $stmt_update_conta->bindValue(':aprovado', $aprovado_conta ? '1' : '0', PDO::PARAM_STR);
            $stmt_update_conta->bindParam(':associado', $_matricula, PDO::PARAM_STR);
            $stmt_update_conta->bindParam(':mes', $_mes, PDO::PARAM_STR);
            $stmt_update_conta->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
            $stmt_update_conta->bindParam(':divisao', $_divisao, PDO::PARAM_INT);
            $stmt_update_conta->bindParam(':id_associado', $_associado_id, PDO::PARAM_INT);
            $stmt_update_conta->bindParam(':valor', $_valor, PDO::PARAM_STR);
            $stmt_update_conta->execute();
            
            $linhas_afetadas = $stmt_update_conta->rowCount();
            
            if ($linhas_afetadas > 0) {
                error_log("DEBUG CONTA: ✅✅✅ Campo aprovado ATUALIZADO COM SUCESSO! ✅✅✅");
                error_log("DEBUG CONTA: - Linhas afetadas: {$linhas_afetadas}");
                error_log("DEBUG CONTA: - Valor gravado: " . ($aprovado_conta ? 'TRUE' : 'FALSE'));
                
                // Verificar o valor DEPOIS do UPDATE
                $stmt_check->execute();
                $registro_depois = $stmt_check->fetch(PDO::FETCH_ASSOC);
                if ($registro_depois) {
                    error_log("DEBUG CONTA: ✅ Verificação APÓS UPDATE:");
                    error_log("DEBUG CONTA:    - Aprovado DEPOIS: " . ($registro_depois['aprovado'] === null ? 'NULL' : ($registro_depois['aprovado'] === 't' ? 'TRUE' : 'FALSE')));
                }
            } else {
                error_log("DEBUG CONTA: ⚠️ ATENÇÃO: UPDATE executado mas NENHUMA linha foi afetada!");
                error_log("DEBUG CONTA: ⚠️ Isso significa que NÃO EXISTE registro na tabela conta com esses critérios OU o tipo não é 'ANTECIPACAO'");
            }
        } catch (PDOException $erro_update) {
            error_log("DEBUG CONTA: ❌ ERRO ao atualizar campo aprovado: " . $erro_update->getMessage());
            error_log("DEBUG CONTA: ❌ SQL State: " . $erro_update->getCode());
        }
        
        // ✅ SE EXISTE EXATAMENTE 1 REGISTRO COM CONVENIO 249, ATUALIZAR TAMBÉM
        if ($tem_convenio_249) {
            error_log("DEBUG CONTA: ========== ATUALIZANDO REGISTRO COM CONVENIO 249 ==========");
            
            $sql_update_249 = "UPDATE sind.conta 
                              SET aprovado = CAST(:aprovado AS boolean)
                              WHERE associado = :associado 
                              AND mes = :mes 
                              AND empregador = :empregador 
                              AND divisao = :divisao
                              AND id_associado = :id_associado
                              AND convenio = 249";
            
            try {
                error_log("DEBUG CONTA: Executando UPDATE para convenio 249 com os mesmos parâmetros");
                
                $stmt_update_249 = $pdo->prepare($sql_update_249);
                $stmt_update_249->bindValue(':aprovado', $aprovado_conta ? '1' : '0', PDO::PARAM_STR);
                $stmt_update_249->bindParam(':associado', $_matricula, PDO::PARAM_STR);
                $stmt_update_249->bindParam(':mes', $_mes, PDO::PARAM_STR);
                $stmt_update_249->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
                $stmt_update_249->bindParam(':divisao', $_divisao, PDO::PARAM_INT);
                $stmt_update_249->bindParam(':id_associado', $_associado_id, PDO::PARAM_INT);
                $stmt_update_249->execute();
                
                $linhas_afetadas_249 = $stmt_update_249->rowCount();
                
                if ($linhas_afetadas_249 > 0) {
                    error_log("DEBUG CONTA: ✅✅✅ Convenio 249 ATUALIZADO COM SUCESSO! ✅✅✅");
                    error_log("DEBUG CONTA: - Linhas afetadas: {$linhas_afetadas_249}");
                    error_log("DEBUG CONTA: - Valor gravado: " . ($aprovado_conta ? 'TRUE' : 'FALSE'));
                } else {
                    error_log("DEBUG CONTA: ⚠️ ATENÇÃO: UPDATE do convenio 249 executado mas NENHUMA linha foi afetada!");
                }
            } catch (PDOException $erro_update_249) {
                error_log("DEBUG CONTA: ❌ ERRO ao atualizar convenio 249: " . $erro_update_249->getMessage());
                error_log("DEBUG CONTA: ❌ SQL State: " . $erro_update_249->getCode());
            }
            
            error_log("DEBUG CONTA: ========== FIM DA ATUALIZAÇÃO DO CONVENIO 249 ==========");
        }
        
        error_log("DEBUG CONTA: ========== FIM DA ATUALIZAÇÃO DO CAMPO APROVADO ==========");

        // Não insere mais na tabela conta quando aprovado pela primeira vez, pois isso já é feito automaticamente
        // quando o registro de antecipação é criado
        
        // ✅ IMPORTANTE: Quando a antecipação é reprovada (C_aprovado = "3"), NÃO remove o registro da tabela conta
        // Apenas atualiza o campo aprovado = false via UPDATE acima (linhas 182-226)
        // O registro permanece na tabela conta para histórico e controle

        $data2      = new DateTime();
        $data       = $data2->format('Y-m-d h:i:s');
        
        echo $msg_grava_cad;
        error_log("DEBUG GERAL: Processo finalizado com sucesso - msg: {$msg_grava_cad}");

    } catch (PDOException $erro) {
        if($erro->getCode() === '42501'){
            $msg_grava_cad = "Seu usuario não tem permissão!";
        }else{
            $msg_grava_cad = "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
        }
        error_log("DEBUG GERAL: ERRO no processo principal: " . $erro->getMessage());
        echo $msg_grava_cad;
    }