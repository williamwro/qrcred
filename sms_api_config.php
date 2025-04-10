<?php
/**
 * Configurações da API de SMS
 * Este arquivo deve ser colocado no mesmo diretório do script envia_sms_direto.php
 */
return [
    // Chave da API do serviço de SMS
    'api_key' => 'sua_chave_api_aqui',
    
    // Token para autenticação interna
    'token' => 'chave_segura_123',
    
    // ID do remetente que aparecerá no SMS
    'sender_id' => 'QRCred',
    
    // Endpoints das APIs
    'api_endpoint_sms' => 'https://api.smsempresa.com.br/send',
    'api_endpoint_whatsapp' => 'https://api.whatsapp.business.api/send',
    
    // Modo de teste (true para simular envio, false para realmente enviar)
    'teste_mode' => false,
    
    // Número para testes (usado apenas se teste_mode = true)
    'teste_numero' => '5511999999999'
]; 