<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);

// Configurar cabeçalho para JSON
header('Content-Type: application/json; charset=utf-8');

/* cSpell:disable */
include "../../php/banco.php";
include "../../php/funcoes.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Inicializar array de resposta com estrutura válida para DataTables
    $someArray = array('data' => array());
    
    // Obter parâmetro divisao do POST
    $divisao = isset($_POST['divisao']) ? $_POST['divisao'] : null;
    
    // Aceitar divisao como null - não é mais obrigatório
    
    // Obter parâmetro id_situacao do POST com validação
    $id_situacao = isset($_POST['id_situacao']) ? $_POST['id_situacao'] : null;
    
    // Validar se id_situacao foi fornecido - aceitar string vazia e valores falsy mas não null
    if ($id_situacao === null) {
        throw new Exception("Parâmetro id_situacao é obrigatório");
    }
    
    // DEBUG: Log dos parâmetros recebidos
    error_log("DEBUG - POST completo: " . json_encode($_POST));
    error_log("DEBUG - id_situacao recebido: " . var_export($id_situacao, true));
    error_log("DEBUG - divisao recebida: " . ($divisao === null ? 'NULL' : $divisao));

// Filtros baseados na situação selecionada

// Construir filtro de divisao para incluir valor específico E registros NULL
if ($divisao === null) {
    $tipo_sql = "WHERE id_divisao IS NULL";
} else {
    $tipo_sql = "WHERE (id_divisao = :divisao OR id_divisao IS NULL)";
}

if($id_situacao == "true"){
    $tipo_sql .= " AND autorizado = true";
    error_log("DEBUG - Filtro aplicado: autorizado = true com divisao = " . ($divisao === null ? 'NULL' : $divisao . ' + NULL'));
}else if($id_situacao == "false"){
    $tipo_sql .= " AND autorizado = false";
    error_log("DEBUG - Filtro aplicado: autorizado = false com divisao = " . ($divisao === null ? 'NULL' : $divisao . ' + NULL'));
}else if($id_situacao == "signed"){
    // RadioAssinados - mostrar apenas registros com codigo que comece com 'webhook_signed'
    $tipo_sql .= " AND codigo LIKE 'webhook_%'";
    error_log("DEBUG - Filtro RadioAssinados aplicado: codigo LIKE 'webhook_%' com divisao = " . ($divisao === null ? 'NULL' : $divisao . ' + NULL'));
}else if($id_situacao == "approved"){
    // RadioAprovados - mostrar apenas linhas com valor_aprovado > 0
    $tipo_sql .= " AND has_signed = true AND codigo NOT LIKE 'webhook_%'";
    error_log("DEBUG - Filtro RadioAprovados aplicado: has_signed = true E exclui webhook_% com divisao = " . ($divisao === null ? 'NULL' : $divisao . ' + NULL'));
}else if($id_situacao == "reprovados"){
    // RadioReprovados - mostrar apenas linhas reprovadas (reprovado = true)
    $tipo_sql .= " AND reprovado = true";
    error_log("DEBUG - Filtro RadioReprovados aplicado: reprovado = true com divisao = " . ($divisao === null ? 'NULL' : $divisao . ' + NULL'));
}else if($id_situacao == "0"){
    // RadioTodos - mostrar todas linhas aprovadas (has_signed = true) independente de valor aprovado
    $tipo_sql .= " AND has_signed = true";
    error_log("DEBUG - Filtro RadioTodos aplicado: todas linhas aprovadas (has_signed = true) com divisao = " . ($divisao === null ? 'NULL' : $divisao . ' + NULL'));
}else{
    // Sem filtro adicional, apenas divisao
    error_log("DEBUG - Apenas filtro de divisao aplicado: " . ($divisao === null ? 'NULL' : $divisao . ' + NULL'));
}
/* cSpell:enable */

// Adicionar filtro has_signed = true quando necessário
// Não adicionar para RadioTodos (0), RadioAssinados (signed) ou reprovados, pois já têm filtros específicos
if($id_situacao != "0" && $id_situacao != "signed" && $id_situacao != "reprovados") {
    // Para filtros que não sejam RadioTodos (0), RadioAssinados (signed) ou reprovados, adicionar condição has_signed = true
    $tipo_sql .= " AND has_signed = true";
}

