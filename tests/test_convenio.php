<?php
/**
 * Testes para módulo de Convênio
 * Sistema QRCred - TestSprite
 */

class TestConvenio {
    private $baseUrl = 'http://localhost/qrcred/';
    
    public function executarTodos() {
        echo "=== INICIANDO TESTES DE CONVÊNIO ===\n";
        
        $this->testarConvenioLogin();
        $this->testarConvenioLancamentos();
        $this->testarConvenioDashboard();
        $this->testarMesesCorrenteApp();
        $this->testarLocalizaasApp();
        $this->testarContaApp();
        $this->testarConsultaPassAssoc();
        $this->testarEstornosRealizados();
        
        echo "=== TESTES DE CONVÊNIO CONCLUÍDOS ===\n\n";
    }
    
    private function testarConvenioLogin() {
        echo "Testando convenio_login.php...\n";
        
        // Teste 1: Login válido
        $dados = [
            'usuario' => 'admin',
            'senha' => 'senha123',
            'empregador_id' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('convenio_login.php', $dados);
        $this->verificarResposta($resultado, 'Login de convênio válido');
        
        // Teste 2: Credenciais inválidas
        $dados['senha'] = 'senhaerrada';
        $resultado = $this->fazerRequisicao('convenio_login.php', $dados);
        $this->verificarResposta($resultado, 'Login com credenciais inválidas');
    }
    
    private function testarConvenioLancamentos() {
        echo "Testando convenio_lancamentos.php...\n";
        
        $dados = [
            'empregador_id' => '1',
            'data_inicio' => '2024-01-01',
            'data_fim' => '2024-12-31'
        ];
        
        $resultado = $this->fazerRequisicao('convenio_lancamentos.php', $dados);
        $this->verificarResposta($resultado, 'Buscar lançamentos do convênio');
    }
    
    private function testarConvenioDashboard() {
        echo "Testando convenio_dashboard.php...\n";
        
        $dados = [
            'empregador_id' => '1',
            'mes' => date('m'),
            'ano' => date('Y')
        ];
        
        $resultado = $this->fazerRequisicao('convenio_dashboard.php', $dados);
        $this->verificarResposta($resultado, 'Dashboard do convênio');
    }
    
    private function testarMesesCorrenteApp() {
        echo "Testando meses_corrente_app.php...\n";
        
        $dados = [
            'empregador_id' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('meses_corrente_app.php', $dados);
        $this->verificarResposta($resultado, 'Meses correntes');
    }
    
    private function testarLocalizaasApp() {
        echo "Testando localizaasapp.php...\n";
        
        // Teste 1: Buscar associado por código
        $dados = [
            'codigo' => '12345',
            'empregador_id' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('localizaasapp.php', $dados);
        $this->verificarResposta($resultado, 'Localizar associado por código');
        
        // Teste 2: Buscar associado por nome
        $dados = [
            'nome' => 'João Silva',
            'empregador_id' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('localizaasapp.php', $dados);
        $this->verificarResposta($resultado, 'Localizar associado por nome');
    }
    
    private function testarContaApp() {
        echo "Testando conta_app.php...\n";
        
        $dados = [
            'codigo_associado' => '12345',
            'empregador_id' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('conta_app.php', $dados);
        $this->verificarResposta($resultado, 'Consultar conta do associado');
    }
    
    private function testarConsultaPassAssoc() {
        echo "Testando consulta_pass_assoc.php...\n";
        
        $dados = [
            'matricula' => '12345',
            'empregador' => '1',
            'pass' => 'senha123',
            'id_associado' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('consulta_pass_assoc.php', $dados);
        $this->verificarResposta($resultado, 'Verificar senha do associado');
        
        // Teste 2: Senha incorreta
        $dados['pass'] = 'senhaerrada';
        $resultado = $this->fazerRequisicao('consulta_pass_assoc.php', $dados);
        $this->verificarResposta($resultado, 'Verificar senha incorreta');
    }
    
    private function testarEstornosRealizados() {
        echo "Testando estornos_realizados.php...\n";
        
        $dados = [
            'empregador_id' => '1',
            'data_inicio' => '2024-01-01',
            'data_fim' => '2024-12-31'
        ];
        
        $resultado = $this->fazerRequisicao('estornos_realizados.php', $dados);
        $this->verificarResposta($resultado, 'Listar estornos realizados');
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
    $teste = new TestConvenio();
    $teste->executarTodos();
}
?>
