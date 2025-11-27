<?php
/**
 * Arquivo para vincular código do associado
 * Busca o código na tabela sind.associado pelo CPF e atualiza na sind.associados_sasmais
 */

ini_set('display_errors', true);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

include "../../php/banco.php";

try {
    // Verificar se os dados foram enviados
    if (!isset($_POST['cpf']) || !isset($_POST['id_registro'])) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'CPF e ID do registro são obrigatórios'
        ]);
        exit;
    }

    $cpf = trim($_POST['cpf']);
    $id_registro = intval($_POST['id_registro']);

    // Remover formatação do CPF (pontos e traços)
    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);

    if (empty($cpf_limpo)) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'CPF não informado ou inválido'
        ]);
        exit;
    }

    if ($id_registro <= 0) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'ID do registro inválido'
        ]);
        exit;
    }

    // Conectar ao banco
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buscar código do associado na tabela sind.associado
    $query_busca = "SELECT codigo, nome FROM sind.associado WHERE cpf = :cpf LIMIT 1";
    $stmt_busca = $pdo->prepare($query_busca);
    $stmt_busca->execute([':cpf' => $cpf_limpo]);
    $associado = $stmt_busca->fetch();

    if (!$associado) {
        echo json_encode([
            'status' => 'nao_encontrado',
            'mensagem' => 'Associado não encontrado na base de dados para o CPF informado',
            'cpf' => $cpf_limpo
        ]);
        exit;
    }

    // Verificar se o registro existe na tabela sind.associados_sasmais
    $query_verifica = "SELECT id, nome, codigo FROM sind.associados_sasmais WHERE id = :id";
    $stmt_verifica = $pdo->prepare($query_verifica);
    $stmt_verifica->execute([':id' => $id_registro]);
    $registro_atual = $stmt_verifica->fetch();

    if (!$registro_atual) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Registro não encontrado na tabela de assinaturas digitais'
        ]);
        exit;
    }

    // Verificar se o código encontrado é diferente do atual
    // Se for igual, não precisa atualizar mas retorna sucesso
    if (!empty($registro_atual['codigo']) && $registro_atual['codigo'] === $associado['codigo']) {
        echo json_encode([
            'status' => 'sucesso',
            'mensagem' => 'Código já estava vinculado corretamente!',
            'dados' => [
                'id_registro' => $id_registro,
                'cpf' => $cpf_limpo,
                'codigo_vinculado' => $associado['codigo'],
                'nome_associado' => $associado['nome'],
                'nome_registro' => $registro_atual['nome']
            ]
        ]);
        exit;
    }
    
    // Log da operação para debug
    $operacao = empty($registro_atual['codigo']) ? 'vinculação' : 'atualização';
    $codigo_anterior = $registro_atual['codigo'] ?? 'vazio';

    // Tentar atualizar o código na tabela sind.associados_sasmais
    // Se der erro de constraint única, gerar código único baseado no original
    $codigo_final = $associado['codigo'];
    $tentativa = 0;
    $sucesso = false;
    
    while (!$sucesso && $tentativa < 10) { // Máximo 10 tentativas
        try {
            $query_update = "UPDATE sind.associados_sasmais SET codigo = :codigo WHERE id = :id";
            $stmt_update = $pdo->prepare($query_update);
            $stmt_update->execute([
                ':codigo' => $codigo_final,
                ':id' => $id_registro
            ]);
            
            // Se chegou aqui, a atualização foi bem-sucedida
            if ($stmt_update->rowCount() > 0) {
                $mensagem = $operacao === 'vinculação' 
                    ? 'Código do associado vinculado com sucesso!' 
                    : "Código atualizado com sucesso! (anterior: {$codigo_anterior})";
                
                if ($tentativa > 0) {
                    $mensagem .= " (Código ajustado para evitar duplicação: {$codigo_final})";
                }
                    
                echo json_encode([
                    'status' => 'sucesso',
                    'mensagem' => $mensagem,
                    'dados' => [
                        'id_registro' => $id_registro,
                        'cpf' => $cpf_limpo,
                        'codigo_vinculado' => $codigo_final,
                        'nome_associado' => $associado['nome'],
                        'nome_registro' => $registro_atual['nome'],
                        'operacao' => $operacao,
                        'codigo_anterior' => $codigo_anterior,
                        'codigo_original' => $associado['codigo'],
                        'tentativas' => $tentativa + 1
                    ]
                ]);
                $sucesso = true;
            } else {
                echo json_encode([
                    'status' => 'erro',
                    'mensagem' => 'Erro ao atualizar o registro. Nenhuma linha foi afetada.'
                ]);
                $sucesso = true; // Para sair do loop
            }
            
        } catch (PDOException $e) {
            // Verificar se é erro de constraint única no campo codigo
            if (strpos($e->getMessage(), 'uk_associados_sasmais_codigo') !== false || 
                strpos($e->getMessage(), 'duplicate key value') !== false) {
                
                $tentativa++;
                // Gerar novo código único baseado no original
                $codigo_final = $associado['codigo'] . '_' . $tentativa;
                
                // Se é a última tentativa, reportar o erro
                if ($tentativa >= 10) {
                    echo json_encode([
                        'status' => 'erro',
                        'mensagem' => "Não foi possível vincular o código após {$tentativa} tentativas. Código base '{$associado['codigo']}' já existe múltiplas vezes na base de dados."
                    ]);
                    $sucesso = true; // Para sair do loop
                }
            } else {
                // Outro tipo de erro, repassar
                throw $e;
            }
        }
    }

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro de banco de dados: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro geral: ' . $e->getMessage()
    ]);
}
?> 