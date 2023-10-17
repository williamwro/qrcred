<?php
date_default_timezone_set('America/Araguaina');
ini_set('max_execution_time', 360);
include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$divisao = 1;
if(isset($_POST['mes_atual'])){
    $mes = $_POST['mes_atual'];
}
if(isset($_POST['empregador'])) {
    $cod_empregador = $_POST['empregador'];
}
$matricula = "";
$nome = "";
$compras = 0;
$farmacia = 0;
$unimed = 0;
$cnd = 0;
$fnd = 0;
$endes = 0;
$dnd = 0;

$card1 = $_POST['card1'];
$card2 = $_POST['card2'];
$card3 = $_POST['card3'];
$card4 = $_POST['card4'];
$card5 = $_POST['card5'];
$card6 = $_POST['card6'];

$sql_aux = "AND associado.empregador = $cod_empregador AND conta.associado <> '$card1'  AND conta.associado <> '$card2' AND conta.associado <> '$card3' AND conta.associado <> '$card4' AND conta.associado <> '$card5' AND conta.associado <> '$card6' ORDER BY associado.nome ASC, conta.data DESC";

require("../components/fpdf/fpdf.php");
require('../components/fpdf/makefont/makefont.php');
class PDF extends FPDF
{
    private static $AS;
    public static function setAS( $ASS ) {
        self::$AS = $ASS;
    }
    private static $MT;
    public static function setMT( $matricula ) {
        self::$MT = $matricula;
    }
    private static $MS;
    public static function setMS( $MES ) {
        self::$MS = $MES;
    }
    private static $EM;
    public static function setEM( $cod_empregador ) {
        self::$EM = $cod_empregador;
    }
    private static $EMP_N;
    public static function setEM_N( $nome_empregador ) {
        self::$EMP_N = $nome_empregador;
    }
    private static $PG;
    public static function setPG( $PAGINA ) {
        self::$PG = $PAGINA;
    }
    private static $DV;
    public static function getDV( $divisao ) {
        return self::$DV = $divisao;
    }
// Page header
    function Header()
    {

        // Logo
        if(self::$DV == 1){//CASSERV
            $this->Image('../../../pictures_site-sind/logo4.png',10,8,18);
        }else if(self::$DV == 2){//SINDICATO
            //$this->Image('../../../pictures_site-sind/logo4.png',10,8,18);
        }
        // Arial bold 15

        $this->SetFont('arial','B',12);

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,utf8_decode('EXTRATO DO ASSOCIADO'));

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,date('d/m/Y')." - ".date('H:i:s'));

        //$this->Cell(14);
        //$this->Write(0,"Pagina: ".self::$PG);

        $this->Ln();//pula linha
        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(12,"associado: ".utf8_decode(self::$AS));// associado

        $this->Ln();//pula linha
        $this->Cell(22);
        $this->Write(0,utf8_decode("Mês: ").self::$MS);// mes


        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,"Matricula: ".utf8_decode(self::$MT));// matricula
        //

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,"empregador: ".utf8_decode(self::$EMP_N));// empregador





        $this->Ln(12);//pula linha
        $this->SetFont('Arial','B',10);

        $this->Cell(20,-6,"Registro",0,0,'L');

        $this->Cell(89,-6,"Convenio",0,0,'L');

        $this->Cell(18,-6,"Parcela",0,0,'C');

        $this->Cell(25,-6,"data",0,0,'L');

        $this->Cell(12,-6,"Valor",0,0,'R');

        $this->Cell(12,-6,"Tipo",0,0,'R');

        // Line break
        $this->Ln(0);
        //linha horizontal
        $this->SetLineWidth(0.2);
        $this->Line("7","33","201","33");

    }

// Page footer
    function Footer()
    {

        // Position at 1.5 cm from bottom
        $this->SetY(-15);


        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        //$this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
        $this->Cell(0,10,'',0,0,'C');
        $this->SetLineWidth(0.2);
        $this->Line("7","280","201","280");
    }
}
$pagina=1;
$item   = 0;
$item_pagina = 0;
$total  = 0;

//PDF::setMS($mes);
//PDF::setPG($pagina);
//PDF::setMT($matricula);
PDF::setEM_N("PMV");
PDF::getDV($divisao);

if (isset($mes) and $mes != "") {
        $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, empregador.id AS codigo_empregador, convenio.razaosocial AS convenio, convenio.nomefantasia, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao, tipoconvenio.nome AS nome_tipo
        FROM sind.tipoconvenio RIGHT JOIN (sind.associado RIGHT JOIN (sind.empregador RIGHT JOIN (sind.convenio RIGHT JOIN sind.conta ON convenio.codigo = conta.convenio) 
        ON empregador.id = conta.empregador) ON associado.codigo = conta.associado AND associado.empregador = conta.empregador) ON tipoconvenio.codigo = convenio.Tipo 
        WHERE conta.mes = '$mes' $sql_aux";
}

