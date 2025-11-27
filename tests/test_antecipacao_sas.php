<?php
/**
 * Testes para módulo de Antecipação e SAS+
 * Sistema QRCred - TestSprite
 */

class TestAntecipacaoSas {
    private $baseUrl = 'http://localhost/qrcred/';
    
    public function executarTodos() {
        echo "=== INICIANDO TESTES DE ANTECIPAÇÃO E SAS+ ===\n";
        
        $this->testarGravaAntecipacaoApp();
        $this->testarApiVerificarAdesaoSasmais();
        $this->testarVerificarAntecipacaoSasmais();
        $this->testarApiAssociados();
        $this->testarVerificarAssinaturaAprovada();
        
        echo "=== TESTES DE ANTECIPAÇÃO E SAS+ CONCLUÍDOS ===\n\n";
    }
    
    private function testarGravaAntecipacaoApp() {
        echo "Testando grava_antecipacao_app.php...\n";
        
        // Teste 1: Gravar antecipação válida
        $dados = [
            'codigo_associado' => '12345',
            'valor' => '500.00',
            'parcelas' => '12',
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('grava_antecipacao_app.php', $dados);
        $this->verificarResposta($resultado, 'Gravar antecipação válida');
        
        // Teste 2: Valor inválido
        $dados['valor'] = '0';
        $resultado = $this->fazerRequisicao('grava_antecipacao_app.php', $dados);
        $this->verificarResposta($resultado, 'Gravar antecipação com valor inválido');
    }
    
    private function testarApiVerificarAdesaoSasmais() {
        echo "Testando api_verificar_adesao_sasmais.php...\n";
        
        // Teste 1: Verificar adesão existente
        $dados = [
            'codigo_associado' => '12345',
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('api_verificar_adesao_sasmais.php', $dados);
        $this->verificarResposta($resultado, 'Verificar adesão SAS+ existente');
        
        // Teste 2: Verificar adesão inexistente
        $dados['codigo_associado'] = '99999';
        $resultado = $this->fazerRequisicao('api_verificar_adesao_sasmais.php', $dados);
        $this->verificarResposta($resultado, 'Verificar adesão SAS+ inexistente');
    }
    
    private function testarVerificarAntecipacaoSasmais() {
        echo "Testando verificar_antecipacao_sasmais.php...\n";
        
        $dados = [
            'codigo_associado' => '12345',
            'divisao' => '1',
            'valor_solicitado' => '1000.00'
        ];
        
        $resultado = $this->fazerRequisicao('verificar_antecipacao_sasmais.php', $dados);
        $this->verificarResposta($resultado, 'Verificar antecipação SAS+');
    }
    
    private function testarApiAssociados() {
        echo "Testando api_associados.php...\n";
        
        // Teste 1: Listar associados
        $dados = [
            'acao' => 'listar',
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('api_associados.php', $dados);
        $this->verificarResposta($resultado, 'Listar associados');
        
        // Teste 2: Buscar associado específico
        $dados = [
            'acao' => 'buscar',
            'codigo' => '12345',
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('api_associados.php', $dados);
        $this->verificarResposta($resultado, 'Buscar associado específico');
    }
    
    private function testarVerificarAssinaturaAprovada() {
        echo "Testando verificar_assinatura_aprovada.php...\n";
        
        $dados = [
            'documento_id' => 'DOC123456',
            'codigo_associado' => '12345'
        ];
        
        $resultado = $this->fazerRequisicao('verificar_assinatura_aprovada.php', $dados);
        $this->verificarResposta($resultado, 'Verificar assinatura aprovada');
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
    $teste = new TestAntecipacaoSas();
    $teste->executarTodos();
}
?>
