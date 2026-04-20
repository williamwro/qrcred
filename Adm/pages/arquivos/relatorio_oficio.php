<?php
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('America/Araguaina');

// Verificar se a extensão mbstring está disponível (necessária para mb_convert_encoding)
if (!extension_loaded('mbstring')) {
    die('Erro: A extensão mbstring do PHP é necessária para este relatório.');
}
include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$mes_atual = $_POST['mes_atual'];
$empregador = $_POST['empregador'];
$divisao    = $_POST['divisao'];
$divisao_nome = $_POST['divisao_nome'];

// Buscar período do mês na tabela meses_conta
$periodo_mes = '';
$sql_periodo = $pdo->prepare("SELECT periodo FROM sind.meses_conta WHERE abreviacao = :mes_atual AND divisao = :divisao LIMIT 1");
$sql_periodo->bindParam(':mes_atual', $mes_atual, PDO::PARAM_STR);
$sql_periodo->bindParam(':divisao', $divisao, PDO::PARAM_INT);
$sql_periodo->execute();
$row_periodo = $sql_periodo->fetch();
if ($row_periodo && !empty($row_periodo['periodo'])) {
    $periodo_mes = $row_periodo['periodo'];
}

if (isset($_POST['tipo']) && !empty($_POST['tipo'])) {
    $tipo = $_POST['tipo'];
    $sql = $pdo->prepare("SELECT nome FROM sind.tipoconvenio WHERE codigo = :tipo");
    $sql->bindParam(':tipo', $tipo, PDO::PARAM_INT);
    $sql->execute();
    $row = $sql->fetch();
    if ($row) {
        $nome_tipo = $row['nome'];
    } else {
        $nome_tipo = "Todos";
    }
} else {
    $nome_tipo = "Todos";
    $tipo = null;
}

// Receber data de vencimento do formulário
if (isset($_POST['data_vencimento']) && !empty($_POST['data_vencimento'])) {
    // Converter de formato YYYY-MM-DD para DD/MM/YYYY
    $data_venc_input = $_POST['data_vencimento'];
    $data_parts = explode('-', $data_venc_input);
    if (count($data_parts) == 3) {
        $data_vencimento = sprintf("%02d/%02d/%04d", (int)$data_parts[2], (int)$data_parts[1], (int)$data_parts[0]);
    } else {
        $data_vencimento = $data_venc_input;
    }
} else {
    // Calcular data de vencimento padrão (dia 17 do mês seguinte) se não informada
    $mes_atual_array = explode('/', $mes_atual);
    if (count($mes_atual_array) == 2) {
        $mes_num = (int)$mes_atual_array[0];
        $ano_num = (int)$mes_atual_array[1];
        
        $mes_seguinte = $mes_num + 1;
        $ano_vencimento = $ano_num;
        
        if ($mes_seguinte > 12) {
            $mes_seguinte = 1;
            $ano_vencimento++;
        }
        
        $data_vencimento = sprintf("17/%02d/%04d", $mes_seguinte, $ano_vencimento);
    } else {
        $data_vencimento = "17/__/____";
    }
}

// Data atual para o fechamento do ofício
$data_atual = date('d \d\e F \d\e Y');
$meses_pt = array(
    'January' => 'janeiro', 'February' => 'fevereiro', 'March' => 'março',
    'April' => 'abril', 'May' => 'maio', 'June' => 'junho',
    'July' => 'julho', 'August' => 'agosto', 'September' => 'setembro',
    'October' => 'outubro', 'November' => 'novembro', 'December' => 'dezembro'
);
$data_atual = str_replace(array_keys($meses_pt), array_values($meses_pt), $data_atual);

require('rotation.php');

class PDF_Oficio extends PDF_Rotate
{
    private static $RS;
    public static function setRS( $RSL ) {
        self::$RS = $RSL;
    }
    private static $MS;
    public static function setMS( $MES ) {
        self::$MS = $MES;
    }
    private static $TP;
    public static function setTIPO( $TIPO ) {
        self::$TP = $TIPO;
    }
    private static $PG;
    public static function setPG( $PAGINA ) {
        self::$PG = $PAGINA;
    }
    private static $DV;
    public static function getDV( $DIVISAOX ) {
        return self::$DV = $DIVISAOX;
    }
    private static $DN;
    public static function setDN( $DIVISAON ) {
        self::$DN = $DIVISAON;
    }
    public static function getDN_get( $DIVISAON ) {
        return self::$DN = $DIVISAON;
    }
    private static $DT_VENC;
    public static function setDataVencimento( $data ) {
        self::$DT_VENC = $data;
    }
    private static $PERIODO;
    public static function setPeriodo( $periodo ) {
        self::$PERIODO = $periodo;
    }
    private static $PRIMEIRA_PAGINA = true;
    
