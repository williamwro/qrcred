<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
    //ini_set('max_execution_time', 360);
include "../../php/funcoes.php";
include "../../php/banco.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include '../../../PHPMailer-master/src/Exception.php';
include '../../../PHPMailer-master/src/PHPMailer.php';
include '../../../PHPMailer-master/src/SMTP.php';


$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$caminho = "contratos//".$_POST['codigo'];
$_codigo = $_POST['codigo'];
$_codigo = (int)$_codigo;
if(!file_exists($caminho)){
    mkdir($caminho,0777,true);
}
$razaosocial = $_POST['razaosocial'];
if(isset($_POST['cnpj'])){
    $cnpj        = $_POST['cnpj'];
}else{
    $cnpj        = "";
}
if(isset($_POST['cpf'])){
    $cpf         = $_POST['cpf'];
}else{
    $cpf         = "";
}
if(isset($_POST['endereco'])){
    $endereco = $_POST['endereco'];
}else{
    $endereco = "";
}
if(isset($_POST['numero'])){
    $numero = $_POST['numero'];
}else{
    $numero = "";
}
if(isset($_POST['complemento'])){
    $complemento = $_POST['complemento'];
}else{
    $complemento = "";
}
if(isset($_POST['bairro'])) {
    $bairro = $_POST['bairro'];
}else{
    $bairro = "";
}
if(isset($_POST['cidade'])) {
    $cidade = $_POST['cidade'];
}else{
    $cidade = "";
}
if(isset($_POST['estado'])) {
    $estado = $_POST['estado'];
}else{
    $estado = "";
}
if(isset($_POST['email'])) {
    $email = $_POST['email'];
}else{
    $email = "";
}
if(isset($_POST['data_cadastro'])) {
    //$data_cadastro =  date("d", strtotime($_POST['data_cadastro']))." de ".date("F", strtotime($_POST['data_cadastro']))." de ".date("Y", strtotime($_POST['data_cadastro']));
    $data_cadastro = strftime('%A, %d de %B de %Y',strtotime($_POST['data_cadastro']));
}else{
    $data_cadastro = "";
}
//$mes_atual   = $_POST['mes_atual'];
if ($cnpj ==! ''){
    $cnpjorcpf      = $cnpj;
    $cnpjorcpftexto = ", pessoa jurídica de direito privado inscrita no CNPJ sob o nº ";
}else{
    $cnpjorcpf = $cpf;
    $cnpjorcpftexto = ", pessoa fisica inscrita no CPF sob o nº ";
}
/*$data = substr($_POST['data'],-4)."-".substr($_POST['data'],3,2)."-".substr($_POST['data'],0,2);
$data = strtotime($data);
$data = strftime('%A, %d de %B de %Y',$data);*/
//define('FPDF_FONTPATH','C:\xampp\htdocs\sind\fonte\TTF\TomSans-Regular.ttf');
require('../components/fpdf/fpdf.php');

