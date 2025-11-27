<?php
/**
 * Testes para módulo de Recuperação de Senha e Códigos
 * Sistema QRCred - TestSprite
 */

class TestRecuperacaoSenha {
    private $baseUrl = 'http://localhost/qrcred/';
    
    public function executarTodos() {
        echo "=== INICIANDO TESTES DE RECUPERAÇÃO DE SENHA ===\n";
        
        $this->testarEnviaCodigoRecuperacao();
        $this->testarEnviaCodigoEmail();
        $this->testarEnviaSms();
        $this->testarEnviaSmsDireto();
        $this->testarEnviaWhatsapp();
        $this->testarGerenciaCodigoRecuperacao();
        $this->testarAdminCodigosRecuperacao();
        $this->testarValidaCodigoRecuperacao();
        $this->testarAlteraSenhaAssociado();
        
        echo "=== TESTES DE RECUPERAÇÃO DE SENHA CONCLUÍDOS ===\n\n";
    }
    
    private function testarEnviaCodigoRecuperacao() {
        echo "Testando envia_codigo_recuperacao.php...\n";
        
        // Teste 1: Enviar código válido
        $dados = [
            'codigo_associado' => '12345',
            'divisao' => '1',
            'tipo_envio' => 'sms'
        ];
        
        $resultado = $this->fazerRequisicao('envia_codigo_recuperacao.php', $dados);
        $this->verificarResposta($resultado, 'Enviar código por SMS');
        
        // Teste 2: Enviar código por email
        $dados['tipo_envio'] = 'email';
        $resultado = $this->fazerRequisicao('envia_codigo_recuperacao.php', $dados);
        $this->verificarResposta($resultado, 'Enviar código por email');
    }
    
    private function testarEnviaCodigoEmail() {
        echo "Testando envia_codigo_email.php...\n";
        
        $dados = [
            'email' => 'teste@exemplo.com',
            'codigo' => '123456',
            'nome' => 'Teste Usuario'
        ];
        
        $resultado = $this->fazerRequisicao('envia_codigo_email.php', $dados);
        $this->verificarResposta($resultado, 'Enviar código por email');
    }
    
    private function testarEnviaSms() {
        echo "Testando envia_sms.php...\n";
        
        $dados = [
            'telefone' => '11999999999',
            'codigo' => '123456',
            'nome' => 'Teste'
        ];
        
        $resultado = $this->fazerRequisicao('envia_sms.php', $dados);
        $this->verificarResposta($resultado, 'Enviar SMS');
    }
    
    private function testarEnviaSmsDireto() {
        echo "Testando envia_sms_direto.php...\n";
        
        $dados = [
            'telefone' => '11999999999',
            'mensagem' => 'Seu código é: 123456'
        ];
        
        $resultado = $this->fazerRequisicao('envia_sms_direto.php', $dados);
        $this->verificarResposta($resultado, 'Enviar SMS direto');
    }
    
    private function testarEnviaWhatsapp() {
        echo "Testando envia_whatsapp.php...\n";
        
        $dados = [
            'telefone' => '11999999999',
            'codigo' => '123456',
            'nome' => 'Teste'
        ];
        
        $resultado = $this->fazerRequisicao('envia_whatsapp.php', $dados);
        $this->verificarResposta($resultado, 'Enviar WhatsApp');
    }
    
    private function testarGerenciaCodigoRecuperacao() {
        echo "Testando gerencia_codigo_recuperacao.php...\n";
        
        // Teste 1: Gerar código
        $dados = [
            'acao' => 'gerar',
            'codigo_associado' => '12345',
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('gerencia_codigo_recuperacao.php', $dados);
        $this->verificarResposta($resultado, 'Gerar código de recuperação');
        
        // Teste 2: Validar código
        $dados = [
            'acao' => 'validar',
            'codigo_associado' => '12345',
            'codigo' => '123456'
        ];
        
        $resultado = $this->fazerRequisicao('gerencia_codigo_recuperacao.php', $dados);
        $this->verificarResposta($resultado, 'Validar código de recuperação');
    }
    
    private function testarAdminCodigosRecuperacao() {
        echo "Testando admin_codigos_recuperacao.php...\n";
        
        $dados = [
            'codigo_associado' => '12345',
            'acao' => 'listar'
        ];
        
        $resultado = $this->fazerRequisicao('admin_codigos_recuperacao.php', $dados);
        $this->verificarResposta($resultado, 'Administrar códigos');
    }
    
    private function testarValidaCodigoRecuperacao() {
        echo "Testando valida_codigo_recuperacao.php...\n";
        
        $dados = [
            'codigo_associado' => '12345',
            'codigo' => '123456'
        ];
        
        $resultado = $this->fazerRequisicao('valida_codigo_recuperacao.php', $dados);
        $this->verificarResposta($resultado, 'Validar código');
    }
    
    private function testarAlteraSenhaAssociado() {
        echo "Testando altera_senha_associado.php...\n";
        
        $dados = [
            'codigo_associado' => '12345',
            'nova_senha' => 'novaSenha123',
            'codigo_validacao' => '123456'
        ];
        
        $resultado = $this->fazerRequisicao('altera_senha_associado.php', $dados);
        $this->verificarResposta($resultado, 'Alterar senha do associado');
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
    $teste = new TestRecuperacaoSenha();
    $teste->executarTodos();
}
?>
