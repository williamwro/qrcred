<?php
header('Content-Type: application/json; charset=utf-8');

require '../../php/banco.php';
include "../../php/funcoes.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id_beneficiario = $_POST['id_beneficiario'] ?? null;
    $id_associado = $_POST['id_associado'] ?? null;
    $id_divisao = $_POST['id_divisao'] ?? null;
    $nome_beneficiario = $_POST['nome'] ?? '';
    $cpf_zap = $_POST['cpf'] ?? '';
    $parentesco = $_POST['parentesco'] ?? '';
    $data_nascimento = $_POST['data_nascimento'] ?? null;
    $status = $_POST['status'] ?? 'pendente';

    if (!$id_beneficiario || !$id_associado || !$id_divisao) {
        echo json_encode([
            'success' => false,
            'error' => 'Dados obrigatórios não informados'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Remover formatação do CPF
    $cpf_zap = preg_replace('/[^0-9]/', '', $cpf_zap);

    $query = "UPDATE sind.seguro_beneficiarios 
              SET nome_beneficiario = :nome_beneficiario,
                  cpf_zap = :cpf_zap,
                  parentesco = :parentesco,
                  data_nascimento = :data_nascimento,
                  status = :status
              WHERE id_beneficiario = :id_beneficiario 
                AND id_associado = :id_associado 
                AND id_divisao = :id_divisao";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':nome_beneficiario', $nome_beneficiario, PDO::PARAM_STR);
    $stmt->bindParam(':cpf_zap', $cpf_zap, PDO::PARAM_STR);
    $stmt->bindParam(':parentesco', $parentesco, PDO::PARAM_STR);
    $stmt->bindParam(':data_nascimento', $data_nascimento, PDO::PARAM_STR);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':id_beneficiario', $id_beneficiario, PDO::PARAM_INT);
    $stmt->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
    $stmt->bindParam(':id_divisao', $id_divisao, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Beneficiário atualizado com sucesso'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao atualizar beneficiário'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    error_log("Erro ao atualizar beneficiário: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao atualizar beneficiário'
    ], JSON_UNESCAPED_UNICODE);
}
