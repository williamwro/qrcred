<?php
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('America/Araguaina');

// Verificar se a extensão mbstring está disponível
if (!extension_loaded('mbstring')) {
    die('Erro: A extensão mbstring do PHP é necessária para este relatório.');
}

// Verificar se a extensão zip está disponível
if (!extension_loaded('zip')) {
    die('Erro: A extensão ZIP do PHP é necessária para gerar múltiplos PDFs.');
}

include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mes_atual = $_POST['mes_atual'];
$divisao = $_POST['divisao'];
$divisao_nome = $_POST['divisao_nome'];
$parcela = isset($_POST['parcela']) ? $_POST['parcela'] : '';
$empregador = isset($_POST['empregador']) ? $_POST['empregador'] : '';

require('../components/fpdf/fpdf.php');

// Criar diretório temporário para os PDFs
$temp_dir = sys_get_temp_dir() . '/qrcred_pdfs_' . time();
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// Buscar todos os convênios
$query = "SELECT DISTINCT E.codigo, E.razaosocial
          FROM sind.conta AS C 
          INNER JOIN sind.empregador as S ON C.empregador = S.id
          INNER JOIN sind.divisao as D ON S.id_divisao = D.id_divisao
          INNER JOIN sind.convenio AS E ON C.convenio = E.codigo
          WHERE C.mes = :mes
            AND D.id_divisao = :divisao
            AND (C.aprovado = true OR C.aprovado IS NULL)";

if (!empty($empregador)) {
    $query .= " AND C.empregador = :empregador";
}
if (!empty($parcela)) {
    $query .= " AND C.parcela = :parcela";
}

$query .= " ORDER BY E.razaosocial";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':mes', $mes_atual, PDO::PARAM_STR);
$stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
if (!empty($empregador)) {
    $stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
}
if (!empty($parcela)) {
    $stmt->bindParam(':parcela', $parcela, PDO::PARAM_INT);
}
$stmt->execute();
$convenios = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($convenios)) {
    die('Nenhum convênio encontrado para o período selecionado.');
}

// Incluir a classe PDF do gerador principal
require_once('producao_gerador_pdf.php');

// Gerar um PDF para cada convênio
$pdf_files = array();
foreach ($convenios as $convenio) {
    $cod_convenio = $convenio['codigo'];
    $razao_social = $convenio['razaosocial'];
    
    // Gerar o PDF usando a mesma lógica do producao_gerador_pdf.php
    // Aqui vamos capturar o output do PDF
    ob_start();
    
    // Simular as variáveis POST para o gerador
    $_POST['mes_atual'] = $mes_atual;
    $_POST['cod_convenio'] = $cod_convenio;
    $_POST['divisao'] = $divisao;
    $_POST['divisao_nome'] = $divisao_nome;
    if (!empty($empregador)) {
        $_POST['empregador'] = $empregador;
    }
    if (!empty($parcela)) {
        $_POST['parcela'] = $parcela;
    }
    
    // Incluir e executar o gerador de PDF
    // Nota: Isso requer que o producao_gerador_pdf.php seja modificável ou que copiemos a lógica aqui
    
    // Por enquanto, vamos criar um nome de arquivo limpo
    $nome_arquivo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $razao_social);
    $nome_arquivo = substr($nome_arquivo, 0, 50); // Limitar tamanho
    $nome_arquivo = $nome_arquivo . '_' . $mes_atual . '.pdf';
    
    $pdf_path = $temp_dir . '/' . $nome_arquivo;
    
    // Aqui você precisaria gerar o PDF real
    // Como não podemos incluir o producao_gerador_pdf.php diretamente (ele faz output),
    // vamos precisar de uma abordagem diferente
    
    $pdf_files[] = array(
        'path' => $pdf_path,
        'name' => $nome_arquivo,
        'convenio' => $cod_convenio
    );
}

// Criar arquivo ZIP
$zip_filename = 'Relatorios_Producao_' . $mes_atual . '_' . date('YmdHis') . '.zip';
$zip_path = $temp_dir . '/' . $zip_filename;

$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
    die('Erro ao criar arquivo ZIP');
}

// Adicionar cada PDF ao ZIP
foreach ($pdf_files as $pdf_info) {
    if (file_exists($pdf_info['path'])) {
        $zip->addFile($pdf_info['path'], $pdf_info['name']);
    }
}

$zip->close();

// Enviar o ZIP para download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
header('Content-Length: ' . filesize($zip_path));
header('Pragma: no-cache');
header('Expires: 0');

readfile($zip_path);

// Limpar arquivos temporários
foreach ($pdf_files as $pdf_info) {
    if (file_exists($pdf_info['path'])) {
        unlink($pdf_info['path']);
    }
}
if (file_exists($zip_path)) {
    unlink($zip_path);
}
rmdir($temp_dir);

exit;
