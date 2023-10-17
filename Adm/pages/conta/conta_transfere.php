<?PHP
include "../../php/banco.php";
include "../../php/funcoes.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
function replace_unicode_escape_sequence($match) {
    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}
function unicode_decode($str) {
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $str);
}
$Matricula_destino = '';
$resultado  = json_decode($_POST["data"]);
$stmt = new stdClass();
if(isset($_POST["data"])) {
    foreach ($resultado as $row){
        $Registro           = $row->REGISTRO;
        $Matricula_origem  = $row->MATRICULA_ORIGEM;
        $Empregador_origem = (int)$row->EMPREGADOR_ORIGEM;
        $Matricula_destino  = $row->MATRICULA_DESTINO;
        $Empregador_destino = (int)$row->EMPREGADOR_DESTINO;
        $Tipo = "T";

        $sql = "UPDATE CONTA SET ";
        $sql .= "Associado  = :Associado, ";
        $sql .= "Empregador = :Empregador, ";
        $sql .= "Tipo = :Tipo ";
        $sql .= "WHERE Lancamento = " . $Registro;
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':Associado', $Matricula_destino, PDO::PARAM_STR);
        $stmt->bindParam(':Empregador', $Empregador_destino, PDO::PARAM_INT);
        $stmt->bindParam(':Tipo', $Tipo, PDO::PARAM_STR);

        $stmt->execute();
    }
    echo json_encode("{'sucesso':'true'}");
}


