<?PHP
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
session_start();
$userconv="";
$passconv="";
include "Adm/php/banco.php";
$username = trim($_POST['username']); // Remove espaços em branco do início e fim do usuário
$passuser = $_POST['password'];
$_SESSION["user_name"]=$username;
$cod_convenio = 0;
$codigo = 0;
$existe_senha = false;
$std = new stdClass();
$stmt = new stdClass();
$stmt2 = new stdClass();
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$data2 = new DateTime();
$data = $data2->format('Y-m-d H:i:s');
if (isset($_POST['username']) && isset($_POST['password'])){
    // VERIFICA SENHA ******************************************************************************************************************************************************
    $stmt = $pdo->prepare("SELECT codigo,senha,email FROM sind.usuarios WHERE username = :username");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll();
    //$rs = $stmt->rowCount();
    foreach ($result as $row) {
        $codigo_usuario = $row["codigo"];
    }

    $senha_crypto = sha1($passuser);
    $stmt_senha = $pdo->prepare("SELECT * FROM sind.usuarios WHERE senha = :senha AND username = :username");
    $stmt_senha->bindParam(':senha', $senha_crypto, PDO::PARAM_STR);
    $stmt_senha->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt_senha->execute();
    while($row = $stmt_senha->fetch()) {
        $existe_senha = true;
    }
    if($existe_senha) {
        $stmt_conv_senha = $pdo->prepare("SELECT usuarios.codigo, usuarios.username, usuarios.password, 
                                                usuarios.senha, usuarios.email, usuarios.lastname, 
                                                usuarios.situacao, usuarios.nome, usuarios.divisao, 
                                                divisao.nome AS divisao_nome, divisao.descricao
        FROM sind.divisao RIGHT JOIN sind.usuarios ON divisao.id_divisao = usuarios.divisao WHERE usuarios.username = :username AND usuarios.senha = :senha");
        $stmt_conv_senha->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt_conv_senha->bindParam(':senha', $senha_crypto, PDO::PARAM_STR);
        $stmt_conv_senha->execute();
        while ($row = $stmt_conv_senha->fetch()) {
            $codigo = $row["codigo"];
            $std->tipo_login = "login sucesso";
            $std->codigo = $codigo;
            $std->Username = $row["username"];
            $std->senha = $passuser;
            $std->nome = $row["nome"];
            $std->divisao = $row["divisao"];
            $std->descricao = $row["descricao"];
            $std->divisao_nome = $row["divisao_nome"];
            
            // SEGURANÇA MULTI-TENANT: Armazenar divisão em $_SESSION
            $_SESSION['usuario_cod'] = $codigo;
            $_SESSION['divisao'] = $row["divisao"];
            $_SESSION['user_name'] = $username;
            
            if($row["situacao"] == 2){
                $std->tipo_login = "login bloqueado";
                session_unset();
                session_destroy();
            }
        }

        $std->card1 = "123139";
        $std->nomecard1 = "MARCIO HENRIQUE DE SOUZA";
        $std->card2 = "173577";
        $std->nomecard2 = "MARCIA HELENA MORAES";
        $std->card3 = "800030";
        $std->nomecard3 = "WILLIAM R OLIVEIRA";
        $std->card4 = "145630";
        $std->nomecard4 = "CLAUDIO BORGES DO ESPIRITO SANTO";
        $std->card5 = "163816";
        $std->nomecard5 = "VITOR LUCIO DA SILVA";
        $std->card6 = "195847";
        $std->nomecard6 = "ANA PAULA ALVES";
        
        if ($codigo == 0) {
            $codigo = 0;
            $std->tipo_login = "login inativo";
            $std->codigo = $codigo;
            $std->Username = "";
            $std->nome = "";
            $std->divisao = 0;
            $std->divisao_nome = "";
            session_unset();
            session_destroy();
        } else {
            // Atualizar ultimo_acesso quando o login é bem-sucedido
            try {
                $usuario_ip = $_SERVER['REMOTE_ADDR'] ?? 
                              $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
                              $_SERVER['HTTP_X_REAL_IP'] ?? 
                              'unknown';
                
                // Iniciar sessão PHP para obter session_id
                $php_session_id = session_id();
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                
                // Verificar se as colunas existem, se não, criar
                $checkColumn = $pdo->prepare("
                    SELECT column_name 
                    FROM information_schema.columns 
                    WHERE table_schema = 'sind' 
                    AND table_name = 'usuarios' 
                    AND column_name = 'ultimo_acesso'
                ");
                $checkColumn->execute();
                $columnExists = $checkColumn->fetch();
                
                if (!$columnExists) {
                    $pdo->exec("ALTER TABLE sind.usuarios ADD COLUMN ultimo_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                    $pdo->exec("ALTER TABLE sind.usuarios ADD COLUMN ip_ultimo_acesso VARCHAR(45)");
                }
                
                // Atualizar ultimo_acesso
                $updateStmt = $pdo->prepare("
                    UPDATE sind.usuarios 
                    SET ultimo_acesso = NOW(), ip_ultimo_acesso = :ip
                    WHERE codigo = :codigo
                ");
                $updateStmt->execute([
                    ':codigo' => $codigo,
                    ':ip' => $usuario_ip
                ]);
                
                // Verificar se a tabela de sessões ativas existe
                $checkTable = $pdo->prepare("
                    SELECT table_name 
                    FROM information_schema.tables 
                    WHERE table_schema = 'sind' 
                    AND table_name = 'sessoes_ativas'
                ");
                $checkTable->execute();
                $tableExists = $checkTable->fetch();
                
                if ($tableExists) {
                    // FORÇAR: Desativar TODAS as sessões antigas do usuário antes de criar nova
                    $forceCleanup = $pdo->prepare("
                        UPDATE sind.sessoes_ativas 
                        SET is_active = false 
                        WHERE codigo_usuario = :codigo AND is_active = true
                    ");
                    $forceCleanup->execute([':codigo' => $codigo]);
                    $cleaned = $forceCleanup->rowCount();
                    error_log("FORÇA LIMPEZA LOGIN: $cleaned sessões antigas desativadas para usuário $codigo");
                    
                    // Verificar se já existe uma sessão ativa para este usuário e session_id
                    $checkSession = $pdo->prepare("
                        SELECT id FROM sind.sessoes_ativas 
                        WHERE codigo_usuario = :codigo AND session_id = :session_id
                    ");
                    $checkSession->execute([
                        ':codigo' => $codigo,
                        ':session_id' => $php_session_id
                    ]);
                    
                    if ($checkSession->fetch()) {
                        // Atualizar sessão existente
                        $sessionStmt = $pdo->prepare("
                            UPDATE sind.sessoes_ativas 
                            SET last_activity = NOW(), is_active = true, ip_address = :ip, user_agent = :user_agent
                            WHERE codigo_usuario = :codigo AND session_id = :session_id
                        ");
                        $sessionStmt->execute([
                            ':codigo' => $codigo,
                            ':session_id' => $php_session_id,
                            ':ip' => $usuario_ip,
                            ':user_agent' => $user_agent
                        ]);
                        error_log("Sessão atualizada no login: usuário $codigo, session $php_session_id");
                    } else {
                        // Inserir nova sessão ativa usando session_id do PHP
                        // (limpeza já foi feita acima)
                        $sessionStmt = $pdo->prepare("
                            INSERT INTO sind.sessoes_ativas 
                            (codigo_usuario, session_id, ip_address, user_agent, login_time, last_activity, is_active)
                            VALUES (:codigo, :session_id, :ip, :user_agent, NOW(), NOW(), true)
                        ");
                        $sessionStmt->execute([
                            ':codigo' => $codigo,
                            ':session_id' => $php_session_id,
                            ':ip' => $usuario_ip,
                            ':user_agent' => $user_agent
                        ]);
                        error_log("Nova sessão criada no login: usuário $codigo, session $php_session_id");
                    }
                }
                
                // Delay maior para servidor de produção
                usleep(500000); // 0.5 segundo - aumentado para produção
                
                // Verificar se a sessão foi criada corretamente
                $verify = $pdo->prepare("
                    SELECT is_active FROM sind.sessoes_ativas 
                    WHERE codigo_usuario = :codigo AND session_id = :session_id
                ");
                $verify->execute([
                    ':codigo' => $codigo,
                    ':session_id' => $php_session_id
                ]);
                $sessionStatus = $verify->fetch();
                
                if ($sessionStatus) {
                    error_log("Verificação pós-criação: sessão ativa = " . ($sessionStatus['is_active'] ? 'true' : 'false'));
                } else {
                    error_log("AVISO: Sessão não encontrada após criação!");
                }
                
                // Adicionar session_id ao objeto de resposta para o JavaScript usar
                $std->session_id = $php_session_id;
                
            } catch (Exception $e) {
                // Log do erro mas não interrompe o login
                error_log("Erro ao atualizar ultimo_acesso no login: " . $e->getMessage());
            }
        }
    }else{
        $codigo           = 0;
        $std->tipo_login  = "login incorreto";
        $std->codigo      = $codigo;
        $std->Username    = "";
        $std->divisao     = 0;
        $std->divisao_nome = "";
        session_unset();
        session_destroy();
    }
}else{
    $codigo           = 0;
    $std->tipo_login  = "login vazio";
    $std->codigo      = $codigo;
    $std->Username    = "";
    $std->divisao     = 0;
    $std->divisao_nome = "";
    session_unset();
    session_destroy();
}

$stmt->execute();
$resultado = $std->tipo_login;
$sql2 = "INSERT INTO sind.usuarios_log(";
$sql2 .= "cod_usuario,data,resultado) ";
$sql2 .= "VALUES(:cod_usuario,:data,:resultado)";
$stmt2 = $pdo->prepare($sql2);
$stmt2->bindParam(':cod_usuario', $codigo_usuario, PDO::PARAM_INT);
$stmt2->bindParam(':data', $data, PDO::PARAM_STR);
$stmt2->bindParam(':resultado',  $resultado, PDO::PARAM_STR);
$stmt2->execute();

echo json_encode($std);