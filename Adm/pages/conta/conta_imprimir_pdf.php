<?php
date_default_timezone_set('America/Araguaina');
ini_set('max_execution_time', 360);
include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if (isset($_POST['matricula'])){
    $matricula = $_POST['matricula'];
}
if(isset($_POST['mes'])){
    $mes = $_POST['mes'];
}
if(isset($_POST['empregador'])) {
    $empregador = $_POST['empregador'];
}
if(isset($_POST['cod_empregador'])) {
    $cod_empregador = $_POST['cod_empregador'];
}
if(isset($_POST['limite'])) {
    if ($_POST['limite'] != 'NaN') {
        $limite = $_POST['limite'];
        $limite = str_replace(',', '.', $limite);
    } else {
        $limite = 0;
    }
}
if(isset($_POST['farmacia'])) {
    $farmacia = str_replace(',', '.', str_replace('.', '', $_POST['farmacia']));
}else{
    $farmacia = 0;
}
if(isset($_POST['compras'])) {
    $compras = str_replace(',','.',str_replace('.','',$_POST['compras']));
}else{
    $compras = 0;
}
if(isset($_POST['emprestimo'])) {
    $emprestimo = str_replace(',', '.', str_replace('.', '', $_POST['emprestimo']));
}else{
    $emprestimo = 0;
}
if(isset($_POST['unimed'])) {
    $unimed = str_replace(',', '.', str_replace('.', '', $_POST['unimed']));
}else{
    $unimed = 0;
}
if(isset($_POST['fnd'])) {
    $fnd = str_replace(',', '.', str_replace('.', '', $_POST['fnd']));
}else{
    $fnd = 0;
}
if(isset($_POST['cnd'])) {
    $cnd = str_replace(',', '.', str_replace('.', '', $_POST['cnd']));
}else{
    $cnd = 0;
}
if(isset($_POST['endes'])) {
    $endes = str_replace(',', '.', str_replace('.', '', $_POST['endes']));
}else{
    $endes = 0;
}
if(isset($_POST['dnd'])) {
    $dnd = str_replace(',', '.', str_replace('.', '', $_POST['dnd']));
}else{
    $dnd = 0;
}
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
    public static function setEM( $empregador ) {
        self::$EM = $empregador;
    }
    private static $PG;
    public static function setPG( $PAGINA ) {
        self::$PG = $PAGINA;
    }
// Page header
    function Header()
    {
        // Logo
        $this->Image('../../../pictures_site-sind/logo4.png',10,8,18);
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
        $this->Write(0,"empregador: ".utf8_decode(self::$EM));// empregador





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
        $this->Cell(0,10,'QRCRED',0,0,'C');
        $this->SetLineWidth(0.2);
        $this->Line("7","280","201","280");
    }
}
$pagina=1;
$item   = 0;
$item_pagina = 0;
$total  = 0;

PDF::setMS($mes);
PDF::setPG($pagina);
PDF::setMT($matricula);
PDF::setEM($empregador);

if (isset($_POST["matricula"]) and $_POST["matricula"] != "") {
        $query = "SELECT conta.lancamento, conta.associado AS matricula, conta.valor, conta.data, conta.hora, conta.mes, empregador.nome AS empregador, empregador.id AS codigo_empregador, convenio.razaosocial AS convenio, convenio.nomefantasia, convenio.codigo AS cod_convenio, associado.nome AS associado, conta.funcionario, conta.parcela, conta.descricao, tipoconvenio.nome AS nome_tipo
        FROM sind.tipoconvenio 
        RIGHT JOIN (sind.associado 
        RIGHT JOIN (sind.empregador 
        RIGHT JOIN (sind.convenio RIGHT JOIN sind.conta ON convenio.codigo = conta.convenio) 
        ON empregador.id = conta.empregador) 
        ON associado.codigo = conta.associado AND associado.empregador = conta.empregador) 
        ON tipoconvenio.codigo = convenio.Tipo 
        WHERE conta.associado = '" . $_POST["matricula"] . "' AND conta.mes = '" . $_POST["mes"] . "'
        AND associado.empregador =" . $_POST["cod_empregador"] . " ORDER BY conta.lancamento;";
}

