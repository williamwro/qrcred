<?php
// Script de limpeza para remover sessões duplicadas e órfãs
include "../../php/banco.php";

try {
    $pdo = Banco::conectar_postgres();
    
    echo "<h3>Limpeza de Sessões - " . date('Y-m-d H:i:s') . "</h3>";
    
    // 1. Mostrar estatísticas antes da limpeza
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_sessoes,
            COUNT(CASE WHEN is_active = true THEN 1 END) as sessoes_ativas,
            COUNT(CASE WHEN is_active = false THEN 1 END) as sessoes_inativas,
            COUNT(DISTINCT codigo_usuario) as usuarios_unicos
        FROM sind.sessoes_ativas
    ")->fetch();
    
    echo "<p><strong>Antes da limpeza:</strong></p>";
    echo "<ul>";
    echo "<li>Total de sessões: " . $stats['total_sessoes'] . "</li>";
    echo "<li>Sessões ativas: " . $stats['sessoes_ativas'] . "</li>";
    echo "<li>Sessões inativas: " . $stats['sessoes_inativas'] . "</li>";
    echo "<li>Usuários únicos: " . $stats['usuarios_unicos'] . "</li>";
    echo "</ul>";
    
    // 2. Desativar sessões antigas (mais de 5 minutos sem atividade)
    $tempo_limite = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    $result = $pdo->prepare("
        UPDATE sind.sessoes_ativas 
        SET is_active = false 
        WHERE last_activity < :tempo_limite AND is_active = true
    ");
    $result->execute([':tempo_limite' => $tempo_limite]);
    $desativadas = $result->rowCount();
    
    echo "<p><strong>Sessões desativadas por inatividade:</strong> $desativadas</p>";
    
    // 3. Para cada usuário, manter apenas a sessão mais recente ativa
    $usuarios = $pdo->query("
        SELECT DISTINCT codigo_usuario 
        FROM sind.sessoes_ativas 
        WHERE is_active = true
    ")->fetchAll();
    
    $sessoes_duplicadas_removidas = 0;
    
    foreach ($usuarios as $usuario) {
        $codigo_usuario = $usuario['codigo_usuario'];
        
        // Buscar todas as sessões ativas deste usuário
        $sessoes = $pdo->prepare("
            SELECT id, session_id, last_activity 
            FROM sind.sessoes_ativas 
            WHERE codigo_usuario = :codigo AND is_active = true
            ORDER BY last_activity DESC
        ");
        $sessoes->execute([':codigo' => $codigo_usuario]);
        $sessoes_usuario = $sessoes->fetchAll();
        
        // Se há mais de uma sessão ativa, desativar as antigas
        if (count($sessoes_usuario) > 1) {
            $primeira = true;
            foreach ($sessoes_usuario as $sessao) {
                if ($primeira) {
                    $primeira = false;
                    continue; // Manter a mais recente
                }
                
                // Desativar sessões antigas
                $desativar = $pdo->prepare("
                    UPDATE sind.sessoes_ativas 
                    SET is_active = false 
                    WHERE id = :id
                ");
                $desativar->execute([':id' => $sessao['id']]);
                $sessoes_duplicadas_removidas++;
            }
        }
    }
    
    echo "<p><strong>Sessões duplicadas removidas:</strong> $sessoes_duplicadas_removidas</p>";
    
    // 4. Remover sessões muito antigas (mais de 24 horas)
    $tempo_remover = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $result = $pdo->prepare("
        DELETE FROM sind.sessoes_ativas 
        WHERE login_time < :tempo_remover
    ");
    $result->execute([':tempo_remover' => $tempo_remover]);
    $removidas = $result->rowCount();
    
    echo "<p><strong>Sessões antigas removidas (>24h):</strong> $removidas</p>";
    
    // 5. Mostrar estatísticas após a limpeza
    $stats_final = $pdo->query("
        SELECT 
            COUNT(*) as total_sessoes,
            COUNT(CASE WHEN is_active = true THEN 1 END) as sessoes_ativas,
            COUNT(CASE WHEN is_active = false THEN 1 END) as sessoes_inativas,
            COUNT(DISTINCT codigo_usuario) as usuarios_unicos
        FROM sind.sessoes_ativas
    ")->fetch();
    
    echo "<p><strong>Após a limpeza:</strong></p>";
    echo "<ul>";
    echo "<li>Total de sessões: " . $stats_final['total_sessoes'] . "</li>";
    echo "<li>Sessões ativas: " . $stats_final['sessoes_ativas'] . "</li>";
    echo "<li>Sessões inativas: " . $stats_final['sessoes_inativas'] . "</li>";
    echo "<li>Usuários únicos: " . $stats_final['usuarios_unicos'] . "</li>";
    echo "</ul>";
    
    echo "<p><strong>✅ Limpeza concluída com sucesso!</strong></p>";
    
    // 6. Mostrar sessões ativas atuais
    echo "<h4>Sessões Ativas Atuais:</h4>";
    $ativas = $pdo->query("
        SELECT s.codigo_usuario, u.username, s.session_id, s.ip_address, s.last_activity
        FROM sind.sessoes_ativas s
        JOIN sind.usuarios u ON s.codigo_usuario = u.codigo
        WHERE s.is_active = true
        ORDER BY s.last_activity DESC
    ")->fetchAll();
    
    if (count($ativas) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Usuário</th><th>Session ID</th><th>IP</th><th>Última Atividade</th></tr>";
        foreach ($ativas as $sessao) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($sessao['username']) . " (" . $sessao['codigo_usuario'] . ")</td>";
            echo "<td>" . htmlspecialchars(substr($sessao['session_id'], 0, 20)) . "...</td>";
            echo "<td>" . htmlspecialchars($sessao['ip_address']) . "</td>";
            echo "<td>" . $sessao['last_activity'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhuma sessão ativa encontrada.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erro:</strong> " . $e->getMessage() . "</p>";
}
?>
