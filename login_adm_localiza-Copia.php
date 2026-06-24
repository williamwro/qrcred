<?PHP
session_start();
$userconv="";
$passconv="";
include "Adm/php/banco.php";
if (isset($_POST['login-username']) && isset($_POST['password'])){
    $username = trim($_POST['login-username']); // Remove espaços em branco do início e fim do usuário
    $passuser = $_POST['password'];
    $cod_convenio = 0;
    $codigo = 0;
    $existe_senha = false;
    $std = new stdClass();
    $pdo = Banco::conectar_postgres();
    // VERIFICA SENHA **************************************************************************************************************************************************
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
    $stmt_senha = $pdo->prepare("SELECT * FROM sind.usuarios WHERE senha = :senha AND username = :username");
    $stmt_senha->bindParam(':senha', $senha_crypto, PDO::PARAM_STR);
    $stmt_senha->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt_senha->execute();
    while($row = $stmt_senha->fetch()) {
        $existe_senha = true;
    }
    if($existe_senha) {
        $stmt_conv_senha = $pdo->prepare("SELECT usuarios.codigo, usuarios.username, usuarios.password, usuarios.senha, usuarios.email, usuarios.lastname, usuarios.situacao, usuarios.nome, usuarios.divisao, divisao.nome AS divisao_nome
        FROM sind.divisao RIGHT JOIN sind.usuarios ON divisao.id_divisao = usuarios.divisao WHERE usuarios.username = :username AND usuarios.senha = :senha");
        $stmt_conv_senha->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt_conv_senha->bindParam(':senha', $senha_crypto, PDO::PARAM_STR);
        $stmt_conv_senha->execute();
        while ($row = $stmt_conv_senha->fetch()) {
            $codigo = $row["codigo"];
            $std->tipo_login = "login sucesso";
            $std->codigo = $codigo;
            $std->Username = $row["username"];
            $std->senha = $passuser;
            $std->nome = $row["nome"];
            $std->divisao = $row["divisao"];
            $std->divisao_nome = $row["divisao_nome"];
            
            // SEGURANÇA MULTI-TENANT: Armazenar divisão em $_SESSION
            $_SESSION['usuario_cod'] = $codigo;
            $_SESSION['divisao'] = $row["divisao"];
            $_SESSION['user_name'] = $username;
            
            if($row["situacao"] == 2){
                $std->tipo_login = "login bloqueado";
            }
        }
        if ($codigo == 0) {
            $codigo = 0;
            $std->tipo_login = "login inativo";
            $std->codigo = $codigo;
            $std->Username = "";
            $std->nome = "";
            $std->divisao = 0;
            $std->divisao_nome = "";
        }
    }else{
        $codigo           = 0;
        $std->tipo_login  = "login incorreto";
        $std->codigo      = $codigo;
        $std->Username    = "";
        $std->divisao     = 0;
        $std->divisao_nome = "";
    }
}else{
    $codigo           = 0;
    $std->tipo_login  = "login vazio";
    $std->codigo      = $codigo;
    $std->Username    = "";
    $std->divisao     = 0;
    $std->divisao_nome = "";

}
echo json_encode($std);