<?php
header("Content-type: application/json");
include "Adm/php/banco.php";
include "Adm/php/funcoes.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buscar o mês corrente
    $query = $pdo->query("SELECT id, abreviacao, id_divisao, status 
                         FROM sind.mes_corrente 
                         WHERE status = 1 
                         ORDER BY id DESC 
                         LIMIT 1");
    
    $mesCorrente = $query->fetch(PDO::FETCH_ASSOC);

    if ($mesCorrente) {
        // Buscar a taxa de antecipação
        $taxa_query = $pdo->query("SELECT porcentagem FROM sind.taxa_antecipacao ORDER BY id DESC LIMIT 1");
        $taxa = $taxa_query->fetch(PDO::FETCH_ASSOC);

        // Buscar o email de antecipação
        $email_query = $pdo->query("SELECT email FROM sind.email_antecipacao ORDER BY id DESC LIMIT 1");
        $email = $email_query->fetch(PDO::FETCH_ASSOC);

        // Montar o objeto de resposta
        $response = [
            'id' => $mesCorrente['id'],
            'abreviacao' => $mesCorrente['abreviacao'],
            'id_divisao' => $mesCorrente['id_divisao'],
            'status' => $mesCorrente['status'],
            'porcentagem' => $taxa ? $taxa['porcentagem'] : null,
            'email' => $email ? $email['email'] : null
        ];

        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'Nenhum mês corrente encontrado']);
    }

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao conectar com o banco de dados: ' . $e->getMessage()]);
}