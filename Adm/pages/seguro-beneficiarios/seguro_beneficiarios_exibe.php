<?php
header('Content-Type: application/json; charset=utf-8');

require '../../php/banco.php';
include "../../php/funcoes.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id_beneficiario = $_POST['id_beneficiario'] ?? null;

    if (!$id_beneficiario) {
        echo json_encode([
            'success' => false,
            'error' => 'ID do beneficiário não informado'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

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
                a.nome as nome_associado
              FROM sind.seguro_beneficiarios sb
              INNER JOIN sind.associado a ON sb.id_associado = a.id::integer
              WHERE sb.id_beneficiario = :id_beneficiario";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id_beneficiario', $id_beneficiario, PDO::PARAM_INT);
    $stmt->execute();

    $beneficiario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$beneficiario) {
        echo json_encode([
            'success' => false,
            'error' => 'Beneficiário não encontrado'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $beneficiario
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Erro ao exibir beneficiário: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao carregar dados do beneficiário'
    ], JSON_UNESCAPED_UNICODE);
}