PDF::setMS($mes);
PDF::setAS("ADEMILZA AP.FIRMINO SILVA");
PDF::setMT("266126");
$associado_aux="";
$aux = 0;
$total_paginas=0;
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('P');
$pdf->SetFont('Arial','B',8);


    $sql_conv_vendas = $pdo->query($query);//$xxx = count($sql_conv_vendas->fetchAll()); //QUANTIDADE DE REGISTROS
    while($row = $sql_conv_vendas->fetch()) {
       
        $matricula = $row['matricula'];
        $nome = $row['associado'];
        PDF::setAS($nome);
        PDF::setMT($matricula);
        if($associado_aux != $row['associado']){
            if($associado_aux != "" && $associado_aux != $row['associado']){


                $pdf->Cell(144, 10, "Total : ", 0, 0, 'R');
                $pdf->Cell(20, 10, number_format($total, "2", ",", "."), 0, 0, 'R');
                $pdf->Ln(8);
                PDF::setPG($pagina);
                // SOMAS DA ULTIMA PAGINA **********************************************
                $pdf->Cell(60, 20, "TOTAIS", 0, 0, 'R');
                $pdf->Ln(6);
                $pdf->Cell(40, 20, "COMPRAS : ", 0, 0, 'R');
                $pdf->Cell(18, 20, number_format($compras, "2", ",", "."), 0, 0, 'R');
                $pdf->Ln(5);
                $pdf->Cell(40, 20, "FARMACIA : ", 0, 0, 'R');
                $pdf->Cell(18, 20, number_format($farmacia, "2", ",", "."), 0, 0, 'R');
                $pdf->Ln(5);
                $pdf->Cell(40, 20, "UNIMED : ", 0, 0, 'R');
                $pdf->Cell(18, 20, number_format($unimed, "2", ",", "."), 0, 0, 'R');
                $pdf->Ln(6);
                $pdf->Cell(40, 20, "Total : ", 0, 0, 'R');
                $pdf->Cell(18, 20, number_format($total, "2", ",", "."), 0, 0, 'R');
                
                $total = 0;
                $item=0;


            }
            if($associado_aux != ""){
               
               
                $pdf->AddPage('P');
                $pdf->SetFont('Arial','B',8);
                $item_pagina = 0;
            }
            $associado_aux = $row['associado'];
           
            
            
            // SOMAS DA ULTIMA PAGINA **********************************************

            $total = 0;

            $item=0;
            $compras = 0;
            $farmacia = 0;
            $unimed = 0;
        }
        // soma categorias
        if ($row['nome_tipo'] === 'FARMACIA') {//farmacia
            $farmacia = $farmacia + $row['valor'];
        } else if ($row['nome_tipo'] === 'COMPRAS') {//compras
            $compras = $compras + $row['valor'];
        } else if ($row['nome_tipo'] === 'UNIMED') {//unimed
            $unimed = $unimed + $row['valor'];
        } 


        $item++;
        $item_pagina++;
        if ($item_pagina  ==  60){
            $pagina = $pagina + 1;
            $item_pagina = 0;
        }
      
        $valor = floatval($row['valor']);
        $total = $total + $valor;
        $valor = number_format($valor, 2, ',', '.');

        $pdf->Cell(20, 4, $row['lancamento']);
        $pdf->Cell(89, 4, substr($row['nomefantasia'],0,33));
        $pdf->Cell(15, 4, $row['parcela'], '', '', 'C');
        $pdf->Cell(20, 4, date('d/m/y', strtotime($row['data'])));
        //$objDate = DateTime::createFromFormat('Y-m-d H:i:s', $row['hora']);
        //$pdf->Cell(12, 4, substr($row['hora'],0,7));
        $pdf->Cell(20, 4, $valor, '', '', 'R');
        $pdf->Cell(25, 4, $row['nome_tipo'], '', '', 'R');

       

        $pdf->Ln();

    }

    $pdf->Cell(144, 10, "Total : ", 0, 0, 'R');
    $pdf->Cell(20, 10, number_format($total, "2", ",", "."), 0, 0, 'R');
    $pdf->Ln(8);
    PDF::setPG($pagina);
    // SOMAS DA ULTIMA PAGINA **********************************************
    $pdf->Cell(60, 20, "TOTAIS", 0, 0, 'R');
    $pdf->Ln(6);
    $pdf->Cell(40, 20, "COMPRAS : ", 0, 0, 'R');
    $pdf->Cell(18, 20, number_format($compras, "2", ",", "."), 0, 0, 'R');
    $pdf->Ln(5);
    $pdf->Cell(40, 20, "FARMACIA : ", 0, 0, 'R');
    $pdf->Cell(18, 20, number_format($farmacia, "2", ",", "."), 0, 0, 'R');
    $pdf->Ln(5);
    $pdf->Cell(40, 20, "UNIMED : ", 0, 0, 'R');
    $pdf->Cell(18, 20, number_format($unimed, "2", ",", "."), 0, 0, 'R');
    $pdf->Ln(6);
    $pdf->Cell(40, 20, "Total : ", 0, 0, 'R');
    $pdf->Cell(18, 20, number_format($total, "2", ",", "."), 0, 0, 'R');
    
    $total = 0;
    $item=0;

if($associado_aux != ""){
    $pdf->Output('I',$associado_aux."-".$mes."-QRCRED.pdf");
}
