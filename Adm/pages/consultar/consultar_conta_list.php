<?php
header('Content-Type: application/json; charset=utf-8');
include "../../php/banco.php";
include "../../php/funcoes.php";

$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$someArray = array();
$sub_arrayli = array();
$mes = '';

if (isset($_POST["mes"])) {
    $std = new stdClass();
    $mes = $_POST["mes"];
    $matricula = $_POST["matricula"];
    $codempregador = $_POST["codempregador"];
    $id_associado_origem = isset($_POST["id_associado"]) ? $_POST["id_associado"] : null;
    $id_divisao_origem = isset($_POST["id_divisao"]) ? $_POST["id_divisao"] : null;

    // Obter ID do associado baseado na matrícula e empregador
    $query_associado = "SELECT id FROM sind.associado WHERE codigo = :matricula AND empregador = :empregador";
    if ($id_associado_origem && $id_divisao_origem) {
        $query_associado .= " AND id = :id_associado AND id_divisao = :id_divisao";
    }
    $stmt_associado = $pdo->prepare($query_associado);
    $stmt_associado->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt_associado->bindParam(':empregador', $codempregador, PDO::PARAM_INT);
    if ($id_associado_origem && $id_divisao_origem) {
        $stmt_associado->bindParam(':id_associado', $id_associado_origem, PDO::PARAM_INT);
        $stmt_associado->bindParam(':id_divisao', $id_divisao_origem, PDO::PARAM_INT);
    }
    $stmt_associado->execute();
    $result_associado = $stmt_associado->fetchAll();
    $id_associado = 0;
    foreach ($result_associado as $row) {
        $id_associado = (int)$row["id"];
    }

    if ($mes == 'todos') {
        $sqlmes = "";
    } else {
        $sqlmes = "conta.mes = '" . $mes . "' AND ";
    }

    // Buscar limite do associado
    $sql_lim_saldo = "SELECT codigo, limite, empregador 
                      FROM sind.associado 
                      WHERE id = :id_associado";
    if ($id_divisao_origem) {
        $sql_lim_saldo .= " AND id_divisao = :id_divisao";
    }
    $statments = $pdo->prepare($sql_lim_saldo);
    $statments->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
    if ($id_divisao_origem) {
        $statments->bindParam(':id_divisao', $id_divisao_origem, PDO::PARAM_INT);
    }
    $statments->execute();
    $results = $statments->fetchAll();
    foreach ($results as $row) {
        $sub_arrayli["limite"] = $row['limite'];
        $someArray["limite"] = $sub_arrayli;
    }

    // Buscar registros da conta (sem botões de alterar/excluir)
    $sql = "SELECT DISTINCT 
                conta.associado, 
                conta.valor, 
                empregador.abreviacao, 
                conta.lancamento, 
                conta.data, 
                conta.mes, 
                conta.parcela, 
                empregador.id, 
                empregador.nome, 
                usuarios.username, 
                associado.nome AS nome_associado, 
                convenio.razaosocial, 
                convenio.nomefantasia,
                conta.hora,
                situacao_conta.descri as situacao,
                associado.limite,
                conta.exclui,
                conta.uri_cupom,
                controle.mes as mes_controle,
                associado.id_divisao as id_divisao
            FROM sind.convenio 
            RIGHT JOIN (sind.associado 
            RIGHT JOIN (sind.usuarios 
            RIGHT JOIN (sind.empregador 
            RIGHT JOIN (sind.situacao_conta 
            RIGHT JOIN (
                sind.controle 
                INNER JOIN sind.conta 
                    ON conta.divisao = controle.divisao
                    AND controle.codigo = (
                        SELECT MAX(c2.codigo) 
                        FROM sind.controle c2 
                        WHERE c2.divisao = conta.divisao
                    )
            ) ON conta.id_situacao = situacao_conta.id_situacao OR conta.id_situacao IS NULL
            ) ON empregador.id = conta.empregador
            ) ON usuarios.codigo = conta.Funcionario
            ) ON associado.id = conta.id_associado
            ) ON convenio.codigo = conta.convenio
            WHERE " . $sqlmes . " conta.id_associado = :id_associado AND (conta.aprovado = true OR conta.aprovado IS NULL)";
    
    if ($id_divisao_origem) {
        $sql .= " AND associado.id_divisao = :id_divisao";
    }

    $statment = $pdo->prepare($sql);
    $statment->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
    if ($id_divisao_origem) {
        $statment->bindParam(':id_divisao', $id_divisao_origem, PDO::PARAM_INT);
    }
    $statment->execute();
    $result = $statment->fetchAll();

    foreach ($result as $row) {
        $sub_array = array();

        $sub_array["registro"]        = $row['lancamento'];
        $sub_array["matricula"]       = $row['associado'];
        $sub_array["associado"]       = $row['nome_associado'];
        $sub_array["valor"]           = $row['valor'];
        $sub_array["data"]            = date('d/m/Y', strtotime($row['data']));
        $sub_array["hora"]            = substr($row['hora'], -8);
        $sub_array["mes"]             = $row['mes'];
        if ($row['parcela'] == null) {
            $sub_array["parcela"]     = '';
        } else {
            $sub_array["parcela"]     = $row['parcela'];
        }
        $sub_array["id_empregador"]   = $row['id'];
        $sub_array["nome_empregador"] = $row['nome'];
        $sub_array["razaosocial"]     = $row['razaosocial'];
        $sub_array["nomefantasia"]    = $row['nomefantasia'];
        $sub_array["funcionario"]     = $row['username'];
        $sub_array["situacao"]        = $row['situacao'];
        $sub_array["excluir"]         = $row['exclui'];
        $sub_array["uri_cupom"]       = $row['uri_cupom'];
        
        if ($row['mes_controle'] !== $row['mes']) {
            $sub_array["mes_controle"] = "<span class='label label-success'>Aberto</span>";
        } else {
            $sub_array["mes_controle"] = "<span class='label label-warning'>Fechado</span>";
        }

        $someArray["data"][] = $sub_array;
    }
}

