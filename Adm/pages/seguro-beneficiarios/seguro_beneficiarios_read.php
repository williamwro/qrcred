<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require '../../php/banco.php';
include "../../php/funcoes.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuario_global = $_POST['usuario_global'] ?? '';
    $divisao = $_POST['divisao'] ?? '';
    $usuario_cod = $_POST['usuario_cod'] ?? '';

    // Parâmetros do DataTables
    $draw = $_POST['draw'] ?? 1;
    $start = $_POST['start'] ?? 0;
    $length = $_POST['length'] ?? 10;
    $search_value = $_POST['search']['value'] ?? '';

    // Query base
    $query = "SELECT 
                sb.id_beneficiario,
                sb.id_associado,
                sb.id_divisao,
                sb.nome_beneficiario,
                sb.cpf_zap,
                sb.parentesco,
                sb.data_nascimento,
                sb.status,
                sb.data_criacao,
                sb.data_assinatura,
                a.nome as nome_associado,
                a.cpf as cpf_associado
              FROM sind.seguro_beneficiarios sb
              INNER JOIN sind.associado a ON sb.id_associado = a.id::integer
              WHERE sb.id_divisao = :divisao";

    // Adicionar busca se houver
    $search_param = null;
    if (!empty($search_value)) {
        $search_param = "%$search_value%";
        $query .= " AND (
            sb.nome_beneficiario ILIKE :search OR
            sb.cpf_zap ILIKE :search OR
            a.nome ILIKE :search OR
            a.cpf ILIKE :search OR
            sb.parentesco ILIKE :search
        )";
    }

    // Contar total de registros
    $count_query = "SELECT COUNT(*) as total FROM sind.seguro_beneficiarios sb
                    INNER JOIN sind.associado a ON sb.id_associado = a.id::integer
                    WHERE sb.id_divisao = :divisao";
    
    if (!empty($search_value)) {
        $count_query .= " AND (
            sb.nome_beneficiario ILIKE :search OR
            sb.cpf_zap ILIKE :search OR
            a.nome ILIKE :search OR
            a.cpf ILIKE :search OR
            sb.parentesco ILIKE :search
        )";
    }

    $stmt_count = $pdo->prepare($count_query);
    $stmt_count->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    if (!empty($search_value)) {
        $search_param = "%$search_value%";
        $stmt_count->bindParam(':search', $search_param, PDO::PARAM_STR);
    }
    $stmt_count->execute();
    $total_records = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

    // Ordenação
    $order_column = $_POST['order'][0]['column'] ?? 9;
    $order_dir = $_POST['order'][0]['dir'] ?? 'desc';
    
    $columns = ['', 'sb.id_beneficiario', 'a.nome', 'a.cpf', 'sb.nome_beneficiario', 'sb.cpf_zap', 'sb.parentesco', 'sb.data_nascimento', 'sb.status', 'sb.data_criacao'];
    $order_by = $columns[$order_column] ?? 'sb.data_criacao';
    
    $query .= " ORDER BY $order_by $order_dir";
    $query .= " LIMIT :length OFFSET :start";

    // Executar query principal
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    if (!empty($search_value)) {
        $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
    }
    $stmt->bindParam(':length', $length, PDO::PARAM_INT);
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Formatar CPF
        $cpf_beneficiario = $row['cpf_zap'] ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $row['cpf_zap']) : '';
        $cpf_associado = $row['cpf_associado'] ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $row['cpf_associado']) : '';
        
        // Formatar data
        $data_nascimento = $row['data_nascimento'] ? date('d/m/Y', strtotime($row['data_nascimento'])) : '';
        $data_criacao = $row['data_criacao'] ? date('d/m/Y H:i', strtotime($row['data_criacao'])) : '';
        
        // Status traduzido
        $status_map = [
            'pendente' => 'Pendente',
            'assinado' => 'Assinado',
            'cancelado' => 'Cancelado'
        ];
        $status = $status_map[$row['status']] ?? $row['status'];
        
        // Botões de ação
        $acoes = '<button class="btn btn-sm btn-primary btn-editar" data-id="'.$row['id_beneficiario'].'">
                    <i class="fa fa-edit"></i> Editar
                  </button> ';
        
        if ($row['status'] !== 'assinado') {
            $acoes .= '<button class="btn btn-sm btn-danger btn-excluir" data-id="'.$row['id_beneficiario'].'" data-associado="'.$row['id_associado'].'">
                        <i class="fa fa-trash"></i> Excluir
                      </button>';
        }
        
        $data[] = [
            'id_beneficiario' => $row['id_beneficiario'],
            'nome_associado' => $row['nome_associado'],
            'cpf_associado' => $cpf_associado,
            'nome' => $row['nome_beneficiario'],
            'cpf' => $cpf_beneficiario,
            'parentesco' => $row['parentesco'],
            'data_nascimento' => $data_nascimento,
            'status' => $status,
            'data_criacao' => $data_criacao,
            'acoes' => $acoes
        ];
    }

    echo json_encode([
        "draw" => intval($draw),
        "recordsTotal" => intval($total_records),
        "recordsFiltered" => intval($total_records),
        "data" => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Erro ao listar beneficiários: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        "draw" => 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => "Erro ao carregar dados: " . $e->getMessage(),
        "debug" => [
            "message" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
