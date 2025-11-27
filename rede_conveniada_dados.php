<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'functions.php';
include "Adm/php/banco.php";

try {
    $pdo = Banco::conectar_postgres();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql_profissionais = $pdo->query("SELECT 
        c.razaosocial AS convenio_nome,
        e.nome_especialidade AS especialidade,
        p.nome_profissional AS profissional,
        COALESCE(te.nome_tipo, 'Não informado') AS tipo_especialidade
    FROM 
        sind.convenio_especialidades ce
    JOIN 
        sind.convenio c ON ce.cod_convenio = c.codigo
    JOIN 
        sind.profissionais p ON ce.cod_profissional = p.id_profissional
    JOIN 
        sind.profissionais_especialidade pe ON p.id_profissional = pe.id_profissional
    JOIN 
        sind.especialidade e ON pe.id_especialidade = e.id_especialidade
    LEFT JOIN 
        sind.tipo_especialidade te ON e.id_tipo_especialidade = te.id_tipo_especialidade
    WHERE 
        c.lista_site = true 
        AND c.codigo IS NOT NULL
    ORDER BY 
        c.razaosocial, e.nome_especialidade, p.nome_profissional;");

    $data = array();
    while ($row = $sql_profissionais->fetch(PDO::FETCH_ASSOC)) {
        $data[] = array(
            'convenio' => $row['convenio_nome'],
            'especialidade' => $row['especialidade'],
            'profissional' => $row['profissional'],
            'tipo_especialidade' => $row['tipo_especialidade']
        );
    }

    echo json_encode(array("data" => $data));

} catch (Exception $e) {
    echo json_encode(array(
        "error" => "Erro ao conectar com o banco de dados: " . $e->getMessage(),
        "data" => array()
    ));
}
?> 