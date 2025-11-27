<?php
include "../../php/funcoes.php";
include "../../php/banco.php";

// Recebe os dados via POST e converte de UTF-8 para ISO-8859-1
$codigo = isset($_POST['codigo']) ? mb_convert_encoding($_POST['codigo'], 'ISO-8859-1', 'UTF-8') : '';
$nome = isset($_POST['nome']) ? mb_convert_encoding($_POST['nome'], 'ISO-8859-1', 'UTF-8') : '';
$cpf = isset($_POST['cpf']) ? mb_convert_encoding($_POST['cpf'], 'ISO-8859-1', 'UTF-8') : '';
$cartao = isset($_POST['cartao']) ? mb_convert_encoding($_POST['cartao'], 'ISO-8859-1', 'UTF-8') : '';
$empregador = isset($_POST['empregador']) ? mb_convert_encoding($_POST['empregador'], 'ISO-8859-1', 'UTF-8') : '';
$divisao = isset($_POST['divisao']) ? mb_convert_encoding($_POST['divisao'], 'ISO-8859-1', 'UTF-8') : '';
$nome_divisao = isset($_POST['nome_divisao']) ? mb_convert_encoding($_POST['nome_divisao'], 'ISO-8859-1', 'UTF-8') : '';

require("../components/fpdf/fpdf.php");

class PDF extends FPDF
{
    // Page header
    function Header()
    {
        // Logo
        $this->Image('../../../pictures_site-sind/logo_saspng.png',10,8,18);
        
        // Arial bold 15
        $this->SetFont('Arial','B',16);
        
        // Move to the right
        $this->Cell(35);
        
        // Title
        $this->Cell(120,10,mb_convert_encoding('COMPROVANTE DE BLOQUEIO DE CARTÃO', 'ISO-8859-1', 'UTF-8'),0,0,'C');
        
        // Line break
        $this->Ln(20);
    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        $this->Cell(0,10,mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8').$this->PageNo().'/{nb}',0,0,'C');
    }
}

// Instanciation of inherited class
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Adiciona borda ao redor do conteúdo
$pdf->SetLineWidth(0.5);
$pdf->Rect(10, 35, 190, 100);

// Conteúdo do comprovante
$pdf->SetFont('Arial','B',12);
$pdf->SetXY(15, 40);
$pdf->Cell(0,10,mb_convert_encoding('DADOS DO ASSOCIADO', 'ISO-8859-1', 'UTF-8'),0,1,'L');

$pdf->SetFont('Arial','',11);
$pdf->SetX(15);
$pdf->Cell(40,8,mb_convert_encoding('Nome Completo:', 'ISO-8859-1', 'UTF-8'),0,0,'L');
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8,$nome,0,1,'L');

$pdf->SetFont('Arial','',11);
$pdf->SetX(15);
$pdf->Cell(40,8,'CPF:',0,0,'L');
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8,$cpf,0,1,'L');

$pdf->SetFont('Arial','',11);
$pdf->SetX(15);
$pdf->Cell(40,8,'Empresa:',0,0,'L');
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8,$empregador,0,1,'L');

$pdf->SetFont('Arial','',11);
$pdf->SetX(15);
$pdf->Cell(40,8,mb_convert_encoding('Número do Cartão:', 'ISO-8859-1', 'UTF-8'),0,0,'L');
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8,$cartao,0,1,'L');

// Linha separadora
$pdf->Ln(5);
$pdf->SetLineWidth(0.2);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

// Status do cartão
$pdf->SetFont('Arial','B',14);
$pdf->SetTextColor(255,0,0); // Vermelho
$pdf->SetX(15);
$pdf->Cell(0,10,'STATUS: BLOQUEADO',0,1,'C');
$pdf->SetTextColor(0,0,0); // Volta para preto

// Data e hora
$pdf->Ln(5);
$pdf->SetFont('Arial','',10);
$pdf->SetX(15);
$pdf->Cell(0,8,'Data: '.date('d/m/Y').' - Hora: '.date('H:i:s'),0,1,'C');

// Mensagem adicional
$pdf->Ln(10);
$pdf->SetFont('Arial','',10);
$pdf->SetX(15);
$pdf->MultiCell(180,6,'Este documento comprova que o cartão de número '.$cartao.' encontra-se bloqueado no sistema.',0,'C');

// Assinatura
$pdf->Ln(20);
$pdf->SetLineWidth(0.2);
$pdf->Line(60, $pdf->GetY(), 150, $pdf->GetY());
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,5,mb_convert_encoding('Assinatura do Responsável', 'ISO-8859-1', 'UTF-8'),0,1,'C');

// Output do PDF
$pdf->Output('I',"comprovante_bloqueio_cartao_".$cartao."_".date('dmY').".pdf");
?>
