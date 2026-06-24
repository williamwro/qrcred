<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
date_default_timezone_set('America/Araguaina');

if (!extension_loaded('mbstring')) {
    die('Erro: A extensão mbstring do PHP é necessária para este relatório.');
}

include "../../php/funcoes.php";
include "../../php/banco.php";
$pdo = Banco::conectar_postgres();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mes_atual = $_POST['mes_atual'];
$empregador = $_POST['empregador'];
$divisao = $_POST['divisao'];
$divisao_nome = $_POST['divisao_nome'];

// Buscar período do mês
$periodo_mes = '';
$sql_periodo = $pdo->prepare("SELECT periodo FROM sind.meses_conta WHERE abreviacao = :mes_atual AND divisao = :divisao LIMIT 1");
$sql_periodo->bindParam(':mes_atual', $mes_atual, PDO::PARAM_STR);
$sql_periodo->bindParam(':divisao', $divisao, PDO::PARAM_INT);
$sql_periodo->execute();
$row_periodo = $sql_periodo->fetch();
if ($row_periodo && !empty($row_periodo['periodo'])) {
    $periodo_mes = $row_periodo['periodo'];
}

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
    // Calcular data de vencimento padrão (dia 17 do mês seguinte)
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

// Data atual por extenso
$data_atual = date('d \d\e F \d\e Y');
$meses_pt = array(
    'January' => 'janeiro', 'February' => 'fevereiro', 'March' => 'março',
    'April' => 'abril', 'May' => 'maio', 'June' => 'junho',
    'July' => 'julho', 'August' => 'agosto', 'September' => 'setembro',
    'October' => 'outubro', 'November' => 'novembro', 'December' => 'dezembro'
);
$data_atual = str_replace(array_keys($meses_pt), array_values($meses_pt), $data_atual);

// Buscar nome do empregador
$sql = $pdo->prepare("SELECT nome FROM sind.empregador WHERE id = :id AND id_divisao = :divisao");
$sql->bindParam(':id', $empregador, PDO::PARAM_INT);
$sql->bindParam(':divisao', $divisao, PDO::PARAM_INT);
$sql->execute();
$row = $sql->fetch();
$nome_empregador = $row ? $row['nome'] : '';

// Query dos dados
$sql_query = "SELECT codigo, nome, sum(valor) as valor, empregador, tipoconvenio, cpf FROM sind.qrelatoriofinal WHERE mes = :mes_atual AND empregador = :empregador AND (aprovado = true OR aprovado IS NULL)";
if ($tipo !== null) {
    $sql_query .= " AND tipoconvenio = :tipo";
}
$sql_query .= " GROUP BY codigo, nome, tipoconvenio, empregador, cpf ORDER BY nome";

$sql = $pdo->prepare($sql_query);
$sql->bindParam(':mes_atual', $mes_atual, PDO::PARAM_STR);
$sql->bindParam(':empregador', $empregador, PDO::PARAM_INT);
if ($tipo !== null) {
    $sql->bindParam(':tipo', $tipo, PDO::PARAM_INT);
}
$sql->execute();

// Dimensões ajustadas para otimizar espaço
// Largura e altura reduzidas para tornar o relatório mais compacto
$width = 1050;  // Largura ajustada para conteúdo
$height = 1754; // Altura reduzida em 50% (3508 / 2)
$img = imagecreatetruecolor($width, $height);

// Cores
$white = imagecolorallocate($img, 255, 255, 255);
$black = imagecolorallocate($img, 0, 0, 0);
$pink = imagecolorallocate($img, 255, 192, 203);
$gray = imagecolorallocate($img, 200, 200, 200);

imagefill($img, 0, 0, $white);

// Fontes
$font_title = 5;
$font_normal = 4;
$font_small = 3;
$font_tiny = 2;

$y = 80;

// === CABEÇALHO ===

// Logo (se existir)
$logo_path = '../../../pictures_site-sind/logo_saspng.png';
if (file_exists($logo_path)) {
    $logo = imagecreatefrompng($logo_path);
    if ($logo) {
        $logo_width = 117;  // 90 * 1.3
        $logo_height = imagesy($logo) * ($logo_width / imagesx($logo));
        imagecopyresampled($img, $logo, 65, $y, 0, 0, $logo_width, $logo_height, imagesx($logo), imagesy($logo));
        imagedestroy($logo);
    }
}

