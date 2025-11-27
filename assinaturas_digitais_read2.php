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

// Filtros baseados na situação selecionada
if($_POST['id_situacao'] == "true"){
    $tipo_sql = "WHERE autorizado = true";
}else if($_POST['id_situacao'] == "false"){
    $tipo_sql = "WHERE autorizado = false";
}else if($_POST['id_situacao'] == "signed"){
    $tipo_sql = "WHERE has_signed = true";
}else{
    $tipo_sql = "";
}
/* cSpell:enable */

$query = "SELECT id, codigo, nome, celular, data_hora, autorizado, aceitou_termo, event, doc_token, doc_name, signed_at, name, email, cpf, has_signed, cel_informado
            FROM sind.associados_sasmais
            ".$tipo_sql ."
            ORDER BY id DESC";

$statment = $pdo->prepare($query);
$statment->execute();
$result = $statment->fetchAll();

// Criar array para rastrear códigos duplicados na mesma data
$codigosPorData = array();
$registrosAutorizados = array();

// Primeira passagem: identificar códigos duplicados na mesma data
foreach ($result as $row) {
    if (!empty($row["codigo"]) && !empty($row["data_hora"])) {
        // Extrair apenas a data (sem hora) para comparação
        $data = date('Y-m-d', strtotime($row["data_hora"]));
        $codigo = trim($row["codigo"]);
        
        // Criar chave única para código + data
        $chave = $codigo . '_' . $data;
        
        if (!isset($codigosPorData[$chave])) {
            $codigosPorData[$chave] = array();
        }
        
        $codigosPorData[$chave][] = $row["id"];
    }
}

// Identificar registros que devem ser marcados como "autorizados" (duplicados)
foreach ($codigosPorData as $chave => $ids) {
    if (count($ids) > 1) {
        // Se há mais de um registro com o mesmo código na mesma data,
        // marcar todos eles como "autorizados"
        foreach ($ids as $id) {
            $registrosAutorizados[$id] = true;
        }
    }
}

$data = array();
$linhas_filtradas = $statment->rowCount();

foreach ($result as $row){
    $sub_array = array();

    $sub_array["id"]              = $row["id"];
    $sub_array["codigo"]          = $row["codigo"];
    $sub_array["nome"]            = htmlspecialchars($row["nome"]);
    $sub_array["celular"]         = $row["celular"];
    
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
    
    // Botão para vincular código do associado
    $cpf_formatado = htmlspecialchars($row["cpf"]);
    $codigo_atual = $row["codigo"];
    $id_registro = $row["id"];
    
    // Verificar se este registro é um duplicado autorizado
    $isDuplicadoAutorizado = isset($registrosAutorizados[$id_registro]);
    
    if (!empty($cpf_formatado)) {
        if ($isDuplicadoAutorizado) {
            // Para registros duplicados: botão verde, desabilitado, com texto "Autorizado"
            $data_registro = !empty($row["data_hora"]) ? date('Y-m-d', strtotime($row["data_hora"])) : '';
            $tooltip_title = "Autorizado automaticamente (código duplicado na mesma data: {$data_registro})";
            
            $sub_array["botao_vincular"] = '<button type="button" class="btn btn-success btn-xs" disabled data-toggle="tooltip" data-placement="top" title="'.$tooltip_title.'">
                                                <span class="glyphicon glyphicon-ok"></span> Autorizado
                                            </button>';
        } else {
            // Lógica normal: sempre mostrar botão "Vincular" se tiver CPF
            $tooltip_title = "Vincular código do associado";
            if (!empty($codigo_atual)) {
                $tooltip_title .= " (Código atual: " . $codigo_atual . " - temporário do webhook)";
            }
            
            $sub_array["botao_vincular"] = '<button type="button" name="vincular_codigo" data-id="'.$row["id"].'" data-cpf="'.$cpf_formatado.'" data-codigo-atual="'.$codigo_atual.'" class="btn btn-primary btn-xs vincular_codigo" data-toggle="tooltip" data-placement="top" title="'.$tooltip_title.'">
                                                <span class="glyphicon glyphicon-link"></span> Vincular
                                            </button>';
        }
    } else {
        $sub_array["botao_vincular"] = '<button type="button" class="btn btn-secondary btn-xs" disabled data-toggle="tooltip" data-placement="top" title="CPF não informado">
                                            <span class="glyphicon glyphicon-remove"></span> Sem CPF
                                        </button>';
    }
    
    $sub_array["botao"]           = '<button type="button" name="update_assinatura" id="'.$row["id"].'" class="btn btn-warning glyphicon glyphicon-edit btn-xs update_assinatura" data-toggle="tooltip" data-placement="top" title="Alterar"></button>';
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