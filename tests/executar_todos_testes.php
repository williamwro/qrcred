<?php
/**
 * Executor principal de todos os testes do sistema QRCred
 * TestSprite - Suite completa de testes
 */

require_once 'test_autenticacao.php';
require_once 'test_recuperacao_senha.php';
require_once 'test_convenio.php';
require_once 'test_antecipacao_sas.php';
require_once 'test_outros_servicos.php';
require_once 'test_administracao.php';

class ExecutorTestesQRCred {
    private $inicioTeste;
    private $resultados = [];
    
    public function __construct() {
        $this->inicioTeste = microtime(true);
    }
    
    public function executarTodosOsTestes() {
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                    TESTSPRITE - QRCRED                       ║\n";
        echo "║              Suite Completa de Testes Backend               ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";
        
        echo "Iniciando execução de todos os testes em: " . date('d/m/Y H:i:s') . "\n";
        echo "Servidor: http://localhost/qrcred/\n\n";
        
        // Executar todos os módulos de teste
        $this->executarModulo('Autenticação e Login', new TestAutenticacao());
        $this->executarModulo('Recuperação de Senha', new TestRecuperacaoSenha());
        $this->executarModulo('Convênio', new TestConvenio());
        $this->executarModulo('Antecipação e SAS+', new TestAntecipacaoSas());
        $this->executarModulo('Outros Serviços', new TestOutrosServicos());
        $this->executarModulo('Administração e Debug', new TestAdministracao());
        
        $this->gerarRelatorioFinal();
    }
    
    private function executarModulo($nomeModulo, $classeTestе) {
        echo "┌─────────────────────────────────────────────────────────────┐\n";
        echo "│ MÓDULO: $nomeModulo" . str_repeat(' ', 60 - strlen($nomeModulo) - 8) . "│\n";
        echo "└─────────────────────────────────────────────────────────────┘\n";
        
        $inicioModulo = microtime(true);
        
        ob_start();
        $classeTestе->executarTodos();
        $saida = ob_get_clean();
        
        $tempoModulo = microtime(true) - $inicioModulo;
        
        echo $saida;
        echo "Tempo do módulo: " . number_format($tempoModulo, 2) . " segundos\n\n";
        
        // Analisar resultados
        $this->analisarResultadosModulo($nomeModulo, $saida, $tempoModulo);
    }
    
    private function analisarResultadosModulo($modulo, $saida, $tempo) {
        $passou = substr_count($saida, '✓ PASSOU');
        $aviso = substr_count($saida, '⚠ AVISO');
        $falhou = substr_count($saida, '✗ FALHOU');
        
        $this->resultados[$modulo] = [
            'passou' => $passou,
            'aviso' => $aviso,
            'falhou' => $falhou,
            'tempo' => $tempo
        ];
    }
    
    private function gerarRelatorioFinal() {
        $tempoTotal = microtime(true) - $this->inicioTeste;
        
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                      RELATÓRIO FINAL                        ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";
        
        $totalPassou = 0;
        $totalAviso = 0;
        $totalFalhou = 0;
        
        foreach ($this->resultados as $modulo => $resultado) {
            echo "📊 $modulo:\n";
            echo "   ✓ Passou: {$resultado['passou']}\n";
            echo "   ⚠ Avisos: {$resultado['aviso']}\n";
            echo "   ✗ Falhou: {$resultado['falhou']}\n";
            echo "   ⏱ Tempo: " . number_format($resultado['tempo'], 2) . "s\n\n";
            
            $totalPassou += $resultado['passou'];
            $totalAviso += $resultado['aviso'];
            $totalFalhou += $resultado['falhou'];
        }
        
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "🎯 RESUMO GERAL:\n";
        echo "   Total de testes executados: " . ($totalPassou + $totalAviso + $totalFalhou) . "\n";
        echo "   ✓ Testes que passaram: $totalPassou\n";
        echo "   ⚠ Testes com avisos: $totalAviso\n";
        echo "   ✗ Testes que falharam: $totalFalhou\n";
        echo "   ⏱ Tempo total de execução: " . number_format($tempoTotal, 2) . " segundos\n\n";
        
        // Calcular taxa de sucesso
        $totalTestes = $totalPassou + $totalAviso + $totalFalhou;
        if ($totalTestes > 0) {
            $taxaSucesso = (($totalPassou + $totalAviso) / $totalTestes) * 100;
            echo "📈 Taxa de sucesso: " . number_format($taxaSucesso, 1) . "%\n";
        }
        
        echo "\n🏁 Testes finalizados em: " . date('d/m/Y H:i:s') . "\n";
        
        // Salvar relatório em arquivo
        $this->salvarRelatorioArquivo($totalPassou, $totalAviso, $totalFalhou, $tempoTotal);
    }
    
    private function salvarRelatorioArquivo($passou, $aviso, $falhou, $tempo) {
        $relatorio = "RELATÓRIO DE TESTES QRCRED - " . date('d/m/Y H:i:s') . "\n";
        $relatorio .= "=" . str_repeat("=", 60) . "\n\n";
        
        foreach ($this->resultados as $modulo => $resultado) {
            $relatorio .= "$modulo:\n";
            $relatorio .= "  Passou: {$resultado['passou']}\n";
            $relatorio .= "  Avisos: {$resultado['aviso']}\n";
            $relatorio .= "  Falhou: {$resultado['falhou']}\n";
            $relatorio .= "  Tempo: " . number_format($resultado['tempo'], 2) . "s\n\n";
        }
        
        $relatorio .= "RESUMO GERAL:\n";
        $relatorio .= "Total: " . ($passou + $aviso + $falhou) . " testes\n";
        $relatorio .= "Passou: $passou\n";
        $relatorio .= "Avisos: $aviso\n";
        $relatorio .= "Falhou: $falhou\n";
        $relatorio .= "Tempo total: " . number_format($tempo, 2) . "s\n";
        
        file_put_contents('relatorio_testes_' . date('Y-m-d_H-i-s') . '.txt', $relatorio);
        echo "📄 Relatório salvo em: relatorio_testes_" . date('Y-m-d_H-i-s') . ".txt\n";
    }
}

// Executar todos os testes se chamado diretamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $executor = new ExecutorTestesQRCred();
    $executor->executarTodosOsTestes();
}
?>
