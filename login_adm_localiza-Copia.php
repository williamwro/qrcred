<?PHP
$userconv="";
$passconv="";
include "Adm/php/banco.php";
if (isset($_POST['login-username']) && isset($_POST['password'])){
    $username = $_POST['login-username'];
    $passuser = $_POST['password'];
    $cod_convenio = 0;
    $codigo = 0;
    $existe_senha = false;
    $std = new stdClass();
    $pdo = Banco::conectar_postgres();
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
        $sql_conv_senha = $pdo->query("SELECT usuarios.codigo, usuarios.username, usuarios.password, usuarios.senha, usuarios.email, usuarios.lastname, usuarios.situacao, usuarios.nome, usuarios.divisao, divisao.nome AS divisao_nome
        FROM sind.divisao RIGHT JOIN sind.usuarios ON divisao.id_divisao = usuarios.divisao WHERE usuarios.username='" . $username . "' AND usuarios.senha='" . $senha_crypto . "'");
        while ($row = $sql_conv_senha->fetch()) {
            $codigo = $row["codigo"];
            $std->tipo_login = "login sucesso";
            $std->codigo = $codigo;
            $std->Username = $row["username"];
            $std->senha = $passuser;
            $std->nome = $row["nome"];
            $std->divisao = $row["divisao"];
            $std->divisao_nome = $row["divisao_nome"];
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