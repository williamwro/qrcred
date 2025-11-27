<?PHP
// Backup do arquivo original - versão simplificada para teste
header('Content-Type: application/json; charset=utf-8');

// Resposta JSON mínima válida para DataTables
$response = array(
    'data' => array(),
    'recordsTotal' => 0,
    'recordsFiltered' => 0
);

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
