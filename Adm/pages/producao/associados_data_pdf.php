<?php
ob_start();
ob_clean();

date_default_timezone_set('America/Araguaina');

include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mes = isset($_POST['mes']) ? $_POST['mes'] : "";
$data_inicial = isset($_POST['data_inicial']) ? $_POST['data_inicial'] : "";
$data_final = isset($_POST['data_final']) ? $_POST['data_final'] : "";
$divisao = $_POST['divisao'];
$divisao_nome = $_POST['divisao_nome'];

require("../components/fpdf/fpdf.php");

class PDF extends FPDF
{
    private static $MS;
    public static function setMS($MES) {
        self::$MS = $MES;
    }
    private static $DI;
    public static function setDI($DATA_INI) {
        self::$DI = $DATA_INI;
    }
    private static $DF;
    public static function setDF($DATA_FIM) {
        self::$DF = $DATA_FIM;
    }
    private static $PG;
    public static function setPG($PAGINA) {
        self::$PG = $PAGINA;
    }
    private static $DN;
    public static function setDN($DIVISAON) {
        self::$DN = $DIVISAON;
    }

    function Header()
    {
        // Logo
        $this->Image('../../../pictures_site-sind/logo_saspng.png',10,8,18);
        
        // Arial bold 12
        $this->SetFont('Arial','B',12);
        
        $this->Cell(22); // move para direita 22 posições
        $this->Write(0,mb_convert_encoding('Relatório de Associados por Data', 'ISO-8859-1', 'UTF-8'));
        
        $this->Cell(22); // move para direita 22 posições
        $this->Write(0,date('d/m/Y')." - ".date('H:i:s'));
        
        $this->Ln(); // pula linha
        $this->Cell(22); // move para direita 22 posições
        
        if (!empty(self::$MS)) {
            $this->Write(12,mb_convert_encoding("Mês: ", 'ISO-8859-1', 'UTF-8').self::$MS);
        } elseif (!empty(self::$DI) && !empty(self::$DF)) {
            $this->Write(12,mb_convert_encoding("Período: ", 'ISO-8859-1', 'UTF-8').self::$DI." a ".self::$DF);
        } else {
            $this->Write(12,mb_convert_encoding("Período: Todos", 'ISO-8859-1', 'UTF-8'));
        }
        
        $this->Ln(); // pula linha
        $this->Cell(22);
        $this->Write(0,"Regional: ".mb_convert_encoding(self::$DN, 'ISO-8859-1', 'UTF-8'));
        
        $this->Cell(175);
        $this->Write(0,"Pagina: ".self::$PG);
        
        $this->Ln(12); // pula linha
        $this->SetFont('Arial','B',8);
        
        $this->Cell(70,-6,"Nome",0,0,'L');
        $this->Cell(25,-6,mb_convert_encoding("Abreviação", 'ISO-8859-1', 'UTF-8'),0,0,'L');
        $this->Cell(90,-6,mb_convert_encoding("Razão Social", 'ISO-8859-1', 'UTF-8'),0,0,'L');
        $this->Cell(20,-6,mb_convert_encoding("Mês", 'ISO-8859-1', 'UTF-8'),0,0,'L');
        $this->Cell(25,-6,"Data",0,0,'L');
        $this->Cell(30,-6,"Total(R$)",0,0,'R');
        
        // Line break
        $this->Ln(0);
        // linha horizontal
        $this->SetLineWidth(0.2);
        $this->Line("10","34","287","34");
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,self::$DN,0,0,'C');
        $this->SetLineWidth(0.2);
        $this->Line("10","195","287","195");
    }
}

$titulo_periodo = "";
if (!empty($mes)) {
    PDF::setMS($mes);
    $titulo_periodo = $mes;
} elseif (!empty($data_inicial) && !empty($data_final)) {
    PDF::setDI(date('d/m/Y', strtotime($data_inicial)));
    PDF::setDF(date('d/m/Y', strtotime($data_final)));
    $titulo_periodo = date('d/m/Y', strtotime($data_inicial))." a ".date('d/m/Y', strtotime($data_final));
} else {
    PDF::setMS("");
    PDF::setDI("");
    PDF::setDF("");
    $titulo_periodo = "TODOS";
}

PDF::setDN($divisao_nome);

$query = "SELECT assoc.nome, emp.abreviacao,
          div.nome AS estabelecimento,
          TO_CHAR(c.data, 'DD/MM/YYYY') AS data,
          c.mes,
          COALESCE(c.valor, 0) AS total,
          conv.razaosocial,
          conv.nomefantasia
          FROM sind.associado assoc
          INNER JOIN sind.empregador emp ON emp.id = assoc.empregador
          INNER JOIN sind.divisao div ON div.id_divisao = assoc.id_divisao
          INNER JOIN sind.conta c ON c.associado = assoc.codigo AND c.empregador = assoc.empregador
          INNER JOIN sind.convenio conv ON conv.codigo = c.convenio
          WHERE assoc.id_divisao = :divisao";

$params = array(':divisao' => $divisao);

if (!empty($mes)) {
    $query .= " AND c.mes = :mes";
    $params[':mes'] = $mes;
}

if (!empty($data_inicial) && !empty($data_final)) {
    $query .= " AND c.data BETWEEN :data_inicial AND :data_final";
    $params[':data_inicial'] = $data_inicial;
    $params[':data_final'] = $data_final;
}

$query .= " ORDER BY assoc.nome ASC, c.data DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);

$pagina = 1;
PDF::setPG($pagina);
$pdf = new PDF('L','mm','A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);
$item_pagina = 0;
$total_registros = 0;
$soma_total = 0;

while($row = $stmt->fetch()) {
    $item_pagina++;
    $total_registros++;
    $soma_total += $row['total'];
    
    if ($item_pagina > 31) {
        $pagina = $pagina + 1;
        $item_pagina = 1;
        PDF::setPG($pagina);
        $pdf->AddPage();
    }
    
    $valor_formatado = number_format($row['total'], 2, ',', '.');
    
    $pdf->Cell(70, 5, mb_convert_encoding($row['nome'], 'ISO-8859-1', 'UTF-8'));
    $pdf->Cell(25, 5, $row['abreviacao']);
    $pdf->Cell(90, 5, mb_convert_encoding($row['razaosocial'], 'ISO-8859-1', 'UTF-8'));
    $pdf->Cell(20, 5, $row['mes']);
    $pdf->Cell(25, 5, $row['data']);
    $pdf->Cell(30, 5, $valor_formatado, 0, 0, 'R');
    $pdf->Ln();
}

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 10, "TOTAL DE REGISTROS: ".$total_registros, 0, 0, 'L');
$pdf->Ln();
$soma_formatada = number_format($soma_total, 2, ',', '.');
$pdf->Cell(230, 10, "TOTAL GERAL:", 0, 0, 'R');
$pdf->Cell(30, 10, "R$ ".$soma_formatada, 0, 0, 'R');

if (ob_get_length()) {
    ob_end_clean();
}

$pdf->Output('I',"Associados_".$titulo_periodo."-".$divisao_nome.".pdf");
