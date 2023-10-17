<?php
include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$mes_atual = $_POST['mes_atual'];
$cod_convenio = $_POST['cod_convenio'];
$ordem = $_POST['ordem'];
$card1 = $_POST["card1"];
$card2 = $_POST["card2"];
$card3 = $_POST["card3"];
$card4 = $_POST["card4"];
$card5 = $_POST["card5"];
$card6 = $_POST["card6"];

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
        $this->Image('../../../pictures_site-sind/logo4.png',10,8,18);
        // Arial bold 15
        $this->SetFont('Arial','B',12   );

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,utf8_decode('Relatório de produção do convenio Sindserva'));

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,date('d/m/Y')." - ".date('H:i:s'));

        $this->Ln();//pula linha
        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(12,"Estabelecimento: ".utf8_decode(self::$RS));// razao social

        $this->Ln();//pula linha
        $this->Cell(22);
        $this->Write(0,utf8_decode("Mês: ").self::$MS);

        $this->Ln(8);//pula linha
        $this->SetFont('Arial','B',8);

        $this->Cell(15,-6,"Registro",0,0,'L');

        $this->Cell(90,-6,"Nome",0,0,'L');

        $this->Cell(16,-6,"Data",0,0,'L');

        $this->Cell(19,-6,"Hora",0,0,'L');

        $this->Cell(8,-6,"Valor",0,0,'R');

        $this->Cell(20,-6,"Parcela",0,0,'C');

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

$sql_conv = $pdo->query("SELECT * FROM Convenio WHERE codigo = ".$cod_convenio);
$result = $sql_conv->fetch(PDO::FETCH_ASSOC);

PDF::setRS( $result['RazaoSocial'] );
PDF::setMS( $mes_atual );

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SetFont('Arial','B',8);
$item  = 0;
$total = 0;

$pmv    = 0;
$fc     = 0;
$fh     = 0;
$cm     = 0;
$sind   = 0;
$isa    = 0;
$guar   = 0;
$inprev = 0;
$semul  = 0;

$sql_conv_vendas = $pdo->query("SELECT * FROM qConvenio 
                                         WHERE mes = '".$mes_atual."' AND codigo = ".$cod_convenio." ORDER BY ".$ordem);
while($row = $sql_conv_vendas->fetch()) {

    $item++;
    $Valor = floatval($row['Valor']);
    $total = $total + $Valor;
    $Valor =  number_format( $Valor, 2, ',', '.');

    if($row['Empregador'] == "1"){$pmv    += $row['Valor'];};
    if($row['Empregador'] == 2){$fc     += $row['Valor'];};
    if($row['Empregador'] == 3){$fh     += $row['Valor'];};
    if($row['Empregador'] == 4){$cm     += $row['Valor'];};
    if($row['Empregador'] == 5){$sind   += $row['Valor'];};
    if($row['Empregador'] == 6){$isa    += $row['Valor'];};
    if($row['Empregador'] == 7){$guar   += $row['Valor'];};
    if($row['Empregador'] == 8){$inprev += $row['Valor'];};
    if($row['Empregador'] == 9){$semul  += $row['Valor'];};


    $pdf->Cell(15,4,$row['Lancamento']);
    $pdf->Cell(90,4,$row['Nome']);
    $pdf->Cell(15,4,$row['Dia']);
    $pdf->Cell(19,4,$row['hora']);
    $pdf->Cell(10,4,$Valor,'','','R');
    $pdf->Cell(20,4,$row['parcela'],'','','C');
    $pdf->Ln();


}

$pdf->Cell(10, 10,"PREFEITURA MUNICIPAL : ".number_format($pmv,"2",",","."),0,0,'R');
$pdf->Cell(10, 11,"FUNDAÇÃO CULTURAL    : ".number_format($fc,"2",",","."),0,0,'R');
$pdf->Cell(10, 12,"FUNDAÇÃO HOSPITALAR  : ".number_format($fh,"2",",","."),0,0,'R');
$pdf->Cell(10, 13,"CAMARA MUNICIPAL     : ".number_format($cm,"2",",","."),0,0,'R');
$pdf->Cell(10, 14,"SINDICATO            : ".number_format($sind,"2",",","."),0,0,'R');
$pdf->Cell(10, 15,"ISA                  : ".number_format($isa,"2",",","."),0,0,'R');
$pdf->Cell(10, 16,"GUARDA MUNICIPAL     : ".number_format($guar,"2",",","."),0,0,'R');
$pdf->Cell(10, 17,"INPREV               : ".number_format($inprev,"2",",","."),0,0,'R');
$pdf->Cell(10, 18,"SEMUL                : ".number_format($semul,"2",",","."),0,0,'R');

$pdf->Cell(149, 10,"Total :: ".number_format($total,"2",",","."),0,0,'R');
$pdf->Ln();
$pdf->Cell(142, 11,"Registros : ".$item,0,0,'R');


$pdf->Output('I',$result['RazaoSocial']."-".$mes_atual."-QRCRED.pdf");