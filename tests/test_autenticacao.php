<?php
/**
 * Testes para módulo de Autenticação e Login
 * Sistema QRCred - TestSprite
 */

require_once '../Adm/php/banco.php';

class TestAutenticacao {
    private $baseUrl = 'http://localhost/qrcred/';
    
    public function executarTodos() {
        echo "=== INICIANDO TESTES DE AUTENTICAÇÃO ===\n";
        
        $this->testarLocalizaAssociadoApp2();
        $this->testarLoginApp();
        $this->testarLocalizaAssociadoCartao();
        
        echo "=== TESTES DE AUTENTICAÇÃO CONCLUÍDOS ===\n\n";
    }
    
    private function testarLocalizaAssociadoApp2() {
        echo "Testando localiza_associado_app_2.php...\n";
        
        // Teste 1: Buscar associado válido
        $dados = [
            'codigo' => '12345',
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('localiza_associado_app_2.php', $dados);
        $this->verificarResposta($resultado, 'Buscar associado válido');
        
        // Teste 2: Buscar associado inexistente
        $dados = [
            'codigo' => '99999',
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('localiza_associado_app_2.php', $dados);
        $this->verificarResposta($resultado, 'Buscar associado inexistente');
        
        // Teste 3: Parâmetros inválidos
        $dados = [];
        $resultado = $this->fazerRequisicao('localiza_associado_app_2.php', $dados);
        $this->verificarResposta($resultado, 'Parâmetros inválidos');
    }
    
    private function testarLoginApp() {
        echo "Testando login_app.php...\n";
        
        // Teste 1: Login válido
        $dados = [
            'codigo' => '12345',
            'senha' => 'senha123',
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('login_app.php', $dados);
        $this->verificarResposta($resultado, 'Login válido');
        
        // Teste 2: Senha incorreta
        $dados = [
            'codigo' => '12345',
            'senha' => 'senhaerrada',
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('login_app.php', $dados);
        $this->verificarResposta($resultado, 'Senha incorreta');
    }
    
    private function testarLocalizaAssociadoCartao() {
        echo "Testando localiza_associado_app_cartao.php...\n";
        
        // Teste 1: Buscar por cartão válido
        $dados = [
            'cartao' => '1234567890123456'
        ];
        
        $resultado = $this->fazerRequisicao('localiza_associado_app_cartao.php', $dados);
        $this->verificarResposta($resultado, 'Buscar por cartão válido');
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

// Executar testes se chamado diretamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $teste = new TestAutenticacao();
    $teste->executarTodos();
}
?>