class PDF_WriteTag extends FPDF
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
        $this->Image('../../../pictures_site-sind/logo4.png',10,8,18);
        // Arial bold 15
        $this->SetFont('Arial','B',8);
        $this->Ln(3);
        $this->SetTextColor(0,0,156);
        $this->Cell(54);//move para direita 20 posiçoes
        $this->Write(0,utf8_decode('Sindicato dos Funcionários da Prefeitura Municipal de Varginha MG,'));
        $this->Ln(4);
        $this->Cell(69);//move para direita 20 posiçoes
        $this->Write(0,utf8_decode('das Autarquias e Fundações Municipais'));
        $this->Ln(4);
        $this->Cell(79);//move para direita 20 posiçoes
        $this->Write(0,utf8_decode('CNPJ: 17.680.975/0001-00'));
        $this->Ln(15);//pula linha
        //$this->SetFont('Arial','B',8);
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
        $this->Cell(0,10,'Rua Argengina, 245 - Vila Pinto',0,0,'C');
        $this->Ln(3);
        $this->Cell(0,10,'Varginha - MG',0,0,'C');
        $this->Cell(0,15,"Pagina: ".self::$PG,0,0,'R');

        $this->SetLineWidth(0.2);
        $this->Line("7","280","201","280");
    }
    protected $wLine; // Maximum width of the line
    protected $hLine; // Height of the line
    protected $Text; // Text to display
    protected $border;
    protected $align; // Justification of the text
    protected $fill;
    protected $Padding;
    protected $lPadding;
    protected $tPadding;
    protected $bPadding;
    protected $rPadding;
    protected $TagStyle; // Style for each tag
    protected $Indent;
    protected $Space; // Minimum space between words
    protected $PileStyle;
    protected $Line2Print; // Line to display
    protected $NextLineBegin; // Buffer between lines
    protected $TagName;
    protected $Delta; // Maximum width minus width
    protected $StringLength;
    protected $LineLength;
    protected $wTextLine; // Width minus paddings
    protected $nbSpace; // Number of spaces in the line
    protected $Xini; // Initial position
    protected $href; // Current URL
    protected $TagHref; // URL for a cell
    // Public Functions
    function WriteTag($w, $h, $txt, $border=0, $align="J", $fill=false, $padding=0)
    {
        $this->wLine=$w;
        $this->hLine=$h;
        $this->Text=trim($txt);
        $this->Text=preg_replace("/\n|\r|\t/","",$this->Text);
        $this->border=$border;
        $this->align=$align;
        $this->fill=$fill;
        $this->Padding=$padding;

        $this->Xini=$this->GetX();
        $this->href="";
        $this->PileStyle=array();
        $this->TagHref=array();
        $this->LastLine=false;
        $this->NextLineBegin=array();

        $this->SetSpace();
        $this->Padding();
        $this->LineLength();
        $this->BorderTop();

        while($this->Text!="")
        {
            $this->MakeLine();
            $this->PrintLine();
        }

        $this->BorderBottom();
    }
    function SetStyle($tag, $family, $style, $size, $color, $indent=-1)
    {
        $tag=trim($tag);
        $this->TagStyle[$tag]['family']=trim($family);
        $this->TagStyle[$tag]['style']=trim($style);
        $this->TagStyle[$tag]['size']=trim($size);
        $this->TagStyle[$tag]['color']=trim($color);
        $this->TagStyle[$tag]['indent']=$indent;
    }
    // Private Functions
    function SetSpace() // Minimal space between words
    {
        $tag=$this->Parser($this->Text);
        $this->FindStyle($tag[2],0);
        $this->DoStyle(0);
        $this->Space=$this->GetStringWidth(" ");
    }
    function Padding()
    {
        if(preg_match("/^.+,/",$this->Padding)) {
            $tab=explode(",",$this->Padding);
            $this->lPadding=$tab[0];
            $this->tPadding=$tab[1];
            if(isset($tab[2]))
                $this->bPadding=$tab[2];
            else
                $this->bPadding=$this->tPadding;
            if(isset($tab[3]))
                $this->rPadding=$tab[3];
            else
                $this->rPadding=$this->lPadding;
        }
        else
        {
            $this->lPadding=$this->Padding;
            $this->tPadding=$this->Padding;
            $this->bPadding=$this->Padding;
            $this->rPadding=$this->Padding;
        }
        if($this->tPadding<$this->LineWidth)
            $this->tPadding=$this->LineWidth;
    }
    function LineLength()
    {
        if($this->wLine==0)
            $this->wLine=$this->w - $this->Xini - $this->rMargin;

        $this->wTextLine = $this->wLine - $this->lPadding - $this->rPadding;
    }
    function BorderTop()
    {
        $border=0;
        if($this->border==1)
            $border="TLR";
        $this->Cell($this->wLine,$this->tPadding,"",$border,0,'C',$this->fill);
        $y=$this->GetY()+$this->tPadding;
        $this->SetXY($this->Xini,$y);
    }
    function BorderBottom()
    {
        $border=0;
        if($this->border==1)
            $border="BLR";
        $this->Cell($this->wLine,$this->bPadding,"",$border,0,'C',$this->fill);
    }
    function DoStyle($tag) // Applies a style
    {
        $tag=trim($tag);
        $this->SetFont($this->TagStyle[$tag]['family'],
            $this->TagStyle[$tag]['style'],
            $this->TagStyle[$tag]['size']);

        $tab=explode(",",$this->TagStyle[$tag]['color']);
        if(count($tab)==1)
            $this->SetTextColor($tab[0]);
        else
            $this->SetTextColor($tab[0],$tab[1],$tab[2]);
    }
    function FindStyle($tag, $ind) // Inheritance from parent elements
    {
        $tag=trim($tag);

        // Family
        if($this->TagStyle[$tag]['family']!="")
            $family=$this->TagStyle[$tag]['family'];
        else
        {
            foreach($this->PileStyle as $val)
            {
                $val=trim($val);
                if($this->TagStyle[$val]['family']!="") {
                    $family=$this->TagStyle[$val]['family'];
                    break;
                }
            }
        }

        // Style
        $style="";
        $style1=strtoupper($this->TagStyle[$tag]['style']);
        if($style1!="N")
        {
            $bold=false;
            $italic=false;
            $underline=false;
            foreach($this->PileStyle as $val)
            {
                $val=trim($val);
                $style1=strtoupper($this->TagStyle[$val]['style']);
                if($style1=="N")
                    break;
                else
                {
                    if(strpos($style1,"B")!==false)
                        $bold=true;
                    if(strpos($style1,"I")!==false)
                        $italic=true;
                    if(strpos($style1,"U")!==false)
                        $underline=true;
                }
            }
            if($bold)
                $style.="B";
            if($italic)
                $style.="I";
            if($underline)
                $style.="U";
        }

        // Size
        if($this->TagStyle[$tag]['size']!=0)
            $size=$this->TagStyle[$tag]['size'];
        else
        {
            foreach($this->PileStyle as $val)
            {
                $val=trim($val);
                if($this->TagStyle[$val]['size']!=0) {
                    $size=$this->TagStyle[$val]['size'];
                    break;
                }
            }
        }

        // Color
        if($this->TagStyle[$tag]['color']!="")
            $color=$this->TagStyle[$tag]['color'];
        else
        {
            foreach($this->PileStyle as $val)
            {
                $val=trim($val);
                if($this->TagStyle[$val]['color']!="") {
                    $color=$this->TagStyle[$val]['color'];
                    break;
                }
            }
        }

        // Result
        $this->TagStyle[$ind]['family']=$family;
        $this->TagStyle[$ind]['style']=$style;
        $this->TagStyle[$ind]['size']=$size;
        $this->TagStyle[$ind]['color']=$color;
        $this->TagStyle[$ind]['indent']=$this->TagStyle[$tag]['indent'];
    }
    function Parser($text)
    {
        $tab=array();
        // Closing tag
        if(preg_match("|^(</([^>]+)>)|",$text,$regs)) {
            $tab[1]="c";
            $tab[2]=trim($regs[2]);
        }
        // Opening tag
        else if(preg_match("|^(<([^>]+)>)|",$text,$regs)) {
            $regs[2]=preg_replace("/^a/","a ",$regs[2]);
            $tab[1]="o";
            $tab[2]=trim($regs[2]);

            // Presence of attributes
            if(preg_match("/(.+) (.+)='(.+)'/",$regs[2])) {
                $tab1=preg_split("/ +/",$regs[2]);
                $tab[2]=trim($tab1[0]);
                foreach($tab1 as $i=>$couple)
                {
                    if($i>0) {
                        $tab2=explode("=",$couple);
                        $tab2[0]=trim($tab2[0]);
                        $tab2[1]=trim($tab2[1]);
                        $end=strlen($tab2[1])-2;
                        $tab[$tab2[0]]=substr($tab2[1],1,$end);
                    }
                }
            }
        }
        // Space
        else if(preg_match("/^( )/",$text,$regs)) {
            $tab[1]="s";
            $tab[2]=' ';
        }
        // Text
        else if(preg_match("/^([^< ]+)/",$text,$regs)) {
            $tab[1]="t";
            $tab[2]=trim($regs[1]);
        }

        $begin=strlen($regs[1]);
        $end=strlen($text);
        $text=substr($text, $begin, $end);
        $tab[0]=$text;

        return $tab;
    }
    function MakeLine()
    {
        $this->Text.=" ";
        $this->LineLength=array();
        $this->TagHref=array();
        $Length=0;
        $this->nbSpace=0;

        $i=$this->BeginLine();
        $this->TagName=array();

        if($i==0) {
            $Length=$this->StringLength[0];
            $this->TagName[0]=1;
            $this->TagHref[0]=$this->href;
        }

        while($Length<$this->wTextLine)
        {
            $tab=$this->Parser($this->Text);
            $this->Text=$tab[0];
            if($this->Text=="") {
                $this->LastLine=true;
                break;
            }

            if($tab[1]=="o") {
                array_unshift($this->PileStyle,$tab[2]);
                $this->FindStyle($this->PileStyle[0],$i+1);

                $this->DoStyle($i+1);
                $this->TagName[$i+1]=1;
                if($this->TagStyle[$tab[2]]['indent']!=-1) {
                    $Length+=$this->TagStyle[$tab[2]]['indent'];
                    $this->Indent=$this->TagStyle[$tab[2]]['indent'];
                }
                if($tab[2]=="a")
                    $this->href=$tab['href'];
            }

            if($tab[1]=="c") {
                array_shift($this->PileStyle);
                if(isset($this->PileStyle[0]))
                {
                    $this->FindStyle($this->PileStyle[0],$i+1);
                    $this->DoStyle($i+1);
                }
                $this->TagName[$i+1]=1;
                if($this->TagStyle[$tab[2]]['indent']!=-1) {
                    $this->LastLine=true;
                    $this->Text=trim($this->Text);
                    break;
                }
                if($tab[2]=="a")
                    $this->href="";
            }

            if($tab[1]=="s") {
                $i++;
                $Length+=$this->Space;
                $this->Line2Print[$i]="";
                if($this->href!="")
                    $this->TagHref[$i]=$this->href;
            }

            if($tab[1]=="t") {
                $i++;
                $this->StringLength[$i]=$this->GetStringWidth($tab[2]);
                $Length+=$this->StringLength[$i];
                $this->LineLength[$i]=$Length;
                $this->Line2Print[$i]=$tab[2];
                if($this->href!="")
                    $this->TagHref[$i]=$this->href;
            }

        }

        trim($this->Text);
        if($Length>$this->wTextLine || $this->LastLine==true)
            $this->EndLine();
    }
    function BeginLine()
    {
        $this->Line2Print=array();
        $this->StringLength=array();

        if(isset($this->PileStyle[0]))
        {
            $this->FindStyle($this->PileStyle[0],0);
            $this->DoStyle(0);
        }

        if(count($this->NextLineBegin)>0) {
            $this->Line2Print[0]=$this->NextLineBegin['text'];
            $this->StringLength[0]=$this->NextLineBegin['length'];
            $this->NextLineBegin=array();
            $i=0;
        }
        else {
            preg_match("/^(( *(<([^>]+)>)* *)*)(.*)/",$this->Text,$regs);
            $regs[1]=str_replace(" ", "", $regs[1]);
            $this->Text=$regs[1].$regs[5];
            $i=-1;
        }

        return $i;
    }
    function EndLine()
    {
        if(end($this->Line2Print)!="" && $this->LastLine==false) {
            $this->NextLineBegin['text']=array_pop($this->Line2Print);
            $this->NextLineBegin['length']=end($this->StringLength);
            array_pop($this->LineLength);
        }

        while(end($this->Line2Print)==="")
            array_pop($this->Line2Print);

        $this->Delta=$this->wTextLine-end($this->LineLength);

        $this->nbSpace=0;
        for($i=0; $i<count($this->Line2Print); $i++) {
            if($this->Line2Print[$i]=="")
                $this->nbSpace++;
        }
    }
    function PrintLine()
    {
        $border=0;
        if($this->border==1)
            $border="LR";
        $this->Cell($this->wLine,$this->hLine,"",$border,0,'C',$this->fill);
        $y=$this->GetY();
        $this->SetXY($this->Xini+$this->lPadding,$y);

        if($this->Indent!=-1) {
            if($this->Indent!=0)
                $this->Cell($this->Indent,$this->hLine);
            $this->Indent=-1;
        }

        $space=$this->LineAlign();
        $this->DoStyle(0);
        for($i=0; $i<count($this->Line2Print); $i++)
        {
            if(isset($this->TagName[$i]))
                $this->DoStyle($i);
            if(isset($this->TagHref[$i]))
                $href=$this->TagHref[$i];
            else
                $href='';
            if($this->Line2Print[$i]=="")
                $this->Cell($space,$this->hLine,"         ",0,0,'C',false,$href);
            else
                $this->Cell($this->StringLength[$i],$this->hLine,$this->Line2Print[$i],0,0,'C',false,$href);
        }

        $this->LineBreak();
        if($this->LastLine && $this->Text!="")
            $this->EndParagraph();
        $this->LastLine=false;
    }
    function LineAlign()
    {
        $space=$this->Space;
        if($this->align=="J") {
            if($this->nbSpace!=0)
                $space=$this->Space + ($this->Delta/$this->nbSpace);
            if($this->LastLine)
                $space=$this->Space;
        }

        if($this->align=="R")
            $this->Cell($this->Delta,$this->hLine);

        if($this->align=="C")
            $this->Cell($this->Delta/2,$this->hLine);

        return $space;
    }
    function LineBreak()
    {
        $x=$this->Xini;
        $y=$this->GetY()+$this->hLine;
        $this->SetXY($x,$y);
    }
    function EndParagraph()
    {
        $border=0;
        if($this->border==1)
            $border="LR";
        $this->Cell($this->wLine,$this->hLine/2,"",$border,0,'C',$this->fill);
        $x=$this->Xini;
        $y=$this->GetY()+$this->hLine/2;
        $this->SetXY($x,$y);
    }
}
$convenio_aux = "";
$aux = 0;
//$date = strftime('%A, %d de %B de %Y', strtotime('today'));
//$date = strftime('%A, %d de %B de %Y', $data);
$pdf = new PDF_WriteTag();
//$pdf->SetMargins(30,15,25);
$pdf->SetFont('Arial','',12);
$pdf->AddPage();
$pdf->SetStyle("p","Arial","N",12,"0,0,0",15);
$pdf->SetStyle("h1","Arial","N",16,"0,0,0",0);
$pdf->SetStyle("a","Times","BU",9,"0,0,255");
$pdf->SetStyle("pers","Times","I",0,"255,0,0");
$pdf->SetStyle("place","Arial","U",0,"153,0,0");
$pdf->SetStyle("vb","Times","B",12,"0,0,0");

