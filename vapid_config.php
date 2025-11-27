<?php
/**
 * Configuração das Chaves VAPID
 * Arquivo dedicado para definir as constantes VAPID
 */

// Definir chaves VAPID
define('VAPID_PUBLIC_KEY', 'BMpJvAe-NVu8XEeReHPqFS-yeY-yo9rYTnnTt2Nok4Au_2PuBtqh-qbUwv0F-YMSnOJYlQGg1rUJZtJH_B2bcFo');
define('VAPID_PRIVATE_KEY', '-77Bk1wmMJHpJ__DGpuaf02y4BBQ5l0CPVO9cK8WpTI');
define('VAPID_SUBJECT', 'mailto:admin@sas.makecard.com.br');

// Verificar se as constantes foram definidas
if (defined('VAPID_PUBLIC_KEY') && defined('VAPID_PRIVATE_KEY')) {
    // Constantes definidas com sucesso
    $vapid_status = 'OK';
} else {
    // Erro na definição
    $vapid_status = 'ERROR';
} 