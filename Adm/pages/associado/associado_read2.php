<?PHP
// Limpa qualquer saída anterior
if (ob_get_level()) {
    ob_end_clean();
}

// Desabilita exibição de erros para não quebrar o JSON
ini_set('display_errors', false);
error_reporting(0);

// Inicia novo buffer de saída
ob_start();

try {
    include "../../php/banco.php";
    include "../../php/funcoes.php";
    include "../../php/tenant_security.php";
    
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SEGURANÇA MULTI-TENANT: Validar divisão
    $tenantSec = new TenantSecurity($pdo);
    
    // Obter parâmetros do POST (enviados pelo JavaScript)
    $usuario_cod = isset($_POST['usuario_cod']) ? $_POST['usuario_cod'] : null;
    $divisao = $tenantSec->getSecureDivisao($_POST['divisao'] ?? null);
    $usuario_global = isset($_POST['usuario_global']) ? $_POST['usuario_global'] : null;
    $cod_situacao = isset($_POST['cod_situacao']) ? $_POST['cod_situacao'] : null;
    
    // Validar se temos os parâmetros necessários
    if (!$usuario_cod || !$divisao) {
        throw new Exception("Parâmetros obrigatórios não fornecidos: usuario_cod=" . $usuario_cod . ", divisao=" . $divisao);
    }

    $columns = array(
        0 => 'associado.codigo',
        1 => 'associado.nome',
        2 => 'associado.cpf',
        3 => 'associado.rg',
        4 => 'associado.endereco',
        5 => 'associado.bairro',
        6 => 'associado.cidade',
        7 => 'associado.cep',
        8 => 'associado.telres',
        9 => 'associado.cel',
        10 => 'associado.email',
        11 => 'associado.nascimento',
        12 => 'associado.data_filiacao',
        13 => 'associado.id',
        14 => 'associado.id_situacao'
        
    );

    $tipo_sql = "";
    if (isset($_POST["search"]["value"]) && $_POST["search"]["value"] != '') {
        $tipo_sql = " AND (associado.codigo ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.nome ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.cpf ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.rg ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.endereco ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.bairro ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.cidade ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.cep ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.telres ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.cel ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.email ILIKE '%" . $_POST["search"]["value"] . "%')";
    }

    if (isset($_POST["order"])) {
        $tipo_sql .= ' ORDER BY ' . $columns[$_POST['order']['0']['column']] . ' ' . $_POST['order']['0']['dir'] . ' ';
    } else {
        $tipo_sql .= ' ORDER BY associado.id DESC ';
    }

    // Implementar paginação server-side
    if (isset($_POST["start"]) && $_POST["length"] != -1) {
        $tipo_sql .= ' LIMIT ' . $_POST["length"] . ' OFFSET ' . $_POST["start"];
    }

    $query = "SELECT associado.codigo, associado.nome, associado.endereco, associado.bairro, 
                     associado.nascimento, associado.empregador, associado.id, associado.salario, 
                     associado.limite, associado.cep, associado.telres, associado.telcom, associado.cel,
                     associado.complemento, empregador.nome AS empregador_nome, empregador.abreviacao,
                     situacao_associado.nome as nome_situacao,
                     associado.id_divisao
              FROM sind.associado 
              LEFT JOIN sind.empregador ON empregador.id = associado.empregador
              LEFT JOIN sind.situacao_associado ON situacao_associado.codigo = associado.id_situacao
              WHERE associado.id_divisao = :divisao" . ($cod_situacao === '0' ? '' : " AND associado.id_situacao = :cod_situacao") . $tipo_sql;

    // Debug da query final
    error_log("DEBUG: Query final: " . $query);
    error_log("DEBUG: Divisão sendo usada no filtro: " . $divisao);
    
    $statment = $pdo->prepare($query);
    $statment->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    if ($cod_situacao !== '0') {
        $statment->bindParam(':cod_situacao', $cod_situacao, PDO::PARAM_INT);
    }
    $statment->execute();
    $result = $statment->fetchAll();
    
    // Contar registros filtrados sem LIMIT para paginação correta
    $count_query = "SELECT COUNT(*) as filtered_count
              FROM sind.associado 
              LEFT JOIN sind.empregador ON empregador.id = associado.empregador
              LEFT JOIN sind.situacao_associado ON situacao_associado.codigo = associado.id_situacao
              WHERE associado.id_divisao = :divisao" . ($cod_situacao === '0' ? '' : " AND associado.id_situacao = :cod_situacao");
    
    // Adicionar condições de busca se existirem
    if (isset($_POST["search"]["value"]) && $_POST["search"]["value"] != '') {
        $count_query .= " AND (associado.codigo ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.nome ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.cpf ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.rg ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.endereco ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.bairro ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.cidade ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.cep ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.telres ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.cel ILIKE '%" . $_POST["search"]["value"] . "%' 
                         OR associado.email ILIKE '%" . $_POST["search"]["value"] . "%')";
    }
    
    $count_statment = $pdo->prepare($count_query);
    $count_statment->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    if ($cod_situacao !== '0') {
        $count_statment->bindParam(':cod_situacao', $cod_situacao, PDO::PARAM_INT);
    }
    $count_statment->execute();
    $count_result = $count_statment->fetch();
    $filtered_rows = $count_result['filtered_count'];
    
    error_log("DEBUG: Número de registros encontrados: " . $filtered_rows);

    $query = "SELECT COUNT(*) as total FROM sind.associado WHERE associado.id_divisao = :divisao";
    $statment = $pdo->prepare($query);
    $statment->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $statment->execute();
    $result_count = $statment->fetch();
    $total_rows = $result_count['total'];

    $data = array();
    foreach ($result as $row) {
        $sub_array = array();
        $sub_array["codigo"] = $row["codigo"];
        $sub_array["nome"] = $row["nome"];
        $sub_array["endereco"] = $row["endereco"];
        $sub_array["bairro"] = $row["bairro"];
        $sub_array["nascimento"] = $row["nascimento"] ? date('d/m/Y', strtotime($row["nascimento"])) : '';
        $sub_array["abreviacao"] = $row["abreviacao"];
        $sub_array["id_empregador"] = $row["empregador"];
        $sub_array["nome_situacao"] = $row["nome_situacao"];
        $sub_array["salario"] = $row["salario"];
        $sub_array["limite"] = $row["limite"];
        $sub_array["cep"] = $row["cep"];
        $sub_array["telres"] = $row["telres"];
        $sub_array["telcom"] = $row["telcom"];
        $sub_array["complemento"] = $row["complemento"];
        $sub_array["botao"] = '<button type="button" name="update_assoc" id="' . $row["codigo"] . '" class="btn btn-warning glyphicon glyphicon-edit btn-xs update_assoc" data-toggle="tooltip" data-placement="top" title="Alterar"></button>';
        $sub_array["botaosenha"] = '<button type="button" name="btnsenha_assoc" id="' . $row["codigo"] . '" class="btn btn-facebook glyphicon glyphicon-lock btn-xs btnsenha_assoc" data-toggle="tooltip" data-placement="top" title="Senha do associado"></button>';
        $sub_array["botaoexcluir"] = '<button type="button" name="btnexcluir" id="' . $row["codigo"] . '" class="btn btn-danger glyphicon glyphicon-trash btn-xs btnexcluir" data-toggle="tooltip" data-placement="top" title="Excluir"></button>';
        $sub_array["id"] = $row["id"];
        $sub_array["id_divisao"] = $row["id_divisao"];
        $data[] = $sub_array;
    }

    $output = array(
        "draw" => intval($_POST["draw"]),
        "recordsTotal" => intval($total_rows),
        "recordsFiltered" => intval($filtered_rows),
        "data" => $data
    );

    // Limpa o buffer e define o header
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    // Log da resposta para debug
    error_log("JSON Response: " . json_encode($output, JSON_UNESCAPED_UNICODE));
    
    echo json_encode($output, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Em caso de erro, limpa o buffer e retorna JSON de erro
    if (ob_get_level()) {
        ob_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $error_response = array(
        "draw" => isset($_POST["draw"]) ? intval($_POST["draw"]) : 0,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => "Erro interno: " . $e->getMessage()
    );
    
    // Log do erro para debug
    error_log("Erro no associado_read2.php: " . $e->getMessage());
    
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
}

// Finaliza o buffer
if (ob_get_level()) {
    ob_end_flush();
}
?>