<?PHP
require '../../php/banco.php';
include "../../php/funcoes.php";
include '../../../PHPMailer-master/src/Exception.php';
require '../../../PHPMailer-master/src/PHPMailer.php';
require '../../../PHPMailer-master/src/SMTP.php';

$C_codigo = $_POST['C_codigo'];
if(isset($_POST['C_nome'])){
    if($_POST['C_nome'] != ''){
        $C_nome = $_POST['C_nome'];
    }else{
        $C_nome = '';
    }
}else{
    $C_nome = '';
}
if(isset($_POST['C_sobrenome'])){
    if($_POST['C_sobrenome'] != ''){
        $C_sobrenome = $_POST['C_sobrenome'];
    }else{
        $C_sobrenome = '';
    }
}else{
    $C_sobrenome = '';
}
$C_user        = isset($_POST['C_user']) ? $_POST['C_user'] : "";
if($_POST['C_senha']  != ""){
    $C_senha = sha1($_POST['C_senha']);
}else{
    $C_senha = "";
}
$C_situacao        = isset($_POST['C_situacao']) ? $_POST['C_situacao'] : 0;
$C_Email              = isset($_POST['C_Email']) ? $_POST['C_Email']: "";
$C_divisao           = isset($_POST['C_divisao']) ? $_POST['C_divisao'] : 0;

$stmt = new stdClass();
$stmt_menu = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$msg_grava_cad="";

