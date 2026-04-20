<?php
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('America/Araguaina');

// Verificar se a extensão mbstring está disponível
if (!extension_loaded('mbstring')) {
    die('Erro: A extensão mbstring do PHP é necessária para este relatório.');
}

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

// Receber data de vencimento do formulário ou gerar automaticamente
if (isset($_POST['data_vencimento']) && !empty($_POST['data_vencimento'])) {
    $data_venc_input = $_POST['data_vencimento'];
    $data_parts = explode('-', $data_venc_input);
    if (count($data_parts) == 3) {
        $data_vencimento = sprintf("%02d/%02d/%04d", (int)$data_parts[2], (int)$data_parts[1], (int)$data_parts[0]);
    } else {
        $data_vencimento = $data_venc_input;
    }
} else {
    $data_vencimento = "__/__/____";
}

// Data atual para o fechamento do ofício
$data_atual = date('d \d\e F \d\e Y');
$meses_pt = array(
    'January' => 'janeiro', 'February' => 'fevereiro', 'March' => 'março',
    'April' => 'abril', 'May' => 'maio', 'June' => 'junho',
    'July' => 'julho', 'August' => 'agosto', 'September' => 'setembro',
    'October' => 'outubro', 'November' => 'novembro', 'December' => 'dezembro'
);
$data_atual = str_replace(array_keys($meses_pt), array_values($meses_pt), $data_atual);

require('../components/fpdf/fpdf.php');

class PDF_Oficio_Producao extends FPDF
{
    private static $RS;
    private static $MS;
    private static $DN;
    private static $DATA_VENC;
    private static $EMPRESA_NOME_FANTASIA = '';
    private static $EMPRESA_CNPJ = '';
    private static $PRIMEIRA_PAGINA = true;
    
    public static function setRS($RSL) { self::$RS = $RSL; }
    public static function setMS($MES) { self::$MS = $MES; }
    public static function setDN($DIVISAON) { self::$DN = $DIVISAON; }
    public static function setDataVenc($data) { self::$DATA_VENC = $data; }
    public static function setEmpresaInfo($nomeFantasia, $cnpj) {
        self::$EMPRESA_NOME_FANTASIA = $nomeFantasia;
        self::$EMPRESA_CNPJ = $cnpj;
    }
    
    function Header()
    {
        // Logo
        $this->Image('../../../pictures_site-sind/logo_saspng.png',10,8,18);
        $this->SetFont('Arial','B',11);
        
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding('Ofício Cartão Convênio - Relatório de Utilização e Comprovante de pagamento', 'ISO-8859-1', 'UTF-8'));
        $this->Ln();
        
        $this->Cell(22);
        $this->SetFont('Arial','B',10);
        $this->Write(12, mb_convert_encoding("Convênio: ", 'ISO-8859-1', 'UTF-8') . mb_convert_encoding(self::$RS, 'ISO-8859-1', 'UTF-8'));
        $this->Ln();
        
        $this->Cell(22);
        $this->SetFont('Arial','B',9);
        $this->Write(0, mb_convert_encoding("Mês: ", 'ISO-8859-1', 'UTF-8'));
        $this->SetFont('Arial','B',9);
        $this->Write(0, self::$MS);
        
        $this->Cell(12);
        $this->SetFont('Arial','B',9);
        $this->Write(0, date('d/m/Y'));
        
        $this->Cell(10);
        $this->SetFont('Arial','B',9);
        $this->Write(0, date('H:i:s'));
        
        // Texto introdutório apenas na primeira página
        if (self::$PRIMEIRA_PAGINA) {
            $this->Ln(10);
            $this->SetFont('Arial','',10);
            
            $this->MultiCell(0, 5, mb_convert_encoding("Ilmo. Representante Legal", 'ISO-8859-1', 'UTF-8'));
            $this->Ln(2);
            $this->MultiCell(0, 5, mb_convert_encoding("Prezado (a) Senhor (a),", 'ISO-8859-1', 'UTF-8'));
            $this->Ln(2);
            
            // Texto introdutório com parte em negrito itálico
            $this->SetFont('Arial','',10);
            $this->Write(5, mb_convert_encoding("Ao tempo de cumprimentá-lo (a), ", 'ISO-8859-1', 'UTF-8'));
            $this->SetFont('Arial','BI',10);
            $this->Write(5, mb_convert_encoding("serve do presente para encaminhar o relatório de utilização do convênio em vossa empresa", 'ISO-8859-1', 'UTF-8'));
            $this->SetFont('Arial','',10);
            $this->Write(5, mb_convert_encoding(", sendo:", 'ISO-8859-1', 'UTF-8'));
            $this->Ln();
            
            $this->Ln(8);
            self::$PRIMEIRA_PAGINA = false;
        } else {
            $this->Ln(8);
        }
        
