<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");
header("Content-type: application/json");
require 'Adm/php/banco.php';
date_default_timezone_set('America/Sao_Paulo');

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Recebe o corpo da requisição JSON
$data = json_decode(file_get_contents('php://input'), true);

// Validação do convênio
if (!isset($data['convenio'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Código do convênio não fornecido'
    ]);
    exit;
}

$convenio = (int)$data['convenio'];

try {
    // Busca os estornos do convênio
    $sql = "SELECT 
                e.lancamento, 
                e.associado, 
                a.nome AS nome_associado,
                e.convenio, 
                e.valor, 
                e.data, 
                e.hora, 
                e.descricao, 
                e.mes, 
                e.empregador, 
                emp.nome AS nome_empregador,
                e.funcionario, 
                e.parcela, 
                e.ip_convenio, 
                e.mac_adress, 
                e.exclui, 
                e.user_exclui, 
                e.uri_cupom, 
                e.tipo, 
                e.id_situacao, 
                e.data_estorno, 
                e.hora_estorno, 
                e.id, 
                e.func_estorno, 
                e.id_divisao, 
                e.data_fatura, 
                e.uuid_conta
            FROM sind.estornos e
            INNER JOIN sind.associado a ON e.associado = a.codigo
            INNER JOIN sind.empregador emp ON e.empregador = emp.id
            WHERE e.convenio = :convenio 
            ORDER BY e.data_estorno DESC, e.hora_estorno DESC;";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':convenio', $convenio, PDO::PARAM_INT);
    $stmt->execute();
    
    $estornos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formata os dados para o frontend
    $estornosFormatados = array_map(function($estorno) {
        return [
            'id' => $estorno['id'],
            'lancamento' => $estorno['lancamento'],
            'associado' => $estorno['associado'],
            'nome_associado' => $estorno['nome_associado'],
            'convenio' => $estorno['convenio'],
            'valor' => $estorno['valor'],
            'data' => $estorno['data'],
            'hora' => $estorno['hora'],
            'descricao' => $estorno['descricao'],
            'mes' => $estorno['mes'],
            'empregador' => $estorno['empregador'],
            'nome_empregador' => $estorno['nome_empregador'],
            'funcionario' => $estorno['funcionario'],
            'parcela' => $estorno['parcela'],
            'ip_convenio' => $estorno['ip_convenio'],
            'mac_adress' => $estorno['mac_adress'],
            'exclui' => $estorno['exclui'],
            'user_exclui' => $estorno['user_exclui'],
            'uri_cupom' => $estorno['uri_cupom'],
            'tipo' => $estorno['tipo'],
            'id_situacao' => $estorno['id_situacao'],
            'data_estorno' => $estorno['data_estorno'],
            'hora_estorno' => $estorno['hora_estorno'],
            'func_estorno' => $estorno['func_estorno'],
            'id_divisao' => $estorno['id_divisao'],
            'data_fatura' => $estorno['data_fatura'],
            'uuid_conta' => $estorno['uuid_conta']
        ];
    }, $estornos);

    echo json_encode([
        'success' => true,
        'data' => $estornosFormatados
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar estornos: ' . $e->getMessage()
    ]);
}
?>
