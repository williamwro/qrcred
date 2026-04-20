<?php
/**
 * Configuração das Chaves VAPID
 * Arquivo dedicado para definir as constantes VAPID
 */

// Definir chaves VAPID
define('VAPID_PUBLIC_KEY', 'BM7z6QhdLZUACWiMZvwVb6JL2Qtvr2zFOOFqqi5E5yhFeZWj2k1YewWgAxXidqbGmcznD5LcfRComGe8h6TOAHM');
define('VAPID_PRIVATE_KEY', 'MSA8Clt7h_bbUhLq9Sbh6zPjXCzwZvecNHCqexeJPu8');
define('VAPID_SUBJECT', 'mailto:admin@sas.makecard.com.br');

// Verificar se as constantes foram definidas
if (defined('VAPID_PUBLIC_KEY') && defined('VAPID_PRIVATE_KEY')) {
    // Constantes definidas com sucesso
    $vapid_status = 'OK';
} else {
    // Erro na definição
    $vapid_status = 'ERROR';
} 