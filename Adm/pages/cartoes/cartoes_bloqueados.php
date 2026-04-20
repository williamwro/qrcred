<?PHP
header('Content-Type: application/json; charset=utf-8');
include "../../php/banco.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'UTF8'");
    $divisao = $_POST['divisao'];
    $idsituacao = $_POST['idsituacao'];
    
    $query = "SELECT c_cartaoassociado.cod_verificacao, associado.nome, c_cartaoassociado.data_pedido,
                     associado.codigo, associado.cpf, divisao.id_divisao, empregador.nome as empregador
                FROM sind.associado
          INNER JOIN sind.c_cartaoassociado 
                  ON associado.codigo = c_cartaoassociado.cod_associado and associado.empregador = c_cartaoassociado.empregador
          INNER JOIN sind.empregador 
                  ON associado.empregador = empregador.id
          INNER JOIN sind.divisao
                  ON empregador.id_divisao = divisao.id_divisao
               WHERE c_cartaoassociado.cod_situacaocartao = :idsituacao
                 AND associado.id_divisao = :divisao
            ORDER BY nome";

    // Inicializa array com estrutura correta para DataTable
    $someArray = array("data" => array());
    
    $statment = $pdo->prepare($query);
    $statment->execute([
        ':idsituacao' => $idsituacao,
        ':divisao' => $divisao
    ]);
    $result = $statment->fetchAll();
    
    foreach ($result as $row){
        $sub_array = array();
        if($row['data_pedido'] !== null){
            $sub_array["data_pedido"] = date('d/m/Y', strtotime($row['data_pedido']));
        }else{
            $sub_array["data_pedido"] = '';
        }
        $sub_array["cartao"]       = $row['cod_verificacao'] ?? '';
        $sub_array["nome"]         = $row['nome'] ?? '';
        $sub_array["empregador"]   = $row['empregador'] ?? '';
        $sub_array["codigo"]       = $row['codigo'] ?? '';
        $sub_array["cpf"]          = $row['cpf'] ?? '';
        
        if($idsituacao == 1) {
            $sub_array["botaoexcluir"] = '<span class="badge badge-success" style="background: green">Liberado</span>';
        }elseif($idsituacao == 2) {
            $sub_array["botaoexcluir"] = '<span class="badge badge-danger" style="background: red">Bloqueado</span>';
        }elseif($idsituacao == 3) {
            $sub_array["botaoexcluir"] = '<span class="badge badge-dark" style="background: black">Cancelado</span>';
        }elseif($idsituacao == 4) {
            $sub_array["botaoexcluir"] = '<span class="badge badge-primary" style="background: blue">Producao</span>';
        }elseif($idsituacao == 5) {
            $sub_array["botaoexcluir"] = '<span class="badge badge-primary" style="background: maroon">Segunda Via</span>';
        }elseif($idsituacao == 6) {
            $sub_array["botaoexcluir"] = '<span class="badge badge-warning" style="background: orange"> Disponivel</span>';
        }elseif($idsituacao == 7) {
            $sub_array["botaoexcluir"] = '<span class="badge badge-info" style="background: cyan;color: black">Entregue</span>';
        }
        
        // Adiciona botão de comprovante para cartões bloqueados
        if($idsituacao == 2) {
            $sub_array["comprovante"] = '<button type="button" class="btn btn-sm btn-primary btn-comprovante" 
                                          data-codigo="'.$row['codigo'].'" 
                                          data-nome="'.$row['nome'].'" 
                                          data-cpf="'.$row['cpf'].'" 
                                          data-cartao="'.$row['cod_verificacao'].'" 
                                          data-empregador="'.$row['empregador'].'">
                                          <i class="fa fa-print"></i> PDF</button>';
        } else {
            $sub_array["comprovante"] = '';
        }

        // Adiciona ao array de dados sem conversão dupla
        $someArray["data"][] = $sub_array;
    }
    
    // Garante que sempre há uma estrutura válida
    echo json_encode($someArray);
    
} catch (Exception $e) {
    // Em caso de erro, retorna JSON vazio válido
    echo json_encode(array("data" => array()));
}