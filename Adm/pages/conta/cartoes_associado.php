<?PHP
header("Content-type: application/json");
include "../../php/banco.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $divisao = $_POST['divisao'];
    $matricula = $_POST['matricula'];
    $id_empregador = $_POST['id_empregador'];
    $id_associado = $_POST['id_associado'];

    $query = "SELECT c_cartaoassociado.cod_verificacao, 
                     c_cartaoassociado.cod_situacaocartao,
                     c_cartaoassociado.data_pedido,
                     c_cartaoassociado.lote,
                     c_situacaocartao.descri as situacao_desc,
                     associado.nome,
                     associado.codigo
                FROM sind.c_cartaoassociado
                INNER JOIN sind.associado 
                        ON c_cartaoassociado.cod_associado = associado.codigo 
                        AND c_cartaoassociado.empregador = associado.empregador
                INNER JOIN sind.c_situacaocartao 
                        ON c_cartaoassociado.cod_situacaocartao = c_situacaocartao.id
                WHERE associado.id_divisao = :divisao 
                  AND associado.codigo = :matricula
                  AND associado.empregador = :id_empregador
                  AND associado.id = :id_associado
                ORDER BY c_cartaoassociado.data_pedido DESC";

    // Inicializa array com estrutura correta para DataTable
    $someArray = array("data" => array());
    
    $statment = $pdo->prepare($query);
    $statment->execute([
        ':divisao' => $divisao,
        ':matricula' => $matricula,
        ':id_empregador' => $id_empregador,
        ':id_associado' => $id_associado
    ]); 
    
    $result = $statment->fetchAll();
    
    foreach ($result as $row){
        $sub_array = array();

        $sub_array["cartao"] = $row['cod_verificacao'] ?? '';
        $sub_array["situacao"] = $row['situacao_desc'] ?? '';
        $sub_array["data_criacao"] = $row['data_pedido'] ? date('d/m/Y', strtotime($row['data_pedido'])) : '';
        $sub_array["lote"] = $row['lote'] ?? '';
        $sub_array["cod_situacao"] = $row['cod_situacaocartao'] ?? '';
        
        // Status do cartão com botões de ação
        if ($row['cod_situacaocartao'] == 1 || $row['cod_situacaocartao'] == 4 || $row['cod_situacaocartao'] == 5 || $row['cod_situacaocartao'] == 6 || $row['cod_situacaocartao'] == 7) {
            $sub_array["checkbox_ativo"] = '<span class="label label-success">LIBERADO</span><br><button type="button" class="btn btn-warning btn-xs btn-bloquear-cartao" data-cartao="' . ($row['cod_verificacao'] ?? '') . '" style="margin-top:5px;">Bloquear</button>';
        } else {
            $sub_array["checkbox_ativo"] = '<span class="label label-danger">BLOQUEADO</span><br><button type="button" class="btn btn-success btn-xs btn-liberar-cartao" data-cartao="' . ($row['cod_verificacao'] ?? '') . '" style="margin-top:5px;">Liberar</button>';
        }
        
        // Botão para ver histórico
        $sub_array["botao_historico"] = '<button type="button" class="btn btn-info btn-xs btn-historico-cartao" data-cartao="' . ($row['cod_verificacao'] ?? '') . '" data-empregador="' . $id_empregador . '" data-associado="' . $id_associado . '">Ver Histórico</button>';

        // Aplica encoding e adiciona ao array de dados
        $sub_array_convertido = array_map(function($value) {
            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
        }, $sub_array);

        $someArray["data"][] = $sub_array_convertido;
    }
    
    // Garante que sempre há uma estrutura válida
    echo json_encode($someArray);
    
} catch (Exception $e) {
    // Em caso de erro, retorna JSON vazio válido
    echo json_encode(array("data" => array()));
}
?>
