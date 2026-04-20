<?php
// Iniciar buffer de output para evitar problemas com warnings/notices
ob_start();

date_default_timezone_set('America/Araguaina');
ini_set('max_execution_time', 360);

// Verificar se a extensão mbstring está disponível
if (!extension_loaded('mbstring')) {
    die('Erro: A extensão PHP mbstring é necessária para este script.');
}
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
if(isset($_POST['adiantamento'])) {
    $adiantamento = str_replace(',', '.', str_replace('.', '', $_POST['adiantamento']));
}else{
    $adiantamento = 0;
}
if(isset($_POST['taxa_cartao'])) {
    $taxa_cartao = str_replace(',','.',str_replace('.','',$_POST['taxa_cartao']));
}else{
    $taxa_cartao = 0;
}
if(isset($_POST['cartao'])) {
    $cartao = str_replace(',', '.', str_replace('.', '', $_POST['cartao']));
}else{
    $cartao = 0;
}
/*if(isset($_POST['unimed'])) {
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
}*/
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
        $this->Image('../../../pictures_site-sind/logo_saspng.png',10,8,18);
        // Arial bold 15

        $this->SetFont('arial','B',10);

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,mb_convert_encoding('EXTRATO DO ASSOCIADO', 'ISO-8859-1', 'UTF-8'));

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,date('d/m/Y')." - ".date('H:i:s'));

        //$this->Cell(14);
        //$this->Write(0,"Pagina: ".self::$PG);

        $this->Ln();//pula linha
        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(12,"associado: ".mb_convert_encoding(self::$AS, 'ISO-8859-1', 'UTF-8'));// associado

        $this->Ln();//pula linha
        $this->Cell(22);
        $this->Write(0,mb_convert_encoding("Mês: ", 'ISO-8859-1', 'UTF-8').self::$MS);// mes


        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,"Matricula: ".mb_convert_encoding(self::$MT, 'ISO-8859-1', 'UTF-8'));// matricula
        //

        $this->Cell(22);//move para direita 20 posiçoes
        $this->Write(0,"empregador: ".mb_convert_encoding(self::$EM, 'ISO-8859-1', 'UTF-8'));// empregador





        $this->Ln(12);//pula linha
        $this->SetFont('Arial','B',10);

        $this->Cell(25,-6,"Registro",0,0,'L');

        $this->Cell(65,-6,"Convenio",0,0,'L');

        $this->Cell(20,-6,"Parcela",0,0,'C');

        $this->Cell(16,-6,"Data",0,0,'L');

        $this->Cell(25,-6,"Valor",0,0,'R');

        $this->Cell(35,-6,"Tipo",0,0,'R');

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
        AND associado.empregador =" . $_POST["cod_empregador"] . " 
        AND (conta.aprovado = true OR conta.aprovado IS NULL)
        ORDER BY conta.lancamento;";
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
            $pdf->SetFont('Arial','B',9);
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

        $pdf->Cell(25, 6, $row['lancamento']);
        $pdf->Cell(65, 6, mb_convert_encoding(substr($row['nomefantasia'],0,28), 'ISO-8859-1', 'UTF-8'));
        $pdf->Cell(20, 6, $row['parcela'], '', '', 'C');
        $pdf->Cell(16, 6, date('d/m/y', strtotime($row['data'])));
        $pdf->Cell(25, 6, $valor, '', '', 'R');
        $pdf->Cell(35, 6, mb_convert_encoding($row['nome_tipo'], 'ISO-8859-1', 'UTF-8'), '', '', 'R');

        $pdf->Ln();

    }

$pdf->Cell(135, 10, "Total : ", 0, 0, 'R');
$pdf->Cell(16, 10, number_format($total, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(8);

$pdf->Cell(135, 10, "Limite : ", 0, 0, 'R');
$pdf->Cell(16, 10, number_format($limite, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(3);

PDF::setPG($pagina);
// SOMAS DA ULTIMA PAGINA **********************************************
//$pdf->Cell(60, 20, "TOTAIS", 0, 0, 'R');
//$pdf->Cell(30, 20, "DESCONTOS", 0, 0, 'R');
//$pdf->Cell(45, 20, "NAO DESCONTADO", 0, 0, 'R');
$pdf->Ln(6);

$pdf->Cell(40, 20, "Adiantamento : ", 0, 0, 'R');
$pdf->Cell(18, 20, number_format($adiantamento, "2", ",", "."), 0, 0, 'R');
//$pdf->Cell(30, 20, number_format($compras-$cnd, "2", ",", "."), 0, 0, 'R');
//$pdf->Cell(30, 20, number_format($cnd, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(5);

$pdf->Cell(40, 20, mb_convert_encoding("Taxa cartão : ", 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');
$pdf->Cell(18, 20, number_format($taxa_cartao, "2", ",", "."), 0, 0, 'R');
//$pdf->Cell(30, 20, number_format($farmacia-$fnd, "2", ",", "."), 0, 0, 'R');
//$pdf->Cell(30, 20, number_format($fnd, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(5);

$pdf->Cell(40, 20, mb_convert_encoding("Cartão : ", 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');
$pdf->Cell(18, 20, number_format($cartao, "2", ",", "."), 0, 0, 'R');
//$pdf->Cell(30, 20, number_format($unimed-$dnd, "2", ",", "."), 0, 0, 'R');
//$pdf->Cell(30, 20, number_format($dnd, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(6);

$pdf->Cell(40, 20, "Total : ", 0, 0, 'R');
$pdf->Cell(18, 20, number_format($total, "2", ",", "."), 0, 0, 'R');
//$pdf->Cell(30, 20, number_format(($adiantamento+$taxa_cartao+$cartao)-($cnd+$fnd+$endes+$dnd), "2", ",", "."), 0, 0, 'R');
//$pdf->Cell(30, 20, number_format(($cnd+$fnd+$endes+$dnd), "2", ",", "."), 0, 0, 'R');
$total = 0;

$item=0;

if($associado_aux != ""){
    // Limpar qualquer output buffer para evitar problemas com o PDF
    ob_end_clean();
    $pdf->Output('I',$associado_aux."-".$mes."-QRCRED.pdf");
}