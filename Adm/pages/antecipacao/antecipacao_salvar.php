<?PHP
/**
 * =====================================================================================
 *  ANTECIPAÇÃO — SALVAR (chamado pelo botão Salvar do modal "ANTECIPAÇÃO Alterando")
 * =====================================================================================
 *  REGRA CRÍTICA DE NEGÓCIO:
 *  Quando o status (select C_aprovado) muda para APROVADO (valor "2"), a gravação do
 *  lançamento na tabela sind.conta é OBRIGATÓRIA. Esta etapa NÃO PODE FALHAR em
 *  silêncio. Este arquivo foi reestruturado para tornar IMPOSSÍVEL aprovar a
 *  antecipação sem o lançamento correspondente na conta:
 *
 *  1) TRANSAÇÃO ÚNICA (BEGIN/COMMIT/ROLLBACK):
 *     O UPDATE em sind.antecipacao e TODAS as gravações em sind.conta acontecem
 *     dentro da MESMA transação. Se qualquer etapa falhar, TUDO é desfeito.
 *     Nunca mais existirá o estado "aprovado na antecipação, mas sem conta".
 *
 *  2) VALIDAÇÃO PRÉVIA:
 *     Antes de gravar qualquer coisa, os dados obrigatórios para o lançamento
 *     (matrícula, empregador, mês, divisão, id_associado e valor) são validados.
 *     Se faltar algo, devolve erro claro e NADA é alterado.
 *
 *  3) FONTE DA VERDADE É O BANCO:
 *     Os dados-chave são lidos do próprio registro de sind.antecipacao (com trava
 *     FOR UPDATE), usando o formulário apenas como reserva. Isso elimina as falhas
 *     antigas causadas por campo vazio no modal, sessionStorage expirado
 *     (divisao = "null"), formatação de moeda etc.
 *
 *  4) VERIFICAÇÃO FINAL OBRIGATÓRIA:
 *     Antes do COMMIT, o script CONFERE no banco que o lançamento aprovado
 *     (convênio 221, tipo ANTECIPACAO) realmente existe na sind.conta.
 *     Se não existir, é feito ROLLBACK e o usuário recebe mensagem de erro.
 *
 *  5) NENHUM ERRO É ENGOLIDO:
 *     As exceções não são mais capturadas apenas para gravar log e seguir adiante.
 *     Qualquer falha => ROLLBACK + resposta iniciada por "ERRO:" que o JavaScript
 *     exibe ao usuário, mantendo o modal aberto para nova tentativa.
 *
 *  6) CONCORRÊNCIA:
 *     - FOR UPDATE na linha da antecipação: dois usuários não alteram o mesmo
 *       registro ao mesmo tempo.
 *     - pg_advisory_xact_lock na geração do número de lançamento: elimina a
 *       corrida do MAX(lancamento)+1 (causa clássica de INSERT falhando por
 *       lançamento duplicado — antes, esse erro era silenciosamente ignorado).
 *     - Retry com SAVEPOINT caso outro módulo insira um lançamento concorrente.
 *
 *  RESPOSTAS POSSÍVEIS (texto puro, interpretadas pelo antecipacao_read_script.js):
 *    "atualizado"                      -> sucesso total (conta conferida no banco)
 *    "Seu usuario não tem permissão!"  -> erro de permissão (SQLSTATE 42501)
 *    "ERRO: ..."                       -> falha; NADA foi gravado (rollback)
 *
 *  Regras de negócio aplicadas:
 *    - ⭐ REGRA DO CLIENTE (12/08/2026): na sind.conta o campo aprovado (boolean)
 *      é gravado SEMPRE com o valor true — este sistema NUNCA grava false.
 *      Lançamento de antecipação não aprovada NÃO permanece na conta: é excluído.
 *      Há uma verificação final desta invariante antes do COMMIT.
 *    - Status 1 (Analisando): antecipacao.aprovado = NULL, data_aprovacao = NULL;
 *      lançamentos 221/249 são EXCLUÍDOS da conta (recriados ao aprovar de novo).
 *    - Status 2 (Aprovado): antecipacao.aprovado = true, data_aprovacao = hoje;
 *      se NÃO existir lançamento na sind.conta, INSERE convênio 221 (valor a
 *      descontar) e, se houver taxa > 0 e ainda não existir, convênio 249 (taxa).
 *      Em seguida marca aprovado = true (o trigger BEFORE INSERT
 *      fn_insere_taxa_cartao_automatica força aprovado=false em 221/249 na inserção;
 *      por isso o UPDATE depois do INSERT é obrigatório). Anti-duplicação mantida.
 *    - Status 3 (Reprovado): antecipacao.aprovado = false, data_aprovacao = hoje;
 *      EXCLUI da conta os lançamentos convênio 221 e 249 do associado/mês.
 * =====================================================================================
 */
