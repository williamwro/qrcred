<?PHP
require '../../php/banco.php';
include "../../php/funcoes.php";
$_C_user = isset($_POST['C_user']) ? $_POST['C_user'] : "";
$_C_email = isset($_POST['C_Email']) ? $_POST['C_Email'] : "";
$_C_divisao = isset($_POST['C_divisao']) ? (int)$_POST['C_divisao'] : 0;

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad = "nao repitido";
try{
        $select = $pdo ->query("SELECT codigo,username,password,senha,email,lastname,situacao,nome,divisao 
                                          FROM sind.usuarios
                                         WHERE username = '".$_C_user."'  AND divisao = ".$_C_divisao." OR 
                                                  email = '".$_C_email."' AND divisao = '".$_C_divisao."'");
        $select->execute();
        foreach ($select as $row) {
            $msg_grava_cad = "repitido";
        }
        echo $msg_grava_cad;
    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