    // Page header
    function Header()
    {
        // Logo
        $this->Image('../../../pictures_site-sind/logo_saspng.png',10,8,18);
        // Arial bold 12
        $this->SetFont('Arial','B',11);

        $this->Cell(22);
        $this->Write(0,mb_convert_encoding('Ofício Cartão Convênio - Relatório de Utilização e Solicitação de Desconto', 'ISO-8859-1', 'UTF-8'));

        $this->Ln();
        $this->Cell(22);
        $this->SetFont('Arial','B',10);
        $this->Write(12,"Empregador: ".mb_convert_encoding(self::$RS, 'ISO-8859-1', 'UTF-8'));

        $this->Ln();
        $this->Cell(22);
        $this->SetFont('Arial','B',9);
        $this->Write(0,mb_convert_encoding("Mês: ", 'ISO-8859-1', 'UTF-8'));
        $this->SetFont('Arial','B',9);
        $this->Write(0,self::$MS);
        if (!empty(self::$PERIODO)) {
            $this->SetFont('Arial','B',8);
            $this->Write(0,' - ' . mb_convert_encoding(self::$PERIODO, 'ISO-8859-1', 'UTF-8'));
        }

        $this->Cell(12);
        $this->SetFont('Arial','B',9);
        $this->Write(0,date('d/m/Y'));

        $this->Cell(10);
        $this->SetFont('Arial','B',9);
        $this->Write(0,date('H:i:s'));

        $this->Cell(18);
        $this->SetFont('Arial','B',9);
        $this->Write(0,mb_convert_encoding("Tipo: ", 'ISO-8859-1', 'UTF-8'));
        $this->SetFont('Arial','B',9);
        $this->Write(0,self::$TP);

        $this->Cell(20);
        $this->SetFont('Arial','B',9);
        $this->Write(0,"Pagina: ");
        $this->SetFont('Arial','B',9);
        $this->Write(0,self::$PG);

        
       
               
     

        // Texto introdutório apenas na primeira página
        if (self::$PRIMEIRA_PAGINA) {
            $this->Ln(10);
            $this->SetFont('Arial','',10);
            
            $this->MultiCell(0, 5, mb_convert_encoding("Ilmo. Representante Legal", 'ISO-8859-1', 'UTF-8'));
            $this->Ln(2);
            $this->MultiCell(0, 5, mb_convert_encoding("Prezado Senhor,", 'ISO-8859-1', 'UTF-8'));
            $this->Ln(2);
            
            $texto_intro = "Ao tempo de cumprimentá-lo, serve do presente para encaminhar o relatório de utilização dos convênios para o efetivo desconto e repasse até o dia " . self::$DT_VENC . " no valor descrito na relação de funcionários e valores abaixo descritos, no amparo do artigo 462 da CLT e Cláusula 28ª da CCT, sendo:";
            $this->MultiCell(0, 5, mb_convert_encoding($texto_intro, 'ISO-8859-1', 'UTF-8'), 0, 'J');
            
            $this->Ln(10);
            self::$PRIMEIRA_PAGINA = false;
        } else {
            $this->Ln(8);
        }

        // Cabeçalho da tabela
        $this->SetFont('Arial','B',7);

        $this->Cell(21,-6,"CPF",0,0,'L');
        $this->Cell(70,-6,"Nome",0,0,'L');
        $this->Cell(25,-6,"Adiantamento",0,0,'R');
        $this->Cell(25,-6,mb_convert_encoding("Cartão", 'ISO-8859-1', 'UTF-8'),0,0,'R');
        $this->Cell(25,-6,mb_convert_encoding("Taxa de adm /", 'ISO-8859-1', 'UTF-8'),0,0,'R');
        $this->Cell(18,-6,"Total",0,0,'R');
        $this->Ln(4);
        $this->Cell(140);
        $this->Cell(25,-6,mb_convert_encoding("manutenção", 'ISO-8859-1', 'UTF-8'),0,0,'R');

        // Line break
        $this->Ln(0);
        // Linha horizontal
        $this->SetLineWidth(0.2);
        $y_pos = $this->GetY();
        $this->Line(11, $y_pos, 205, $y_pos);
        
        // Insere marca d'água
        $this->SetFont('Arial','B',50);
        $this->SetTextColor(255,192,203);
        $this->RotatedText(55,200,'S  A  S  C  R  E  D',45);
    }
    
