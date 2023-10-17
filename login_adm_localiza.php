<?PHP
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
session_start();
$userconv="";
$passconv="";
include "Adm/php/banco.php";
$username = $_POST['username'];
$passuser = $_POST['password'];
$_SESSION["user_name"]=$username;
$cod_convenio = 0;
$codigo = 0;
$existe_senha = false;
$std = new stdClass();
$stmt = new stdClass();
$stmt2 = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$data2 = new DateTime();
$data = $data2->format('Y-m-d H:i:s');
if (isset($_POST['username']) && isset($_POST['password'])){
    // VERIFICA SENHA ******************************************************************************************************************************************************
    $stmt = $pdo->prepare("SELECT codigo,senha,email FROM sind.usuarios WHERE username = :username");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll();
    //$rs = $stmt->rowCount();
    foreach ($result as $row) {
        $codigo_usuario = $row["codigo"];
    }

    $senha_crypto = sha1($passuser);
    $sql_senha = $pdo->query("SELECT * FROM sind.usuarios WHERE senha='".$senha_crypto."' AND username = '".$username."'");
    while($row = $sql_senha->fetch()) {
        $existe_senha = true;
    }
    if($existe_senha) {
        $sql_conv_senha = $pdo->query("SELECT usuarios.codigo, usuarios.username, usuarios.password, 
                                                usuarios.senha, usuarios.email, usuarios.lastname, 
                                                usuarios.situacao, usuarios.nome, usuarios.divisao, 
                                                divisao.nome AS divisao_nome, divisao.descricao
        FROM sind.divisao RIGHT JOIN sind.usuarios ON divisao.id_divisao = usuarios.divisao WHERE usuarios.username='" . $username . "' AND usuarios.senha='" . $senha_crypto . "'");
        while ($row = $sql_conv_senha->fetch()) {
            $codigo = $row["codigo"];
            $std->tipo_login = "login sucesso";
            $std->codigo = $codigo;
            $std->Username = $row["username"];
            $std->senha = $passuser;
            $std->nome = $row["nome"];
            $std->divisao = $row["divisao"];
            $std->descricao = $row["descricao"];
            $std->divisao_nome = $row["divisao_nome"];
            if($row["situacao"] == 2){
                $std->tipo_login = "login bloqueado";
                session_unset();
                session_destroy();
            }
        }

        $std->card1 = "123139";
        $std->nomecard1 = "MARCIO HENRIQUE DE SOUZA";
        $std->card2 = "173577";
        $std->nomecard2 = "MARCIA HELENA MORAES";
        $std->card3 = "800030";
        $std->nomecard3 = "WILLIAM R OLIVEIRA";
        $std->card4 = "145630";
        $std->nomecard4 = "CLAUDIO BORGES DO ESPIRITO SANTO";
        $std->card5 = "163816";
        $std->nomecard5 = "VITOR LUCIO DA SILVA";
        $std->card6 = "195847";
        $std->nomecard6 = "ANA PAULA ALVES";
        
        if ($codigo == 0) {
            $codigo = 0;
            $std->tipo_login = "login inativo";
            $std->codigo = $codigo;
            $std->Username = "";
            $std->nome = "";
            $std->divisao = 0;
            $std->divisao_nome = "";
            session_unset();
            session_destroy();
        }
    }else{
        $codigo           = 0;
        $std->tipo_login  = "login incorreto";
        $std->codigo      = $codigo;
        $std->Username    = "";
        $std->divisao     = 0;
        $std->divisao_nome = "";
        session_unset();
        session_destroy();
    }
}else{
    $codigo           = 0;
    $std->tipo_login  = "login vazio";
    $std->codigo      = $codigo;
    $std->Username    = "";
    $std->divisao     = 0;
    $std->divisao_nome = "";
    session_unset();
    session_destroy();
}

$stmt->execute();
$resultado = $std->tipo_login;
$sql2 = "INSERT INTO sind.usuarios_log(";
$sql2 .= "cod_usuario,data,resultado) ";
$sql2 .= "VALUES(:cod_usuario,:data,:resultado)";
$stmt2 = $pdo->prepare($sql2);
$stmt2->bindParam(':cod_usuario', $codigo_usuario, PDO::PARAM_INT);
$stmt2->bindParam(':data', $data, PDO::PARAM_STR);
$stmt2->bindParam(':resultado',  $resultado, PDO::PARAM_STR);
$stmt2->execute();

echo json_encode($std);