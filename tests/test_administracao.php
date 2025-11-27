<?php
/**
 * Testes para módulo de Administração e Debug
 * Sistema QRCred - TestSprite
 */

class TestAdministracao {
    private $baseUrl = 'http://localhost/qrcred/';
    
    public function executarTodos() {
        echo "=== INICIANDO TESTES DE ADMINISTRAÇÃO E DEBUG ===\n";
        
        $this->testarAssociadoCadastroApp();
        $this->testarAtualizaAssociadoApp();
        $this->testarPushSubscriptionApp();
        $this->testarInsereCodigoApp();
        
        echo "=== TESTES DE ADMINISTRAÇÃO E DEBUG CONCLUÍDOS ===\n\n";
    }
    
    private function testarAssociadoCadastroApp() {
        echo "Testando associado_cadastro_app.php...\n";
        
        // Teste 1: Cadastrar novo associado
        $dados = [
            'nome' => 'João Silva Teste',
            'cpf' => '12345678901',
            'email' => 'joao.teste@exemplo.com',
            'telefone' => '11999999999',
            'divisao' => '1',
            'empregador_id' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('associado_cadastro_app.php', $dados);
        $this->verificarResposta($resultado, 'Cadastrar novo associado');
        
        // Teste 2: Cadastrar com CPF duplicado
        $resultado = $this->fazerRequisicao('associado_cadastro_app.php', $dados);
        $this->verificarResposta($resultado, 'Cadastrar com CPF duplicado');
        
        // Teste 3: Dados obrigatórios faltando
        $dadosIncompletos = [
            'nome' => 'Teste Incompleto'
        ];
        
        $resultado = $this->fazerRequisicao('associado_cadastro_app.php', $dadosIncompletos);
        $this->verificarResposta($resultado, 'Cadastrar com dados incompletos');
    }
    
    private function testarAtualizaAssociadoApp() {
        echo "Testando atualiza_associado_app.php...\n";
        
        // Teste 1: Atualizar dados do associado
        $dados = [
            'codigo' => '12345',
            'nome' => 'João Silva Atualizado',
            'email' => 'joao.atualizado@exemplo.com',
            'telefone' => '11888888888',
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('atualiza_associado_app.php', $dados);
        $this->verificarResposta($resultado, 'Atualizar dados do associado');
        
        // Teste 2: Atualizar associado inexistente
        $dados['codigo'] = '99999';
        $resultado = $this->fazerRequisicao('atualiza_associado_app.php', $dados);
        $this->verificarResposta($resultado, 'Atualizar associado inexistente');
    }
    
    private function testarPushSubscriptionApp() {
        echo "Testando push_subscription_app.php...\n";
        
        // Teste 1: Registrar nova inscrição push
        $dados = [
            'codigo_associado' => '12345',
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/teste123',
            'p256dh_key' => 'BTestKey123',
            'auth_key' => 'AuthTestKey123'
        ];
        
        $resultado = $this->fazerRequisicao('push_subscription_app.php', $dados);
        $this->verificarResposta($resultado, 'Registrar inscrição push');
        
        // Teste 2: Atualizar inscrição existente
        $dados['endpoint'] = 'https://fcm.googleapis.com/fcm/send/teste456';
        $resultado = $this->fazerRequisicao('push_subscription_app.php', $dados);
        $this->verificarResposta($resultado, 'Atualizar inscrição push');
    }
    
    private function testarInsereCodigoApp() {
        echo "Testando insere_codigo_app.php...\n";
        
        // Teste 1: Inserir novo código
        $dados = [
            'codigo_associado' => '12345',
            'tipo_codigo' => 'recuperacao',
            'codigo' => '123456',
            'validade' => '2024-12-31 23:59:59'
        ];
        
        $resultado = $this->fazerRequisicao('insere_codigo_app.php', $dados);
        $this->verificarResposta($resultado, 'Inserir novo código');
        
        // Teste 2: Inserir código duplicado
        $resultado = $this->fazerRequisicao('insere_codigo_app.php', $dados);
        $this->verificarResposta($resultado, 'Inserir código duplicado');
    }
    
    private function fazerRequisicao($arquivo, $dados) {
        $url = $this->baseUrl . $arquivo;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dados));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $resposta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'resposta' => $resposta,
            'http_code' => $httpCode
        ];
    }
    
    private function verificarResposta($resultado, $teste) {
        echo "  - $teste: ";
        
        if ($resultado['http_code'] == 200) {
            $json = json_decode($resultado['resposta'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "✓ PASSOU (HTTP 200, JSON válido)\n";
            } else {
                echo "⚠ AVISO (HTTP 200, mas resposta não é JSON válido)\n";
            }
        } else {
            echo "✗ FALHOU (HTTP {$resultado['http_code']})\n";
        }
    }
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $teste = new TestAdministracao();
    $teste->executarTodos();
}
?>
