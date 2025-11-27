<?php
/**
 * SCRIPT DE TESTE - COMPATIBILIDADE PHP 8.1
 * Execute este script para verificar se todas as correções estão funcionando
 */

echo "<h2>🧪 TESTE DE COMPATIBILIDADE PHP 8.1 - PROJETO QRCRED</h2>";
echo "<hr>";

// 1. Verificar versão do PHP
echo "<h3>1. Versão do PHP</h3>";
echo "Versão atual: <strong>" . PHP_VERSION . "</strong><br>";
echo "Versão ID: " . PHP_VERSION_ID . "<br>";
echo "Requer PHP 8.1+: " . (PHP_VERSION_ID >= 80100 ? "✅ OK" : "❌ FALHA") . "<br><br>";

// 2. Verificar extensões necessárias
echo "<h3>2. Extensões Necessárias</h3>";
$extensoes_necessarias = ['pdo', 'pdo_pgsql', 'json', 'filter', 'hash'];
foreach ($extensoes_necessarias as $ext) {
    echo "Extensão {$ext}: " . (extension_loaded($ext) ? "✅ Carregada" : "❌ Não encontrada") . "<br>";
}
echo "<br>";

// 3. Verificar extensão mbstring (não mais obrigatória para números)
echo "<h3>3. Extensão MBString</h3>";
echo "MBString: " . (extension_loaded('mbstring') ? "✅ Disponível" : "⚠️ Não disponível (mas não é mais necessária para números)") . "<br><br>";

// 4. Testar classe de banco
echo "<h3>4. Teste de Conexão com Banco</h3>";
try {
    require_once 'Adm/php/banco.php';
    echo "Classe Banco carregada: ✅ OK<br>";
    
    // Não vamos conectar de verdade para não expor credenciais
    echo "Método conectar_postgres disponível: " . (method_exists('Banco', 'conectar_postgres') ? "✅ OK" : "❌ FALHA") . "<br>";
} catch (Exception $e) {
    echo "Erro ao carregar classe Banco: ❌ " . $e->getMessage() . "<br>";
}
echo "<br>";

// 5. Testar classe NumeroPorExtenso
echo "<h3>5. Teste de Conversão por Extenso</h3>";
try {
    require_once 'Adm/pages/recibos/NumeroPorExtenso.php';
    $extenso = new NumeroPorExtenso();
    $resultado = $extenso->converter(1234.56);
    echo "Conversão de 1234.56: " . htmlentities($resultado) . "<br>";
    echo "Classe NumeroPorExtenso: ✅ OK<br>";
} catch (Exception $e) {
    echo "Erro na classe NumeroPorExtenso: ❌ " . $e->getMessage() . "<br>";
}
echo "<br>";

// 6. Testar tipos float (antigo real)
echo "<h3>6. Teste de Casting Float</h3>";
$numero_string = "123.45";
$numero_float = (float)$numero_string;
echo "Casting (float) funciona: " . ($numero_float === 123.45 ? "✅ OK" : "❌ FALHA") . "<br>";
echo "Valor convertido: {$numero_float}<br><br>";

// 7. Verificar composer
echo "<h3>7. Dependências Composer</h3>";
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    echo "Autoload do Composer: ✅ Carregado<br>";
    
    // Testar PHPMailer
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "PHPMailer disponível: ✅ OK<br>";
    } else {
        echo "PHPMailer: ❌ Não encontrado<br>";
    }
} else {
    echo "Vendor do Composer: ❌ Não encontrado (execute: composer install)<br>";
}
echo "<br>";

// 8. Resumo final
echo "<h3>8. Resumo Final</h3>";
$php_ok = PHP_VERSION_ID >= 80100;
$extensoes_ok = extension_loaded('pdo') && extension_loaded('pdo_pgsql');
$classes_ok = class_exists('Banco') && class_exists('NumeroPorExtenso');

if ($php_ok && $extensoes_ok && $classes_ok) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<strong>🎉 PROJETO COMPATÍVEL COM PHP 8.1!</strong><br>";
    echo "Todas as verificações principais passaram. O projeto está pronto para PHP 8.1.";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<strong>⚠️ ATENÇÃO - VERIFICAÇÕES PENDENTES</strong><br>";
    echo "Algumas verificações falharam. Revise os problemas acima antes de usar PHP 8.1.";
    echo "</div>";
}

echo "<br><hr>";
echo "<p><strong>IMPORTANTE:</strong> Após verificar que tudo está OK, DELETE este arquivo por segurança.</p>";
echo "<p><em>Desenvolvido para migração PHP 8.1 - Projeto QRCred</em></p>";
?> 