        // Cabeçalho da tabela
        $this->SetFont('Arial','B',7);
        $this->Cell(15, 4, "Registro", 0, 0, 'L');
        $this->Cell(20, 4, mb_convert_encoding("Matrícula", 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->Cell(90, 4, "Nome", 0, 0, 'L');
        $this->Cell(26, 4, "Data", 0, 0, 'L');
        $this->Cell(17, 4, "Hora", 0, 0, 'L');
        $this->Cell(10, 4, "Valor", 0, 0, 'R');
        $this->Cell(23, 4, "Parcela", 0, 0, 'C');
        $this->Ln(5);
        
        // Linha horizontal
        $this->SetLineWidth(0.2);
        $y_pos = $this->GetY();
        $this->Line(11, $y_pos, 203, $y_pos);
        $this->Ln(2);
    }
    
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, self::$DN, 0, 0, 'C');
        $this->SetLineWidth(0.2);
        $this->Line(7, 280, 201, 280);
    }
}

// Buscar informações do convênio
$sql_convenio = "SELECT 
    c.razaosocial,
    c.nomefantasia,
    c.cnpj
FROM sind.convenio c
WHERE c.codigo = :codigo_convenio";

$stmt_convenio = $pdo->prepare($sql_convenio);
$stmt_convenio->execute([':codigo_convenio' => $cod_convenio]);
$convenio = $stmt_convenio->fetch(PDO::FETCH_ASSOC);

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
PDF_Oficio_Producao::setRS($convenio['razaosocial'] ?? '');
PDF_Oficio_Producao::setMS($mes_atual);
PDF_Oficio_Producao::setDN($divisao_nome);
PDF_Oficio_Producao::setDataVenc($data_vencimento);
PDF_Oficio_Producao::setEmpresaInfo(
    $convenio['nomefantasia'] ?? '',
    $convenio['cnpj'] ?? ''
);

$pdf = new PDF_Oficio_Producao();
$pdf->AliasNbPages();
$pdf->AddPage('P');
$pdf->SetFont('Arial','B',7);

$total = 0;
$item_pagina = 0;

while($row = $stmt->fetch()) {
    $item_pagina++;
    if ($item_pagina === 45) {
        $item_pagina = 0;
        $pdf->AddPage('P');
        $pdf->SetFont('Arial','B',7);
    }
    
    $valor = floatval($row['valor']);
    $total += $valor;
    $valor_formatado = number_format($valor, 2, ',', '.');
    $data_formatada = date('d/m/Y', strtotime($row['data']));
    $hora_formatada = substr($row['hora'], 0, 5);
    
    $pdf->Cell(15, 4, $row['lancamento'], 0, 0, 'L');
    $pdf->Cell(20, 4, $row['matricula'], 0, 0, 'L');
    $pdf->Cell(90, 4, mb_convert_encoding($row['associado'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
    $pdf->Cell(25, 4, $data_formatada, 0, 0, 'L');
    $pdf->Cell(17, 4, $hora_formatada, 0, 0, 'L');
    $pdf->Cell(13, 4, $valor_formatado, 0, 0, 'R');
    $pdf->Cell(23, 4, $row['parcela'], 0, 1, 'C');
}

// Total
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(150, 10, "TOTAL: ", 0, 0, 'R');
$pdf->Cell(18, 10, number_format($total, 2, ',', '.'), 0, 1, 'R');

// Texto de fechamento do ofício
$pdf->Ln(5);
$pdf->SetFont('Arial','',10);
$pdf->Write(5, mb_convert_encoding("Encaminha ainda, em anexo, ", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','BI',10);
$pdf->Write(5, mb_convert_encoding("o comprovante de repasse destes gastos na conta previamente indicada.", 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial','',10);
$pdf->Ln();
$pdf->Ln(3);

$pdf->MultiCell(0, 5, mb_convert_encoding("Sem mais, reiteramos os préstimos de elevada estima, ficando a disposição para qualquer divergência de valores.", 'ISO-8859-1', 'UTF-8'), 0, 'J');
$pdf->Ln(5);

$pdf->MultiCell(0, 5, mb_convert_encoding("Alphaville, Barueri/SP, " . $data_atual, 'ISO-8859-1', 'UTF-8'));
$pdf->Ln(5);

$pdf->MultiCell(0, 5, mb_convert_encoding("Atenciosamente,", 'ISO-8859-1', 'UTF-8'));

// Forçar download com nome: Oficio-NomeEmpresa-Mes-Data.pdf
$data_atual_arquivo = date('d-m-Y');
$nome_empresa = $convenio['nomefantasia'] ?? $convenio['razaosocial'] ?? 'Convenio';
$nome_arquivo = "Oficio-{$nome_empresa}-{$mes_atual}-{$data_atual_arquivo}.pdf";
// Limpar nome do arquivo de caracteres inválidos
$nome_arquivo = preg_replace('/[^A-Za-z0-9\-_.]/', '_', $nome_arquivo);
$pdf->Output('D', $nome_arquivo);
?>
