<?PHP
// Configurar cabeçalho para JSON
header('Content-Type: application/json; charset=utf-8');

include "../../php/banco.php";
include "../../php/funcoes.php";
// Suprimir warnings para não quebrar o JSON response
ini_set('display_errors', false);
error_reporting(E_ERROR | E_PARSE);
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_POST["id_assinatura"])){
    $std = new stdClass();
    $id_assinatura = $_POST["id_assinatura"];

    $query = "SELECT id, codigo, nome, celular, data_hora, autorizado, aceitou_termo, event, doc_token, doc_name, signed_at, name, email, cpf, has_signed, cel_informado, limite, valor_aprovado, data_pgto, chave_pix, reprovado
                FROM sind.associados_sasmais
                WHERE id = :id_assinatura";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id_assinatura', $id_assinatura, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $std->id               = $row["id"];
        $std->codigo           = $row["codigo"];
        $std->nome             = htmlspecialchars($row["nome"] ?? '');
        $std->celular          = $row["celular"];
        
        if($row["data_hora"] != null){
            $std->data_hora = date('d/m/Y H:i:s', strtotime($row["data_hora"]));
        }else{
            $std->data_hora = "";
        }
        
        $std->autorizado       = $row["autorizado"];
        $std->aceitou_termo    = $row["aceitou_termo"];
        $std->event            = $row["event"];
        $std->doc_token        = $row["doc_token"];
        $std->doc_name         = $row["doc_name"];
        $std->signed_at        = $row["signed_at"];
        $std->name             = $row["name"];
        $std->email            = $row["email"];
        $std->cpf              = $row["cpf"];
        $std->has_signed       = $row["has_signed"];
        $std->cel_informado    = $row["cel_informado"];
        // Formatação segura dos valores monetários
        $limite_value = !empty($row["limite"]) && is_numeric($row["limite"]) ? floatval($row["limite"]) : 0;
        $std->limite = number_format($limite_value, 2, ',', '.');
        
        $valor_aprovado_value = !empty($row["valor_aprovado"]) && is_numeric($row["valor_aprovado"]) ? floatval($row["valor_aprovado"]) : 0;
        $std->valor_aprovado = number_format($valor_aprovado_value, 2, ',', '.');
        
        // Para campo TIMESTAMP (data e hora)
        if($row["data_pgto"] != null){
            $std->data_pgto = date('Y-m-d\TH:i', strtotime($row["data_pgto"]));
        }else{
            $std->data_pgto = "";
        }
        
        $std->chave_pix        = $row["chave_pix"];
        $std->reprovado        = $row["reprovado"];
    }
    echo json_encode($std);
}
?> 