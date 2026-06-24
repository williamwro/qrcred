<?php
/**
 * relatorio_todos_oficio.php
 * Gera um único PDF com o ofício de todos os empregadores do mês,
 * seguindo o padrão de relatorio_todos_empregadores.php + relatorio_oficio.php
 */
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('America/Araguaina');

if (!extension_loaded('mbstring')) {
    die('Erro: A extensão mbstring do PHP é necessária para este relatório.');
}

include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mes_atual    = $_POST['mes_atual'];
$divisao      = $_POST['divisao'];
$divisao_nome = $_POST['divisao_nome'];

// Data de vencimento
if (isset($_POST['data_vencimento']) && !empty($_POST['data_vencimento'])) {
    $data_venc_input = $_POST['data_vencimento'];
    $data_parts = explode('-', $data_venc_input);
    if (count($data_parts) == 3) {
        $data_vencimento = sprintf("%02d/%02d/%04d", (int)$data_parts[2], (int)$data_parts[1], (int)$data_parts[0]);
    } else {
        $data_vencimento = $data_venc_input;
    }
} else {
    $mes_atual_array = explode('/', $mes_atual);
    if (count($mes_atual_array) == 2) {
        $mes_num = (int)$mes_atual_array[0];
        $ano_num = (int)$mes_atual_array[1];
        $mes_seguinte = $mes_num + 1;
        $ano_vencimento = $ano_num;
        if ($mes_seguinte > 12) { $mes_seguinte = 1; $ano_vencimento++; }
        $data_vencimento = sprintf("17/%02d/%04d", $mes_seguinte, $ano_vencimento);
    } else {
        $data_vencimento = "17/__/____";
    }
}

// Período do mês
$periodo_mes = '';
$sql_periodo = $pdo->prepare("SELECT periodo FROM sind.meses_conta WHERE abreviacao = :mes_atual AND divisao = :divisao LIMIT 1");
$sql_periodo->bindParam(':mes_atual', $mes_atual, PDO::PARAM_STR);
$sql_periodo->bindParam(':divisao',   $divisao,   PDO::PARAM_INT);
$sql_periodo->execute();
$row_periodo = $sql_periodo->fetch();
if ($row_periodo && !empty($row_periodo['periodo'])) {
    $periodo_mes = $row_periodo['periodo'];
}

// Data atual por extenso
$data_atual = date('d \d\e F \d\e Y');
$meses_pt = [
    'January'   => 'janeiro',   'February' => 'fevereiro', 'March'     => 'março',
    'April'     => 'abril',     'May'      => 'maio',       'June'      => 'junho',
    'July'      => 'julho',     'August'   => 'agosto',     'September' => 'setembro',
    'October'   => 'outubro',   'November' => 'novembro',   'December'  => 'dezembro',
];
$data_atual = str_replace(array_keys($meses_pt), array_values($meses_pt), $data_atual);

// Tipo de convênio
if (isset($_POST['tipo']) && !empty($_POST['tipo'])) {
    $tipo = $_POST['tipo'];
    $sql = $pdo->prepare("SELECT nome FROM sind.tipoconvenio WHERE codigo = :tipo");
    $sql->bindParam(':tipo', $tipo, PDO::PARAM_INT);
    $sql->execute();
    $row = $sql->fetch();
    $nome_tipo = $row ? $row['nome'] : "Todos";
} else {
    $nome_tipo = "Todos";
    $tipo = null;
}

require('rotation.php');

class PDF_Oficio_Todos extends PDF_Rotate
{
    private static $RS       = '';
    private static $MS       = '';
    private static $TP       = '';
    private static $PG       = '';
    private static $DV       = '';
    private static $DN       = '';
    private static $DT_VENC  = '';
    private static $PERIODO  = '';
    private static $PRIMEIRA_PAGINA_EMP = true;

    public static function setRS($v)             { self::$RS      = $v; }
    public static function setMS($v)             { self::$MS      = $v; }
    public static function setTIPO($v)           { self::$TP      = $v; }
    public static function setPG($v)             { self::$PG      = $v; }
    public static function getDV($v)             { self::$DV      = $v; }
    public static function setDN($v)             { self::$DN      = $v; }
    public static function setDataVencimento($v) { self::$DT_VENC = $v; }
    public static function setPeriodo($v)        { self::$PERIODO = $v; }
    public static function resetPrimeiraPagina() { self::$PRIMEIRA_PAGINA_EMP = true; }