PDF_WriteTag::setPG(1);

$pdf->Ln(13);
$titulo = utf8_decode("<h1>CONTRATO DE CREDENCIAMENTO AO CONVÊNIO SINDSERVA</h1>");
$pdf->WriteTag(0, 4,$titulo,0,'C',0,0);
$pdf->Ln(13);

$y = $pdf->GetY();
$pdf->SetXY(10,$y);
$objeto = utf8_decode("<p>Sindicato dos Funcionários da Prefeitura Municipal de Varginha-MG, das autarquias e 
                               das Fundações Municipais, inscrita no CNPJ/MF sob o nº 17.680.975/0001-00, com sede 
                               administrativa na cidade de Varginha/MG na Rua Argentina, 245 Vila Pinto, doravante 
                               denominada <vb>CONVENIO SINDSERVA</vb> e ".$razaosocial." ".$cnpjorcpftexto.$cnpjorcpf.", com sede administrativa na cidade de $cidade - $estado, na $endereco, $numero - $bairro $complemento, doravante denominada <vb>CONVENIADO</vb>.</p>
                            <p>OBJETO DO CONTRATO. O presente contrato tem por objeto o credenciamento do 
                               ESTABELECIMENTO ao SISTEMA de cartão CONVENIO SINDSERVA, através do 
                               recebimento, aceitação e/ou utilização pelo ESTABELECIMENTO, do cartão SINDSERVA e 
                               APLICATIVO denominados CARTÃO DO CONVENIO SINDSERVA que venham a ser 
                               utilizados por trabalhadores associados ao CONVENIO SINDSERVA.</p>
                            <p><vb>1º</vb> O CONVENIADO está autorizado a fornecer produtos e prestação de serviços aos 
                               titulares do cartão, CONVENIO SINDSERVA;</p>
                            <p><vb>2º</vb> Os valores lançados no sistema web ou aplicativo serão descontados na folha de 
                               pagamento do titular do cartão;</p>
                            <p><vb>3º</vb> O CONVENIADO deverá exigir assinatura do portador do cartão CONVENIO 
                               SINDSERVA no comprovante de compra emitido pelo sistema web ou no cupom fiscal ou 
                               qualquer documento que comprove o fornecimento de produtos e serviços;</p>
                            <p><vb>4º</vb> O CONVENIO SINDSERVA compromete se a Implantar, organizar, manter e 
                               gerenciar o SISTEMA e o APLICATIVO junto à empresa conveniada;</p>
                            <p><vb>5º</vb> O CONVENIADO compromete-se a guardar os comprovantes devidamente 
                               assinados durante cinco anos;</p>
                            <p><vb>6º</vb> O CONVENIADO receberá o pagamento das vendas ou prestação de serviços 
                               através de cheque nominal impresso em duas vias com recibo, após 30 dias da data de 
                               fechamento do convenio. O pagamento estará disponível no endereço: Av Rio branco, 417, sala 305 - Centro, Varginha - MG, de segunda a sexta-feira de 13:00 as 17:00 hrs;</p>");
$pdf->WriteTag(180, 6.5, $objeto,0,'J',0,0);
$pdf->AddPage();
PDF_WriteTag::setPG(2);
$objeto = utf8_decode("<p><vb>7º</vb> O CONVENIADO poderá definir o preço praticado na venda de produtos ou 
                               prestação de serviços;</p>
                            <p><vb>8º</vb> O período de fornecimento produtos e serviços inicia se no dia 4 (quatro) de cada 
                               mês e encerrará no dia 3 (três) do mês subsequente;</p>
                            <p><vb>9º</vb> O CONVENIO SINDSERVA não irá fazer o repasse dos valores dos produtos ou 
                               serviços que não tiverem devidamente cadastrados no sistema ou não possuir o comprovante 
                               assinado pelo portador do cartão;</p>
                            <p><vb>10º</vb> A Taxa administrativa do cartão CONVENIO SINDSERVA é de (4,0%), sendo que 
                               1% vem descontado no cheque e 3% enviamos um boleto bancário para pagamento;</p>
                            <p><vb>11º</vb> O CONVENIADO tem duas opções para lançar os valores no sistema, através do 
                               site www.makecard.com.br, ou através do Aplicativo disponível para androide no GooglePlay;</p>
                            <p><vb>12º</vb> O CONVENIADO se compromete a fornecer produtos ou prestação de serviços 
                               somente para os associados que possuírem o cartão de identificação ou o Aplicativo no celular;</p>
                            <p><vb>13º</vb> O CONVENIADO não pode fornecer produtos ou serviços em outro endereço 
                               diferente ao deste contrato. Para utilizar outro endereço é necessário fazer um novo cadastro;</p>
                            <p><vb>14º</vb> O CONVENIADO autoriza a divulgação e a publicação da logomarca e informações 
                               cadastrais no site do convenio e no Aplicativo e em outros meios de divulgação, renunciando a 
                               qualquer pagamento referente a direito de imagem;</p>
                            <p><vb>15º</vb> As partes elegem o foro da Comarca de Varginha-MG, para dirimir qualquer 
                               controvérsia em razão do presente instrumento.</p>");

$pdf->WriteTag(180, 6.5, $objeto,0,'J',0,0);

$pdf->Ln(8);
$objeto = "<p><p>Varginha, ".$data_cadastro.".</p></p>";
$pdf->WriteTag(180, 6, $objeto,0,'J',0,0);

$pdf->Ln(4);
$objeto = "<p><p>CONVENIO SINDSERVA</p></p>";
$pdf->WriteTag(180, 6, $objeto,0,'C',0,0);
$pdf->Ln();
if(!file_exists($caminho)) {
    $pdf->Output('F', $caminho . '//' . $razaosocial . ".pdf");
    $caminhox = $caminho . '//' . $razaosocial . ".pdf";
    enviar_email($caminhox,$email,$razaosocial);
}else{
    excluiDir($caminho);
    mkdir($caminho,0777,true);
    $pdf->Output('F', $caminho . '//' . $razaosocial . ".pdf");
    $caminhox = $caminho . '//' . $razaosocial . ".pdf";
    enviar_email($caminhox,$email,$razaosocial);
}
function excluiDir($dir){
    if ($dd = opendir($dir)) {
        while (false !== ($arq = readdir($dd))) {
            if($arq != "." && $arq != ".."){
                $path = "$dir/$arq";
                if(is_dir($path)){
                    excluiDir($path);
                }elseif(is_file($path)){
                    unlink($path);
                }
            }
        }
        closedir($dd);
    }
    rmdir($dir);
}
function enviar_email($caminho,$email_convenio,$nome_convenio){
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        //$mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;                      // Enable verbose debug output
        $mail->isSMTP();                               // Send using SMTP
        $mail->Host       = 'zeus.iphosting.com.br';   // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                      // Enable SMTP authentication
        $mail->Username   = 'suporte@makecard.com.br'; // SMTP username
        $mail->Password   = 'Kb109733*123';            // SMTP password
        $mail->SMTPSecure = 'tls';
        //\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = 587;       // TCP port to connect to
        // anexar arquivo
        $mail->AddAttachment($caminho,
            $name = 'contrato convenio',
            $encoding = 'base64',
            $type = 'application/pdf');
        //Recipients
        $mail->setFrom('suporte@makecard.com.br', 'Administrador');
        $mail->addAddress($email_convenio, $nome_convenio);     // Add a recipient
        $mail->addReplyTo('no-reply@makecard.com.br', 'No reply');

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Contrato convenio Sindserva';
        $mail->Body    = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
                            <html xmlns=\"http://www.w3.org/1999/xhtml\">
                            <head>
                                <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
                                <title>Email de confirmação</title>
                                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"/>
                            </head>
                            <body style=\"margin: 0; padding: 0;\">
                            <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                <tr>
                                    <td style=\"padding: 10px 0 30px 0;\">
                                        <table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"600\" style=\"border: 1px solid #cccccc; border-collapse: collapse;\">
                                            <tr style=\"border-bottom:1px solid #cccccc;\">
                                                <td align=\"center\" bgcolor=\"#ffffff\" style=\"padding: 40px 0 20px 0; color: #153643; font-size: 28px; font-weight: bold; font-family: Arial, sans-serif;\">
                                                    <img src=\"https://sind.makecard.com.br/Adm/pages/convenio/logo.png\" alt=\"Contrato\" width=\"100\" height=\"90\" style=\"display: block;\" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td bgcolor=\"#ffffff\" style=\"padding: 40px 30px 40px 30px;\">
                                                    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                                        <tr>
                                                            <td style=\"color: #153643; font-family: Arial, sans-serif; font-size: 24px;\">
                                                                <b>Contrato do convenio Sindserva</b>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style=\"padding: 20px 0 30px 0; color: #153643; font-family: Arial, sans-serif; font-size: 16px; line-height: 20px;\">
                                                                <p class='mb-0'>Seu em anexo o contrato do convenio<br/><br/></p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr style=\"border-top:1px solid #cccccc;\">
                                                <td bgcolor=\"#ffffff\" style=\"padding: 30px 30px 30px 30px;\">
                                                    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                                        <tr>
                                                            <td style=\"color: #000000; font-family: Arial, sans-serif; font-size: 14px;\" width=\"75%\">
                                                                &reg; MAKECARD 2021<br/>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            </body>
                            </html>";

        $mail->send();

        $msgdeenvio="<div class=\"container\">
                        <div id=\"loginbox\" style=\"margin-top:50px;\" class=\"mainbox col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2\">
                            <div class=\"panel panel-info\" >
                                <div class=\"panel-heading\">
                                    <div class=\"panel-title\">Atenção</div>
                                </div>
                                <div style=\"padding-top:30px\" class=\"panel-body\" >
                                    <div style=\"display:none\" id=\"login-alert\" class=\"alert alert-danger col-sm-12\"></div>
                                    <p>Enviamos um E-mail para <b> $email_convenio </b></p>
                                   
                                </div>
                            </div>
                        </div>
                    </div>";
        echo $msgdeenvio;
    } catch (Exception $e) {
        echo "Mensagem não pode ser enviada. Mailer Error: {$mail->ErrorInfo}";
    }
}
$sql = "UPDATE sind.convenio SET ";
$sql .= "contrato = :contrato ";
$sql .= "WHERE codigo = :codigo_convenio";

$_contrato = true;

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':codigo_convenio', $_codigo, PDO::PARAM_INT);
$stmt->bindParam(':contrato', $_contrato, PDO::PARAM_STR);

$stmt->execute();