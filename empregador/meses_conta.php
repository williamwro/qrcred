<?php
header('Content-Type: application/json; charset=utf-8');
include "../Adm/php/banco.php";

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$divisao = isset($_GET['divisao']) ? (int)$_GET['divisao'] : 0;
$origem = isset($_GET['origem']) ? $_GET['origem'] : '';

$meses = array();

try {
    // Buscar mês corrente
    $sqlMesCorrente = "SELECT abreviacao FROM sind.mes_corrente WHERE id_divisao = :divisao LIMIT 1";
    $stmtMes = $pdo->prepare($sqlMesCorrente);
    $stmtMes->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $stmtMes->execute();
    $mesCorrente = $stmtMes->fetchColumn();

    if ($mesCorrente) {
        $meses[] = array('mes_corrente' => $mesCorrente);
    }

    // Buscar todos os meses disponíveis
    $sql = "SELECT DISTINCT mes as abreviacao 
            FROM sind.conta 
            WHERE divisao = :divisao 
            ORDER BY mes DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result as $row) {
        $meses[] = array('abreviacao' => $row['abreviacao']);
    }

} catch (PDOException $e) {
    $meses = array('error' => $e->getMessage());
}

echo json_encode($meses, JSON_UNESCAPED_UNICODE);
?>