// Soma das categorias
if ($mes != '' && $id_associado > 0) {
    $sql_categorias = "SELECT DISTINCT Sum(conta.valor) AS total, tipoconvenio.nome
                       FROM sind.tipoconvenio RIGHT JOIN 
                           (sind.convenio RIGHT JOIN 
                           (sind.situacao_conta RIGHT JOIN 
                           sind.conta ON 
                           situacao_conta.id_situacao = conta.id_situacao OR conta.id_situacao ISNULL) ON 
                           convenio.codigo = conta.convenio) ON 
                           tipoconvenio.codigo = convenio.tipo
                       WHERE " . $sqlmes . " conta.id_associado = :id_associado AND (conta.aprovado = true OR conta.aprovado IS NULL)
                       GROUP BY convenio.tipo, conta.id_associado, tipoconvenio.nome;";

    $statmentx = $pdo->prepare($sql_categorias);
    $statmentx->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
    $statmentx->execute();
    $resultx = $statmentx->fetchAll();

    $sub_array2 = array();
    $sub_array2["taxacartao"]   = 0;
    $sub_array2["cartao"]       = 0;
    $sub_array2["adiantamento"] = 0;

    foreach ($resultx as $rowx) {
        if ($rowx['nome'] == "TAXA_CARTÃO") {
            $sub_array2["taxacartao"] = $rowx['total'];
        }
        if ($rowx['nome'] == "CARTÃO") {
            $sub_array2["cartao"] = $rowx['total'];
        }
        if ($rowx['nome'] == "ADIANTAMENTO") {
            $sub_array2["adiantamento"] = $rowx['total'];
        }
    }
    $someArray["categorias"] = $sub_array2;

    if ($mes !== 'todos') {
        $mes_posterior = somames_gravar($mes);
        
        // Buscar não descontados
        $sql_ND = "SELECT DISTINCT conta.valor, convenio.codigo
                   FROM sind.tipoconvenio RIGHT JOIN 
                        (sind.convenio RIGHT JOIN 
                        (sind.situacao_conta RIGHT JOIN 
                        sind.conta ON 
                        situacao_conta.id_situacao = conta.id_situacao OR conta.id_situacao ISNULL) ON 
                        convenio.codigo = conta.convenio) ON 
                        tipoconvenio.codigo = convenio.tipo
                   WHERE conta.mes = '" . $mes_posterior . "' AND conta.id_associado = :id_associado AND (conta.aprovado = true OR conta.aprovado IS NULL)";

        $statmentnd = $pdo->prepare($sql_ND);
        $statmentnd->bindParam(':id_associado', $id_associado, PDO::PARAM_INT);
        $statmentnd->execute();
        $resultnd = $statmentnd->fetchAll();

        $sub_arraynd = array();
        $FND = 0;
        $CND = 0;
        $ENDES = 0;
        $DND = 0;
        $sub_arraynd["FND"] = 0;
        $sub_arraynd["CND"] = 0;
        $sub_arraynd["ENDES"] = 0;
        $sub_arraynd["DND"] = 0;

        foreach ($resultnd as $rownd) {
            if ($rownd['codigo'] == 47) { // CND
                $CND = $CND + $rownd['valor'];
            }
            if ($rownd['codigo'] == 48) { // FND
                $FND = $FND + $rownd['valor'];
            }
            if ($rownd['codigo'] == 49) { // END
                $ENDES = $ENDES + $rownd['valor'];
            }
            if ($rownd['codigo'] == 68) { // DND
                $DND = $DND + $rownd['valor'];
            }
        }
        $sub_arraynd["CND"] = $CND;
        $sub_arraynd["FND"] = $FND;
        $sub_arraynd["ENDES"] = $ENDES;
        $sub_arraynd["DND"] = $DND;

        $someArray["naodescontado"] = $sub_arraynd;
    }
}

echo json_encode($someArray, JSON_UNESCAPED_UNICODE);
?>