if(isset($_POST["operation"])) {
    if($_POST["operation"] == "Update") {
        if($C_senha != "") {
            $sql = "UPDATE sind.usuarios SET ";
            $sql .= "username = :username, ";
            $sql .= "senha = :senha, ";
            $sql .= "email = :email, ";
            $sql .= "lastname = :lastname, ";
            $sql .= "situacao = :situacao, ";
            $sql .= "nome = :nome, ";
            $sql .= "divisao = :divisao ";
            $sql .= "WHERE codigo = :codigo";
        }else{
            $sql = "UPDATE sind.usuarios SET ";
            $sql .= "username = :username, ";
            $sql .= "email = :email, ";
            $sql .= "lastname = :lastname, ";
            $sql .= "situacao = :situacao, ";
            $sql .= "nome = :nome, ";
            $sql .= "divisao = :divisao ";
            $sql .= "WHERE codigo = :codigo";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':codigo', $C_codigo, PDO::PARAM_INT);
        $stmt->bindParam(':username', $C_user, PDO::PARAM_STR);
        if($C_senha != ""){
            $stmt->bindParam(':senha', $C_senha, PDO::PARAM_STR);
        }
        $stmt->bindParam(':email', $C_Email, PDO::PARAM_STR);
        $stmt->bindParam(':lastname', $C_sobrenome, PDO::PARAM_STR);
        $stmt->bindParam(':situacao', $C_situacao, PDO::PARAM_INT);
        $stmt->bindParam(':nome', $C_nome, PDO::PARAM_STR);
        $stmt->bindParam(':divisao', $C_divisao, PDO::PARAM_INT);
        $stmt->execute();
        $msg_grava_cad = "atualizado";
        $arr = array('codigo' =>$C_codigo,'resultado'=>$msg_grava_cad);
    }elseif($_POST["operation"] == "Add") {

        $sql = "INSERT INTO sind.usuarios( ";
        $sql .= "username,senha,email,lastname,situacao,nome,divisao) ";
        $sql .= " VALUES(";
        $sql .= ":username, ";
        $sql .= ":senha, ";
        $sql .= ":email, ";
        $sql .= ":lastname, ";
        $sql .= ":situacao, ";
        $sql .= ":nome, ";
        $sql .= ":divisao) RETURNING lastval()";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $C_user, PDO::PARAM_STR);
        $stmt->bindParam(':senha', $C_senha, PDO::PARAM_STR);
        $stmt->bindParam(':email', $C_Email, PDO::PARAM_STR);
        $stmt->bindParam(':lastname', $C_sobrenome, PDO::PARAM_STR);
        $stmt->bindParam(':situacao', $C_situacao, PDO::PARAM_INT);
        $stmt->bindParam(':nome', $C_nome, PDO::PARAM_STR);
        $stmt->bindParam(':divisao', $C_divisao, PDO::PARAM_INT);
        $stmt->execute();

        $ultimo_codigo =  $stmt->fetchColumn();
        $msg_grava_cad = "cadastrado";


        //inserir os codigos dos menus para o usuario INICIO
        $sql_menu = "SELECT * FROM sind.dynamic_menu ORDER BY id";
        $statment_menu = $pdo->prepare($sql_menu);
        $statment_menu->execute();
        $result = $statment_menu->fetchAll();
        foreach ($result as $row) {
            $id = $row["id"];
            $codigo_usuario = $ultimo_codigo;
            $title = $row["title"];
            if ($title === "Administrador") {
                $status = 0;
            } else {
                $status = 1;
            }
            $sql2 = "INSERT INTO sind.usuarios_menu( ";
            $sql2 .= "codigo_usuario,id_menu,status) ";
            $sql2 .= "VALUES(";
            $sql2 .= ":codigo_usuario, ";
            $sql2 .= ":id_menu, ";
            $sql2 .= ":status)";

            $stmt_menu = $pdo->prepare($sql2);
            $stmt_menu->bindParam(':codigo_usuario', $codigo_usuario, PDO::PARAM_INT);
            $stmt_menu->bindParam(':id_menu', $id, PDO::PARAM_INT);
            $stmt_menu->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt_menu->execute();
        }
        //inserir os codigos dos menus para o usuario FIM
        $resultado_email = getFuncao_enviar_email($C_Email);

        $arr = array('codigo' =>$ultimo_codigo,'resultado'=>$msg_grava_cad,'result_email'=>$resultado_email);
    }
    try {


        $someArray = array_map("utf8_encode",$arr);
        echo json_encode($someArray);

    } catch (PDOException $erro) {
        echo "Não foi possivel inserir os dados no banco: " . $erro->getMessage();
    }
}
function getFuncao_enviar_email($email)
{
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    $nome = "";
    $link = "";
    $email = preg_replace('/[^[:alnum:]_.-@]/','',$email);

    $stmt = new stdClass();
    $pdo = Banco::conectar_postgres();
    $sql = "SELECT codigo,senha,email,nome FROM sind.usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    $rs = $stmt->rowCount();

    if($rs > 1){
        $result = $stmt->fetchAll();
        foreach ($result as $row) {
            $chave = sha1($row['codigo'].$row['senha']);
            $link = '<a href="https://sind.makecard.com.br/alterar_senha.php?chave=' . $chave . '">http://127.0.0.1/sind/alterar_senha.php?chave=' . $chave . '</a>';
            $nome = $row['nome'];
        }
        try {
            //Server settings
            //$mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;                      // Enable verbose debug output
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = 'zeus.iphosting.com.br';                    // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = 'suporte@makecard.com.br';                     // SMTP username
            $mail->Password   = 'Kb109733*123';                               // SMTP password
            $mail->SMTPSecure = 'ssl'; //\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
            $mail->Port       = 465;                                    // TCP port to connect to

            //Recipients
            $mail->setFrom('william@makecard.com.br', 'MAKECARD');
            $mail->addAddress($email, $nome);     // Add a recipient
            $mail->addReplyTo('no-reply@makecard.com.br', 'No reply');

            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = 'Recuperar senha do sistema MAKECARD';

            $mail->Body    = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
                            <html xmlns=\"http://www.w3.org/1999/xhtml\">
                            <head>
                                <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
                                <title>Demystifying Email Design</title>
                                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"/>
                            </head>
                            <body style=\"margin: 0; padding: 0;\">
                            <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                <tr>
                                    <td style=\"padding: 10px 0 30px 0;\">
                                        <table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"600\" style=\"border: 1px solid #cccccc; border-collapse: collapse;\">
                                            <tr>
                                                <td align=\"center\" bgcolor=\"#f5f5dc\" style=\"padding: 40px 0 20px 0; color: #153643; font-size: 28px; font-weight: bold; font-family: Arial, sans-serif;\">
                                                    <img src=\"https://sind.makecard.com.br/makecard.png\" alt=\"Creating Email Magic\" width=\"280\" height=\"90\" style=\"display: block;\" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td bgcolor=\"#ffffff\" style=\"padding: 40px 30px 40px 30px;\">
                                                    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                                        <tr>
                                                            <td style=\"color: #153643; font-family: Arial, sans-serif; font-size: 24px;\">
                                                                <b>E-mail para criar senha.</b>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style=\"padding: 20px 0 30px 0; color: #153643; font-family: Arial, sans-serif; font-size: 16px; line-height: 20px;\">
                                                                <p class='mb-0'>Ola <b>$nome</b>, click no link abaixo para redefinir sua senha<br/><br/></p>
                                                                <p style=\"font-size: 12px;\">$link</p><br/>
                                                                <p class='mb-0'>Att,</p>
                                                                <p class='mb-0'>Administrador</p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td bgcolor=\"#f5f5dc\" style=\"padding: 30px 30px 30px 30px;\">
                                                    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                                        <tr>
                                                            <td style=\"color: #000000; font-family: Arial, sans-serif; font-size: 14px;\" width=\"75%\">
                                                                &reg; MAKECARD 2021<br/>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            </body>
                            </html>";
            $mail->send();
            return 'Email enviado';
        } catch (Exception $e) {
            return "Mensagem não pode ser enviada. Mailer Error: {$mail->ErrorInfo}";
        }
    }else{
        return "Erro";
    }
}