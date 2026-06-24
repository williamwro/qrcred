<?PHP
error_reporting(E_ALL ^ E_NOTICE);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

// ✅ Função helper para gravar logs com file_put_contents
function debug_log($message) {
    $log_file = __DIR__ . '/debug_antecipacao.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

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
debug_log("\n\n========== NOVO PROCESSAMENTO INICIADO ==========");
debug_log("DEBUG GERAL: Dados recebidos - C_aprovado: " . ($_POST['C_aprovado'] ?? 'NÃO DEFINIDO'));
debug_log("DEBUG GERAL: Matricula: {$_matricula}, Empregador: {$_empregador}, Mes: {$_mes}");
debug_log("DEBUG GERAL: C_associado_id recebido: {$_associado_id}");
debug_log("DEBUG GERAL: Divisao: {$_divisao}");

// ✅ BUSCAR id_associado se não foi enviado via POST
if (empty($_associado_id) && !empty($_matricula) && !empty($_empregador)) {
    debug_log("DEBUG GERAL: C_associado_id está vazio, buscando na tabela sind.associado...");
    debug_log("DEBUG GERAL: Critérios de busca - Matricula: {$_matricula}, Empregador: {$_empregador}, Divisao: {$_divisao}");
    
    $sql_busca_id = "SELECT id FROM sind.associado WHERE codigo = :matricula AND empregador = :empregador AND id_divisao = :divisao LIMIT 1";
    $stmt_busca = $pdo->prepare($sql_busca_id);
    $stmt_busca->bindParam(':matricula', $_matricula, PDO::PARAM_STR);
    $stmt_busca->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
    $stmt_busca->bindParam(':divisao', $_divisao, PDO::PARAM_INT);
    $stmt_busca->execute();
    $resultado_busca = $stmt_busca->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado_busca) {
        $_associado_id = $resultado_busca['id'];
        debug_log("DEBUG GERAL: ✅ id_associado encontrado: {$_associado_id}");
    } else {
        debug_log("DEBUG GERAL: ❌ ERRO: Não foi possível encontrar id_associado para matrícula {$_matricula}, empregador {$_empregador} e divisão {$_divisao}");
    }
} else {
    debug_log("DEBUG GERAL: ✅ C_associado_id já foi enviado via POST: {$_associado_id}");
}
$valor_a_descontar = $_POST['C_valor_a_descontar'];
$valor_a_descontar = str_replace(['R$', ' '], '', $valor_a_descontar);
$valor_a_descontar = str_replace('.', '', $valor_a_descontar);
$valor_a_descontar = str_replace(',', '.', $valor_a_descontar);
$valor_a_descontar = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $valor_a_descontar);
$_valor = $valor_a_descontar;