// Título
$titulo = mb_convert_encoding("Oficio Cartao Convenio - Relatorio de Utilizacao e Solicitacao de Desconto", 'ISO-8859-1', 'UTF-8');
imagestring($img, $font_title, 195, $y, $titulo, $black);  // 150 * 1.3
$y += 50;

// Empregador
$emp_texto = mb_convert_encoding("Empregador: " . $nome_empregador, 'ISO-8859-1', 'UTF-8');
imagestring($img, $font_normal, 195, $y, $emp_texto, $black);  // 150 * 1.3
$y += 40;

// Mês + Período
$mes_texto = mb_convert_encoding("Mes: " . $mes_atual, 'ISO-8859-1', 'UTF-8');
if (!empty($periodo_mes)) {
    $mes_texto .= mb_convert_encoding(" - " . $periodo_mes, 'ISO-8859-1', 'UTF-8');
}
imagestring($img, $font_small, 195, $y, $mes_texto, $black);

// Calcular posição após o texto do mês (aproximadamente)
$pos_base = 195 + (strlen($mes_texto) * imagefontwidth($font_small)) + 30;

// Data atual
$data_hoje = date('d/m/Y');
imagestring($img, $font_small, $pos_base, $y, $data_hoje, $black);

// Hora atual
$hora_atual = date('H:i:s');
imagestring($img, $font_small, $pos_base + 100, $y, $hora_atual, $black);

// Tipo
$tipo_texto = mb_convert_encoding("Tipo: " . $nome_tipo, 'ISO-8859-1', 'UTF-8');
imagestring($img, $font_small, $pos_base + 200, $y, $tipo_texto, $black);

// Página
imagestring($img, $font_small, $pos_base + 350, $y, "Pagina: 1", $black);
$y += 80;

// === TEXTO INTRODUTÓRIO ===
$intro1 = mb_convert_encoding("Ilmo. Representante Legal", 'ISO-8859-1', 'UTF-8');
imagestring($img, $font_normal, 65, $y, $intro1, $black);  // 50 * 1.3
$y += 40;

$intro2 = mb_convert_encoding("Prezado Senhor,", 'ISO-8859-1', 'UTF-8');
imagestring($img, $font_normal, 65, $y, $intro2, $black);  // 50 * 1.3
$y += 50;

// Texto introdutório (quebrado em linhas)
// Nota: GD library tem limitações com caracteres especiais, usando 'a' como alternativa comum
$texto_intro = "Ao tempo de cumprimentá-lo, serve do presente para encaminhar o relatório de utilização dos convênios para o efetivo desconto e repasse até o dia " . $data_vencimento . " no valor descrito na relação de funcionários e valores abaixo descritos, no amparo do artigo 462 da CLT e Cláusula 28a da CCT, sendo:";
$texto_intro = mb_convert_encoding($texto_intro, 'ISO-8859-1', 'UTF-8');

// Quebrar texto em linhas (aumentado para 110 caracteres por linha)
$linhas_intro = wordwrap($texto_intro, 110, "\n");
$linhas_array = explode("\n", $linhas_intro);
foreach ($linhas_array as $linha) {
    imagestring($img, $font_small, 65, $y, $linha, $black);  // 50 * 1.3
    $y += 30;
}
$y += 50;

// === CABEÇALHO DA TABELA ===
$x_start = 65;  // 50 * 1.3
$col_cpf = 94;  // 72 * 1.3
$col_nome = 370;  // 240 * 1.3
$col_adiant = 130;  // 90 * 1.3
$col_cartao = 117;  // 90 * 1.3
$col_taxa = 117;  // 90 * 1.3
$col_total = 94;  // 72 * 1.3

// CPF e Nome alinhados à esquerda
imagestring($img, $font_normal, $x_start, $y, "CPF", $black);
imagestring($img, $font_normal, $x_start + $col_cpf, $y, "Nome", $black);

// Adiantamento alinhado à direita
$adiant_label = "Adiantamento";
$adiant_width = strlen($adiant_label) * imagefontwidth($font_normal);
imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant - $adiant_width, $y, $adiant_label, $black);

