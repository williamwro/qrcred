<?php
// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");
header("Content-type: application/json");

// Incluir arquivos de conexão e funções
include 'Adm/php/banco.php';
include "Adm/php/funcoes.php";

try {
    // Conectar ao banco PostgreSQL
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar se o código do convênio foi fornecido
    if (!isset($_GET['cod_convenio'])) {
        throw new Exception('Código do convênio não fornecido');
    }

    $cod_convenio = intval($_GET['cod_convenio']);

    // Consulta SQL para buscar os lançamentos do convênio
    $query = "SELECT 
                conta.lancamento, 
                conta.associado AS matricula, 
                conta.valor, 
                conta.data, 
                to_char(conta.hora, 'HH24:MI') as hora,
                conta.mes, 
                empregador.nome AS empregador, 
                empregador.id AS codigoempregador, 
                convenio.razaosocial AS convenio, 
                convenio.codigo AS cod_convenio, 
                associado.nome AS associado, 
                conta.funcionario, 
                conta.parcela, 
                conta.descricao,
                conta.data_fatura,
                convenio.senha_estorno
            FROM sind.associado 
            RIGHT JOIN (
                sind.empregador 
                RIGHT JOIN (
                    sind.convenio 
                    RIGHT JOIN sind.conta 
                    ON convenio.codigo = conta.convenio
                ) 
                ON empregador.id = conta.empregador
            ) 
            ON associado.codigo = conta.associado 
            AND associado.empregador = conta.empregador 
            WHERE convenio.codigo = :cod_convenio 
            AND convenio.desativado = false 
            ORDER BY conta.lancamento DESC";

    // Preparar e executar a consulta
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':cod_convenio', $cod_convenio, PDO::PARAM_INT);
    $stmt->execute();

    // Array para armazenar os resultados
    $lancamentos = array();

    // Processar os resultados
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Formatar os dados
        $lancamento = array(
            'id' => $row['lancamento'],
            'data' => date('d/m/Y', strtotime($row['data'])),
            'hora' => $row['hora'],
            'valor' => number_format($row['valor'], 2, ',', '.'),
            'associado' => $row['associado'] ?: 'N/A',
            'matricula' => $row['matricula'] ?: 'N/A',
            'empregador' => $row['empregador'] ?: 'N/A',
            'codigoempregador' => $row['codigoempregador'],
            'mes' => $row['mes'],
            'parcela' => $row['parcela'],
            'descricao' => $row['descricao'] ?: '-',
            'data_fatura' => $row['data_fatura'] ? date('d/m/Y', strtotime($row['data_fatura'])) : '-'
        );

        $lancamentos[] = $lancamento;
    }

    // Retornar os resultados
    echo json_encode(array(
        'success' => true,
        'lancamentos' => $lancamentos
    ));

} catch (Exception $e) {
    // Em caso de erro, retornar mensagem de erro
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Erro ao buscar lançamentos: ' . $e->getMessage()
    ));
}

// Fechar a conexão
if (isset($pdo)) {
    $pdo = null;
}
?> 