    function Header()
    {
        $this->Image('../../../pictures_site-sind/logo_saspng.png', 10, 8, 18);

        $this->SetFont('Arial', 'B', 11);
        $this->Cell(22);
        $this->Write(0, mb_convert_encoding('Ofício Cartão Convênio - Relatório de Utilização e Solicitação de Desconto', 'ISO-8859-1', 'UTF-8'));

        $this->Ln();
        $this->Cell(22);
        $this->SetFont('Arial', 'B', 10);
        $this->Write(12, "Empregador: " . mb_convert_encoding(self::$RS, 'ISO-8859-1', 'UTF-8'));

        $this->Ln();
        $this->Cell(22);
        $this->SetFont('Arial', 'B', 9);
        $this->Write(0, mb_convert_encoding("Mês: ", 'ISO-8859-1', 'UTF-8'));
        $this->Write(0, self::$MS);
        if (!empty(self::$PERIODO)) {
            $this->SetFont('Arial', 'B', 8);
            $this->Write(0, ' - ' . mb_convert_encoding(self::$PERIODO, 'ISO-8859-1', 'UTF-8'));
        }

        $this->Cell(12);
        $this->SetFont('Arial', 'B', 9);
        $this->Write(0, date('d/m/Y'));
        $this->Cell(10);
        $this->Write(0, date('H:i:s'));
        $this->Cell(18);
        $this->Write(0, mb_convert_encoding("Tipo: ", 'ISO-8859-1', 'UTF-8'));
        $this->Write(0, self::$TP);
        $this->Cell(20);
        $this->Write(0, "Pagina: " . self::$PG);

        // Texto introdutório apenas na primeira página de cada empregador
        if (self::$PRIMEIRA_PAGINA_EMP) {
            $this->Ln(10);
            $this->SetFont('Arial', '', 10);
            $this->MultiCell(0, 5, mb_convert_encoding("Ilmo. Representante Legal", 'ISO-8859-1', 'UTF-8'));
            $this->Ln(2);
            $this->MultiCell(0, 5, mb_convert_encoding("Prezado Senhor,", 'ISO-8859-1', 'UTF-8'));
            $this->Ln(2);
            $texto_intro = "Ao tempo de cumprimentá-lo, serve do presente para encaminhar o relatório de utilização dos convênios para o efetivo desconto e repasse até o dia " . self::$DT_VENC . " no valor descrito na relação de funcionários e valores abaixo descritos, no amparo do artigo 462 da CLT e Cláusula 28ª da CCT, sendo:";
            $this->MultiCell(0, 5, mb_convert_encoding($texto_intro, 'ISO-8859-1', 'UTF-8'), 0, 'J');
            $this->Ln(10);
            self::$PRIMEIRA_PAGINA_EMP = false;
        } else {
            $this->Ln(8);
        }

        // Cabeçalho da tabela
        $this->SetFont('Arial', 'B', 7);
        $this->Cell(21, -6, "CPF", 0, 0, 'L');
        $this->Cell(70, -6, "Nome", 0, 0, 'L');
        $this->Cell(25, -6, "Adiantamento", 0, 0, 'R');
        $this->Cell(25, -6, mb_convert_encoding("Cartão", 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');
        $this->Cell(25, -6, mb_convert_encoding("Taxa de adm /", 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');
        $this->Cell(18, -6, "Total", 0, 0, 'R');
        $this->Ln(4);
        $this->Cell(140);
        $this->Cell(25, -6, mb_convert_encoding("manutenção", 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');
        $this->Ln(0);
        $this->SetLineWidth(0.2);
        $this->Line(11, $this->GetY(), 205, $this->GetY());

        // Marca d'água
        $this->SetFont('Arial', 'B', 50);
        $this->SetTextColor(255, 192, 203);
        if (self::$DV == 1) {
            $this->RotatedText(55, 200, 'S  A  S  C  R  E  D', 45);
        }
    }

    function RotatedText($x, $y, $txt, $angle)
    {
        $this->Rotate($angle, $x, $y);
        $this->Text($x, $y, $txt);
        $this->Rotate(0);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, self::$DN, 0, 0, 'C');
        $this->SetLineWidth(0.2);
        $this->Line(7, 280, 201, 280);
    }
}

// ── Buscar todos os empregadores com dados no mês ────────────────────────────
$sql_empregadores = "SELECT DISTINCT e.id, e.nome 
                     FROM sind.empregador e
                     INNER JOIN sind.qrelatoriofinal q ON q.empregador = e.id
                     WHERE e.id_divisao = :divisao 
                       AND (q.aprovado = true OR q.aprovado IS NULL)
                       AND q.mes = :mes_atual";
if ($tipo !== null) {
    $sql_empregadores .= " AND q.tipoconvenio = :tipo";
}
$sql_empregadores .= " ORDER BY e.nome";

$stmt_emp = $pdo->prepare($sql_empregadores);
$stmt_emp->bindParam(':divisao',   $divisao,   PDO::PARAM_INT);
$stmt_emp->bindParam(':mes_atual', $mes_atual, PDO::PARAM_STR);
if ($tipo !== null) {
    $stmt_emp->bindParam(':tipo', $tipo, PDO::PARAM_INT);
}
$stmt_emp->execute();
$empregadores = $stmt_emp->fetchAll();

if (empty($empregadores)) {
    die("Nenhum empregador encontrado com dados para o mês selecionado.");
}

// ── Inicializar PDF ───────────────────────────────────────────────────────────
$pdf = new PDF_Oficio_Todos();
$pdf->AliasNbPages();
PDF_Oficio_Todos::getDV($divisao);
PDF_Oficio_Todos::setDN($divisao_nome);
PDF_Oficio_Todos::setDataVencimento($data_vencimento);
PDF_Oficio_Todos::setPeriodo($periodo_mes);

foreach ($empregadores as $emp) {
    $empregador_id   = $emp['id'];
    $empregador_nome = $emp['nome'];

    // Resetar flag de primeira página para cada novo empregador
    PDF_Oficio_Todos::resetPrimeiraPagina();
    PDF_Oficio_Todos::setMS($mes_atual);
    PDF_Oficio_Todos::setRS($empregador_nome);
    PDF_Oficio_Todos::setTIPO($nome_tipo);

    // Buscar dados do empregador
    if ($tipo === null) {
        $query = "SELECT codigo, nome, sum(valor) as valor, empregador, tipoconvenio, cpf
                  FROM sind.qrelatoriofinal 
                  WHERE empregador = :empregador 
                    AND mes = :mes_atual
                    AND (aprovado = true OR aprovado IS NULL)
                  GROUP BY codigo, nome, tipoconvenio, empregador, cpf
                  ORDER BY nome";
    } else {
        $query = "SELECT codigo, nome, sum(valor) as valor, empregador, tipoconvenio, cpf
                  FROM sind.qrelatoriofinal 
                  WHERE empregador = :empregador 
                    AND mes = :mes_atual
                    AND tipoconvenio = :tipo
                    AND (aprovado = true OR aprovado IS NULL)
                  GROUP BY codigo, nome, tipoconvenio, empregador, cpf
                  ORDER BY nome";
    }

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':empregador', $empregador_id, PDO::PARAM_INT);
    $stmt->bindParam(':mes_atual',  $mes_atual,     PDO::PARAM_STR);
    if ($tipo !== null) {
        $stmt->bindParam(':tipo', $tipo, PDO::PARAM_INT);
    }
    $stmt->execute();
    $dados_empregador = $stmt->fetchAll();

    if (empty($dados_empregador)) {
        continue;
    }

    $pagina         = 1;
    PDF_Oficio_Todos::setPG($pagina);

    $item_pagina    = 0;
    $registros      = 0;
    $total          = 0;
    $emprestimo     = $emprestimo_tot = 0;
    $cartao         = $cartao_tot     = 0;
    $taxacartao     = $taxacartao_tot = 0;
    $total_assoc    = 0;
    $aux_cpf        = "";
    $aux_nome       = "";

    $pdf->AddPage('P');
    $pdf->SetFont('Arial', 'B', 7);

    foreach ($dados_empregador as $row) {
        if ($registros == 0) {
            $registros = 1;
            $aux_cpf   = $row['cpf'];
            $aux_nome  = $row['nome'];
            if ($row['tipoconvenio'] == 1) { $cartao     += $row['valor']; $cartao_tot     += $row['valor']; }
            if ($row['tipoconvenio'] == 2) { $emprestimo += $row['valor']; $emprestimo_tot += $row['valor']; }
            if ($row['tipoconvenio'] == 3) { $taxacartao += $row['valor']; $taxacartao_tot += $row['valor']; }
            $total_assoc = $cartao + $emprestimo + $taxacartao;
            $total += floatval($row['valor']);
        } else {
            if ($aux_cpf === $row['cpf']) {
                if ($row['tipoconvenio'] == 1) { $cartao     += $row['valor']; $cartao_tot     += $row['valor']; }
                if ($row['tipoconvenio'] == 2) { $emprestimo += $row['valor']; $emprestimo_tot += $row['valor']; }
                if ($row['tipoconvenio'] == 3) { $taxacartao += $row['valor']; $taxacartao_tot += $row['valor']; }
                $total_assoc = $cartao + $emprestimo + $taxacartao;
                $total += floatval($row['valor']);
            } else {
                $registros++;
                $item_pagina++;

                $pdf->SetTextColor(0, 0, 0);
                $pdf->Cell(21, 4, $aux_cpf, 0, 0, 'C');
                $pdf->Cell(70, 4, mb_convert_encoding($aux_nome, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
                $pdf->Cell(25, 4, number_format($emprestimo,  2, ',', '.'), 0, 0, 'R');
                $pdf->Cell(25, 4, number_format($cartao,      2, ',', '.'), 0, 0, 'R');
                $pdf->Cell(25, 4, number_format($taxacartao,  2, ',', '.'), 0, 0, 'R');
                $pdf->Cell(18, 4, number_format($total_assoc, 2, ',', '.'), 0, 0, 'R');
                $pdf->Ln();

                $cartao = $emprestimo = $taxacartao = $total_assoc = 0;
                $aux_cpf  = $row['cpf'];
                $aux_nome = $row['nome'];
                if ($row['tipoconvenio'] == 1) { $cartao     += $row['valor']; $cartao_tot     += $row['valor']; }
                if ($row['tipoconvenio'] == 2) { $emprestimo += $row['valor']; $emprestimo_tot += $row['valor']; }
                if ($row['tipoconvenio'] == 3) { $taxacartao += $row['valor']; $taxacartao_tot += $row['valor']; }
                $total_assoc = $cartao + $emprestimo + $taxacartao;
                $total += $row['valor'];
            }
        }

        if ($item_pagina == 45) {
            $pagina++;
            $item_pagina = 0;
            $pdf->Ln(4);
            PDF_Oficio_Todos::setPG($pagina);
            $pdf->AddPage('P');
            $pdf->SetFont('Arial', 'B', 7);
        }
    }

    // Última linha do empregador
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(21, 4, $aux_cpf, 0, 0, 'C');
    $pdf->Cell(70, 4, mb_convert_encoding($aux_nome, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
    $pdf->Cell(25, 4, number_format($emprestimo,  2, ',', '.'), 0, 0, 'R');
    $pdf->Cell(25, 4, number_format($cartao,      2, ',', '.'), 0, 0, 'R');
    $pdf->Cell(25, 4, number_format($taxacartao,  2, ',', '.'), 0, 0, 'R');
    $pdf->Cell(18, 4, number_format($total_assoc, 2, ',', '.'), 0, 0, 'R');
    $pdf->Ln();

    // Totais
    $pdf->Cell(15, 14, "Registros: " . $registros, 0, 0, 'L');
    $pdf->Cell(102, 10, number_format($emprestimo_tot, 2, ',', '.'), 0, 0, 'R');
    $pdf->Cell(25,  10, number_format($cartao_tot,     2, ',', '.'), 0, 0, 'R');
    $pdf->Cell(25,  10, number_format($taxacartao_tot, 2, ',', '.'), 0, 0, 'R');
    $pdf->Cell(18,  10, number_format($total,          2, ',', '.'), 0, 0, 'R');
    $pdf->Ln(10);

    // Texto de fechamento do ofício
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 10);
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
}

$data_arquivo = date('Y-m-d_H-i-s');
$nome_arquivo = "oficios_todos-" . str_replace('/', '-', $mes_atual) . "-{$data_arquivo}-{$divisao_nome}.pdf";
$pdf->Output('I', $nome_arquivo);
?>