    function RotatedText($x, $y, $txt, $angle)
    {
        $this->Rotate($angle,$x,$y);
        $this->Text($x,$y,$txt);
        $this->Rotate(0);
    }
    
    // Page footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,self::$DN,0,0,'C');
        $this->SetLineWidth(0.2);
        $this->Line(7,280,201,280);
    }
}

$sql_emp = $pdo->query("SELECT * FROM sind.empregador WHERE id = ".$empregador);
$result = $sql_emp->fetch(PDO::FETCH_ASSOC);

$pagina=1;
PDF_Oficio::setMS($mes_atual);
PDF_Oficio::setRS($result['nome']);
PDF_Oficio::setPG($pagina);
PDF_Oficio::setTIPO($nome_tipo);
PDF_Oficio::getDV($divisao);
PDF_Oficio::setDN($divisao_nome);
PDF_Oficio::setDataVencimento($data_vencimento);
PDF_Oficio::setPeriodo($periodo_mes);

$item_pagina  = 0;
$registros    = 0;
$total        = 0;
$emprestimo      = 0;
$emprestimo_tot  = 0;
$cartao     = 0;
$cartao_tot = 0;
$taxacartao     = 0;
$taxacartao_tot = 0;
$unimed       = 0;
$unimed_tot   = 0;
$total_assoc  = 0;

if (isset($_POST["empregador"]) and $_POST["mes_atual"] != "" and $tipo === null ) {
    $query = "SELECT codigo,nome,sum(valor) as valor,empregador,tipoconvenio,cpf
                FROM sind.qrelatoriofinal 
               WHERE empregador = " . $_POST["empregador"] . " 
                 AND mes = '" . $_POST["mes_atual"] . "'
                 AND (aprovado = true OR aprovado IS NULL)
            GROUP BY codigo, nome, tipoconvenio,empregador,cpf
            ORDER BY nome";
}else{
    $query = "SELECT codigo,nome,sum(valor) as valor,empregador,tipoconvenio,cpf
                FROM sind.qrelatoriofinal 
               WHERE empregador = " . $_POST["empregador"] . " 
                 AND mes = '" . $_POST["mes_atual"] . "'
                 AND tipoconvenio = " . $tipo . "
                 AND (aprovado = true OR aprovado IS NULL)
            GROUP BY codigo, nome, tipoconvenio,empregador,cpf
            ORDER BY nome";
}

PDF_Oficio::setMS($mes_atual);
$convenio_aux="";
$aux = 0;
$total_paginas=0;
$pdf = new PDF_Oficio();
$pdf->AliasNbPages();
$pdf->AddPage('P');
$pdf->SetFont('Arial','B',7);
$aux_cpf = "";
$aux_nome = "";
$sql_conv_vendas = $pdo->query($query);

while($row = $sql_conv_vendas->fetch()) {
    if ($registros == 0){
        $registros = 1;
        $aux_cpf = $row['cpf'];
        $aux_nome = $row['nome'];
        if ($row['tipoconvenio'] == 1) {
            $cartao += $row['valor'];
            $cartao_tot += $row['valor'];
        }
        if ($row['tipoconvenio'] == 2) {
            $emprestimo += $row['valor'];
            $emprestimo_tot += $row['valor'];
        }
        if ($row['tipoconvenio'] == 3) {
            $taxacartao += $row['valor'];
            $taxacartao_tot += $row['valor'];
        }
        $total_assoc = $cartao + $emprestimo + $taxacartao;
        $valor = floatval($row['valor']);
        $total += $valor;
        $valor = number_format($valor, 2, ',', '.');
    }else{
        if($aux_cpf === $row['cpf']) {
            if ($row['tipoconvenio'] == 1) {
                $cartao += $row['valor'];
                $cartao_tot += $row['valor'];
            }
            if ($row['tipoconvenio'] == 2) {
                $emprestimo += $row['valor'];
                $emprestimo_tot += $row['valor'];
            }
            if ($row['tipoconvenio'] == 3) {
                $taxacartao += $row['valor'];
                $taxacartao_tot += $row['valor'];
            }
            $total_assoc = $cartao + $emprestimo + $taxacartao;
            $valor = floatval($row['valor']);
            $total += $valor;
            $valor = number_format($valor, 2, ',', '.');
        }else{
            $registros++;
            $item_pagina++;

            $pdf->Cell(21, 4, $aux_cpf,0,0,'C');
            $pdf->Cell(70, 4, mb_convert_encoding($aux_nome, 'ISO-8859-1', 'UTF-8'),0,0,'L');
            $pdf->Cell(25, 4, number_format($emprestimo, 2, ',', '.'),0,0,'R');
            $pdf->Cell(25, 4, number_format($cartao, 2, ',', '.'),0,0,'R');
            $pdf->Cell(25, 4, number_format($taxacartao, 2, ',', '.'),0,0,'R');
            $pdf->Cell(18, 4, number_format($total_assoc, 2, ',', '.'),0,0,'R');
            $pdf->Ln();

            $cartao    = 0;
            $emprestimo     = 0;
            $taxacartao    = 0;
            $total_assoc = 0;

            $aux_cpf = $row['cpf'];
            $aux_nome = $row['nome'];
            if ($row['tipoconvenio'] == 1) {
                $cartao += $row['valor'];
                $cartao_tot += $row['valor'];
            }
            if ($row['tipoconvenio'] == 2) {
                $emprestimo += $row['valor'];
                $emprestimo_tot += $row['valor'];
            }
            if ($row['tipoconvenio'] == 3) {
                $taxacartao += $row['valor'];
                $taxacartao_tot += $row['valor'];
            }
            $total_assoc = $cartao + $emprestimo + $taxacartao;
            $total += $row['valor'];
        }
    }
    if ($item_pagina  ==  45){  // Reduzido para 45 linhas por causa do texto introdutório
        $pagina = $pagina + 1;
        $item_pagina = 0;
        $pdf->Ln(4);
        PDF_Oficio::setPG($pagina);
        $pdf->AddPage('P');
        $pdf->SetFont('Arial','B',7);
    }
}

