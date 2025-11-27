<?PHP
header("Content-type: application/json");
include "../../php/banco.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $cartao = $_POST['cartao'];
    $id_empregador = $_POST['id_empregador'];
    $id_associado = $_POST['id_associado'];

    $query = "SELECT h.data,
                     h.hora,
                     s.descri as situacao_desc,
                     h.usuario as operador,
                     h.obs
                FROM sind.c_historico_cartoes h
                INNER JOIN sind.c_situacaocartao s 
                        ON h.cod_situacaocartao = s.id
                WHERE h.cod_verificacao = :cartao
                  AND h.id_empregador = :id_empregador
                  AND h.matricula IN (SELECT codigo FROM sind.associado WHERE id = :id_associado)
                ORDER BY h.data DESC, h.hora DESC";

    // Inicializa array com estrutura correta para DataTable
    $someArray = array("data" => array());
    
    $statment = $pdo->prepare($query);
    $statment->execute([
        ':cartao' => $cartao,
        ':id_empregador' => $id_empregador,
        ':id_associado' => $id_associado
    ]);
    
    $result = $statment->fetchAll();
    
    foreach ($result as $row){
        $sub_array = array();

        $sub_array["data"] = $row['data'] ? date('d/m/Y', strtotime($row['data'])) : '';
        $sub_array["hora"] = $row['hora'] ?? '';
        $sub_array["situacao"] = $row['situacao_desc'] ?? '';
        $sub_array["operador"] = $row['operador'] ?? '';
        $sub_array["obs"] = $row['obs'] ?? '';

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
