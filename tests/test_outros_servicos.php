<?php
/**
 * Testes para módulo de Outros Serviços
 * Sistema QRCred - TestSprite
 */

class TestOutrosServicos {
    private $baseUrl = 'http://localhost/qrcred/';
    
    public function executarTodos() {
        echo "=== INICIANDO TESTES DE OUTROS SERVIÇOS ===\n";
        
        $this->testarConveniosApp();
        $this->testarEmpregadoresApp();
        $this->testarExtratoApp();
        $this->testarHistoricoAntecipacaoApp();
        $this->testarAgendamentoApp();
        $this->testarCancelarAgendamentoApp();
        $this->testarAgendamentosListaApp();
        $this->testarCheckAgendamentosNotifications();
        
        echo "=== TESTES DE OUTROS SERVIÇOS CONCLUÍDOS ===\n\n";
    }
    
    private function testarConveniosApp() {
        echo "Testando convenios_app.php...\n";
        
        $dados = [
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('convenios_app.php', $dados);
        $this->verificarResposta($resultado, 'Listar convênios');
    }
    
    private function testarEmpregadoresApp() {
        echo "Testando empregadores_app.php...\n";
        
        $dados = [
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('empregadores_app.php', $dados);
        $this->verificarResposta($resultado, 'Listar empregadores');
    }
    
    private function testarExtratoApp() {
        echo "Testando extrato_app.php...\n";
        
        $dados = [
            'codigo_associado' => '12345',
            'divisao' => '1',
            'data_inicio' => '2024-01-01',
            'data_fim' => '2024-12-31'
        ];
        
        $resultado = $this->fazerRequisicao('extrato_app.php', $dados);
        $this->verificarResposta($resultado, 'Gerar extrato do associado');
    }
    
    private function testarHistoricoAntecipacaoApp() {
        echo "Testando historico_antecipacao_app.php...\n";
        
        $dados = [
            'codigo_associado' => '12345',
            'divisao' => '1'
        ];
        
        $resultado = $this->fazerRequisicao('historico_antecipacao_app.php', $dados);
        $this->verificarResposta($resultado, 'Histórico de antecipação');
    }
    
    private function testarAgendamentoApp() {
        echo "Testando agendamento_app.php...\n";
        
        // Teste 1: Criar agendamento
        $dados = [
            'acao' => 'criar',
            'codigo_associado' => '12345',
            'data_agendamento' => '2024-12-31',
            'hora_agendamento' => '10:00',
            'servico' => 'Consulta'
        ];
        
        $resultado = $this->fazerRequisicao('agendamento_app.php', $dados);
        $this->verificarResposta($resultado, 'Criar agendamento');
        
        // Teste 2: Listar agendamentos
        $dados = [
            'acao' => 'listar',
            'codigo_associado' => '12345'
        ];
        
        $resultado = $this->fazerRequisicao('agendamento_app.php', $dados);
        $this->verificarResposta($resultado, 'Listar agendamentos');
    }
    
    private function testarCancelarAgendamentoApp() {
        echo "Testando cancelar_agendamento_app.php...\n";
        
        $dados = [
            'id_agendamento' => '1',
            'codigo_associado' => '12345'
        ];
        
        $resultado = $this->fazerRequisicao('cancelar_agendamento_app.php', $dados);
        $this->verificarResposta($resultado, 'Cancelar agendamento');
    }
    
    private function testarAgendamentosListaApp() {
        echo "Testando agendamentos_lista_app.php...\n";
        
        $dados = [
            'codigo_associado' => '12345',
            'data_inicio' => '2024-01-01',
            'data_fim' => '2024-12-31'
        ];
        
        $resultado = $this->fazerRequisicao('agendamentos_lista_app.php', $dados);
        $this->verificarResposta($resultado, 'Lista de agendamentos');
    }
    
    private function testarCheckAgendamentosNotifications() {
        echo "Testando check_agendamentos_notifications.php...\n";
        
        $dados = [
            'codigo_associado' => '12345'
        ];
        
        $resultado = $this->fazerRequisicao('check_agendamentos_notifications.php', $dados);
        $this->verificarResposta($resultado, 'Verificar notificações de agendamentos');
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
    $teste = new TestOutrosServicos();
    $teste->executarTodos();
}
?>
