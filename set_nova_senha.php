<?PHP
include "Adm/php/banco.php";
$email             = $_POST['email'];
$novasenha         = $_POST['novasenha'];
$confirmanovasenha = $_POST['confirmanovasenha'];
$chave             = $_POST['chave'];
$usuario           = $_POST['user'];
$email             = preg_replace('/[^[:alnum:]_.-@]/','',$email);
$chave             = preg_replace('/[^[:alnum:]]/','',$chave);
$novasenha         = addslashes($novasenha);

$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = "SELECT * FROM sind.usuarios where email = :email";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();

$rs = $stmt->rowCount();

if($rs){
    if($novasenha == $confirmanovasenha) {
        $result = $stmt->fetchAll();
        foreach ($result as $row) {
            $chavecorreta = sha1($row['codigo'].$row['senha']);
            if ($chave == $chavecorreta) {
                $senhasha1 = sha1($novasenha);
                $sql = "UPDATE sind.usuarios SET senha = :senha, password = :password WHERE codigo = :codigo";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':codigo', $row['codigo'], PDO::PARAM_INT);
                $stmt->bindParam(':senha', $senhasha1, PDO::PARAM_STR);
                $stmt->bindParam(':password', $confirmanovasenha, PDO::PARAM_STR);
                $stmt->execute();


                header("Location: https://sind.makecard.com.br/login_adm.html");
            } else {
                echo 'Erro: Usuario não encontrado';
            }
        }
    }else{
        echo 'Confirma nova senha não está igual a nova senha!';
    }
}else{
    echo 'E-mail incorreto!';
}