$query = "SELECT id, codigo, nome, celular, data_hora, autorizado, aceitou_termo, event, doc_token, doc_name, signed_at, name, email, cpf, has_signed, cel_informado, limite, valor_aprovado, data_pgto, chave_pix, reprovado, tipo
            FROM sind.associados_sasmais
            ".$tipo_sql ."
            ORDER BY id DESC";

// DEBUG: Log da query completa
error_log("DEBUG - Query executada: " . $query);

$statment = $pdo->prepare($query);
// Só fazer bind do parâmetro divisao se não for null
if ($divisao !== null) {
    $statment->bindParam(':divisao', $divisao, PDO::PARAM_INT);
}
$statment->execute();
$result = $statment->fetchAll();

// DEBUG: Log da quantidade de registros retornados
error_log("DEBUG - Registros encontrados: " . count($result));

// DEBUG: Verificar campos disponíveis no primeiro registro
if (count($result) > 0) {
    $primeiro_registro = $result[0];
    error_log("DEBUG - Campos disponíveis no primeiro registro: " . implode(", ", array_keys($primeiro_registro)));
}

// DEBUG: Verificar valores has_signed dos primeiros 3 registros
if (count($result) > 0) {
    for ($i = 0; $i < min(3, count($result)); $i++) {
        $has_signed_value = $result[$i]["has_signed"];
        $has_signed_type = gettype($has_signed_value);
        error_log("DEBUG - Registro " . ($i+1) . " - ID: " . $result[$i]["id"] . " - has_signed: " . var_export($has_signed_value, true) . " (tipo: " . $has_signed_type . ")");
    }
}

// NOVA LÓGICA: Detectar códigos duplicados na mesma data
$codigo_duplicados = array();

// Primeiro, identificar todos os grupos de códigos duplicados
foreach ($result as $row) {
    if (!empty($row["codigo"]) && !empty($row["cpf"])) {
        // Extrair apenas a data (sem hora) para comparação
        $data_apenas = '';
        if ($row["data_hora"] != null) {
            $data_apenas = date('Y-m-d', strtotime($row["data_hora"]));
        }
        
        // Chave única: CPF + código + data
        $chave_duplicata = $row["cpf"] . "|" . $row["codigo"] . "|" . $data_apenas;
        
        if (!isset($codigo_duplicados[$chave_duplicata])) {
            $codigo_duplicados[$chave_duplicata] = array();
        }
        
        $codigo_duplicados[$chave_duplicata][] = $row["id"];
    }
}

// Identificar quais IDs devem ter botões como "Autorizado"
$ids_autorizados_duplicata = array();
foreach ($codigo_duplicados as $chave => $ids) {
    if (count($ids) >= 2) {
        // Se há 2 ou mais registros com mesmo CPF + código + data
        foreach ($ids as $id) {
            $ids_autorizados_duplicata[] = $id;
        }
    }
}

// DEBUG: Log para verificar detecção de duplicatas
if (!empty($ids_autorizados_duplicata)) {
    error_log("Códigos duplicados detectados: " . json_encode($ids_autorizados_duplicata));
}

// Função auxiliar para tratar valores monetários do PostgreSQL
function formatarCampoMonetario($valor) {
    if (empty($valor) || $valor === null) {
        return "0,00";
    }
    
    // Se é string, remover símbolos de moeda e converter para float
    if (is_string($valor)) {
        // Remover cifrão, vírgulas e outros caracteres não numéricos, exceto ponto
        $valor_limpo = preg_replace('/[^\d.-]/', '', $valor);
        $valor = floatval($valor_limpo);
    }
    
    // Garantir que é um número válido
    if (!is_numeric($valor)) {
        return "0,00";
    }
    
    return number_format(floatval($valor), 2, ',', '.');
}

$data = array();
$linhas_filtradas = $statment->rowCount();

