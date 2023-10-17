<?PHP
header("Content-type: application/json");
include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set('America/Sao_Paulo');
if ( isset($_POST['pass']) ) {
	
    $std              = new stdClass();
	$senha_aux        = 0;
    $matricula        = $_POST['matricula'];
    $empregador       = $_POST['empregador'];
    $senha            = $_POST['pass'];
    $codassoc         = "";
    try {
        $sql_pede_senha = $pdo->query("SELECT * FROM sind.c_senhaassociado WHERE cod_associado = '" . $matricula . "' AND id_empregador = ". $empregador ." AND senha = '" . $senha . "'");
        while ($row_senha = $sql_pede_senha->fetch()) {
            $senha_aux = 1;
        }
        if ($senha_aux == 0) {
                $std->situacao     = 'errado';
        }else{
                $std->situacao     = 'certo';
        }
    } catch (PDOException $erro) {
        $std->situacao = $erro->getMessage(); /*2- erro*/
    }
     echo json_encode($std);
}



