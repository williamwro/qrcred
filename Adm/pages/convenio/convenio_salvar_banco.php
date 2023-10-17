<?PHP
require '../../php/banco.php';
include "../../php/funcoes.php";

if(isset($_POST['C_Codigo_Convenio'])){
    $_codigo     = (int)$_POST['C_Codigo_Convenio'];
    $_banco      = $_POST['C_Banco'];
    if($_POST['C_Conta'] != ""){
        $_conta = $_POST['C_Conta'];
    }else{
        $_conta = null;
    }
    if($_POST['C_Agencia'] != ""){
        $_agencia = $_POST['C_Agencia'];
    }else{
        $_agencia = null;
    }
    if($_POST['C_Tipo_Conta'] != "0"){
        $_tipo_conta = $_POST['C_Tipo_Conta'];
    }else{
        $_tipo_conta = null;
    }
    if($_POST['C_Tipo_Pix'] != "0"){
        $_tipo_pix = $_POST['C_Tipo_Pix'];
    }else{
        $_tipo_pix = null;
    }
    if($_POST['C_Chave_Pix'] != ""){
        $_chave_pix = $_POST['C_Chave_Pix'];
    }else{
        $_chave_pix = null;
    }
    
    $stmt = new stdClass();
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $msg_grava_cad="";
    $_existe_conta="nao";
    $sql = "SELECT * FROM sind.banco_convenio WHERE cod_convenio = :codigo_convenio";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':codigo_convenio', $_codigo, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll();
    foreach ($result as $row){
        $_codigo = $row['cod_convenio'];
        $_existe_conta="sim";
    }
    try {

        if( $_existe_conta=="nao" ) {

            $sql = "INSERT INTO sind.banco_convenio(cod_convenio, cod_banco, agencia, conta, cod_tipo, id_chave_pix, chave_pix) ";
            $sql .= "VALUES(:codigo_convenio, :cod_banco, :agencia, :conta, :cod_tipo, :id_chave_pix, :chave_pix)";
            $msg_grava_cad="cadastrado";
           
            $_codigo = (int)$_codigo;
            $_tipo_conta = (int)$_tipo_conta;
            $_tipo_pix = (int)$_tipo_pix;

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':codigo_convenio', $_codigo, PDO::PARAM_INT);
            $stmt->bindParam(':cod_banco', $_banco, PDO::PARAM_STR);
            $stmt->bindParam(':agencia', $_agencia, PDO::PARAM_STR);
            $stmt->bindParam(':conta', $_conta, PDO::PARAM_STR);
            $stmt->bindParam(':cod_tipo', $_tipo_conta, PDO::PARAM_INT);
            $stmt->bindParam(':id_chave_pix', $_tipo_pix, PDO::PARAM_INT);
            $stmt->bindParam(':chave_pix', $_chave_pix, PDO::PARAM_STR);

            $stmt->execute();

            echo $msg_grava_cad;



        } else {
 
            $sql = "UPDATE sind.banco_convenio SET ";
            $sql .= "cod_convenio = :codigo_convenio, ";
            $sql .= "cod_banco = :cod_banco, ";
            $sql .= "agencia = :agencia, ";
            $sql .= "conta = :conta, ";
            $sql .= "cod_tipo = :cod_tipo, ";
            $sql .= "id_chave_pix = :id_chave_pix, ";
            $sql .= "chave_pix = :chave_pix ";
            $sql .= "WHERE cod_convenio = :codigo_convenio";
            $msg_grava_cad="atualizado";

            $_codigo = (int)$_codigo;
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':codigo_convenio', $_codigo, PDO::PARAM_INT);
            $stmt->bindParam(':cod_banco', $_banco, PDO::PARAM_STR);
            $stmt->bindParam(':agencia', $_agencia, PDO::PARAM_STR);
            $stmt->bindParam(':conta', $_conta, PDO::PARAM_STR);
            $stmt->bindParam(':cod_tipo', $_tipo_conta, PDO::PARAM_INT);
            $stmt->bindParam(':id_chave_pix', $_tipo_pix, PDO::PARAM_INT);
            $stmt->bindParam(':chave_pix', $_chave_pix, PDO::PARAM_STR);

            $stmt->execute();

            echo $msg_grava_cad;

        }


    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();

    }

}