foreach ($result as $row){
    $sub_array = array();

    $sub_array["id"]              = $row["id"];
    $sub_array["codigo"]          = $row["codigo"];
    $sub_array["nome"]            = htmlspecialchars($row["nome"] ?? '');
    $sub_array["celular"]         = $row["celular"];
    $sub_array["tipo"]            = $row["tipo"]; // ✅ Campo tipo para diferenciação adesão/antecipação
    
    if($row["data_hora"] != null){
        $sub_array["data_hora"] = date('d/m/Y H:i:s', strtotime($row["data_hora"]));
    }else{
        $sub_array["data_hora"] = "";
    }
    
    // Formatação dos campos booleanos
    if($row["autorizado"] === null){
        $sub_array["autorizado"] = "Não definido";
    }else if($row["autorizado"] == true){
        $sub_array["autorizado"] = "Sim";
    }else{
        $sub_array["autorizado"] = "Não";
    }
    
    if($row["aceitou_termo"] === null){
        $sub_array["aceitou_termo"] = "Não definido";
    }else if($row["aceitou_termo"] == true){
        $sub_array["aceitou_termo"] = "Sim";
    }else{
        $sub_array["aceitou_termo"] = "Não";
    }
    
    if($row["has_signed"] === null){
        $sub_array["has_signed"] = "Não definido";
    }else if($row["has_signed"] == true){
        $sub_array["has_signed"] = "Sim";
    }else{
        $sub_array["has_signed"] = "Não";
    }
    
    $sub_array["event"]           = $row["event"];
    $sub_array["doc_token"]       = $row["doc_token"];
    $sub_array["doc_name"]        = $row["doc_name"];
    $sub_array["signed_at"]       = $row["signed_at"];
    $sub_array["name"]            = $row["name"];
    $sub_array["email"]           = $row["email"];
    $sub_array["cpf"]             = $row["cpf"];
    $sub_array["cel_informado"]   = $row["cel_informado"];
    
    // Novos campos monetários
    $sub_array["limite"]          = formatarCampoMonetario($row["limite"]);
    $sub_array["valor_aprovado"]  = formatarCampoMonetario($row["valor_aprovado"]);
    
    // Formatação da data de pagamento
    if($row["data_pgto"] != null){
        $sub_array["data_pgto"] = date('d/m/Y H:i:s', strtotime($row["data_pgto"]));
    }else{
        $sub_array["data_pgto"] = "";
    }
    
    // Chave PIX
    $sub_array["chave_pix"] = $row["chave_pix"];
    
    // NOVA LÓGICA: Botões Aprovar e Reprovar com regras específicas
    $cpf_formatado = htmlspecialchars($row["cpf"] ?? '');
    $codigo_atual = $row["codigo"];
    $eh_duplicata_autorizada = in_array($row["id"], $ids_autorizados_duplicata);
    
    // Verificar se tem valor aprovado OU limite preenchido
    $tem_valor_aprovado = (!empty($row["valor_aprovado"]) && floatval(preg_replace('/[^\d.-]/', '', $row["valor_aprovado"])) > 0);
    $tem_limite_preenchido = (!empty($row["limite"]) && floatval(preg_replace('/[^\d.-]/', '', $row["limite"])) > 0);
    $eh_registro_aprovado = ($tem_valor_aprovado || $tem_limite_preenchido);
    
    // Verificar se está reprovado
    $eh_registro_reprovado = ($row["reprovado"] == true);
    
    // Verificar se valor_aprovado é 0 ou nulo
    $valor_zero_ou_nulo = (empty($row["valor_aprovado"]) || floatval(preg_replace('/[^\d.-]/', '', $row["valor_aprovado"])) == 0);
    
    if (!empty($cpf_formatado)) {
        if ($eh_duplicata_autorizada) {
            // Caso especial: código duplicado na mesma data = botão verde "Autorizado"
            $sub_array["botao_vincular"] = '<button type="button" name="aprovar_codigo" data-id="'.$row["id"].'" data-cpf="'.$cpf_formatado.'" data-codigo-atual="'.$codigo_atual.'" class="btn btn-success btn-xs autorizado-duplicado aprovar_codigo" data-toggle="tooltip" data-placement="top" title="Autorizado automaticamente - Código duplicado na mesma data - Clique para editar">
                                                <span class="glyphicon glyphicon-ok"></span> Autorizado
                                            </button>';
        } else if ($eh_registro_reprovado) {
            // Se está reprovado: botão vermelho "Reprovado" HABILITADO para permitir edição
            $sub_array["botao_vincular"] = '<button type="button" name="aprovar_codigo" data-id="'.$row["id"].'" data-cpf="'.$cpf_formatado.'" data-codigo-atual="'.$codigo_atual.'" class="btn btn-danger btn-xs aprovar_codigo btn-reprovado" data-toggle="tooltip" data-placement="top" title="Registro reprovado - Clique para editar">
                                                <span class="glyphicon glyphicon-remove"></span> Reprovado
                                            </button>';
        } else if ($eh_registro_aprovado) {
            // Se está aprovado: botão "Aprovado" desabilitado
            $tooltip_aprovado = "Registro aprovado - ";
            if ($tem_valor_aprovado && $tem_limite_preenchido) {
                $tooltip_aprovado .= "Valor: " . $sub_array["valor_aprovado"] . " | Limite: " . $sub_array["limite"];
            } else if ($tem_valor_aprovado) {
                $tooltip_aprovado .= "Valor: " . $sub_array["valor_aprovado"];
            } else if ($tem_limite_preenchido) {
                $tooltip_aprovado .= "Limite: " . $sub_array["limite"];
            }
            
            $sub_array["botao_vincular"] = '<button type="button" name="aprovar_codigo" data-id="'.$row["id"].'" data-cpf="'.$cpf_formatado.'" data-codigo-atual="'.$codigo_atual.'" class="btn btn-success btn-xs btn-aprovado-filtro aprovar_codigo" data-toggle="tooltip" data-placement="top" title="'.$tooltip_aprovado.' - Clique para editar">
                                                <span class="glyphicon glyphicon-ok"></span> Aprovado
                                            </button>';
        } else {
            // Valor 0 ou nulo: botão "Aprovar" habilitado
            $tooltip_title = "Aprovar assinatura digital";
            if (!empty($codigo_atual)) {
                $tooltip_title .= " (Código atual: " . $codigo_atual . " - temporário do webhook)";
            }
            
            $sub_array["botao_vincular"] = '<button type="button" name="aprovar_codigo" data-id="'.$row["id"].'" data-cpf="'.$cpf_formatado.'" data-codigo-atual="'.$codigo_atual.'" class="btn btn-primary btn-xs aprovar_codigo" data-toggle="tooltip" data-placement="top" title="'.$tooltip_title.'">
                                                <span class="glyphicon glyphicon-ok"></span> Aprovar
                                            </button>';
        }
    } else {
        // CPF vazio - botão habilitado mas com visual diferente para abrir modal
        $sub_array["botao_vincular"] = '<button type="button" name="aprovar_codigo" data-id="'.$row["id"].'" data-cpf="" data-codigo-atual="'.$codigo_atual.'" class="btn btn-warning btn-xs aprovar_codigo" data-toggle="tooltip" data-placement="top" title="CPF não informado - Clique para editar">
                                            <span class="glyphicon glyphicon-warning-sign"></span> Sem CPF
                                        </button>';
    }
    

    
    $sub_array["botaoexcluir"]    = '<button type="button" name="btnexcluir" id="'.$row["id"].'" class="btn btn-danger glyphicon glyphicon-trash btn-xs btnexcluir" data-toggle="tooltip" data-placement="top" title="Excluir"></button>';
    
    $someArray['data'][] = $sub_array;
}

    // Sempre garantir que existe a propriedade 'data' mesmo que vazia
    if (!isset($someArray['data'])) {
        $someArray['data'] = array();
    }
    
    echo json_encode($someArray);
    
} catch (PDOException $e) {
    // Em caso de erro de banco, retornar estrutura válida com erro
    echo json_encode([
        'data' => [],
        'error' => true,
        'message' => 'Erro de banco de dados: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Em caso de outros erros, retornar estrutura válida com erro
    echo json_encode([
        'data' => [],
        'error' => true,
        'message' => 'Erro geral: ' . $e->getMessage()
    ]);
}
?> 