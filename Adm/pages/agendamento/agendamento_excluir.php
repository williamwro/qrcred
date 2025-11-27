<?PHP
error_reporting(E_ALL ^ E_NOTICE);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

require "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$result = new stdClass();

if(isset($_POST["id_agendamento"])){
    $id_agendamento = $_POST["id_agendamento"];
    
    error_log("DEBUG AGENDAMENTO EXCLUIR - Tentando excluir ID: " . $id_agendamento);
    
    try {
        $sql = "DELETE FROM sind.agendamento WHERE id = :id_agendamento";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_agendamento', $id_agendamento, PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $result->Resultado = "excluido";
            $result->Mensagem = "Agendamento excluído com sucesso!";
            error_log("DEBUG AGENDAMENTO EXCLUIR - Excluído com sucesso ID: " . $id_agendamento);
        } else {
            $result->Resultado = "nao_encontrado";
            $result->Mensagem = "Agendamento não encontrado.";
            error_log("DEBUG AGENDAMENTO EXCLUIR - Não encontrado ID: " . $id_agendamento);
        }
        
    } catch (PDOException $erro) {
        error_log("DEBUG AGENDAMENTO EXCLUIR - Erro PDO: " . $erro->getMessage());
        
        if($erro->getCode() === '42501'){
            $result->Resultado = "sem_permissao";
            $result->Mensagem = "Sem permissão para excluir este agendamento.";
        } else {
            $result->Resultado = "erro_banco";
            $result->Mensagem = "Erro no banco de dados: " . $erro->getMessage();
        }
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    $result->Resultado = "erro";
    $result->Mensagem = "ID do agendamento não foi fornecido.";
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
?> 