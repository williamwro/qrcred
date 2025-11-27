<?PHP
header("Content-type: application/json; charset=utf-8");
include "../../php/banco.php";
include "../../php/funcoes.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $divisao = $_POST["divisao"] ?? 1;
    
    $query = "SELECT associado.codigo, 
                     associado.nome, 
                     associado.endereco, 
                     associado.numero, 
                     associado.nascimento,
                     associado.salario,
                     associado.limite,
                     associado.empregador AS id_empregador, 
                     associado.id AS id_associado,
                     associado.id_divisao AS id_divisao,
                     associado.cep, 
                     associado.telres, 
                     associado.telcom, 
                     associado.cel, 
                     associado.bairro, 
                     associado.complemento,
                     associado.cidade,
                     associado.cpf,
                     associado.id_situacao,
                     empregador.divisao,
                     empregador.nome AS empregador, 
                     empregador.abreviacao
                FROM sind.empregador RIGHT JOIN sind.associado ON empregador.Id = associado.empregador 
               WHERE empregador.divisao = :divisao 
               ORDER BY associado.nome";
    
    $someArray = array('data' => array());
    
    $statement = $pdo->prepare($query);
    $statement->execute([':divisao' => $divisao]);
    $result = $statement->fetchAll();
    
    foreach ($result as $row) {
        $sub_array = array();
        
        $sub_array["codigo"]          = $row["codigo"];
        $sub_array["nome"]            = $row["nome"];
        $sub_array["endereco"]        = $row["endereco"];
        $sub_array["numero"]          = $row["numero"];
        $sub_array["bairro"]          = $row["bairro"];
        $sub_array["nascimento"]      = $row["nascimento"] ? date('d/m/Y', strtotime($row["nascimento"])) : '';
        $sub_array["salario"]         = $row["salario"];
        $sub_array["limite"]          = $row["limite"];
        $sub_array["empregador"]      = $row["empregador"];
        $sub_array["codempregador"]   = $row["id_empregador"];
        $sub_array["id_associado"]    = $row["id_associado"];
        $sub_array["id_divisao"]      = $row["id_divisao"];
        $sub_array["cep"]             = $row["cep"];
        $sub_array["telres"]          = $row["telres"];
        $sub_array["telcom"]          = $row["telcom"];
        $sub_array["cel"]             = $row["cel"];
        $sub_array["complemento"]     = $row["complemento"];
        $sub_array["cidade"]          = $row["cidade"];
        $sub_array["cpf"]             = $row["cpf"];
        $sub_array["id_situacao"]     = $row["id_situacao"];
        $sub_array["abreviacao"]      = $row["abreviacao"];
        $sub_array["botao_selecionar"] = '<button type="button" class="btn btn-primary btn-xs selecionar-associado" 
                                                  data-codigo="'.$row["codigo"].'" 
                                                  data-cpf="'.$row["cpf"].'" 
                                                  data-nome="'.$row["nome"].'"
                                                  data-id_associado="'.$row["id_associado"].'"
                                                  data-id_divisao="'.$row["id_divisao"].'"
                                                  data-id_empregador="'.$row["id_empregador"].'"
                                                  data-toggle="tooltip" 
                                                  data-placement="top" 
                                                  title="Selecionar este associado">
                                                  <span class="glyphicon glyphicon-ok"></span> Selecionar
                                           </button>';
        
        $someArray["data"][] = $sub_array;
    }
    
    echo json_encode($someArray, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'data' => [],
        'error' => true,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>
