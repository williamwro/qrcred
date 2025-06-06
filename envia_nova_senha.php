<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recuperar senha</title>
    <link rel="stylesheet" href="Adm/bower_components/bootstrap/dist/css/bootstrap.min.css">
</head>
<body>
<?php


use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;	
use PHPMailer\PHPMailer\PHPMailer;

include "Adm/php/banco.php";
include 'PHPMailer-master/src/Exception.php';
include 'PHPMailer-master/src/PHPMailer.php';
include 'PHPMailer-master/src/SMTP.php';

$mail = new PHPMailer(true);

$email = $_POST['email'];
$usuario = $_POST['usuario'];
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

if($rs > 0){
    $result = $stmt->fetchAll();
    foreach ($result as $row) {
        $chave = sha1($row['codigo'].$row['senha']);
        $link = '<a href="https://qrcred.makecard.com.br/alterar_senha.php?chave='. $chave .'&user='.$usuario.'">https://qrcred.makecard.com.br/alterar_senha.php?chave=' . $chave . '&user='.$usuario.'</a>';
        $nome = $row['nome'];
    }

    try {
        //Server settings
        $mail->SMTPDebug = 3;                      // Enable verbose debug output
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';      // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                           // Enable SMTP authentication
        $mail->Username   = 'qrcredq@gmail.com'; // SMTP username
        $mail->Password   = 'ytce fkvg thme wgas';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; //\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = 587;  
         $mail->SMTPOptions = array(
            'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
            )
        );                                  // TCP port to connect to
 
        //Recipients
        $mail->setFrom('qrcredq@gmail.com', 'Admin QRCRED');
        $mail->addAddress($email, $nome);     // Add a recipient
        $mail->addReplyTo('no-reply@makecard.com.br', 'No reply');

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Recuperar a senha do sistema QRCRED';
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
                                                    <img src=\"https://qrcred.makecard.com.br/pictures_site-sind/logo.png\" alt=\"Recuperar Senha\" width=\"280\" height=\"90\" style=\"display: block;\" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td bgcolor=\"#ffffff\" style=\"padding: 40px 30px 40px 30px;\">
                                                    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                                        <tr>
                                                            <td style=\"color: #153643; font-family: Arial, sans-serif; font-size: 24px;\">
                                                                <b>E-mail para recuperar a senha.</b>
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
                                                                &reg; QRCRED 2023<br/>
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
        $msgdeenvio="<div class=\"container\">
                        <div id=\"loginbox\" style=\"margin-top:50px;\" class=\"mainbox col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2\">
                            <div class=\"panel panel-info\" >
                                <div class=\"panel-heading\">
                                    <div class=\"panel-title\">Atenção</div>
                                </div>
                                <div style=\"padding-top:30px\" class=\"panel-body\" >
                    
                                    <div style=\"display:none\" id=\"login-alert\" class=\"alert alert-danger col-sm-12\"></div>
                                    <p> Enviamos um E-mail para <b> $email </b> com um link que recriará sua senha.</p>
                                    <p> Abre o seu E-mail para redefinir. Caso o E-mail não apareca na caixa de entrada, verifique sua caixa de Spam.</p>
                                   
                                </div>
                            </div>
                        </div>
                    </div>";
        echo $msgdeenvio;

    } catch (Exception $e) {
        echo "Mensagem não pode ser enviada. Mailer Error: {$mail->ErrorInfo}";
    }
}else{
    $msgdeenvio="<div class=\"container\">
                        <div id=\"loginbox\" style=\"margin-top:50px;\" class=\"mainbox col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2\">
                            <div class=\"panel panel-info\" >
                                <div class=\"panel-heading\">
                                    <div class=\"panel-title\">Atenção</div>
                                </div>
                                <div style=\"padding-top:30px\" class=\"panel-body\" >
                    
                                    <div style=\"display:none\" id=\"login-alert\" class=\"alert alert-danger col-sm-12\"></div>
                                    <p> O E-mail : <b> $email </b> não está cadastrado para o seu login.</p>
                                    <p> Entre em contato com o administrador do sistema.</p>
                                   
                                </div>
                            </div>
                        </div>
                    </div>";
    echo $msgdeenvio;
}
?>
</body>
</html>