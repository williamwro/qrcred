<?php
// smtp_config.php - Armazena as configurações de SMTP
return [
    'host' => 'smtp.gmail.com',          // Servidor SMTP do Gmail
    'port' => 465,                       // Porta 465 para SSL
    'username' => 'qrcredq@gmail.com',   // Usuário SMTP (email)
    'password' => 'vsmn dlbl acsz zukc', // Senha de app do Gmail
    'from_email' => 'qrcredq@gmail.com', // Email de origem
    'from_name' => 'QRCred - Recuperação de Senha', // Nome de exibição
    'secure' => 'ssl',                   // Usar SSL em vez de TLS
    'smtp_auth' => true,                 // Autenticação SMTP habilitada
    'smtp_ssl_enable' => true            // SSL habilitado
];
?> 