PDF::setMS($mes);
$associado_aux="";
$aux = 0;
$total_paginas=0;

PDF::setPG($pagina);


    $sql_conv_vendas = $pdo->query($query);//$xxx = count($sql_conv_vendas->fetchAll()); //QUANTIDADE DE REGISTROS
    while($row = $sql_conv_vendas->fetch()) {

        if($associado_aux != $row['associado']){
            $associado_aux = $row['associado'];
            PDF::setAS($associado_aux);
            $pdf = new PDF();
            $pdf->AliasNbPages();
            $pdf->AddPage();
            $pdf->SetFont('Arial','B',12);
            $pagina = 1;
            $item_pagina = 0;
            PDF::setPG($pagina);
            // SOMAS DA ULTIMA PAGINA **********************************************

            $total = 0;

            $item=0;
        }
        $item++;
        $item_pagina++;
        if ($item_pagina  ==  60){
            $pagina = $pagina + 1;
            $item_pagina = 0;
        }
        PDF::setPG($pagina);
        $valor = floatval($row['valor']);
        $total = $total + $valor;
        $valor = number_format($valor, 2, ',', '.');

        $pdf->Cell(20, 6, $row['lancamento']);
        $pdf->Cell(89, 6, substr($row['nomefantasia'],0,33));
        $pdf->Cell(15, 6, $row['parcela'], '', '', 'C');
        $pdf->Cell(20, 6, date('d/m/y', strtotime($row['data'])));
        //$objDate = DateTime::createFromFormat('Y-m-d H:i:s', $row['hora']);
        //$pdf->Cell(12, 4, substr($row['hora'],0,7));
        $pdf->Cell(20, 6, $valor, '', '', 'R');
        $pdf->Cell(25, 6, $row['nome_tipo'], '', '', 'R');

        $pdf->Ln();

    }

$pdf->Cell(144, 10, "Total : ", 0, 0, 'R');
$pdf->Cell(20, 10, number_format($total, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(8);

$pdf->Cell(144, 10, "Limite : ", 0, 0, 'R');
$pdf->Cell(20, 10, number_format($limite, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(3);

PDF::setPG($pagina);
// SOMAS DA ULTIMA PAGINA **********************************************
$pdf->Cell(60, 20, "GASTOS", 0, 0, 'R');
$pdf->Cell(30, 20, "DESCONTOS", 0, 0, 'R');
$pdf->Cell(45, 20, "NAO DESCONTADO", 0, 0, 'R');
$pdf->Ln(6);

$pdf->Cell(40, 20, "COMPRAS : ", 0, 0, 'R');
$pdf->Cell(18, 20, number_format($compras, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(30, 20, number_format($compras-$cnd, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(30, 20, number_format($cnd, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(5);

$pdf->Cell(40, 20, "FARMACIA : ", 0, 0, 'R');
$pdf->Cell(18, 20, number_format($farmacia, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(30, 20, number_format($farmacia-$fnd, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(30, 20, number_format($fnd, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(5);

$pdf->Cell(40, 20, "UNIMED : ", 0, 0, 'R');
$pdf->Cell(18, 20, number_format($unimed, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(30, 20, number_format($unimed-$dnd, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(30, 20, number_format($dnd, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(6);

$pdf->Cell(40, 20, "Total : ", 0, 0, 'R');
$pdf->Cell(18, 20, number_format($total, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(30, 20, number_format(($compras+$farmacia+$emprestimo+$unimed)-($cnd+$fnd+$endes+$dnd), "2", ",", "."), 0, 0, 'R');
$pdf->Cell(30, 20, number_format(($cnd+$fnd+$endes+$dnd), "2", ",", "."), 0, 0, 'R');
$total = 0;

$item=0;

if($associado_aux != ""){
    $pdf->Output('I',$associado_aux."-".$mes."-QRCRED.pdf");
}