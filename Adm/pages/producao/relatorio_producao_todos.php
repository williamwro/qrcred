<?php
/**
 * Relatório de Produção - Todos os Convênios em 1 PDF
 * Gera um único PDF com todos os convênios em páginas separadas
 */

ob_start();
ob_clean();

date_default_timezone_set('America/Araguaina');

include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mes_atual = $_POST['mes_atual'];
$divisao = $_POST['divisao'];
$divisao_nome = $_POST['divisao_nome'];
$parcela = isset($_POST['parcela']) ? $_POST['parcela'] : '';
$empregador = isset($_POST['empregador']) ? $_POST['empregador'] : '';

require("../components/fpdf/fpdf.php");

class PDF_TODOS extends FPDF
{
    private static $MS;
    private static $DN;
    private static $CONVENIO_ATUAL;
    private static $CONVENIO_NOME_FANTASIA = '';
    private static $CONVENIO_CNPJ = '';
    
    public static function setMS($MES) { self::$MS = $MES; }
    public static function setDN($DIVISAON) { self::$DN = $DIVISAON; }
    public static function setConvenioAtual($razao) { self::$CONVENIO_ATUAL = $razao; }
    public static function setConvenioInfo($nomeFantasia, $cnpj) {
        self::$CONVENIO_NOME_FANTASIA = $nomeFantasia;
        self::$CONVENIO_CNPJ = $cnpj;
    }
    
    function Header()
    {
        $this->Image('../../../pictures_site-sind/logo_saspng.png',10,8,18);
        $this->SetFont('Arial','B',12);
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding('Relatório de Produção - Todos os Convênios', 'ISO-8859-1', 'UTF-8'));
        $this->Cell(22);
        $this->Write(0, date('d/m/Y') . " - " . date('H:i:s'));
        $this->Ln(6);
        
        $this->SetFont('Arial','B',10);
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding('Mês: ', 'ISO-8859-1', 'UTF-8') . self::$MS);
        $this->Ln(5);
        
        $this->SetFont('Arial','B',11);
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding('Convênio: ', 'ISO-8859-1', 'UTF-8') . mb_convert_encoding(self::$CONVENIO_ATUAL, 'ISO-8859-1', 'UTF-8'));
        $this->Ln(5);
        
        if (!empty(self::$CONVENIO_NOME_FANTASIA)) {
            $this->Cell(22);
            $this->Write(0, mb_convert_encoding('Nome Fantasia: ', 'ISO-8859-1', 'UTF-8') . mb_convert_encoding(self::$CONVENIO_NOME_FANTASIA, 'ISO-8859-1', 'UTF-8'));
            $this->Ln(5);
        }
        
        if (!empty(self::$CONVENIO_CNPJ)) {
            $this->Cell(22);
            $this->Write(0, mb_convert_encoding('CNPJ: ', 'ISO-8859-1', 'UTF-8') . mb_convert_encoding(self::$CONVENIO_CNPJ, 'ISO-8859-1', 'UTF-8'));
            $this->Ln(5);
        }
        
        $this->Ln(3);
        
        $this->SetFont('Arial','B',8);
        $this->Cell(15, 4, "Registro", 0, 0, 'L');
        $this->Cell(20, 4, mb_convert_encoding("Matrícula", 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->Cell(90, 4, "Nome", 0, 0, 'L');
        $this->Cell(26, 4, "Data", 0, 0, 'L');
        $this->Cell(17, 4, "Hora", 0, 0, 'L');
        $this->Cell(10, 4, "Valor", 0, 0, 'R');
        $this->Cell(23, 4, "Parcela", 0, 0, 'C');
        $this->Ln(5);
        
        $this->SetLineWidth(0.2);
        $this->Line(11, $this->GetY(), 206, $this->GetY());
        $this->Ln(2);
    }
    
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, self::$DN . ' - Página ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Buscar lista de convênios com produção no mês
$sql_convenios = "SELECT DISTINCT 
    c.codigo, 
    c.razaosocial,
    c.nomefantasia,
    c.cnpj
FROM sind.convenio c
INNER JOIN sind.conta ct ON c.codigo = ct.convenio
INNER JOIN sind.empregador e ON ct.empregador = e.id
WHERE ct.mes = :mes_atual
  AND e.id_divisao = :divisao
  AND (ct.aprovado = true OR ct.aprovado IS NULL)";

if (!empty($empregador)) {
    $sql_convenios .= " AND e.id = :empregador";
}

if (!empty($parcela)) {
    $sql_convenios .= " AND LEFT(ct.parcela, 2) = :parcela";
}

$sql_convenios .= " ORDER BY c.razaosocial ASC";

$stmt_conv = $pdo->prepare($sql_convenios);
$stmt_conv->bindParam(':mes_atual', $mes_atual, PDO::PARAM_STR);
$stmt_conv->bindParam(':divisao', $divisao, PDO::PARAM_INT);

if (!empty($empregador)) {
    $stmt_conv->bindParam(':empregador', $empregador, PDO::PARAM_INT);
}

if (!empty($parcela)) {
    $stmt_conv->bindParam(':parcela', $parcela, PDO::PARAM_STR);
}

$stmt_conv->execute();
$convenios = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);

// Configurar PDF
PDF_TODOS::setMS($mes_atual);
PDF_TODOS::setDN($divisao_nome);

$pdf = new PDF_TODOS();
$pdf->AliasNbPages();

$total_geral = 0;

// Processar cada convênio
foreach ($convenios as $conv) {
    PDF_TODOS::setConvenioAtual($conv['razaosocial']);
    PDF_TODOS::setConvenioInfo(
        $conv['nomefantasia'] ?? '',
        $conv['cnpj'] ?? ''
    );
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 8);
    
    // Query para buscar dados de produção do convênio
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
    $stmt->bindParam(':cod_convenio', $conv['codigo'], PDO::PARAM_INT);
    $stmt->bindParam(':mes_atual', $mes_atual, PDO::PARAM_STR);
    $stmt->bindParam(':divisao', $divisao, PDO::PARAM_INT);
    
    if (!empty($empregador)) {
        $stmt->bindParam(':empregador', $empregador, PDO::PARAM_INT);
    }
    
    if (!empty($parcela)) {
        $stmt->bindParam(':parcela', $parcela, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    
    $total_convenio = 0;
    $item_pagina = 0;
    
    while($row = $stmt->fetch()) {
        $item_pagina++;
        if ($item_pagina === 60) {
            $item_pagina = 0;
            $pdf->AddPage();
        }
        
        $valor = floatval($row['valor']);
        $total_convenio += $valor;
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
    
    // Total do convênio
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(150, 8, "TOTAL " . mb_convert_encoding($conv['razaosocial'], 'ISO-8859-1', 'UTF-8') . ": ", 0, 0, 'R');
    $pdf->Cell(18, 8, number_format($total_convenio, 2, ',', '.'), 0, 1, 'R');
    
    $total_geral += $total_convenio;
}

// Total geral
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, mb_convert_encoding('RESUMO GERAL', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(150, 10, "TOTAL GERAL: ", 0, 0, 'R');
$pdf->Cell(30, 10, number_format($total_geral, 2, ',', '.'), 1, 1, 'R');

if (ob_get_length()) {
    ob_end_clean();
}

$pdf->Output('I', "Producao_Todos_Convenios_{$mes_atual}_{$divisao_nome}.pdf");
?>
