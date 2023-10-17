<?PHP
require '../../php/banco.php';
include "../../php/funcoes.php";

if(isset($_POST['codigo_convenio'])){
    $_codigo = (int)$_POST['codigo_convenio'];
    if($_POST['C_Usuario'] != ""){
        $_usuario = md5($_POST['C_Usuario']);
        $_usuario_texto = $_POST['C_Usuario'];
    }else{
        $_usuario = "";
        $_usuario_texto = "";
    }
    $_senha = md5($_POST['C_Senha']);
    $_senhaconfirma = md5($_POST['C_Confirma_Senha']);
    $_existe_senha = $_POST['existe_senha'];
    $stmt = new stdClass();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $msg_grava_cad="";

    $sql = "SELECT * FROM sind.c_senhaconvenio WHERE cod_convenio = :codigo_convenio";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':codigo_convenio', $_codigo, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll();
    foreach ($result as $row){
        if($_usuario == ""){
            $_usuario = $row['usuario'];
        }
    }
    try {

        if($_existe_senha=="nao"){

            $sql = "INSERT INTO sind.c_senhaconvenio(cod_convenio, usuario, senha, usuario_texto) ";
            $sql .= "VALUES(:codigo_convenio, :usuario, :senha, :usuario_texto)";
            $msg_grava_cad="cadastrado";
            if($_usuario == ""){

                echo "solicita_usuario";

            }else{
                $_codigo = (int)$_codigo;
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':codigo_convenio', $_codigo, PDO::PARAM_INT);
                $stmt->bindParam(':usuario', $_usuario, PDO::PARAM_STR);
                $stmt->bindParam(':senha', $_senha, PDO::PARAM_STR);
                $stmt->bindParam(':usuario_texto', $_usuario_texto, PDO::PARAM_STR);

                $stmt->execute();

                echo $msg_grava_cad;
            }



        }else{

            $sql = "UPDATE sind.c_senhaconvenio SET ";
            $sql .= "usuario = :usuario, ";
            $sql .= "senha = :senha, ";
            $sql .= "usuario_texto = :usuario_texto ";
            $sql .= "WHERE cod_convenio = :codigo_convenio";
            $msg_grava_cad="atualizado";

            $_codigo = (int)$_codigo;
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':codigo_convenio', $_codigo, PDO::PARAM_INT);
            $stmt->bindParam(':usuario', $_usuario, PDO::PARAM_STR);
            $stmt->bindParam(':senha', $_senha, PDO::PARAM_STR);
            $stmt->bindParam(':usuario_texto', $_usuario_texto, PDO::PARAM_STR);

            $stmt->execute();

            echo $msg_grava_cad;

        }


    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();

    }

}