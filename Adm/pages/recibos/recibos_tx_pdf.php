<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
//ini_set('max_execution_time', 360);
include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$total = 0;
$prolabore = 0;
$valor_prolabore = 0;
$total_liquido = 0;
$total_liquido2 = 0;
include "NumeroPorExtenso.php";
$extenso = new NumeroPorExtenso;

$mes_atual = $_POST['mes_atual'];

$data = substr($_POST['data'],-4)."-".substr($_POST['data'],3,2)."-".substr($_POST['data'],0,2);
$data = strtotime($data);
$data = strftime('%A, %d de %B de %Y',$data);

require("extenso.php");
require_once('../components/fpdf/fpdf.php');

class PDF extends FPDF
{
    private static $RS;
    public static function setRS( $RSL ) {
        self::$RS = $RSL;
    }
    private static $MS;
    public static function setMS( $MES ) {
        self::$MS = $MES;
    }
    private static $PG;
    public static function setPG( $PAGINA ) {
        self::$PG = $PAGINA;
    }
    function Header()
    {
        // Logo
        $this->Image('logo.jpg',18,12,20);
        // Arial bold 15
        $this->SetFont('Arial','B',8);
        $this->Ln(3);
        $this->SetTextColor(0,0,156);
        $this->Cell(69);//move para direita 20 posiçoes
        $this->Write(0,utf8_decode('Caixa de Assistência à Saúde dos Servidores Públicos da Prefeitura'));
        $this->Ln(4);
        $this->Cell(65);//move para direita 20 posiçoes
        $this->Write(0,utf8_decode('Municipal de Varginha-MG, das Fundação, Autarquias e Camara Muncipal'));
        $this->Ln(4);
        $this->Cell(87);//move para direita 20 posiçoes
        $this->Write(0,utf8_decode('CNPJ: 35.537.390/0001-18'));
        $this->Ln(15);//pula linha


        $this->SetFont('Arial','B',8);
        // Line break
        $this->Ln(0);
        //linha horizontal
        $this->SetLineWidth(0.2);
        $this->Line("7","29","201","29");
    }

// Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-20);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        //$this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
        $this->Cell(0,10,'Avenida Ministro Bias Fortes, 79 Centro',0,0,'C');
        $this->Ln(3);
        $this->Cell(0,10,'Varginha - MG',0,0,'C');
        $this->Cell(0,15,"Pagina: ".self::$PG,0,0,'R');

        $this->SetLineWidth(0.2);
        $this->Line("7","280","201","280");
    }
}
PDF::setMS($mes_atual);
$pagina=0;
PDF::setPG($pagina);

    $query = "Select categoria,sum(val_alicota) as total
                From sind.pagamentos2 
               Where mes = '" . $mes_atual ."'
            Group by categoria";

PDF::setMS($mes_atual);
$convenio_aux = "";
$aux = 0;
$total_paginas = 0;
PDF::setPG($pagina);

//$date = strftime('%A, %d de %B de %Y', strtotime('today'));
//$date = strftime('%A, %d de %B de %Y', $data);
$pdf = new PDF();
$pdf->SetFont('Arial','B',8);
$sql_conv_vendas = $pdo->query($query);
//$xxx = count($sql_conv_vendas->fetchAll());
while($row = $sql_conv_vendas->fetch()) {
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->Ln(5);

    $total = $row["total"];
    $total_liquido2 = number_format($total, 2,'.', '');
    $total_liquido = number_format($total, 2, ',', '.');

    $categoria = $row["categoria"];
    $pagina = $pagina + 1;

    PDF::setPG($pagina);

    PDF::setRS($categoria);

    $pdf->Ln(25);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0, 4,"RECIBO",0,0,'C');
    $pdf->Ln(35);
    $pdf->SetFont('Arial','',10);
    $y = $pdf->GetY();
    $pdf->SetXY(10,$y);
    $corpo_recibo = utf8_decode("                    Recebemos da Caixa de Assistência à Saúde dos Servidores Públicos da Prefeitura Municipal de Varginha-MG, das Fundação, Autarquias e Camara Muncipal, a importancia de R$ ").$total_liquido." ( ".utf8_decode($extenso->converter($total_liquido2))." )"
        .utf8_decode(", referente a taxa administrativa do cartão convenio de ".$row['categoria']." no mês de ").$mes_atual.".";
    $pdf->MultiCell(180, 6, $corpo_recibo,0,'J',0);
    $pdf->Ln(35);
    $pdf->Cell(40, 4, "                          Varginha, ".$data.".");
    $pdf->Ln(35);
    $pdf->Cell(30, 0, utf8_decode("Razão Social : TEC WEB SERVIÇOS DE INFORMÁTICA LTDA"),0,0,'L',0);
    $pdf->Ln(1);
    $pdf->Cell(30, 8, "CNPJ : 29.974.317/0001-00");
    $pdf->Ln(1);
    $pdf->Cell(30, 16,  utf8_decode("Endereço : Avenida Rio Branco 417, sala 305 - Centro, VARGINHA - MG, "));

    $pdf->Ln();
}
PDF::setPG($pagina);

$pdf->Output('I','RECIBOSTX'."-".$mes_atual."-QRCRED.pdf");