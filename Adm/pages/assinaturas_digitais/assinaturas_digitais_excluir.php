<?PHP
error_reporting(E_ALL ^ E_NOTICE);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

require "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$result = new stdClass();

if(isset($_POST["id_assinatura"])){
    $id_assinatura = $_POST["id_assinatura"];
    
    try {
        $sql = "DELETE FROM sind.associados_sasmais WHERE id = :id_assinatura";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_assinatura', $id_assinatura, PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $result->Resultado = "excluido";
        } else {
            $result->Resultado = "nao_encontrado";
        }
        
    } catch (PDOException $erro) {
        if($erro->getCode() === '42501'){
            $result->Resultado = "sem_permissao";
        } else {
            $result->Resultado = "erro_banco";
            $result->Mensagem = $erro->getMessage();
        }
    }
    
    echo json_encode($result);
}
?> 