error_reporting(E_ALL ^ E_NOTICE);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

// Buffer de saída: garante que a resposta enviada ao JavaScript seja EXATAMENTE a
// string esperada (sem avisos/espacos de includes corrompendo a comparação).
ob_start();

// ✅ Função helper para gravar logs com file_put_contents (mesmo arquivo de antes)
function debug_log($message) {
    $log_file = __DIR__ . '/debug_antecipacao.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}\n";
    @file_put_contents($log_file, $log_message, FILE_APPEND);
}

// ✅ Envia a resposta final (texto puro) ao navegador e encerra o script.
//    Descarta qualquer saída acidental gerada antes (notices, espaços de include...).
function responder($msg) {
    $lixo = ob_get_clean();
    if ($lixo !== false && trim($lixo) !== '') {
        debug_log("AVISO: saída inesperada descartada antes da resposta: " . substr($lixo, 0, 500));
    }
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo $msg;
    exit;
}

// ✅ Converte moeda brasileira ("R$ 1.234,56") para número em formato SQL ("1234.56").
//    Mesma lógica do código anterior (usada apenas como RESERVA — a fonte primária
//    dos valores agora é o próprio registro de sind.antecipacao).
function moeda_para_numero($valor) {
    $valor = (string)$valor;
    $valor = str_replace(array('R$', ' '), '', $valor);
    $valor = str_replace('.', '', $valor);
    $valor = str_replace(',', '.', $valor);
    $valor = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $valor);
    return is_numeric($valor) ? $valor : '';
}

require "../../php/banco.php";
include "../../php/funcoes.php";

// ------------------------------------------------------------------
// CONEXÃO
// ------------------------------------------------------------------
try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    debug_log("ERRO: falha de conexão com o banco: " . $e->getMessage());
    responder("ERRO: Falha de conexão com o banco de dados. Nada foi gravado. Tente novamente.");
}

// ------------------------------------------------------------------
// 1. ENTRADAS (mesmos nomes de campos enviados pelo modal)
// ------------------------------------------------------------------
$_usuario_cod    = isset($_POST['usuario_cod']) ? $_POST['usuario_cod'] : '';
$_status         = isset($_POST['C_aprovado']) ? trim((string)$_POST['C_aprovado']) : '';
$_id_antecipacao = isset($_POST['C_id_antecipacao']) ? (int)$_POST['C_id_antecipacao'] : 0;
$_chave_pix      = isset($_POST['C_chave_pix_antecipacao']) ? $_POST['C_chave_pix_antecipacao'] : '';

// Valores vindos do formulário — usados somente como RESERVA (fallback).
$_matricula_post     = isset($_POST['C_matricula_antecipacao']) ? trim((string)$_POST['C_matricula_antecipacao']) : '';
$_empregador_post    = isset($_POST['C_id_empregador_antecipacao']) ? (int)$_POST['C_id_empregador_antecipacao'] : 0;
$_mes_post           = isset($_POST['C_mes']) ? trim((string)$_POST['C_mes']) : '';
$_assoc_id_post      = isset($_POST['C_associado_id']) ? (int)$_POST['C_associado_id'] : 0;
$_divisao_post       = isset($_POST['divisao']) ? (int)$_POST['divisao'] : 0; // "null"/"" viram 0 e caem no fallback
$_divisao_assoc_post = isset($_POST['C_associado_id_divisao']) ? (int)$_POST['C_associado_id_divisao'] : 0;
$_valor_post         = moeda_para_numero(isset($_POST['C_valor_a_descontar']) ? $_POST['C_valor_a_descontar'] : '');
$_taxa_post          = moeda_para_numero(isset($_POST['C_valor_taxa']) ? $_POST['C_valor_taxa'] : '0');

