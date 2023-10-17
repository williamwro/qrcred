<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
include "Adm/php/funcoes.php";
include "Adm/php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$mes_atual = $_POST['mes_atual'];
$cod_convenio = $_POST['cod_convenio'];
//$mes_atual = $mes_atual."/".$_POST['ano'];

require("Adm/pages/components/fpdf/fpdf.php");

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
        $this->Write(0,utf8_decode('Relatório de produção do convenio Casserv'));

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

$sql_conv = $pdo->query("SELECT * FROM sind.convenio WHERE codigo = ".$cod_convenio);
$result = $sql_conv->fetch(PDO::FETCH_ASSOC);

PDF::setRS( $result['razaosocial'] );
PDF::setMS( $mes_atual );


$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SetFont('Arial','B',8);
$item  = 0;
$total = 0;
$query = "SELECT conta.lancamento, 
                     conta.associado AS matricula, 
                     conta.valor, 
                     conta.data, 
                     conta.hora, 
                     conta.mes, 
                     empregador.nome AS empregador, 
                     empregador.id AS codigo_empregador, 
                     convenio.razaosocial AS convenio, 
                     convenio.codigo AS cod_convenio, 
                     associado.nome AS associado, 
                     conta.funcionario, 
                     conta.parcela, 
                     conta.descricao
                FROM sind.associado 
          RIGHT JOIN (sind.empregador 
          RIGHT JOIN (sind.convenio 
          RIGHT JOIN sind.conta 
          ON convenio.codigo = conta.convenio) 
          ON empregador.id = conta.empregador) 
          ON associado.codigo = conta.associado AND associado.empregador = conta.empregador  
          WHERE convenio.codigo = " . $cod_convenio . " AND conta.mes = '" . $mes_atual . "' AND convenio.desativado = false ORDER BY associado.nome, conta.data";

$sql_conv_vendas = $pdo->query($query);
while($row_vendas = $sql_conv_vendas->fetch()) {

    $item++;
    $Valor = floatval($row_vendas['valor']);
    $total = $total + $Valor;
    $Valor =  number_format( $Valor, 2, ',', '.');

    $data_aux = explode("-",$row_vendas['data']);
    $data_formatada = $data_aux[2]."/".$data_aux[1]."/".$data_aux[0];

    $pdf->Cell(15,4,$row_vendas['lancamento']);
    $pdf->Cell(90,4,$row_vendas['associado']);
    $pdf->Cell(15,4,$data_formatada);
    $pdf->Cell(19,4,$row_vendas['hora']);
    $pdf->Cell(10,4,$Valor,'','','R');
    $pdf->Cell(20,4,$row_vendas['parcela'],'','','C');
    $pdf->Ln();
}

$pdf->Cell(149, 10,"Total : ".number_format($total,"2",",","."),0,0,'R');
$pdf->Ln();
$pdf->Cell(142, 11,"Registros : ".$item,0,0,'R');

$pdf->Output();