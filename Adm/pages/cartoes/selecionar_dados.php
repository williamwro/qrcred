<?PHP
header("Content-type: application/json");
include "../../php/banco.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $divisao = $_POST['divisao'];
    $lote = $_POST['lote'];
   

    if($lote == "aberto") {
        $query = "SELECT c_cartaoassociado.cod_verificacao, associado.nome,
                         associado.codigo, divisao.id_divisao, empregador.abreviacao, associado.id,
                         cs.senha
                    FROM sind.associado
                  INNER JOIN sind.empregador 
                              ON associado.empregador = empregador.id 
                  INNER JOIN sind.c_cartaoassociado 
                              ON associado.codigo = c_cartaoassociado.cod_associado 
                              AND associado.empregador = c_cartaoassociado.empregador
                  INNER JOIN sind.divisao 
                              ON associado.id_divisao = divisao.id_divisao
                   LEFT JOIN sind.c_senhaassociado cs
                              ON cs.cod_associado = associado.codigo
                             AND cs.id_empregador = associado.empregador
                   WHERE associado.id_divisao = :divisao 
                     AND (c_cartaoassociado.cod_situacaocartao = 4 OR c_cartaoassociado.cod_situacaocartao = 1)
                     AND lote IS NULL 
                ORDER BY nome";
    } else {
        $query = "SELECT c_cartaoassociado.cod_verificacao, associado.nome,
                         associado.codigo, divisao.id_divisao, empregador.abreviacao, associado.id,
                         cs.senha
                    FROM sind.associado
                  INNER JOIN sind.empregador 
                              ON associado.empregador = empregador.id 
                  INNER JOIN sind.c_cartaoassociado 
                              ON associado.codigo = c_cartaoassociado.cod_associado 
                              AND associado.empregador = c_cartaoassociado.empregador
                  INNER JOIN sind.divisao 
                              ON associado.id_divisao = divisao.id_divisao
                   LEFT JOIN sind.c_senhaassociado cs
                              ON cs.cod_associado = associado.codigo
                             AND cs.id_empregador = associado.empregador
                   WHERE associado.id_divisao = :divisao 
                     AND lote = :lote 
                ORDER BY nome";
    }

    // Inicializa array com estrutura correta para DataTable
    $someArray = array("data" => array());
    
    $statment = $pdo->prepare($query);
    
    if($lote == "aberto") {
        $statment->execute([
            ':divisao' => $divisao
           
        ]);
    } else {
        $statment->execute([
            ':divisao' => $divisao,
            ':lote' => $lote
            
        ]);
    }
    
    $result = $statment->fetchAll();
    
    foreach ($result as $row){
        $sub_array = array();

        $sub_array["cartao"]       = $row['cod_verificacao'] ?? '';
        $sub_array["codigo"]       = $row['codigo'] ?? '';
        $sub_array["abreviacao"]   = $row['abreviacao'] ?? '';
        $sub_array["nome"]         = $row['nome'] ?? '';
        $sub_array["senha"]        = $row['senha'] ?? '';
        $sub_array["id"]           = $row['id'] ?? '';
        $sub_array["botaoexcluir"] = '<button type="button" name="btnexcluirCartao" id="'.($row["cod_verificacao"] ?? '').'" class="btn btn-danger btn-xs btnexcluirCartao" disabled>Excluir</button>';

        // Preservar campo id antes da conversão
        $id_preservado = $sub_array["id"];

        // Aplica encoding e adiciona ao array de dados
        $sub_array_convertido = array_map(function($value) {
            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
        }, $sub_array);

        // Restaurar campo id como integer
        $sub_array_convertido["id"] = (int)$id_preservado;

        $someArray["data"][] = $sub_array_convertido;
    }
    
    // Garante que sempre há uma estrutura válida
    echo json_encode($someArray);
    
} catch (Exception $e) {
    // Em caso de erro, retorna JSON vazio válido
    echo json_encode(array("data" => array()));
}