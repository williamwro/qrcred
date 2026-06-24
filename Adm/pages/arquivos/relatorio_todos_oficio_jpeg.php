<?php
/**
 * relatorio_todos_oficio_jpeg.php
 * Gera um arquivo ZIP contendo um JPEG de ofício para cada empregador do mês.
 * Segue o padrão visual de relatorio_oficio_jpeg_v3.php.
 */
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
date_default_timezone_set('America/Araguaina');

if (!extension_loaded('mbstring')) {
    die('Erro: A extensão mbstring do PHP é necessária para este relatório.');
}
if (!extension_loaded('gd')) {
    die('Erro: A extensão GD do PHP é necessária para este relatório.');
}
if (!extension_loaded('zip')) {
    die('Erro: A extensão zip do PHP é necessária para este relatório.');
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
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    echo "Nenhum empregador encontrado com dados para o mês selecionado.";
    exit;
}

// ── Função: gera o conteúdo JPEG de um empregador ────────────────────────────
function gerarJpegOficio($nome_empregador, $dados_agrupados, $mes_atual, $periodo_mes,
                          $nome_tipo, $data_vencimento, $data_atual, $divisao_nome)
{
    $width  = 1050;
    $height = 1754;
    $img    = imagecreatetruecolor($width, $height);

    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    $pink  = imagecolorallocate($img, 255, 192, 203);

    imagefill($img, 0, 0, $white);

    $font_title  = 5;
    $font_normal = 4;
    $font_small  = 3;
    $font_tiny   = 2;

    $y = 80;

    // Logo
    $logo_path = '../../../pictures_site-sind/logo_saspng.png';
    if (file_exists($logo_path)) {
        $logo = imagecreatefrompng($logo_path);
        if ($logo) {
            $logo_width  = 117;
            $logo_height = imagesy($logo) * ($logo_width / imagesx($logo));
            imagecopyresampled($img, $logo, 65, $y, 0, 0, $logo_width, $logo_height, imagesx($logo), imagesy($logo));
            imagedestroy($logo);
        }
    }

    // Título
    $titulo = mb_convert_encoding("Oficio Cartao Convenio - Relatorio de Utilizacao e Solicitacao de Desconto", 'ISO-8859-1', 'UTF-8');
    imagestring($img, $font_title, 195, $y, $titulo, $black);
    $y += 50;

    // Empregador
    imagestring($img, $font_normal, 195, $y, mb_convert_encoding("Empregador: " . $nome_empregador, 'ISO-8859-1', 'UTF-8'), $black);
    $y += 40;

    // Mês + Período + data/hora/tipo/página
    $mes_texto = mb_convert_encoding("Mes: " . $mes_atual, 'ISO-8859-1', 'UTF-8');
    if (!empty($periodo_mes)) {
        $mes_texto .= mb_convert_encoding(" - " . $periodo_mes, 'ISO-8859-1', 'UTF-8');
    }
    imagestring($img, $font_small, 195, $y, $mes_texto, $black);
    $pos_base = 195 + (strlen($mes_texto) * imagefontwidth($font_small)) + 30;
    imagestring($img, $font_small, $pos_base,       $y, date('d/m/Y'), $black);
    imagestring($img, $font_small, $pos_base + 100, $y, date('H:i:s'), $black);
    imagestring($img, $font_small, $pos_base + 200, $y, mb_convert_encoding("Tipo: " . $nome_tipo, 'ISO-8859-1', 'UTF-8'), $black);
    imagestring($img, $font_small, $pos_base + 350, $y, "Pagina: 1", $black);
    $y += 80;

    // Texto introdutório
    imagestring($img, $font_normal, 65, $y, mb_convert_encoding("Ilmo. Representante Legal", 'ISO-8859-1', 'UTF-8'), $black);
    $y += 40;
    imagestring($img, $font_normal, 65, $y, mb_convert_encoding("Prezado Senhor,", 'ISO-8859-1', 'UTF-8'), $black);
    $y += 50;

    $texto_intro = mb_convert_encoding(
        "Ao tempo de cumprimentá-lo, serve do presente para encaminhar o relatório de utilização dos convênios para o efetivo desconto e repasse até o dia " . $data_vencimento . " no valor descrito na relação de funcionários e valores abaixo descritos, no amparo do artigo 462 da CLT e Cláusula 28a da CCT, sendo:",
        'ISO-8859-1', 'UTF-8'
    );
    foreach (explode("\n", wordwrap($texto_intro, 110, "\n")) as $linha) {
        imagestring($img, $font_small, 65, $y, $linha, $black);
        $y += 30;
    }
    $y += 50;

    // Cabeçalho da tabela
    $x_start    = 65;
    $col_cpf    = 94;
    $col_nome   = 370;
    $col_adiant = 130;
    $col_cartao = 117;
    $col_taxa   = 117;
    $col_total  = 94;

    imagestring($img, $font_normal, $x_start, $y, "CPF", $black);
    imagestring($img, $font_normal, $x_start + $col_cpf, $y, "Nome", $black);

    $adiant_label = "Adiantamento";
    imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant - strlen($adiant_label) * imagefontwidth($font_normal), $y, $adiant_label, $black);

    $cartao_label = mb_convert_encoding("Cartao", 'ISO-8859-1', 'UTF-8');
    imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao - strlen($cartao_label) * imagefontwidth($font_normal), $y, $cartao_label, $black);

    $taxa_label = mb_convert_encoding("Taxa de adm /", 'ISO-8859-1', 'UTF-8');
    imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa - strlen($taxa_label) * imagefontwidth($font_normal), $y, $taxa_label, $black);

    $total_label = "Total";
    imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa + $col_total - strlen($total_label) * imagefontwidth($font_normal), $y, $total_label, $black);
    $y += 30;

    $manut_label = mb_convert_encoding("manutencao", 'ISO-8859-1', 'UTF-8');
    imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa - strlen($manut_label) * imagefontwidth($font_normal), $y, $manut_label, $black);
    $y += 20;

    $linha_fim = $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa + $col_total;
    imageline($img, $x_start, $y, $linha_fim, $y, $black);
    $y += 20;

    // Dados agrupados por CPF
    $registros      = 0;
    $total_geral    = 0;
    $emprestimo_tot = 0;
    $cartao_tot     = 0;
    $taxacartao_tot = 0;

    foreach ($dados_agrupados as $cpf => $dados) {
        if ($y > $height - 400) break;

        $registros++;
        $total_assoc     = $dados['emprestimo'] + $dados['cartao'] + $dados['taxacartao'];
        $emprestimo_tot += $dados['emprestimo'];
        $cartao_tot     += $dados['cartao'];
        $taxacartao_tot += $dados['taxacartao'];
        $total_geral    += $total_assoc;

        imagestring($img, $font_tiny, $x_start, $y, $cpf, $black);
        $nome = mb_convert_encoding(substr($dados['nome'], 0, 50), 'ISO-8859-1', 'UTF-8');
        imagestring($img, $font_tiny, $x_start + $col_cpf, $y, $nome, $black);

        $val_emp = number_format($dados['emprestimo'], 2, ',', '.');
        imagestring($img, $font_tiny, $x_start + $col_cpf + $col_nome + $col_adiant - strlen($val_emp) * imagefontwidth($font_tiny), $y, $val_emp, $black);

        $val_cart = number_format($dados['cartao'], 2, ',', '.');
        imagestring($img, $font_tiny, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao - strlen($val_cart) * imagefontwidth($font_tiny), $y, $val_cart, $black);

        $val_taxa = number_format($dados['taxacartao'], 2, ',', '.');
        imagestring($img, $font_tiny, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa - strlen($val_taxa) * imagefontwidth($font_tiny), $y, $val_taxa, $black);

        $val_total = number_format($total_assoc, 2, ',', '.');
        imagestring($img, $font_tiny, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa + $col_total - strlen($val_total) * imagefontwidth($font_tiny), $y, $val_total, $black);

        $y += 28;
    }
    $y += 20;

    // Totais
    imagestring($img, $font_normal, $x_start, $y, "Registros: " . $registros, $black);

    $tot_emp = number_format($emprestimo_tot, 2, ',', '.');
    imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant - strlen($tot_emp) * imagefontwidth($font_normal), $y, $tot_emp, $black);

    $tot_cart = number_format($cartao_tot, 2, ',', '.');
    imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao - strlen($tot_cart) * imagefontwidth($font_normal), $y, $tot_cart, $black);

    $tot_taxa = number_format($taxacartao_tot, 2, ',', '.');
    imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa - strlen($tot_taxa) * imagefontwidth($font_normal), $y, $tot_taxa, $black);

    $tot_geral = number_format($total_geral, 2, ',', '.');
    imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa + $col_total - strlen($tot_geral) * imagefontwidth($font_normal), $y, $tot_geral, $black);

    $y += 80;

    // Texto de fechamento
    $texto1 = mb_convert_encoding("O repasse dos referidos valores para a empresa SAS Convenio, conforme indicado pelo SINEMPREVS/MT (que nos le em copia) e autorizado pela clausula 28a da CCT e o artigo 462 da CLT, atraves do pagamento do boleto em anexo;", 'ISO-8859-1', 'UTF-8');
    foreach (explode("\n", wordwrap($texto1, 110, "\n")) as $linha) {
        imagestring($img, $font_small, $x_start, $y, $linha, $black);
        $y += 30;
    }
    $y += 30;
    imagestring($img, $font_small, $x_start, $y, mb_convert_encoding("Outrossim, segue em anexo:", 'ISO-8859-1', 'UTF-8'), $black);
    $y += 40;
    imagestring($img, $font_small, $x_start + 39, $y, mb_convert_encoding("1 - Relacao de funcionarios e valores utilizados (a serem descontados);", 'ISO-8859-1', 'UTF-8'), $black);
    $y += 30;
    imagestring($img, $font_small, $x_start + 39, $y, mb_convert_encoding("2 - Boleto de Cobranca;", 'ISO-8859-1', 'UTF-8'), $black);
    $y += 50;
    imagestring($img, $font_small, $x_start, $y, mb_convert_encoding("Alphaville, Barueri/SP, " . $data_atual, 'ISO-8859-1', 'UTF-8'), $black);
    $y += 50;
    imagestring($img, $font_small, $x_start, $y, mb_convert_encoding("Atenciosamente,", 'ISO-8859-1', 'UTF-8'), $black);

    // Marca d'água
    imagestring($img, $font_title, 390, 2000, "S  A  S  C  R  E  D", $pink);

    // Rodapé
    $rodape_y = $height - 100;
    imageline($img, $x_start, $rodape_y, $width - 78, $rodape_y, $black);
    $rodape_y += 30;
    $divisao_texto = mb_convert_encoding($divisao_nome, 'ISO-8859-1', 'UTF-8');
    $divisao_width = strlen($divisao_texto) * imagefontwidth($font_small);
    imagestring($img, $font_small, ($width - $divisao_width) / 2, $rodape_y, $divisao_texto, $black);

    // Capturar JPEG em string
    ob_start();
    imagejpeg($img, null, 95);
    $jpeg_data = ob_get_clean();
    imagedestroy($img);

    return $jpeg_data;
}

