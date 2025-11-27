<?php
header('Content-Type: application/json');

// Função para responder com JSON e encerrar
function responder($status, $mensagem) {
    echo json_encode([
        'status' => $status,
        'mensagem' => $mensagem
    ]);
    exit;
}

// Receber parâmetros via GET ou POST
$cpf = $_REQUEST['cpf'] ?? null;
$token = $_REQUEST['token'] ?? null;

// Validação simples
if (!$cpf || !$token) {
    responder('erro', 'CPF e token são obrigatórios.');
}

// Função principal
function verificarAssinaturaPorCPF($cpfProcurado, $token) {
    $headers = [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ];

    // Requisição para listar documentos
    $ch = curl_init("https://api.zapsign.com.br/api/v1/docs/");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    ]);
    $resposta = curl_exec($ch);
    curl_close($ch);

    $docs = json_decode($resposta, true);

    if (!isset($docs['results'])) {
        responder('erro', 'Erro ao acessar a API ZapSign.');
    }

    // Verificar documentos e signatários
    foreach ($docs['results'] as $doc) {
        $docToken = $doc['token'];

        $ch = curl_init("https://api.zapsign.com.br/api/v1/docs/$docToken/");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ]);
        $respostaDoc = curl_exec($ch);
        curl_close($ch);

        $docDetalhes = json_decode($respostaDoc, true);

        if (isset($docDetalhes['signers'])) {
            foreach ($docDetalhes['signers'] as $signer) {
                if (isset($signer['cpf']) &&
                    preg_replace('/\D/', '', $signer['cpf']) === preg_replace('/\D/', '', $cpfProcurado)) {
                    responder('ok', 'CPF já assinou: ' . $docDetalhes['name']);
                }
            }
        }
    }

    responder('nao_encontrado', 'Nenhuma assinatura encontrada para o CPF informado.');
}

verificarAssinaturaPorCPF($cpf, $token);
