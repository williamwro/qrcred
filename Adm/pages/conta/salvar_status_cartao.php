<?PHP
header("Content-type: application/json");
include "../../php/banco.php";
date_default_timezone_set('America/Sao_Paulo');

function converte_data($date) {
    return substr($date,6,4).'-'.substr($date,3,2).'-'.substr($date,0,2).' 00:00:00';
}

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $cartao = $_POST['cartao'];
    $ativo = $_POST['ativo']; // 1 para ativo, 0 para inativo
    $divisao = $_POST['divisao'];
    $usuario_cod = $_POST['usuario_cod'];
    $usuario_global = $_POST['usuario_global'];
    $matricula = $_POST['matricula'];
    $id_empregador = $_POST['id_empregador'];
    $id_associado = $_POST['id_associado'];

    // Preparar data e hora para o histórico
    $data2 = new DateTime();
    $datax = $data2->format('Y-m-d');
    $data3 = $data2->format('d-m-Y');
    $data4 = new DateTime($data3);
    $data = $data4->format('d/m/Y');
    $data = converte_data($data);
    $hora = date("H:i:s");
    $hora = str_replace("00:00:00",$hora,$data);

    // Determinar a situação baseada no status ativo
    $cod_situacao = $ativo == 1 ? 1 : 2; // 1 = Liberado, 2 = Bloqueado
    $obs = $ativo == 1 ? "Cartão liberado via sistema de manutenção" : "Cartão bloqueado via sistema de manutenção";

    // Atualiza o status do cartão
    $query = "UPDATE sind.c_cartaoassociado 
              SET cod_situacaocartao = :cod_situacao,
                  data_pedido = :data_pedido
              WHERE cod_verificacao = :cartao";

    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        ':cod_situacao' => $cod_situacao,
        ':cartao' => $cartao,
        ':data_pedido' => $datax
    ]);

    if ($result) {
        // Registra no histórico de cartões
        $sql_historico = "INSERT INTO sind.c_historico_cartoes(matricula,cod_verificacao,cod_situacaocartao,data,hora,usuario,obs,id_empregador)";
        $sql_historico .= " VALUES(:matricula, :cod_verificacao, :cod_situacao, :data, :hora, :usuario, :obs, :id_empregador)";
        
        $stmt_historico = $pdo->prepare($sql_historico);
        $stmt_historico->execute([
            ':matricula' => $matricula,
            ':cod_verificacao' => $cartao,
            ':cod_situacao' => $cod_situacao,
            ':data' => $data,
            ':hora' => $hora,
            ':usuario' => $usuario_global,
            ':obs' => $obs,
            ':id_empregador' => $id_empregador
        ]);

        echo json_encode(array("resultado" => "atualizado"));
    } else {
        echo json_encode(array("resultado" => "erro"));
    }
    
} catch (Exception $e) {
    echo json_encode(array("resultado" => "erro", "mensagem" => $e->getMessage()));
}
?>
