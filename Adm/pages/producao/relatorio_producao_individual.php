<?php
/**
 * Relatório de Produção - PDF Individual por Empregador
 * Gera um PDF individual para download com nome do empregador
 */

ob_start();
ob_clean();

date_default_timezone_set('America/Araguaina');

include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mes_atual = $_POST['mes_atual'];
$cod_convenio = $_POST['cod_convenio'];
$empregador = isset($_POST['empregador']) ? $_POST['empregador'] : '';
$divisao = $_POST['divisao'];
$divisao_nome = $_POST['divisao_nome'];
$parcela = isset($_POST['parcela']) ? $_POST['parcela'] : '';

require("../components/fpdf/fpdf.php");

class PDF_INDIVIDUAL extends FPDF
{
    private static $RS;
    private static $MS;
    private static $DN;
    private static $EMPREGADOR_NOME;
    private static $EMPRESA_NOME_FANTASIA = '';
    private static $EMPRESA_CNPJ = '';
    
    public static function setRS($RSL) { self::$RS = $RSL; }
    public static function setMS($MES) { self::$MS = $MES; }
    public static function setDN($DIVISAON) { self::$DN = $DIVISAON; }
    public static function setEmpregadorNome($nome) { self::$EMPREGADOR_NOME = $nome; }
    public static function setEmpresaInfo($nomeFantasia, $cnpj) {
        self::$EMPRESA_NOME_FANTASIA = $nomeFantasia;
        self::$EMPRESA_CNPJ = $cnpj;
    }
    
    function Header()
    {
        $this->Image('../../../pictures_site-sind/logo_saspng.png',10,8,18);
        $this->SetFont('Arial','B',12);
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding('Relatório de Produção', 'ISO-8859-1', 'UTF-8'));
        $this->Cell(22);
        $this->Write(0, date('d/m/Y') . " - " . date('H:i:s'));
        $this->Ln(6);
        
        $this->SetFont('Arial','B',10);
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding('Razão Social: ', 'ISO-8859-1', 'UTF-8') . mb_convert_encoding(self::$RS, 'ISO-8859-1', 'UTF-8'));
        $this->Ln(5);
        
        if (!empty(self::$EMPRESA_NOME_FANTASIA)) {
            $this->Cell(22);
            $this->Write(0, mb_convert_encoding('Nome Fantasia: ', 'ISO-8859-1', 'UTF-8') . mb_convert_encoding(self::$EMPRESA_NOME_FANTASIA, 'ISO-8859-1', 'UTF-8'));
            $this->Ln(5);
        }
        
        if (!empty(self::$EMPRESA_CNPJ)) {
            $this->Cell(22);
            $this->Write(0, mb_convert_encoding('CNPJ: ', 'ISO-8859-1', 'UTF-8') . mb_convert_encoding(self::$EMPRESA_CNPJ, 'ISO-8859-1', 'UTF-8'));
            $this->Ln(5);
        }
        
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding("Mês: ", 'ISO-8859-1', 'UTF-8') . self::$MS);
        $this->Ln(5);
        
        $this->SetFont('Arial','B',11);
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding("Empregador: ", 'ISO-8859-1', 'UTF-8') . mb_convert_encoding(self::$EMPREGADOR_NOME, 'ISO-8859-1', 'UTF-8'));
        
        $this->Ln(7);
        $this->SetLineWidth(0.2);
        $this->Line(11, $this->GetY(), 200, $this->GetY());
        $this->Ln(4);
        
        $this->SetFont('Arial','B',8);
        $this->Cell(15, -6, "Registro", 0, 0, 'L');
        $this->Cell(20, -6, mb_convert_encoding("Matrícula", 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->Cell(90, -6, "Nome", 0, 0, 'L');
        $this->Cell(26, -6, "Data", 0, 0, 'L');
        $this->Cell(17, -6, "Hora", 0, 0, 'L');
        $this->Cell(10, -6, "Valor", 0, 0, 'R');
        $this->Cell(23, -6, "Parcela", 0, 0, 'C');
        $this->Ln(0);
    }
    
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, self::$DN . ' - Página ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Buscar informações da empresa
$sql_empresa = "SELECT 
    c.razaosocial as nome_fantasia,
    c.nomefantasia,
    c.cnpj
FROM sind.convenio c
WHERE c.codigo = :codigo_convenio";

$stmt_empresa = $pdo->prepare($sql_empresa);
$stmt_empresa->execute([':codigo_convenio' => $cod_convenio]);
$empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC);