// ── Criar ZIP com um JPEG por empregador ─────────────────────────────────────
$zip          = new ZipArchive();
$zip_tmp      = tempnam(sys_get_temp_dir(), 'oficio_jpeg_zip_');
$mes_arquivo  = str_replace('/', '-', $mes_atual);

$zip->open($zip_tmp, ZipArchive::OVERWRITE);

foreach ($empregadores as $emp) {
    $empregador_id   = $emp['id'];
    $empregador_nome = $emp['nome'];

    // Buscar dados do empregador
    $sql_query = "SELECT codigo, nome, sum(valor) as valor, empregador, tipoconvenio, cpf
                  FROM sind.qrelatoriofinal
                  WHERE mes = :mes_atual AND empregador = :empregador
                    AND (aprovado = true OR aprovado IS NULL)";
    if ($tipo !== null) {
        $sql_query .= " AND tipoconvenio = :tipo";
    }
    $sql_query .= " GROUP BY codigo, nome, tipoconvenio, empregador, cpf ORDER BY nome";

    $stmt = $pdo->prepare($sql_query);
    $stmt->bindParam(':mes_atual',   $mes_atual,     PDO::PARAM_STR);
    $stmt->bindParam(':empregador',  $empregador_id, PDO::PARAM_INT);
    if ($tipo !== null) {
        $stmt->bindParam(':tipo', $tipo, PDO::PARAM_INT);
    }
    $stmt->execute();

    // Agrupar por CPF
    $dados_agrupados = [];
    while ($row = $stmt->fetch()) {
        $cpf = $row['cpf'];
        if (!isset($dados_agrupados[$cpf])) {
            $dados_agrupados[$cpf] = ['nome' => $row['nome'], 'emprestimo' => 0, 'cartao' => 0, 'taxacartao' => 0];
        }
        if ($row['tipoconvenio'] == 1) { $dados_agrupados[$cpf]['cartao']     += $row['valor']; }
        if ($row['tipoconvenio'] == 2) { $dados_agrupados[$cpf]['emprestimo'] += $row['valor']; }
        if ($row['tipoconvenio'] == 3) { $dados_agrupados[$cpf]['taxacartao'] += $row['valor']; }
    }

    if (empty($dados_agrupados)) {
        continue;
    }

    $jpeg_data = gerarJpegOficio(
        $empregador_nome, $dados_agrupados,
        $mes_atual, $periodo_mes, $nome_tipo,
        $data_vencimento, $data_atual, $divisao_nome
    );

    // Nome do arquivo dentro do ZIP (sanitizado)
    $nome_sanitizado = substr(preg_replace('/[^a-zA-Z0-9_]/', '_', $empregador_nome), 0, 40);
    $nome_arquivo_zip = "oficio_{$mes_arquivo}_{$empregador_id}_{$nome_sanitizado}.jpg";

    $zip->addFromString($nome_arquivo_zip, $jpeg_data);
}

$zip->close();

// ── Enviar ZIP ────────────────────────────────────────────────────────────────
if (ob_get_length()) {
    ob_end_clean();
}

$zip_content  = file_get_contents($zip_tmp);
$data_arquivo = date('Y-m-d_H-i-s');
$nome_zip     = "oficios_jpeg_todos-{$mes_arquivo}-{$data_arquivo}-{$divisao_nome}.zip";

unlink($zip_tmp);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $nome_zip . '"');
header('Content-Length: ' . strlen($zip_content));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo $zip_content;
exit;
?>