debug_log("\n\n========== NOVO PROCESSAMENTO INICIADO ==========");
debug_log("DEBUG GERAL: POST -> C_aprovado={$_status} | id_antecipacao={$_id_antecipacao} | matricula={$_matricula_post} | empregador={$_empregador_post} | mes={$_mes_post} | assoc_id={$_assoc_id_post} | divisao={$_divisao_post} | divisao_assoc={$_divisao_assoc_post} | valor={$_valor_post} | taxa={$_taxa_post} | usuario={$_usuario_cod}");

// ------------------------------------------------------------------
// 2. VALIDAÇÕES BÁSICAS — antes de tocar no banco
// ------------------------------------------------------------------
if ($_id_antecipacao <= 0) {
    debug_log("ERRO: C_id_antecipacao ausente ou inválido. Nada foi gravado.");
    responder("ERRO: Identificador da antecipação não informado. Nada foi gravado. Feche o modal, abra o registro novamente e repita a operação.");
}
if (!in_array($_status, array("1", "2", "3"), true)) {
    debug_log("ERRO: status inválido recebido ('{$_status}'). Nada foi gravado.");
    responder("ERRO: Status inválido recebido ('{$_status}'). Nada foi gravado. Selecione Analisando, Aprovado ou Reprovado.");
}

