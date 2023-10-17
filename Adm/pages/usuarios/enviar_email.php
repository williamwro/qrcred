<?php
include "Adm/php/banco.php";
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

$mail = new \PHPMailer\PHPMailer\PHPMailer(true);

$email = $_GET['email'];
$nome = "";
$link = "";
$email = preg_replace('/[^[:alnum:]_.-@]/','',$email);

$stmt = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = "SELECT codigo,senha,email,nome FROM sind.usuarios WHERE email = :email";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();

$rs = $stmt->rowCount();

if($rs == 1){
    $result = $stmt->fetchAll();
    foreach ($result as $row) {
        $chave = sha1($row['codigo'].$row['senha']);
        $link = '<a href="http://makecard.redirectme.net/sind/alterar_senha.php?chave=' . $chave . '">http://makecard.redirectme.net/sind/alterar_senha.php?chave=' . $chave . '</a>';
        $nome = $row['nome'];
    }
}else{
    echo '<h1>Erro: Login ou senha incorretos<h1>';
}
try {
    //Server settings
    //$mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                    // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'william@makecard.com.br';                     // SMTP username
    $mail->Password   = 'Abc25149076';                               // SMTP password
    $mail->SMTPSecure = 'ssl'; //\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
    $mail->Port       = 465;                                    // TCP port to connect to

    //Recipients
    $mail->setFrom('william@makecard.com.br', 'Administrador');
    $mail->addAddress($email, $nome);     // Add a recipient
    $mail->addReplyTo('no-reply@makecard.com.br', 'No reply');

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Recuperar a senha do sistema MAKECARD';
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
                                                <img src=\"127.0.0.1/sind/makecard.gif\" alt=\"Creating Email Magic\" width=\"280\" height=\"90\" style=\"display: block;\" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td bgcolor=\"#ffffff\" style=\"padding: 40px 30px 40px 30px;\">
                                                <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                                    <tr>
                                                        <td style=\"color: #153643; font-family: Arial, sans-serif; font-size: 24px;\">
                                                            <b>E-mail para recuperação de senha.</b>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style=\"padding: 20px 0 30px 0; color: #153643; font-family: Arial, sans-serif; font-size: 16px; line-height: 20px;\">
                                                            <p class='mb-0'>Olá <b>$nome</b>, click no link abaixo para redefinir sua senha<br/><br/></p>
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
    $mail->AltBody = "teste";

    $mail->send();
} catch (Exception $e) {
    echo "Mensagem não pode ser enviada. Mailer Error: {$mail->ErrorInfo}";
}