// Cartão alinhado à direita
$cartao_label = mb_convert_encoding("Cartao", 'ISO-8859-1', 'UTF-8');
$cartao_width = strlen($cartao_label) * imagefontwidth($font_normal);
imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao - $cartao_width, $y, $cartao_label, $black);

// Taxa de adm alinhado à direita
$taxa_label = mb_convert_encoding("Taxa de adm /", 'ISO-8859-1', 'UTF-8');
$taxa_width = strlen($taxa_label) * imagefontwidth($font_normal);
imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa - $taxa_width, $y, $taxa_label, $black);

// Total alinhado à direita
$total_label = "Total";
$total_width = strlen($total_label) * imagefontwidth($font_normal);
imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa + $col_total - $total_width, $y, $total_label, $black);

$y += 30;

// Manutenção alinhado à direita
$manut_label = mb_convert_encoding("manutencao", 'ISO-8859-1', 'UTF-8');
$manut_width = strlen($manut_label) * imagefontwidth($font_normal);
imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa - $manut_width, $y, $manut_label, $black);
$y += 20;

// Linha horizontal (termina próximo da coluna Total)
$linha_fim = $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa + $col_total;
imageline($img, $x_start, $y, $linha_fim, $y, $black);
$y += 20;

// === PROCESSAR DADOS (Agrupar por CPF) ===
$registros = 0;
$total_geral = 0;
$emprestimo_tot = 0;
$cartao_tot = 0;
$taxacartao_tot = 0;

$dados_agrupados = [];
while ($row = $sql->fetch()) {
    $cpf = $row['cpf'];
    if (!isset($dados_agrupados[$cpf])) {
        $dados_agrupados[$cpf] = [
            'nome' => $row['nome'],
            'emprestimo' => 0,
            'cartao' => 0,
            'taxacartao' => 0
        ];
    }
    
    if ($row['tipoconvenio'] == 1) {
        $dados_agrupados[$cpf]['cartao'] += $row['valor'];
    } elseif ($row['tipoconvenio'] == 2) {
        $dados_agrupados[$cpf]['emprestimo'] += $row['valor'];
    } elseif ($row['tipoconvenio'] == 3) {
        $dados_agrupados[$cpf]['taxacartao'] += $row['valor'];
    }
}

// Renderizar dados agrupados
foreach ($dados_agrupados as $cpf => $dados) {
    if ($y > $height - 400) break; // Evitar ultrapassar a página (ajustado para nova altura)
    
    $registros++;
    $total_assoc = $dados['emprestimo'] + $dados['cartao'] + $dados['taxacartao'];
    
    $emprestimo_tot += $dados['emprestimo'];
    $cartao_tot += $dados['cartao'];
    $taxacartao_tot += $dados['taxacartao'];
    $total_geral += $total_assoc;
    
    // CPF
    imagestring($img, $font_tiny, $x_start, $y, $cpf, $black);
    
    // Nome
    $nome = mb_convert_encoding(substr($dados['nome'], 0, 50), 'ISO-8859-1', 'UTF-8');
    imagestring($img, $font_tiny, $x_start + $col_cpf, $y, $nome, $black);
    
    // Adiantamento (alinhado à direita)
    $val_emp = number_format($dados['emprestimo'], 2, ',', '.');
    $val_emp_width = strlen($val_emp) * imagefontwidth($font_tiny);
    imagestring($img, $font_tiny, $x_start + $col_cpf + $col_nome + $col_adiant - $val_emp_width, $y, $val_emp, $black);
    
    // Cartão (alinhado à direita)
    $val_cart = number_format($dados['cartao'], 2, ',', '.');
    $val_cart_width = strlen($val_cart) * imagefontwidth($font_tiny);
    imagestring($img, $font_tiny, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao - $val_cart_width, $y, $val_cart, $black);
    
    // Taxa (alinhado à direita)
    $val_taxa = number_format($dados['taxacartao'], 2, ',', '.');
    $val_taxa_width = strlen($val_taxa) * imagefontwidth($font_tiny);
    imagestring($img, $font_tiny, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa - $val_taxa_width, $y, $val_taxa, $black);
    
    // Total (alinhado à direita)
    $val_total = number_format($total_assoc, 2, ',', '.');
    $val_total_width = strlen($val_total) * imagefontwidth($font_tiny);
    imagestring($img, $font_tiny, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa + $col_total - $val_total_width, $y, $val_total, $black);
    
    $y += 28;
}