// ------------------------------------------------------------------
// 3. PROCESSAMENTO — TUDO dentro de UMA transação
// ------------------------------------------------------------------
$em_transacao = false;
try {
    $pdo->beginTransaction();
    $em_transacao = true;

    // --------------------------------------------------------------
    // 3.1 FONTE DA VERDADE: lê e TRAVA o registro da antecipação.
    //     FOR UPDATE impede que dois usuários processem o mesmo
    //     registro simultaneamente (segunda sessão espera a primeira).
    // --------------------------------------------------------------
    $stmt_ant = $pdo->prepare(
        "SELECT id, matricula, empregador, mes, valor, valor_taxa, valor_a_descontar,
                data_solicitacao, aprovado, id_associado, id_divisao
           FROM sind.antecipacao
          WHERE id = :id
            FOR UPDATE"
    );
    $stmt_ant->bindValue(':id', $_id_antecipacao, PDO::PARAM_INT);
    $stmt_ant->execute();
    $ant = $stmt_ant->fetch(PDO::FETCH_ASSOC);

    if (!$ant) {
        throw new RuntimeException("Antecipação (id {$_id_antecipacao}) não encontrada no banco. Nada foi gravado. Atualize a listagem e tente novamente.");
    }

    $status_anterior = ($ant['aprovado'] === null) ? 'Analisando' : ($ant['aprovado'] ? 'Aprovado' : 'Reprovado');
    debug_log("DEBUG GERAL: registro travado (FOR UPDATE). Status anterior: {$status_anterior}");

    // --------------------------------------------------------------
    // 3.2 RESOLUÇÃO DOS DADOS-CHAVE (banco primeiro, formulário depois)
    // --------------------------------------------------------------
    $_matricula  = (trim((string)$ant['matricula']) !== '') ? trim((string)$ant['matricula']) : $_matricula_post;
    $_empregador = ((int)$ant['empregador'] > 0) ? (int)$ant['empregador'] : $_empregador_post;
    $_mes        = (trim((string)$ant['mes']) !== '') ? trim((string)$ant['mes']) : $_mes_post;

    // divisão: registro da antecipação -> cadastro do associado -> formulário
    $_divisao = ((int)$ant['id_divisao'] > 0) ? (int)$ant['id_divisao'] : 0;

    // id_associado: registro da antecipação -> busca em sind.associado -> formulário
    $_associado_id = ((int)$ant['id_associado'] > 0) ? (int)$ant['id_associado'] : 0;

    if (($_associado_id <= 0 || $_divisao <= 0) && $_matricula !== '' && $_empregador > 0) {
        debug_log("DEBUG GERAL: id_associado/divisão ausentes no registro — buscando em sind.associado (matricula={$_matricula}, empregador={$_empregador})...");
        $stmt_ass = $pdo->prepare(
            "SELECT id, id_divisao
               FROM sind.associado
              WHERE codigo = :matricula
                AND empregador = :empregador
              ORDER BY id
              LIMIT 1"
        );
        $stmt_ass->bindValue(':matricula', $_matricula, PDO::PARAM_STR);
        $stmt_ass->bindValue(':empregador', $_empregador, PDO::PARAM_INT);
        $stmt_ass->execute();
        $ass = $stmt_ass->fetch(PDO::FETCH_ASSOC);
        if ($ass) {
            if ($_associado_id <= 0) { $_associado_id = (int)$ass['id']; }
            if ($_divisao <= 0)      { $_divisao      = (int)$ass['id_divisao']; }
            debug_log("DEBUG GERAL: ✅ associado localizado: id={$_associado_id}, id_divisao={$_divisao}");
        } else {
            debug_log("DEBUG GERAL: ⚠️ associado NÃO localizado em sind.associado");
        }
    }
    // Últimos fallbacks: campos ocultos do formulário
    if ($_associado_id <= 0) { $_associado_id = $_assoc_id_post; }
    if ($_divisao <= 0)      { $_divisao = ($_divisao_assoc_post > 0) ? $_divisao_assoc_post : $_divisao_post; }

    // Valores: registro da antecipação -> formulário
    $_valor = (is_numeric($ant['valor_a_descontar']) && (float)$ant['valor_a_descontar'] > 0)
            ? (string)$ant['valor_a_descontar']
            : $_valor_post;
    $_valor_taxa = (is_numeric($ant['valor_taxa']) && (float)$ant['valor_taxa'] > 0)
            ? (string)$ant['valor_taxa']
            : $_taxa_post;
    if (!is_numeric($_valor_taxa) || (float)$_valor_taxa < 0) { $_valor_taxa = '0'; }

    debug_log("DEBUG GERAL: dados resolvidos -> matricula={$_matricula} | empregador={$_empregador} | mes={$_mes} | divisao={$_divisao} | id_associado={$_associado_id} | valor={$_valor} | taxa={$_valor_taxa}");

    // --------------------------------------------------------------
    // 3.3 VALIDAÇÃO OBRIGATÓRIA PARA APROVAR
    //     Se falta qualquer dado necessário para gravar na CONTA,
    //     a aprovação é BLOQUEADA antes de alterar qualquer coisa.
    // --------------------------------------------------------------
    if ($_status === "2") {
        $faltas = array();
        if (trim($_matricula) === '')                        { $faltas[] = "matrícula"; }
        if ($_empregador <= 0)                               { $faltas[] = "empregador"; }
        if (trim($_mes) === '')                              { $faltas[] = "mês de desconto"; }
        if ($_divisao <= 0)                                  { $faltas[] = "divisão"; }
        if ($_associado_id <= 0)                             { $faltas[] = "identificação do associado (id_associado)"; }
        if (!is_numeric($_valor) || (float)$_valor <= 0)     { $faltas[] = "valor a descontar"; }
        if (count($faltas) > 0) {
            throw new RuntimeException(
                "não foi possível APROVAR: dado(s) obrigatório(s) ausente(s) ou inválido(s): "
                . implode(", ", $faltas)
                . ". Sem esses dados a gravação na tabela CONTA seria impossível, portanto NADA foi alterado."
            );
        }
    }

    // --------------------------------------------------------------
    // 3.4 UPDATE em sind.antecipacao (dentro da transação!)
    // --------------------------------------------------------------
    if ($_status === "2") {          // Aprovado
        $_aprovado       = true;
        $_data_aprovacao = date('Y-m-d');
    } else if ($_status === "3") {   // Reprovado
        $_aprovado       = false;
        $_data_aprovacao = date('Y-m-d');
    } else {                         // "1" Analisando
        $_aprovado       = null;
        $_data_aprovacao = null;
    }

    $stmt_up = $pdo->prepare(
        "UPDATE sind.antecipacao
            SET aprovado = :aprovado,
                data_aprovacao = :data_aprovacao,
                chave_pix = :chave_pix
          WHERE id = :id_antecipacao"
    );
    if ($_aprovado === null) { $stmt_up->bindValue(':aprovado', null, PDO::PARAM_NULL); }
    else                     { $stmt_up->bindValue(':aprovado', $_aprovado, PDO::PARAM_BOOL); }
    if ($_data_aprovacao === null) { $stmt_up->bindValue(':data_aprovacao', null, PDO::PARAM_NULL); }
    else                           { $stmt_up->bindValue(':data_aprovacao', $_data_aprovacao, PDO::PARAM_STR); }
    $stmt_up->bindValue(':chave_pix', $_chave_pix, PDO::PARAM_STR);
    $stmt_up->bindValue(':id_antecipacao', $_id_antecipacao, PDO::PARAM_INT);
    $stmt_up->execute();
    debug_log("DEBUG GERAL: sind.antecipacao atualizada ({$status_anterior} -> status {$_status}) — ainda SEM commit");

    // --------------------------------------------------------------
    // 3.5 SINCRONIZAÇÃO COM A TABELA sind.conta
    //     Chaves do lançamento: associado(matrícula) + mês + empregador
    //     + divisão + id_associado.
    // --------------------------------------------------------------
    $where_chaves = " associado = :associado
                      AND mes = :mes
                      AND empregador = :empregador
                      AND divisao = :divisao
                      AND id_associado = :id_associado ";
    $bind_chaves = function ($stmt) use ($_matricula, $_mes, $_empregador, $_divisao, $_associado_id) {
        $stmt->bindValue(':associado',    $_matricula,    PDO::PARAM_STR);
        $stmt->bindValue(':mes',          $_mes,          PDO::PARAM_STR);
        $stmt->bindValue(':empregador',   $_empregador,   PDO::PARAM_INT);
        $stmt->bindValue(':divisao',      $_divisao,      PDO::PARAM_INT);
        $stmt->bindValue(':id_associado', $_associado_id, PDO::PARAM_INT);
    };

    if ($_status === "2") {
        // ==========================================================
        // APROVADO — gravação OBRIGATÓRIA na conta
        // ==========================================================
        debug_log("DEBUG CONTA: ========== APROVANDO — SINCRONIZANDO TABELA CONTA ==========");

        // (a) Já existe lançamento 221? (independente de aprovado — o app grava com false)
        $sql_existe_221 = "SELECT COUNT(*) AS n FROM sind.conta
                            WHERE {$where_chaves}
                              AND convenio = 221
                              AND (tipo = 'ANTECIPACAO' OR tipo IS NULL)";
        $stmt_existe221 = $pdo->prepare($sql_existe_221);
        $bind_chaves($stmt_existe221);
        $stmt_existe221->execute();
        $tem_221 = ((int)$stmt_existe221->fetch(PDO::FETCH_ASSOC)['n']) > 0;

        $stmt_c249 = $pdo->prepare("SELECT COUNT(*) AS n FROM sind.conta WHERE {$where_chaves} AND convenio = 249");
        $bind_chaves($stmt_c249);
        $stmt_c249->execute();
        $qtd_249 = (int)$stmt_c249->fetch(PDO::FETCH_ASSOC)['n'];
        debug_log("DEBUG CONTA: existentes antes do INSERT -> 221=" . ($tem_221 ? 'sim' : 'não') . " | 249={$qtd_249}");

        $sql_insert_base = "INSERT INTO sind.conta
                            (associado, mes, empregador, divisao, id_associado, valor, tipo, aprovado, convenio, data, hora, descricao)
                            VALUES
                            (:associado, :mes, :empregador, :divisao, :id_associado, :valor, 'ANTECIPACAO', true, :convenio, :data, :hora, :descricao)
                            RETURNING lancamento";

        $insere_conta = function ($convenio, $valor, $descricao) use ($pdo, $sql_insert_base, $bind_chaves, $ant) {
            $data_lancamento = (!empty($ant['data_solicitacao'])) ? $ant['data_solicitacao'] : date('Y-m-d');
            $hora_lancamento = date('H:i:s');
            $stmt_ins = $pdo->prepare($sql_insert_base);
            $bind_chaves($stmt_ins);
            $stmt_ins->bindValue(':valor',    $valor,           PDO::PARAM_STR);
            $stmt_ins->bindValue(':convenio', $convenio,        PDO::PARAM_INT);
            $stmt_ins->bindValue(':data',     $data_lancamento, PDO::PARAM_STR);
            $stmt_ins->bindValue(':hora',     $hora_lancamento, PDO::PARAM_STR);
            $stmt_ins->bindValue(':descricao',$descricao,       PDO::PARAM_STR);
            $stmt_ins->execute();
            $novo = $stmt_ins->fetch(PDO::FETCH_ASSOC);
            if (!$novo || empty($novo['lancamento'])) {
                throw new RuntimeException(
                    "Falha ao gravar o lançamento (convênio {$convenio}) na tabela CONTA: o INSERT não retornou o número de lançamento."
                );
            }
            return (int)$novo['lancamento'];
        };

        if (!$tem_221) {
            debug_log("DEBUG CONTA: lançamento principal (221) NÃO existe — será INSERIDO");
            $pdo->query("SELECT pg_advisory_xact_lock(hashtext('sind.conta.lancamento'))");

            $tentativa = 0;
            while (true) {
                $tentativa++;
                $pdo->exec("SAVEPOINT sp_insere_conta");
                try {
                    $lanc_221 = $insere_conta(221, $_valor, 'Antecipação salarial');
                    debug_log("DEBUG CONTA: ✅ INSERT antecipação — lançamento {$lanc_221}, valor {$_valor}, convênio 221");

                    // O trigger BEFORE INSERT pode ter criado a taxa de cartão (249) junto com o 221.
                    $stmt_c249->execute();
                    $qtd_249 = (int)$stmt_c249->fetch(PDO::FETCH_ASSOC)['n'];
                    debug_log("DEBUG CONTA: após INSERT 221, lançamento(s) 249 existente(s): {$qtd_249}");

                    $pdo->exec("RELEASE SAVEPOINT sp_insere_conta");
                    break;
                } catch (PDOException $e_ins) {
                    $pdo->exec("ROLLBACK TO SAVEPOINT sp_insere_conta");
                    $sqlstate = (string)$e_ins->getCode();
                    if ($sqlstate === '23505' && $tentativa < 3) {
                        debug_log("DEBUG CONTA: ⚠️ lançamento duplicado na tentativa {$tentativa} — tentando de novo...");
                        usleep(50000);
                        continue;
                    }
                    throw $e_ins;
                }
            }
        } else {
            debug_log("DEBUG CONTA: ✅ lançamento principal (221) já existe — não será duplicado");
        }

        // Taxa (249): insere só se ainda não houver (o trigger pode já ter gravado)
        if ((float)$_valor_taxa > 0) {
            $stmt_c249->execute();
            $qtd_249 = (int)$stmt_c249->fetch(PDO::FETCH_ASSOC)['n'];
            if ($qtd_249 === 0) {
                debug_log("DEBUG CONTA: lançamento de taxa (249) NÃO existe — será INSERIDO");
                $lanc_249 = $insere_conta(249, $_valor_taxa, 'Taxa de antecipação');
                debug_log("DEBUG CONTA: ✅ INSERT taxa — lançamento {$lanc_249}, valor {$_valor_taxa}, convênio 249");
            } else {
                debug_log("DEBUG CONTA: taxa NÃO inserida — já existe(m) {$qtd_249} lançamento(s) 249 (anti-duplicação)");
            }
        }

        // (b) ⭐ OBRIGATÓRIO DEPOIS DO INSERT: o trigger BEFORE INSERT
        //     fn_insere_taxa_cartao_automatica força aprovado=FALSE em convênios 221 e 249.
        //     Sem este UPDATE a verificação final não encontra o lançamento aprovado
        //     e a transação inteira é desfeita (ROLLBACK).
        $stmt1 = $pdo->prepare(
            "UPDATE sind.conta
                SET aprovado = true
              WHERE {$where_chaves}
                AND (tipo = 'ANTECIPACAO' OR convenio = 221 OR convenio = 249)"
        );
        $bind_chaves($stmt1);
        $stmt1->execute();
        debug_log("DEBUG CONTA: UPDATE aprovado=true (após INSERT/existência) -> " . $stmt1->rowCount() . " linha(s) afetada(s)");

        // (c) ✅✅ VERIFICAÇÃO FINAL OBRIGATÓRIA — a etapa que NÃO PODE FALHAR ✅✅
        $sql_tem_221 = "SELECT COUNT(*) AS n FROM sind.conta
                         WHERE {$where_chaves}
                           AND convenio = 221
                           AND (tipo = 'ANTECIPACAO' OR tipo IS NULL)
                           AND aprovado IS TRUE";
        $stmt_verifica = $pdo->prepare($sql_tem_221);
        $bind_chaves($stmt_verifica);
        $stmt_verifica->execute();
        $qtd_final_221 = (int)$stmt_verifica->fetch(PDO::FETCH_ASSOC)['n'];
        if ($qtd_final_221 < 1) {
            throw new RuntimeException(
                "VERIFICAÇÃO FINAL FALHOU: o lançamento da antecipação NÃO foi encontrado na tabela CONTA. "
                . "A aprovação foi CANCELADA e nenhuma alteração foi gravada. Tente novamente e, se o problema persistir, contate o suporte."
            );
        }
        if ((float)$_valor_taxa > 0) {
            $stmt_v249 = $pdo->prepare("SELECT COUNT(*) AS n FROM sind.conta WHERE {$where_chaves} AND convenio = 249");
            $bind_chaves($stmt_v249);
            $stmt_v249->execute();
            $qtd_final_249 = (int)$stmt_v249->fetch(PDO::FETCH_ASSOC)['n'];
            if ($qtd_final_249 < 1) {
                throw new RuntimeException(
                    "VERIFICAÇÃO FINAL FALHOU: o lançamento da TAXA (convênio 249) não foi encontrado na tabela CONTA. "
                    . "A aprovação foi CANCELADA e nenhuma alteração foi gravada."
                );
            }
        }

        // (e) ⭐ REGRA "aprovado sempre true": confere que NENHUM lançamento da
        //     antecipação ficou na conta com aprovado = false ou NULL.
        //     Se ficou, a operação inteira é CANCELADA (rollback).
        $stmt_false = $pdo->prepare(
            "SELECT COUNT(*) AS n FROM sind.conta
              WHERE {$where_chaves}
                AND (tipo = 'ANTECIPACAO' OR convenio = 221 OR convenio = 249)
                AND aprovado IS DISTINCT FROM true"
        );
        $bind_chaves($stmt_false);
        $stmt_false->execute();
        $qtd_nao_true = (int)$stmt_false->fetch(PDO::FETCH_ASSOC)['n'];
        if ($qtd_nao_true > 0) {
            throw new RuntimeException(
                "VERIFICAÇÃO FINAL FALHOU: {$qtd_nao_true} lançamento(s) da antecipação ficaram na CONTA com aprovado diferente de true "
                . "(regra: o campo aprovado é gravado sempre com true). A operação foi CANCELADA e nada foi gravado."
            );
        }
        debug_log("DEBUG CONTA: ✅✅✅ VERIFICAÇÃO FINAL OK — {$qtd_final_221} lançamento(s) 221 aprovado(s) e nenhum lançamento com aprovado ≠ true ✅✅✅");

    } else {
        // ==========================================================
        // ANALISANDO ("1") ou REPROVADO ("3") — EXCLUI os lançamentos
        // da conta (convênios 221 e 249).
        //
        // ⭐ REGRA DO CLIENTE (12/08/2026): na sind.conta o campo
        // aprovado é SEMPRE true — este sistema NUNCA grava false.
        // Portanto, quando a antecipação não está aprovada, o
        // lançamento simplesmente NÃO permanece na conta: ao voltar
        // para Analisando (ou Reprovar) os lançamentos são removidos;
        // ao aprovar novamente, são recriados com aprovado = true.
        // (Antes, "Analisando" gravava aprovado = false — abolido.)
        // ==========================================================
        $rotulo_status = ($_status === "3") ? "REPROVADO" : "ANALISANDO";
        debug_log("DEBUG CONTA: ========== {$rotulo_status} — EXCLUINDO LANÇAMENTOS DA CONTA (regra: aprovado é sempre true; nunca se grava false) ==========");
        $stmt_del = $pdo->prepare(
            "DELETE FROM sind.conta
              WHERE mes = :mes
                AND empregador = :empregador
                AND id_associado = :id_associado
                AND divisao = :divisao
                AND (convenio = 221 OR convenio = 249)"
        );
        $stmt_del->bindValue(':mes',          $_mes,          PDO::PARAM_STR);
        $stmt_del->bindValue(':empregador',   $_empregador,   PDO::PARAM_INT);
        $stmt_del->bindValue(':id_associado', $_associado_id, PDO::PARAM_INT);
        $stmt_del->bindValue(':divisao',      $_divisao,      PDO::PARAM_INT);
        $stmt_del->execute();
        debug_log("DEBUG CONTA: ✅ DELETE executado — " . $stmt_del->rowCount() . " linha(s) removida(s)");

        // Verificação: nenhum lançamento 221/249 pode ter restado para estas chaves
        $stmt_vdel = $pdo->prepare(
            "SELECT COUNT(*) AS n FROM sind.conta
              WHERE mes = :mes
                AND empregador = :empregador
                AND id_associado = :id_associado
                AND divisao = :divisao
                AND (convenio = 221 OR convenio = 249)"
        );
        $stmt_vdel->bindValue(':mes',          $_mes,          PDO::PARAM_STR);
        $stmt_vdel->bindValue(':empregador',   $_empregador,   PDO::PARAM_INT);
        $stmt_vdel->bindValue(':id_associado', $_associado_id, PDO::PARAM_INT);
        $stmt_vdel->bindValue(':divisao',      $_divisao,      PDO::PARAM_INT);
        $stmt_vdel->execute();
        if ((int)$stmt_vdel->fetch(PDO::FETCH_ASSOC)['n'] > 0) {
            throw new RuntimeException(
                "VERIFICAÇÃO FALHOU: ainda restaram lançamentos da antecipação na CONTA após a exclusão. "
                . "A operação foi CANCELADA e nada foi gravado."
            );
        }
    }

    // --------------------------------------------------------------
    // 3.6 COMMIT — só chega aqui se TODAS as etapas deram certo
    // --------------------------------------------------------------
    $pdo->commit();
    $em_transacao = false;
    debug_log("DEBUG GERAL: ✅ COMMIT efetuado com sucesso (antecipação + conta consistentes)");
    debug_log("========== PROCESSAMENTO FINALIZADO ==========\n\n");

    responder("atualizado");

} catch (PDOException $erro) {
    if ($em_transacao && $pdo->inTransaction()) {
        $pdo->rollBack();
        debug_log("DEBUG GERAL: ❌ ROLLBACK executado — NENHUMA alteração foi gravada (PDOException)");
    }
    debug_log("DEBUG GERAL: ❌ ERRO de banco: [" . $erro->getCode() . "] " . $erro->getMessage());
    debug_log("========== PROCESSAMENTO ABORTADO ==========\n\n");
    if ($erro->getCode() === '42501') {
        responder("Seu usuario não tem permissão!");
    }
    responder("ERRO: Falha de banco de dados — NADA foi gravado (a alteração foi desfeita). Detalhe técnico: " . $erro->getMessage());

} catch (Exception $erro) {
    if ($em_transacao && $pdo->inTransaction()) {
        $pdo->rollBack();
        debug_log("DEBUG GERAL: ❌ ROLLBACK executado — NENHUMA alteração foi gravada (Exception)");
    }
    debug_log("DEBUG GERAL: ❌ ERRO: " . $erro->getMessage());
    debug_log("========== PROCESSAMENTO ABORTADO ==========\n\n");
    responder("ERRO: " . $erro->getMessage());
}
