<?PHP
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["cod_associado"])){
    $std = new stdClass();
    $cod_associado = $_POST["cod_associado"];
    $empregador = $_POST["id_empregador"];
    $id_associado = $_POST["id_associado"];
    $divisao = $_POST["divisao"];

    // Verificar tabela CONTA
    $sql_conta = "SELECT COUNT(*) as total FROM sind.conta 
                  WHERE associado = :cod_associado AND empregador = :empregador AND divisao = :divisao";
    $stmt_conta = $pdo->prepare($sql_conta);
    $stmt_conta->bindParam(':cod_associado', $cod_associado, PDO::PARAM_STR);
    $stmt_conta->bindParam(':empregador', $empregador, PDO::PARAM_INT);
    $stmt_conta->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $stmt_conta->execute();
    $result_conta = $stmt_conta->fetch(PDO::FETCH_ASSOC);
    $tem_conta = $result_conta['total'] > 0;

    // Verificar tabela C_cartaoassociado
    $sql_cartao = "SELECT COUNT(*) as total FROM sind.c_cartaoassociado 
                   WHERE id_associado = :id_associado";
    $stmt_cartao = $pdo->prepare($sql_cartao);
    $stmt_cartao->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
    $stmt_cartao->execute();
    $result_cartao = $stmt_cartao->fetch(PDO::FETCH_ASSOC);
    $tem_cartao = $result_cartao['total'] > 0;

    // Verificar tabela antecipacao
    $sql_antecipacao = "SELECT COUNT(*) as total FROM sind.antecipacao 
                        WHERE matricula = :cod_associado AND empregador = :empregador AND id_divisao = :divisao";
    $stmt_antecipacao = $pdo->prepare($sql_antecipacao);
    $stmt_antecipacao->bindParam(':cod_associado', $cod_associado, PDO::PARAM_STR);
    $stmt_antecipacao->bindParam(':empregador', $empregador, PDO::PARAM_INT);
    $stmt_antecipacao->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    $stmt_antecipacao->execute();
    $result_antecipacao = $stmt_antecipacao->fetch(PDO::FETCH_ASSOC);
    $tem_antecipacao = $result_antecipacao['total'] > 0;

    // Verificar se existe algum impedimento (conta e antecipacao bloqueiam; senha e log_limites serão deletados junto)
    if ($tem_conta || $tem_cartao || $tem_antecipacao) {
        $motivos = array();
        if ($tem_conta) $motivos[] = "lançamentos na conta";
        if ($tem_cartao) $motivos[] = "cartões associados";
        if ($tem_antecipacao) $motivos[] = "antecipações";

        $msg = "existe impedimento";
        $arr = array('Resultado' => $msg, 'Motivos' => implode(", ", $motivos));
    } else {
        $msg = "pode excluir";
        $arr = array('Resultado' => $msg);
    }

    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
}