// Buscar nome do empregador (se fornecido)
$empregador_nome = 'Todos os Empregadores';
if (!empty($empregador)) {
    $sql_emp = "SELECT nome FROM sind.empregador WHERE id = :empregador";
    $stmt_emp = $pdo->prepare($sql_emp);
    $stmt_emp->execute([':empregador' => $empregador]);
    $empregador_info = $stmt_emp->fetch(PDO::FETCH_ASSOC);
    $empregador_nome = $empregador_info['nome'] ?? 'Empregador';
}

// Query para buscar dados de produção
$query = "SELECT 
    conta.lancamento, 
    conta.associado AS matricula, 
    conta.valor, 
    conta.data, 
    conta.hora, 
    associado.nome AS associado, 
    conta.parcela
FROM sind.associado 
RIGHT JOIN (sind.empregador 
RIGHT JOIN (sind.convenio 
RIGHT JOIN sind.conta ON convenio.codigo = conta.convenio) 
ON empregador.id = conta.empregador) 
ON associado.codigo = conta.associado AND associado.empregador = conta.empregador
WHERE convenio.codigo = :cod_convenio 
  AND conta.mes = :mes_atual
  AND empregador.id_divisao = :divisao
  AND (conta.aprovado = true OR conta.aprovado IS NULL)";

if (!empty($empregador)) {
    $query .= " AND empregador.id = :empregador";
}

if (!empty($parcela)) {
    $query .= " AND LEFT(conta.parcela, 2) = :parcela";
}

$query .= " ORDER BY associado.nome ASC";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':cod_convenio', $cod_convenio, PDO::PARAM_INT);
$stmt->bindParam(':mes_atual', $mes_atual, PDO::PARAM_STR);
$stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);

if (!empty($empregador)) {
    $stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
}

if (!empty($parcela)) {
    $stmt->bindParam(':parcela', $parcela, PDO::PARAM_STR);
}

$stmt->execute();

// Configurar PDF
PDF_INDIVIDUAL::setRS($empresa['nome_fantasia'] ?? '');
PDF_INDIVIDUAL::setMS($mes_atual);
PDF_INDIVIDUAL::setDN($divisao_nome);
PDF_INDIVIDUAL::setEmpregadorNome($empregador_nome);
PDF_INDIVIDUAL::setEmpresaInfo(
    $empresa['nomefantasia'] ?? '',
    $empresa['cnpj'] ?? ''
);

$pdf = new PDF_INDIVIDUAL();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);

$total = 0;
$item_pagina = 0;

while($row = $stmt->fetch()) {
    $item_pagina++;
    if ($item_pagina === 60) {
        $item_pagina = 0;
        $pdf->AddPage();
    }
    
    $valor = floatval($row['valor']);
    $total += $valor;
    $valor_formatado = number_format($valor, 2, ',', '.');
    $data_formatada = date('d/m/Y', strtotime($row['data']));
    $hora_formatada = substr($row['hora'], 0, 5);
    
    $pdf->Cell(15, 4, $row['lancamento']);
    $pdf->Cell(20, 4, $row['matricula']);
    $pdf->Cell(90, 4, mb_convert_encoding($row['associado'], 'ISO-8859-1', 'UTF-8'));
    $pdf->Cell(25, 4, $data_formatada);
    $pdf->Cell(17, 4, $hora_formatada);
    $pdf->Cell(13, 4, $valor_formatado, '', '', 'R');
    $pdf->Cell(23, 4, $row['parcela'], '', '', 'C');
    $pdf->Ln();
}

// Total
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 10, "TOTAL: ", 0, 0, 'R');
$pdf->Cell(18, 10, number_format($total, 2, ',', '.'), 0, 0, 'R');

if (ob_get_length()) {
    ob_end_clean();
}

// Nome do arquivo com nome do empregador
$empregador_nome_arquivo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $empregador_nome);
$empregador_nome_arquivo = substr($empregador_nome_arquivo, 0, 50); // Limitar tamanho
$timestamp = date('YmdHis');

$pdf->Output('D', "{$empregador_nome_arquivo}_{$mes_atual}_{$timestamp}.pdf");
?>