$valor_taxa_raw = isset($_POST['C_valor_taxa']) ? $_POST['C_valor_taxa'] : '0';
$valor_taxa_raw = str_replace(['R$', ' '], '', $valor_taxa_raw);
$valor_taxa_raw = str_replace('.', '', $valor_taxa_raw);
$valor_taxa_raw = str_replace(',', '.', $valor_taxa_raw);
$valor_taxa_raw = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $valor_taxa_raw);
$_valor_taxa = (is_numeric($valor_taxa_raw) && floatval($valor_taxa_raw) > 0) ? $valor_taxa_raw : '0';
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
        debug_log("DEBUG CONTA: ========== INICIANDO ATUALIZAÇÃO DO CAMPO APROVADO ==========");
        debug_log("DEBUG CONTA: Status recebido (C_aprovado): " . $_POST['C_aprovado']);
        
        // Define o valor de aprovado baseado no status
        // Usando boolean PHP que será convertido corretamente pelo PostgreSQL
        if ($_POST['C_aprovado'] == "2") {
            $aprovado_conta = true;  // Aprovado
            debug_log("DEBUG CONTA: Status = Aprovado (2) - Definindo aprovado = TRUE");
        } else {
            $aprovado_conta = false; // Analisando (1) ou Reprovado (3)
            debug_log("DEBUG CONTA: Status = " . $_POST['C_aprovado'] . " - Definindo aprovado = FALSE");
        }
        
        // Primeiro, vamos verificar se existe registro na tabela conta com esses critérios
        $sql_verifica_conta = "SELECT lancamento, aprovado, valor, convenio, tipo 
                               FROM sind.conta 
                               WHERE associado = :associado 
                               AND mes = :mes 
                               AND empregador = :empregador 
                               AND divisao = :divisao
                               AND id_associado = :id_associado
                               AND tipo = 'ANTECIPACAO'";
        
        try {
            $stmt_verifica_conta = $pdo->prepare($sql_verifica_conta);
            $stmt_verifica_conta->bindParam(':associado', $_matricula, PDO::PARAM_STR);
            $stmt_verifica_conta->bindParam(':mes', $_mes, PDO::PARAM_STR);
            $stmt_verifica_conta->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
            $stmt_verifica_conta->bindParam(':divisao', $_divisao, PDO::PARAM_INT);
            $stmt_verifica_conta->bindParam(':id_associado', $_associado_id, PDO::PARAM_INT);
            $stmt_verifica_conta->execute();
            $registros_conta = $stmt_verifica_conta->fetchAll(PDO::FETCH_ASSOC);
            
            debug_log("DEBUG CONTA: Registros encontrados na tabela conta: " . count($registros_conta));
            foreach ($registros_conta as $reg) {
                debug_log("DEBUG CONTA: - Lancamento: {$reg['lancamento']}, Tipo: {$reg['tipo']}, Valor: {$reg['valor']}, Convenio: {$reg['convenio']}, Aprovado atual: " . ($reg['aprovado'] === null ? 'NULL' : ($reg['aprovado'] === 't' ? 'true' : 'false')));
            }
        } catch (PDOException $erro_verifica) {
            debug_log("DEBUG CONTA: ❌ ERRO ao verificar registros: " . $erro_verifica->getMessage());
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
        
        // ✅ VERIFICAÇÃO ANTI-DUPLICAÇÃO: Verificar se já existe registro aprovado com convenio 221
        // Antes de aprovar, verifica se já não existe outro registro aprovado para evitar duplicação
        $sql_verifica_duplicacao = "SELECT COUNT(*) as total, lancamento 
                                    FROM sind.conta 
                                    WHERE associado = :associado 
                                    AND mes = :mes 
                                    AND empregador = :empregador 
                                    AND divisao = :divisao
                                    AND id_associado = :id_associado
                                    AND convenio = 221
                                    AND aprovado = true
                                    AND tipo = 'ANTECIPACAO'
                                    GROUP BY lancamento";
        
        $pode_atualizar = true;
        try {
            $stmt_verifica_dup = $pdo->prepare($sql_verifica_duplicacao);
            $stmt_verifica_dup->bindParam(':associado', $_matricula, PDO::PARAM_STR);
            $stmt_verifica_dup->bindParam(':mes', $_mes, PDO::PARAM_STR);
            $stmt_verifica_dup->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
            $stmt_verifica_dup->bindParam(':divisao', $_divisao, PDO::PARAM_INT);
            $stmt_verifica_dup->bindParam(':id_associado', $_associado_id, PDO::PARAM_INT);
            $stmt_verifica_dup->execute();
            $resultado_dup = $stmt_verifica_dup->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado_dup && $resultado_dup['total'] > 0) {
                $pode_atualizar = false;
                debug_log("DEBUG CONTA: ⚠️ JÁ EXISTE registro aprovado com convenio 221 para este associado/mês/empregador");
                debug_log("DEBUG CONTA: ⚠️ Lançamento existente: {$resultado_dup['lancamento']}");
                debug_log("DEBUG CONTA: ⚠️ UPDATE NÃO SERÁ EXECUTADO para evitar duplicação");
            } else {
                debug_log("DEBUG CONTA: ✅ Nenhum registro aprovado com convenio 221 encontrado - pode prosseguir com UPDATE");
            }
        } catch (PDOException $erro_verifica_dup) {
            debug_log("DEBUG CONTA: ❌ ERRO ao verificar duplicação: " . $erro_verifica_dup->getMessage());
            // Em caso de erro na verificação, permite o UPDATE (comportamento padrão)
        }
        
        // Agora faz o UPDATE apenas nos registros de ANTECIPACAO
        // Usando CAST para garantir conversão correta para boolean
        // CORREÇÃO: Removida comparação por valor para evitar problemas de formatação numérica
        $sql_update_conta = "UPDATE sind.conta 
                            SET aprovado = CAST(:aprovado AS boolean)
                            WHERE associado = :associado 
                            AND mes = :mes 
                            AND empregador = :empregador 
                            AND divisao = :divisao
                            AND id_associado = :id_associado
                            AND tipo = 'ANTECIPACAO'";
        
        // UPDATE sempre executa; $pode_atualizar só bloqueia o INSERT (evita duplicação)
        try {
            debug_log("DEBUG CONTA: ========== INICIANDO UPDATE DA ANTECIPACAO ==========");
            debug_log("DEBUG CONTA: Executando UPDATE com os seguintes parâmetros:");
            debug_log("DEBUG CONTA: - aprovado: " . ($aprovado_conta ? 'TRUE (1)' : 'FALSE (0)'));
            debug_log("DEBUG CONTA: - associado (matricula): '{$_matricula}'");
            debug_log("DEBUG CONTA: - mes: '{$_mes}'");
            debug_log("DEBUG CONTA: - empregador: {$_empregador}");
            debug_log("DEBUG CONTA: - divisao: {$_divisao}");
            debug_log("DEBUG CONTA: - id_associado: {$_associado_id}");
            debug_log("DEBUG CONTA: - tipo: 'ANTECIPACAO'");
            debug_log("DEBUG CONTA: - NOTA: Comparação por valor removida para evitar problemas de formatação");
            
            // Verificar se o registro existe ANTES do UPDATE
            $sql_check = "SELECT lancamento, aprovado, convenio, tipo, valor FROM sind.conta 
                         WHERE associado = :associado 
                         AND mes = :mes 
                         AND empregador = :empregador 
                         AND divisao = :divisao
                         AND id_associado = :id_associado
                         AND tipo = 'ANTECIPACAO'";
            
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->bindParam(':associado', $_matricula, PDO::PARAM_STR);
            $stmt_check->bindParam(':mes', $_mes, PDO::PARAM_STR);
            $stmt_check->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
            $stmt_check->bindParam(':divisao', $_divisao, PDO::PARAM_INT);
            $stmt_check->bindParam(':id_associado', $_associado_id, PDO::PARAM_INT);
            $stmt_check->execute();
            $registro_antes = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($registro_antes) {
                debug_log("DEBUG CONTA: ✅ Registro encontrado ANTES do UPDATE:");
                debug_log("DEBUG CONTA:    - Lancamento: {$registro_antes['lancamento']}");
                debug_log("DEBUG CONTA:    - Aprovado ANTES: " . ($registro_antes['aprovado'] === null ? 'NULL' : ($registro_antes['aprovado'] === 't' ? 'TRUE' : 'FALSE')));
                debug_log("DEBUG CONTA:    - Convenio: {$registro_antes['convenio']}");
                debug_log("DEBUG CONTA:    - Tipo: {$registro_antes['tipo']}");
                debug_log("DEBUG CONTA:    - Valor no banco: {$registro_antes['valor']}");
            } else {
                debug_log("DEBUG CONTA: ❌ NENHUM registro encontrado com esses critérios ANTES do UPDATE!");
            }
            
            $stmt_update_conta = $pdo->prepare($sql_update_conta);
            // Converte boolean PHP para string '1' ou '0' que o PostgreSQL aceita
            $stmt_update_conta->bindValue(':aprovado', $aprovado_conta ? '1' : '0', PDO::PARAM_STR);
            $stmt_update_conta->bindParam(':associado', $_matricula, PDO::PARAM_STR);
            $stmt_update_conta->bindParam(':mes', $_mes, PDO::PARAM_STR);
            $stmt_update_conta->bindParam(':empregador', $_empregador, PDO::PARAM_INT);
            $stmt_update_conta->bindParam(':divisao', $_divisao, PDO::PARAM_INT);
            $stmt_update_conta->bindParam(':id_associado', $_associado_id, PDO::PARAM_INT);
            $stmt_update_conta->execute();
            
            $linhas_afetadas = $stmt_update_conta->rowCount();
            
            if ($linhas_afetadas > 0) {
                debug_log("DEBUG CONTA: ✅✅✅ Campo aprovado ATUALIZADO COM SUCESSO! ✅✅✅");
                debug_log("DEBUG CONTA: - Linhas afetadas: {$linhas_afetadas}");
                debug_log("DEBUG CONTA: - Valor gravado: " . ($aprovado_conta ? 'TRUE' : 'FALSE'));
                
                // Verificar o valor DEPOIS do UPDATE
                $stmt_check->execute();
                $registro_depois = $stmt_check->fetch(PDO::FETCH_ASSOC);
                if ($registro_depois) {
                    debug_log("DEBUG CONTA: ✅ Verificação APÓS UPDATE:");
                    debug_log("DEBUG CONTA:    - Aprovado DEPOIS: " . ($registro_depois['aprovado'] === null ? 'NULL' : ($registro_depois['aprovado'] === 't' ? 'TRUE' : 'FALSE')));
                }
            } else {
                debug_log("DEBUG CONTA: ⚠️ ATENÇÃO: UPDATE executado mas NENHUMA linha foi afetada!");
                debug_log("DEBUG CONTA: ⚠️ Registro NÃO EXISTE na tabela conta");
                
                // Cria registros na tabela conta somente ao Aprovar (C_aprovado = "2")
                if ($_POST['C_aprovado'] == "2") {
                    if ($pode_atualizar) {
                        debug_log("DEBUG CONTA: ✅ Status = Aprovado - criando registros (antecipação + taxa)...");
                        try {
                            // Buscar data_solicitacao da antecipação (fallback: data atual)
                            $data_solicitacao = date('Y-m-d');
                            $stmt_busca_data = $pdo->prepare("SELECT data_solicitacao FROM sind.antecipacao WHERE id = :id_antecipacao");
                            $stmt_busca_data->bindParam(':id_antecipacao', $_id_antecipacao, PDO::PARAM_INT);
                            $stmt_busca_data->execute();
                            $dados_data = $stmt_busca_data->fetch(PDO::FETCH_ASSOC);
                            if ($dados_data && !empty($dados_data['data_solicitacao'])) {
                                $data_solicitacao = $dados_data['data_solicitacao'];
                            }

                            // Próximo número de lançamento
                            $stmt_max_lanc = $pdo->prepare("SELECT COALESCE(MAX(CAST(lancamento AS INTEGER)), 0) + 1 AS proximo FROM sind.conta WHERE CAST(lancamento AS text) ~ '^[0-9]+$'");
                            $stmt_max_lanc->execute();
                            $proximo_lanc = (int)$stmt_max_lanc->fetch(PDO::FETCH_ASSOC)['proximo'];
                            $hora_atual   = date('H:i:s');

                            $sql_insert_base = "INSERT INTO sind.conta 
                                               (lancamento, associado, mes, empregador, divisao, id_associado, valor, tipo, aprovado, convenio, data, hora) 
                                               VALUES 
                                               (:lancamento, :associado, :mes, :empregador, :divisao, :id_associado, :valor, 'ANTECIPACAO', CAST('1' AS boolean), :convenio, :data, :hora)";

                            // INSERT 1 — Antecipação (convenio 221, valor = valor_a_descontar)
                            $stmt_ant = $pdo->prepare($sql_insert_base);
                            $conv_ant = 221;
                            $stmt_ant->bindParam(':lancamento',   $proximo_lanc,    PDO::PARAM_INT);
                            $stmt_ant->bindParam(':associado',    $_matricula,      PDO::PARAM_STR);
                            $stmt_ant->bindParam(':mes',          $_mes,            PDO::PARAM_STR);
                            $stmt_ant->bindParam(':empregador',   $_empregador,     PDO::PARAM_INT);
                            $stmt_ant->bindParam(':divisao',      $_divisao,        PDO::PARAM_INT);
                            $stmt_ant->bindParam(':id_associado', $_associado_id,   PDO::PARAM_INT);
                            $stmt_ant->bindParam(':valor',        $_valor,          PDO::PARAM_STR);
                            $stmt_ant->bindParam(':convenio',     $conv_ant,        PDO::PARAM_INT);
                            $stmt_ant->bindParam(':data',         $data_solicitacao,PDO::PARAM_STR);
                            $stmt_ant->bindParam(':hora',         $hora_atual,      PDO::PARAM_STR);
                            $stmt_ant->execute();
                            debug_log("DEBUG CONTA: ✅ INSERT antecipação - Lançamento: {$proximo_lanc}, Valor: {$_valor}, Convenio: 221");

                            // INSERT 2 — Taxa (convenio 249, valor = valor_taxa) somente se valor_taxa > 0
                            if (floatval($_valor_taxa) > 0) {
                                $proximo_lanc_taxa = $proximo_lanc + 1;
                                $conv_taxa = 249;
                                $stmt_taxa = $pdo->prepare($sql_insert_base);
                                $stmt_taxa->bindParam(':lancamento',   $proximo_lanc_taxa, PDO::PARAM_INT);
                                $stmt_taxa->bindParam(':associado',    $_matricula,        PDO::PARAM_STR);
                                $stmt_taxa->bindParam(':mes',          $_mes,              PDO::PARAM_STR);
                                $stmt_taxa->bindParam(':empregador',   $_empregador,       PDO::PARAM_INT);
                                $stmt_taxa->bindParam(':divisao',      $_divisao,          PDO::PARAM_INT);
                                $stmt_taxa->bindParam(':id_associado', $_associado_id,     PDO::PARAM_INT);
                                $stmt_taxa->bindParam(':valor',        $_valor_taxa,       PDO::PARAM_STR);
                                $stmt_taxa->bindParam(':convenio',     $conv_taxa,         PDO::PARAM_INT);
                                $stmt_taxa->bindParam(':data',         $data_solicitacao,  PDO::PARAM_STR);
                                $stmt_taxa->bindParam(':hora',         $hora_atual,        PDO::PARAM_STR);
                                $stmt_taxa->execute();
                                debug_log("DEBUG CONTA: ✅ INSERT taxa - Lançamento: {$proximo_lanc_taxa}, Valor: {$_valor_taxa}, Convenio: 249");
                            }
                        } catch (PDOException $erro_insert) {
                            debug_log("DEBUG CONTA: ❌ ERRO ao criar registros na tabela conta: " . $erro_insert->getMessage());
                        }
                    } else {
                        debug_log("DEBUG CONTA: ⛔ INSERT BLOQUEADO: Já existe registro aprovado com convenio 221 - evitando duplicação");
                    }
                } else {
                    debug_log("DEBUG CONTA: ⚠️ Status NÃO é Aprovado - INSERT não será executado");
                }
            }
            } catch (PDOException $erro_update) {
                debug_log("DEBUG CONTA: ❌ ERRO ao atualizar campo aprovado: " . $erro_update->getMessage());
                debug_log("DEBUG CONTA: ❌ SQL State: " . $erro_update->getCode());
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
        
        // Resposta com informações de debug
        $resposta = [
            'status' => $msg_grava_cad,
            'debug' => [
                'update_executado' => $pode_atualizar || $_POST['C_aprovado'] != "2",
                'bloqueado_duplicacao' => !$pode_atualizar && $_POST['C_aprovado'] == "2",
                'aprovado_definido' => $aprovado_conta,
                'associado_id' => $_associado_id,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
        debug_log("DEBUG GERAL: Processo finalizado com sucesso - msg: {$msg_grava_cad}");
        debug_log("DEBUG GERAL: Resposta JSON: " . json_encode($resposta));
        debug_log("========== PROCESSAMENTO FINALIZADO ==========\n\n");
        
        echo $msg_grava_cad;

    } catch (PDOException $erro) {
        if($erro->getCode() === '42501'){
            $msg_grava_cad = "Seu usuario não tem permissão!";
        }else{
            $msg_grava_cad = "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
        }
        debug_log("DEBUG GERAL: ERRO no processo principal: " . $erro->getMessage());
        echo $msg_grava_cad;
    }