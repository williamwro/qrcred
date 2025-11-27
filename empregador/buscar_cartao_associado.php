<?php
// Suprimir exibição de erros para não quebrar o JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

$response = array();

try {
    include "../Adm/php/banco.php";
    
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id_associado = isset($_POST["id_associado"]) ? (int)$_POST["id_associado"] : 0;
    $id_divisao = isset($_POST["id_divisao"]) ? (int)$_POST["id_divisao"] : 0;

    if ($id_associado > 0) {
        // Busca o cartão do associado na tabela sind.c_cartaoassociado
        $sql = "SELECT 
                    ca.cod_verificacao,
                    ca.cod_situacaocartao,
                    sc.descri as situacao_descricao
                FROM sind.c_cartaoassociado ca
                LEFT JOIN sind.c_situacaocartao sc ON ca.cod_situacaocartao = sc.id
                WHERE ca.id_associado = :id_associado";
        
        if ($id_divisao > 0) {
            $sql .= " AND ca.id_divisao = :id_divisao";
        }
        
        // Ordenar pelo mais recente (maior ID) para pegar o cartão atual
        $sql .= " ORDER BY ca.id DESC LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
        
        if ($id_divisao > 0) {
            $stmt->bindParam(':id_divisao', $id_divisao, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $response["status"] = "success";
            $response["numero_cartao"] = $row['cod_verificacao'] ? $row['cod_verificacao'] : '';
            $response["cod_situacao"] = $row['cod_situacaocartao'] ? $row['cod_situacaocartao'] : '';
            $response["situacao_descricao"] = $row['situacao_descricao'] ? $row['situacao_descricao'] : '';
        } else {
            $response["status"] = "not_found";
            $response["numero_cartao"] = "";
            $response["cod_situacao"] = "";
            $response["situacao_descricao"] = "";
        }
    } else {
        $response["status"] = "error";
        $response["message"] = "ID do associado não informado";
    }
} catch (Exception $e) {
    $response["status"] = "error";
    $response["message"] = "Erro: " . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
