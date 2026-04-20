<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$result = [
    'vapid_config_exists' => file_exists('vapid_config.php'),
    'banco_exists' => file_exists('Adm/php/banco.php'),
    'current_dir' => __DIR__,
    'files_in_dir' => scandir(__DIR__)
];

// Tentar incluir vapid_config
try {
    if (file_exists('vapid_config.php')) {
        require_once 'vapid_config.php';
        $result['vapid_loaded'] = true;
        $result['vapid_public_defined'] = defined('VAPID_PUBLIC_KEY');
        $result['vapid_private_defined'] = defined('VAPID_PRIVATE_KEY');
        $result['vapid_subject_defined'] = defined('VAPID_SUBJECT');
    }
} catch (Exception $e) {
    $result['vapid_error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>