$y += 20;

// === LINHA DE TOTAIS ===
$reg_texto = "Registros: " . $registros;
imagestring($img, $font_normal, $x_start, $y, $reg_texto, $black);

// Totais alinhados à direita
$tot_emp = number_format($emprestimo_tot, 2, ',', '.');
$tot_emp_width = strlen($tot_emp) * imagefontwidth($font_normal);
imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant - $tot_emp_width, $y, $tot_emp, $black);

$tot_cart = number_format($cartao_tot, 2, ',', '.');
$tot_cart_width = strlen($tot_cart) * imagefontwidth($font_normal);
imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao - $tot_cart_width, $y, $tot_cart, $black);

$tot_taxa = number_format($taxacartao_tot, 2, ',', '.');
$tot_taxa_width = strlen($tot_taxa) * imagefontwidth($font_normal);
imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa - $tot_taxa_width, $y, $tot_taxa, $black);

$tot_geral = number_format($total_geral, 2, ',', '.');
$tot_geral_width = strlen($tot_geral) * imagefontwidth($font_normal);
imagestring($img, $font_normal, $x_start + $col_cpf + $col_nome + $col_adiant + $col_cartao + $col_taxa + $col_total - $tot_geral_width, $y, $tot_geral, $black);

$y += 80;

// === TEXTO DE FECHAMENTO ===
$texto1 = mb_convert_encoding("O repasse dos referidos valores para a empresa SAS Convenio, conforme indicado pelo SINEMPREVS/MT (que nos le em copia) e autorizado pela clausula 28a da CCT e o artigo 462 da CLT, atraves do pagamento do boleto em anexo;", 'ISO-8859-1', 'UTF-8');
$linhas1 = wordwrap($texto1, 110, "\n");  // 85 * 1.3 ≈ 110
foreach (explode("\n", $linhas1) as $linha) {
    imagestring($img, $font_small, $x_start, $y, $linha, $black);
    $y += 30;
}
$y += 30;

imagestring($img, $font_small, $x_start, $y, mb_convert_encoding("Outrossim, segue em anexo:", 'ISO-8859-1', 'UTF-8'), $black);
$y += 40;

imagestring($img, $font_small, $x_start + 39, $y, mb_convert_encoding("1 - Relacao de funcionarios e valores utilizados (a serem descontados);", 'ISO-8859-1', 'UTF-8'), $black);  // 30 * 1.3
$y += 30;
imagestring($img, $font_small, $x_start + 39, $y, mb_convert_encoding("2 - Boleto de Cobranca;", 'ISO-8859-1', 'UTF-8'), $black);  // 30 * 1.3
$y += 50;

$local_data = mb_convert_encoding("Alphaville, Barueri/SP, " . $data_atual, 'ISO-8859-1', 'UTF-8');
imagestring($img, $font_small, $x_start, $y, $local_data, $black);
$y += 50;

imagestring($img, $font_small, $x_start, $y, mb_convert_encoding("Atenciosamente,", 'ISO-8859-1', 'UTF-8'), $black);

// === MARCA D'ÁGUA ===
imagestring($img, $font_title, 390, 2000, "S  A  S  C  R  E  D", $pink);  // 300 * 1.3

// === RODAPÉ ===
$rodape_y = $height - 100;
imageline($img, $x_start, $rodape_y, $width - 78, $rodape_y, $black);  // 60 * 1.3
$rodape_y += 30;
$divisao_texto = mb_convert_encoding($divisao_nome, 'ISO-8859-1', 'UTF-8');
$divisao_width = strlen($divisao_texto) * imagefontwidth($font_small);
imagestring($img, $font_small, ($width - $divisao_width) / 2, $rodape_y, $divisao_texto, $black);

// === ENVIAR IMAGEM ===
if (ob_get_length()) {
    ob_end_clean();
}
header('Content-Type: image/jpeg');
header('Content-Disposition: attachment; filename="oficio-' . $mes_atual . '-' . $divisao_nome . '.jpg"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

imagejpeg($img, null, 95);
imagedestroy($img);
exit;
?>