// Última linha
$pdf->Cell(21, 4, $aux_cpf,0,0,'C');
$pdf->Cell(70, 4, mb_convert_encoding($aux_nome, 'ISO-8859-1', 'UTF-8'),0,0,'L');
$pdf->Cell(25, 4, number_format($emprestimo, 2, ',', '.'),0,0,'R');
$pdf->Cell(25, 4, number_format($cartao, 2, ',', '.'),0,0,'R');
$pdf->Cell(25, 4, number_format($taxacartao, 2, ',', '.'),0,0,'R');
$pdf->Cell(18, 4, number_format($total_assoc, 2, ',', '.'),0,0,'R');
$pdf->Ln();

$cartao      = 0;
$emprestimo  = 0;
$taxacartao  = 0;
$total_assoc = 0;

PDF_Oficio::setPG($pagina);

// SOMAS DA ULTIMA PAGINA
$pdf->Cell(15, 14, "Registros: ".$registros, 0, 0, 'L');
$pdf->Cell(102, 10, number_format($emprestimo_tot, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(25, 10, number_format($cartao_tot, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(25, 10, number_format($taxacartao_tot, "2", ",", "."), 0, 0, 'R');
$pdf->Cell(18, 10, number_format($total, "2", ",", "."), 0, 0, 'R');
$pdf->Ln(10);

// Texto de fechamento do ofício
$pdf->Ln(5);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0, 5, mb_convert_encoding("O repasse dos referidos valores para a empresa SAS Convênio, conforme indicado pelo SINEMPREVS/MT (que nos lê em cópia) e autorizado pela cláusula 28ª da CCT e o artigo 462 da CLT, através do pagamento do boleto em anexo;", 'ISO-8859-1', 'UTF-8'), 0, 'J');
$pdf->Ln(5);

$pdf->MultiCell(0, 5, mb_convert_encoding("Outrossim, segue em anexo:", 'ISO-8859-1', 'UTF-8'));
$pdf->Ln(2);

$pdf->Cell(10);
$pdf->MultiCell(0, 5, mb_convert_encoding("1 - Relação de funcionários e valores utilizados (a serem descontados);", 'ISO-8859-1', 'UTF-8'));
$pdf->Cell(10);
$pdf->MultiCell(0, 5, mb_convert_encoding("2 - Boleto de Cobrança;", 'ISO-8859-1', 'UTF-8'));
$pdf->Ln(5);

$pdf->MultiCell(0, 5, mb_convert_encoding("Alphaville, Barueri/SP, " . $data_atual, 'ISO-8859-1', 'UTF-8'));
$pdf->Ln(5);

$pdf->MultiCell(0, 5, mb_convert_encoding("Atenciosamente,", 'ISO-8859-1', 'UTF-8'));

$total        = 0;
$cartao     = 0;
$cartao_tot = 0;
$taxacartao     = 0;
$taxacartao_tot = 0;
$emprestimo      = 0;
$emprestimo_tot  = 0;
$total_assoc  = 0;
$registros    = 0;

$pdf->Output('I',"oficio-".$mes_atual."-".$divisao_nome.".pdf");
