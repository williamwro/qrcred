<?php
include "../../php/funcoes.php";
include "../../php/banco.php";
$divisao = $_POST['divisao'];
$nome_divisao = $_POST['nome_divisao'];
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
require("../components/fpdf/fpdf.php");

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
// Page header
    function Header()
    {

        // Logo
        $this->Image('logo.jpg',15,11,15);
        // Arial bold 15
        $this->SetFont('Arial','B',12   );

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,utf8_decode('Cartões bloqueados'));
        $this->Ln(5);
        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,utf8_decode(self::$RS));

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,date('d/m/Y')." - ".date('H:i:s'));

        $this->Ln(14);//pula linha
        $this->SetFont('Arial','B',8);

        $this->Cell(15,-6,"Codigo",0,0,'L');

        $this->Cell(21,-6,utf8_decode("Cartão"),0,0,'L');

        $this->Cell(90,-6,"Nome",0,0,'L');

        $this->Cell(19,-6,"Empregador",0,0,'L');

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
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        $this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
        $this->SetLineWidth(0.2);
        $this->Line("7","280","201","280");
    }
}

PDF::setRS($nome_divisao);
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SetFont('Arial','B',8);
$item  = 0;

$sql_conv_vendas = $pdo->query("SELECT c_cartaoassociado.cod_verificacao, associado.nome,
                 associado.codigo, divisao.id_divisao, empregador.nome as empregador
            FROM sind.associado
      INNER JOIN sind.c_cartaoassociado 
              ON associado.codigo = c_cartaoassociado.cod_associado and associado.empregador = c_cartaoassociado.empregador
      INNER JOIN sind.empregador 
              ON associado.empregador = empregador.id
      INNER JOIN sind.divisao
              ON empregador.divisao = divisao.id_divisao
           WHERE c_cartaoassociado.cod_situacaocartao = 2 
             AND empregador.divisao = ".$divisao."
        ORDER BY nome");
$pdf->Ln(1);
while($row = $sql_conv_vendas->fetch()) {

    $item++;

    $pdf->Cell(15,4,$row['codigo']);
    $pdf->Cell(21,4,$row['cod_verificacao']);
    $pdf->Cell(90,4,$row['nome']);
    $pdf->Cell(19,4,$row['empregador']);
    $pdf->Ln();
}
$pdf->Ln();
$pdf->Cell(25, 11,"Registros : ".$item,0,0,'R');

$pdf->Output('I',"cartoes_bloqueados-".date('d/m/Y')." - ".date('H:i:s')."-QRCRED.pdf");