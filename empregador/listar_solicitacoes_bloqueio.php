<?php
// Suprimir exibição de erros para não quebrar o JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

$someArray = array();
$someArray["data"] = array(); // Sempre inicializar o array data

try {
    include "../Adm/php/banco.php";
    
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $situacao = isset($_POST["situacao"]) ? $_POST["situacao"] : 'todos';
    $empregador_id = isset($_POST["empregador_id"]) ? (int)$_POST["empregador_id"] : 0;

    // Busca as solicitações de bloqueio com filtro por situação e empregador
    $sql = "SELECT 
                sb.id,
                sb.cod_verificacao,
                sb.data_hora,
                sb.data_hora_resposta,
                sb.id_situacao,
                a.nome as nome_associado,
                a.codigo as matricula
            FROM sind.solicitacao_bloqueio sb
            LEFT JOIN sind.associado a ON sb.id_associado = a.id
            WHERE a.empregador = :empregador_id";
    
    // Filtrar por situação
    if ($situacao !== 'todos') {
        if ($situacao == '1') {
            // Pendente: id_situacao = 1 ou NULL
            $sql .= " AND (sb.id_situacao = 1 OR sb.id_situacao IS NULL)";
        } else {
            $sql .= " AND sb.id_situacao = :situacao";
        }
    }
    
    $sql .= " ORDER BY sb.data_hora DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':empregador_id', $empregador_id, PDO::PARAM_INT);
    
    if ($situacao !== 'todos' && $situacao != '1') {
        $stmt->bindParam(':situacao', $situacao, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $result = $stmt->fetchAll();

    if ($result) {
        foreach ($result as $row) {
            $sub_array = array();
            
            $sub_array["id"] = $row['id'];
            $sub_array["cod_verificacao"] = $row['cod_verificacao'];
            $sub_array["nome_associado"] = $row['nome_associado'] ? $row['nome_associado'] : '-';
            $sub_array["matricula"] = $row['matricula'] ? $row['matricula'] : '-';
            $sub_array["data_hora"] = $row['data_hora'] ? date('d/m/Y H:i:s', strtotime($row['data_hora'])) : '';
            $sub_array["data_hora_resposta"] = $row['data_hora_resposta'] ? date('d/m/Y H:i:s', strtotime($row['data_hora_resposta'])) : '-';
            
            // Situação: 1-Pendente, 2-Aprovado, 3-Reprovado
            $id_situacao = $row['id_situacao'];
            if ($id_situacao == 1 || $id_situacao === null) {
                $sub_array["cod_situacao"] = 1;
                $sub_array["situacao"] = "<span class='label label-warning'>Pendente</span>";
            } elseif ($id_situacao == 2) {
                $sub_array["cod_situacao"] = 2;
                $sub_array["situacao"] = "<span class='label label-success'>Aprovado</span>";
            } elseif ($id_situacao == 3) {
                $sub_array["cod_situacao"] = 3;
                $sub_array["situacao"] = "<span class='label label-danger'>Reprovado</span>";
            } else {
                $sub_array["cod_situacao"] = 0;
                $sub_array["situacao"] = "<span class='label label-default'>-</span>";
            }

            $someArray["data"][] = $sub_array;
        }
    }
} catch (Exception $e) {
    // Em caso de erro, retorna array vazio (não quebra o DataTable)
    $someArray["data"] = array();
    // Descomentar para debug:
    // $someArray["error"] = $e->getMessage();
}

echo json_encode($someArray, JSON_UNESCAPED_UNICODE);
?>
