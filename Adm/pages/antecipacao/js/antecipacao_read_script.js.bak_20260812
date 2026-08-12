var usuario_global;
var usuario_cod;
var divisao;
var divisao_nome;
var tabela_antecipacao;
var C_cep_assoc = $("#C_cep_assoc");
var cidadex;
var d = new Date();
var curr_date = d.getDate();
var curr_month = d.getMonth()+1;
var curr_year = d.getFullYear();
var controle = false;
var card1;
var card2;
var card3;
var card4;
var card5;
var card6;
var mescorrente;
var detailRows = [];

$(document).ready(function(){

    d = new Date();
    curr_date = d.getDate();
    curr_month = d.getMonth()+1;
    curr_year = d.getFullYear();
    curr_date = pad(curr_date,2)
    curr_month = pad(curr_month,2)

    divisao = sessionStorage.getItem("divisao");
    divisao_nome = sessionStorage.getItem("divisao_nome");

    $('#divisao').val(divisao);
   
    $('#C_aprovado').append('<option value="' + 1 + '"> Analisando </option>');
    $('#C_aprovado').append('<option value="' + 2 + '"> Aprovado </option>');
    $('#C_aprovado').append('<option value="' + 3 + '"> Reprovado </option>');

    var naodefinico = "Não definido"
   
    
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");
    
    // Carregar meses no select e depois filtrar
    carregarMeses();
   
    $('#tabela_antecipacao_assoc tbody').on('click', 'tr', function () {
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
        } else {
            tabela_antecipacao.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
        }
    });
});
$("#C_nome_assoc").keypress(function(event) {
    var character = String.fromCharCode(event.keyCode);
    return isValid(character);
});
function isValid(str) {
    return !/[~`!@#$%\^&*()+=\-\[\]\\'´.;,/{}|\\":<>\?]/g.test(str);
}
$('#C_matricula_assoc').on('keypress', function (event) {
    var regex = new RegExp("^[0-9]+$");
    var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
    if (!regex.test(key)) {
        event.preventDefault();
        return false;
    }
});

// Função para carregar os meses no select
function carregarMeses() {
    $.getJSON("../Adm/pages/conta/meses_conta.php", { "origem": "convenio", "divisao": divisao }, function(data) {
        $('#C_mes_filtro').append('<option value="todos">Todos os meses</option>');
        $.each(data, function(index, value) {
            if (value.mes_corrente !== undefined) {
                mescorrente = value.mes_corrente;
            }
            if (value.abreviacao !== undefined) {
                if (mescorrente === value.abreviacao) {
                    $('#C_mes_filtro').append('<option selected value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                } else {
                    $('#C_mes_filtro').append('<option value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                }
            }
        });
        
        // Após carregar os meses, aplicar o filtro inicial com o mês corrente
        if(divisao === "1"){ //QRCRED
            var mesSelecionado = $('#C_mes_filtro').val() || 'todos';
            console.log('DEBUG: Mês selecionado após carregar meses:', mesSelecionado);
            filtra_antecipacao(null, divisao, mesSelecionado);
        }
    });
}

// Evento de mudança do select de mês
$('#C_mes_filtro').change(function() {
    var mesSelecionado = $(this).val();
    var situacaoSelecionada = $('input[name="RadioSituacao"]:checked').val();
    
    filtra_antecipacao(situacaoSelecionada, divisao, mesSelecionado);
});
$(document).on('click','.update_antecipacao',function () {
   debugger;
    var id_entecipacao = tabela_antecipacao.row($(this).parents('tr')).data()["id"];
    var cod_associado = tabela_antecipacao.row($(this).parents('tr')).data()["matricula"];
    var tdobj = $(this).closest('tr').find('td');
    var empregador = tabela_antecipacao.row($(this).parents('tr')).data()["id_empregador"];

    $("#rotulo_antecipado").html("Alterando");
    $.ajax({
        url: "pages/antecipacao/antecipacao_exibe.php",
        method: "POST",
        data: {cod_associado : cod_associado, empregador: empregador, id_entecipacao: id_entecipacao},
        dataType: "json",
        success:function (data) {
            $.fn.modal.Constructor.prototype.enforceFocus = function() {};
            $("#ModalEditaAntecipado").modal("show");
            
            $("#C_nome_antecipacao").val(data.nome);
            $("#C_matricula_antecipacao").val(data.matricula);
            $("#C_empregador_antecipacao").val(data.nome_empregador);
            $("#C_id_empregador_antecipacao").val(data.id_empregador);
            $("#C_id_antecipacao").val(data.id);
            $("#C_mes").val(data.mes);
            $("#C_datasolicitacao").val(data.data_solicitacao);
            $("#C_cel_antecipacao").val(data.celular);
            $("#C_chave_pix_antecipacao").val(data.chave_pix);
            $("#C_associado_id").val(data.associado_id);
            $("#C_associado_id_divisao").val(data.associado_id_divisao);
            debugger;
            $('[name="C_aprovado"] option').prop('selected', false); // desmarcar todas as opções primeiro
            if (data.aprovado === null) { //Analisando
                $('[name=C_aprovado] option[value="1"]').prop('selected', true);
            } else if (data.aprovado === true) { //Aprovado
                $('[name=C_aprovado] option[value="2"]').prop('selected', true);
            } else if (data.aprovado === false) { //Reprovado
                $('[name=C_aprovado] option[value="3"]').prop('selected', true);
            }
            debugger;
            $("#C_valor_antecipacao").val(data.valor);
            $("#C_valor_taxa").val(data.valor_taxa); // Adicionado
            $("#C_valor_a_descontar").val(data.valor_a_descontar); // Adicionado
        }
    });
});
$("#btnInserir").click(function(){
    $("#frmantecipado")[0].reset();
    $("#rotulo_antecipado").html("Cadastrando");
    $("#C_empregador_antecipacao").val(0);
    $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    $("#ModalEditaAntecipado").modal("show");
    $('#operation').val("Add");
    var d = new Date().toLocaleString("pt-BR", {timeZone: "America/Sao_Paulo"});
    var d2 = d.substring(0,10);
    $('#C_datacadastro_assoc').val(d2);
    $('#C_uf_assoc').val($('#C_uf_assoc option').eq(11).val());
    $('#C_cidade_assoc').val($('#C_cidade_assoc option').eq(835).val());
    $("#C_matricula_assoc").removeAttr('disabled');
});
$("#btnSalvar").click(function(event){
   waitingDialog.show('Gravando, aguarde ...');
   
   $("#btnSalvar").attr("disabled", true);
   
   // Debug dos dados do formulário
   var formData = $('#frmantecipado').serialize()+'&divisao='+divisao+'&usuario_cod='+usuario_cod;
   console.log('DEBUG FORM: Dados enviados:', formData);
   console.log('DEBUG FORM: C_aprovado valor:', $('#C_aprovado').val());
      
    $.ajax({
        url: "pages/antecipacao/antecipacao_salvar.php",
        method: "POST",
        data: formData,
        success: function (data) {
            $("#frmantecipado")[0].reset();
            if (data === "atualizado") {
                Swal.fire({
                    title: "Parabens!",
                    text: "Antecipação atualizada com sucesso !",
                    icon: "success",
                    showConfirmButton: false,
                    timer: 1500
                });
            } else if (data === "cadastrado") {
                Swal.fire({
                    title: "Parabens!",
                    text: "Associado cadastrado com sucesso !",
                    icon: "success",
                });
            } else if (data === "Seu usuario não tem permissão!") {
                Swal.fire({
                    title: "Atenção!",
                    text: "Seu usuário não tem permissão.",
                    icon: "error",
                });
            }
            $("#frmantecipado")[0].reset();
            $("#btnSalvar").attr("disabled", false);
            waitingDialog.hide();
            $("#ModalEditaAntecipado").modal('hide');
            tabela_antecipacao.ajax.reload();
        }
    });
    tabela_antecipacao.columns.adjust().draw();
});
$('#tabela_antecipacao_assoc').on('click', 'tbody .btnsenha_assoc', function () {

    var data_row = tabela_antecipacao.row($(this).closest('tr')).data();
    var cod_associado = data_row.codigo;
    var id_empregador = data_row.id_empregador;
    $("#frmSenha_assoc")[0].reset();
    $("#ModalSenha").modal("show");
    $.ajax({
        url: "pages/associado/associado_exibe_usuario.php",
        method: "POST",
        data: {cod_associado: cod_associado, id_empregador: id_empregador},
        dataType: "json",
        success: function (data) {

            $("#cod_associado_senha").val(data.matricula);
            $("#senha_associado").val(data.senha);
            $("#C_Senha_assoc").val(data.senha);
            $("#associado_rotulo").html(data.nome);
            $("#existe_senha").val(data.existesenha);
            $("#id_empregador_senha").val(id_empregador);
        }
    })
 });

$("#btnsalvarsenha").click(function(event){
    var senha = $("#C_Senha_assoc").val();
    var confirmasenha = $("#C_Confirma_Senha_assoc").val();
    if(senha !== ""){
        if(confirmasenha !== ""){
            if(senha === confirmasenha){
                $.ajax({
                    url:"pages/associado/associado_salvar_senha.php",
                    method: "POST",
                    data: $('#frmSenha_assoc').serialize(),
                    success:function (data) {
                        if (data === "senha_fazia"){
                            BootstrapDialog.show({
                                closable: false,
                                title: 'Atenção',
                                message: 'Informe a senha!',
                                buttons: [{
                                    cssClass: 'btn-warning',
                                    label: 'Ok',
                                    action: function(dialogItself){
                                        dialogItself.close();
                                        $("#C_Senha_assoc").focus();
                                    }
                                }]
                            });
                        }else if (data === "senha_divergente") {
                            BootstrapDialog.show({
                                closable: false,
                                title: 'Atenção',
                                message: 'Senha e Confirma estão diferentes !',
                                buttons: [{
                                    cssClass: 'btn-warning',
                                    label: 'Ok',
                                    action: function(dialogItself){
                                        dialogItself.close();
                                        $("#C_Senha_assoc").focus();
                                    }
                                }]
                            });
                        }else if (data === "atualizado") {
                            Swal.fire({
                                title: "Parabens!",
                                text: "Senha atualizada com sucesso !",
                                icon: "success",
                                timer: 3000
                            });
                            $("#ModalSenha").modal('hide');
                        }else if(data === "cadastrado"){
                            Swal.fire({
                                title: "Parabens!",
                                text: "Senha cadastrada com sucesso !",
                                icon: "success",
                                timer: 3000
                            });
                            $("#ModalSenha").modal('hide');
                        } else if (data === "Seu usuario não tem permissão!") {
                            BootstrapDialog.show({
                                closable: false,
                                title: 'Atenção',
                                message: 'Atualização cancelada, seu usuario não tem permissão!',
                                buttons: [{
                                    cssClass: 'btn-danger',
                                    label: 'Ok',
                                    action: function (dialogItself) {
                                        dialogItself.close();
                                        $("#ModalSenha").modal('hide');
                                    }
                                }]
                            });
                        }
                    }
                })
            }else{
                BootstrapDialog.show({
                    closable: false,
                    title: 'Atenção',
                    message: 'As senha não sao iguais!',
                    buttons: [{
                        cssClass: 'btn-warning',
                        label: 'Ok',
                        action: function(dialogItself){
                            dialogItself.close();
                            $("#C_Senha_assoc").focus();
                        }
                    }]
                });
            }
        }else{
            BootstrapDialog.show({
                closable: false,
                title: 'Atenção',
                message: 'Digite a confirmação da senha!!',
                buttons: [{
                    cssClass: 'btn-warning',
                    label: 'Ok',
                    action: function(dialogItself){
                        dialogItself.close();
                        $("#C_Confirma_Senha_assoc").focus();
                    }
                }]
            });
        }
    }else{
        BootstrapDialog.show({
            type: [BootstrapDialog.TYPE_DANGER],
            closable: false,
            title: 'Atenção',
            message: 'Digite a senha!!',
            buttons: [{
                cssClass: 'btn-warning',
                label: 'Ok',
                action: function(dialogItself){
                    dialogItself.close();
                    $("#C_Senha_assoc").focus();
                }
            }]
        });
    }
});
$(document).on('click','.btnextrato',function () {
    var caminho = "pages/associado_extrato/extrato_associado_read.php";
    var matricula = $(this).attr("id");
    //********pega o dado da segunda coluna com o nome do associado**
    var tdobj = $(this).closest('tr').find('td');
    var nome = tdobj[2].innerHTML;
    //***************************************************************
    //********pega o dado da segunda coluna com o nome do empregador**
    var tdobjemp = $(this).closest('tr').find('td');
    var empregador = tdobjemp[6].innerHTML;
    //***************************************************************

    $.redirect('index.php',{ caminho: caminho, matricula: matricula, nome: nome, empregador: empregador});
});

// Array to track the ids of the details displayed rows



// On each draw, loop over the `detailRows` array and show any child rows
/* table.on( 'draw', function () {
    $.each( detailRows, function ( i, id ) {
        $('#'+id+' td.details-control').trigger( 'click' );
    } );
} );*/
function moedaParaNumero(valor)
{
    return isNaN(valor) === false ? parseFloat(valor) :   parseFloat(valor.replace("R$","").replace(".","").replace(",","."));
}
function numeroParaMoeda(n, c, d, t)
{
    c = isNaN(c = Math.abs(c)) ? 2 : c, d = d === undefined ? "," : d, t = t === undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}
function format ( d ) {
    function formatarTelefone(telefone) {
        if (!telefone || telefone === '' || telefone === null) {
            return 'Não informado';
        }
        var numeros = telefone.toString().replace(/\D/g, '');
        if (numeros.length === 11) {
            return numeros.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        } else if (numeros.length === 10) {
            return numeros.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        }
        return telefone;
    }

    return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
        '<tr><td><strong>ID Empregador:</strong></td><td>' + (d.id_empregador || 'Não informado') + '</td></tr>' +
        '<tr><td><strong>Empregador:</strong></td><td>' + (d.nome_empregador || 'Não informado') + '</td></tr>' +
        '<tr><td><strong>Data Conclusão:</strong></td><td>' + (d.data_aprovacao || 'Não informado') + '</td></tr>' +
        '<tr><td><strong>Celular:</strong></td><td>' + formatarTelefone(d.celular) + '</td></tr>' +
        '<tr><td><strong>Chave PIX:</strong></td><td>' + (d.chave_pix || 'Não informado') + '</td></tr>' +
        '</table>';
}
function validar(){

    var nome       = $('#C_nome_assoc').val();
    var matricula  = $('#C_matricula_assoc').val();
    var endereco   = $('#C_nome_assoc').val();
    var numero     = $('#C_numero_assoc').val();
    var bairro     = $('#C_bairro_assoc').val();
    var cidade     = $('#C_cidade_assoc').val();
    var uf         = $('#C_uf_assoc').val();
    var nascimento = $('#C_nascimento').val();
    var salario    = $('#C_salario').val();
    var limite     = $('#C_limite_assoc').val();
    if (nome === ""){
        return $('#C_nome_assoc').attr('name');
    }else if (matricula === "") {
        return $('#C_matricula_assoc').attr('name');
    }else if (endereco === "") {
        return $('#C_nome_assoc').attr('name');
    }else if (numero === "") {
        return $('#C_numero_assoc').attr('name');
    }else if (bairro === "") {
        return $('#C_bairro_assoc').attr('name');
    }else if (cidade === "") {
        return $('#C_cidade_assoc').attr('name');
    }else if (uf === "") {
        return $('#C_uf_assoc').attr('name');
    }else if (nascimento === "") {
        return $('#C_nascimento').attr('name');
    }else if (salario === "") {
        return $('#C_salario').attr('name');
    }else if (limite === "") {
        return $('#C_limite_assoc').attr('name');
    }else{
        return "validou";
    }
}
function ucFirstAllWords( str )
{   
    if(str != null){
        var pieces = str.split(" ");
        for ( var i = 0; i < pieces.length; i++ )
        {
            var j = pieces[i].charAt(0).toUpperCase();
            pieces[i] = j + pieces[i].substr(1).toLowerCase();
        }
        return pieces.join(" ");
    } 
}
$('#RadioTodos').change(function(){
    cod_situacao = $('#RadioTodos').val();
    var mesSelecionado = $('#C_mes_filtro').val() || 'todos';
    if(divisao === "1"){ //QRCRED
        filtra_antecipacao(cod_situacao,divisao,mesSelecionado);// filtra todos
    }
});
$('#RadioAnalisando').change(function(){
    debugger;
    cod_situacao = $('#RadioAnalisando').val();
    var mesSelecionado = $('#C_mes_filtro').val() || 'todos';
    if(divisao === "1"){ //QRCRED
        filtra_antecipacao(cod_situacao,divisao,mesSelecionado);// filtra todos
    }
});
$('#RadioAprovados').change(function(){
    cod_situacao = $('#RadioAprovados').val();
    var mesSelecionado = $('#C_mes_filtro').val() || 'todos';
    if(divisao === "1"){ //QRCRED
        filtra_antecipacao(cod_situacao,divisao,mesSelecionado);// filtra todos
    }
});
$('#RadioNaoAprovados').change(function(){
    cod_situacao = $('#RadioNaoAprovados').val();
    var mesSelecionado = $('#C_mes_filtro').val() || 'todos';
    if(divisao === "1"){ //QRCRED
        filtra_antecipacao(cod_situacao,divisao,mesSelecionado);// filtra todos
    }
});


function filtra_antecipacao(codigo,divisao,mes_filtro){
    // Se não foi passado o mês, pega o valor do select
    if (mes_filtro === undefined) {
        mes_filtro = $('#C_mes_filtro').val() || 'todos';
    }
    
    tabela_antecipacao = $('#tabela_antecipacao_assoc').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "destroy": true,
        "processing": false,
        "serverSide": false,
        "paging": true,
        "deferRender": true,
        autoWidth: false,
        "ajax": {
            "url": 'pages/antecipacao/antecipacao_read2.php',
            "method": 'POST',
            "data":  { 'usuario_global': usuario_global, 'divisao': divisao, 'id_situacao': codigo, 'mes_filtro': mes_filtro },
            "dataType": 'json'
        },
        "order": [[ 5, "desc" ], [ 6, "desc" ]],
        "columns": [
            {
                "class":"details-control",
                "orderable":false,
                "data":null,
                "defaultContent": ""
            },
            { "data": "nome" },
            { "data": "id_empregador" },
            { "data": "nome_empregador" },
            { "data": "mes" },
            { "data": "data_solicitacao" },
            { "data": "hora" },
            { 
                "data": "valor",
                render: $.fn.dataTable.render.number( '.', ',', 2, 'R$ ' )
            },
            { 
                "data": "valor_taxa",
                render: $.fn.dataTable.render.number('.', ',', 2, 'R$ ')
            },
            { 
                "data": "valor_a_descontar",
                render: $.fn.dataTable.render.number('.', ',', 2, 'R$ ')
            },
            { "data": "aprovado" },
            { "data": "data_aprovacao" },
            { "data": "celular" },
            { "data": "chave_pix" },
            { "data": "associado_id" },
            { "data": "associado_id_divisao" },
            { "data": "botao" },
            { "data": "botaoexcluir" }
        ],
        "columnDefs": [
            {
                "targets": [ 2, 3, 11, 12, 14, 15 ],
                "visible": false,
                "searchable": false,
            },
            {
                "targets": [ 7, 8, 9 ], // Colunas VALOR, VALOR TAXA, VALOR A DESCONTAR
                "className": "text-right"
            },
            {
                "targets": [ 5, 6, 10, 9 ], // Colunas VALOR, VALOR TAXA, VALOR A DESCONTAR
                "className": "text-center"
            }
        ],
        "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api();
            
            // Função para converter valor formatado para número - SIMPLIFICADA
            var intVal = function ( i ) {
                console.log('🔄 Valor original:', i, 'Tipo:', typeof i);
                
                if (!i) return 0;
                
                // Se já é número, retorna direto
                if (typeof i === 'number') {
                    console.log('✅ Já é número:', i);
                    return i;
                }
                
                // Se é string, processa
                if (typeof i === 'string') {
                    // Remove tudo exceto números, vírgula e ponto
                    var limpo = i.replace(/[^0-9,.]/g, '');
                    console.log('🧹 Após limpeza:', limpo);
                    
                    // Se tem vírgula, é formato brasileiro (1.234,56)
                    if (limpo.includes(',')) {
                        // Troca vírgula por ponto e remove outros pontos
                        var partes = limpo.split(',');
                        var inteira = partes[0].replace(/\./g, ''); // Remove pontos da parte inteira
                        var decimal = partes[1] || '00';
                        var resultado = parseFloat(inteira + '.' + decimal);
                        console.log('🇧🇷 Formato BR convertido:', resultado);
                        return resultado || 0;
                    } else {
                        // Sem vírgula, assume formato americano ou inteiro
                        var resultado = parseFloat(limpo.replace(/\./g, ''));
                        console.log('🇺🇸 Formato US/INT convertido:', resultado);
                        return resultado || 0;
                    }
                }
                
                console.log('❌ Não conseguiu converter, retornando 0');
                return 0;
            };
            
            // Debug dos dados brutos primeiro
            console.log('🔍 Debug dados brutos:');
            var dadosColuna7 = api.column(7, { page: 'current' }).data().toArray();
            console.log('Dados coluna 7 (Valor Taxa):', dadosColuna7);
            
            // Verificar dados ORIGINAIS (antes da formatação)
            var dadosOriginais7 = [];
            api.rows({ page: 'current' }).data().each(function(row) {
                dadosOriginais7.push(row.valor_taxa);
            });
            console.log('🎯 Dados ORIGINAIS coluna 7:', dadosOriginais7);
            
            // Testar conversão de cada valor
            dadosColuna7.forEach(function(valor, index) {
                var convertido = intVal(valor);
                console.log(`Linha ${index}: "${valor}" → ${convertido}`);
            });
            
            // Total de TODOS os dados - usando dados ORIGINAIS
            var totalValor = 0;
            var totalValorTaxa = 0;
            var totalValorDescontar = 0;
            
            api.rows().data().each(function(row) {
                // Usar dados originais (números) em vez dos formatados (strings)
                var valor = parseFloat(row.valor) || 0;
                var valorTaxa = parseFloat(row.valor_taxa) || 0;
                var valorDescontar = parseFloat(row.valor_a_descontar) || 0;
                
                console.log('📊 Linha:', {
                    valor: row.valor + ' → ' + valor,
                    taxa: row.valor_taxa + ' → ' + valorTaxa,
                    descontar: row.valor_a_descontar + ' → ' + valorDescontar
                });
                
                totalValor += valor;
                totalValorTaxa += valorTaxa;
                totalValorDescontar += valorDescontar;
            });
                
            // Debug dos totais
            console.log('🔢 Debug Totais (TODOS OS DADOS):');
            console.log('Total registros processados:', api.rows().count());
            console.log('Total Valor:', totalValor);
            console.log('Total Valor Taxa:', totalValorTaxa);
            console.log('Total Valor Descontar:', totalValorDescontar);
            
            // Atualizar footer
            $(api.column(7).footer()).html(
                'R$ ' + totalValor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            );
            $(api.column(8).footer()).html(
                'R$ ' + totalValorTaxa.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            );
            $(api.column(9).footer()).html(
                'R$ ' + totalValorDescontar.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            );
        },
        language: {
            decimal: ",",
            thousands: ".",
            zeroRecords: "Não ha dados",
            emptyTable: "Não ha dados.",
            infoEmpty: 'Zero registros',
            paginate: {
                next: "Próximo",
                previous: "Anterior",
                first: "Primeiro",
                last: "Último"
            },
            search: "Pesquisar",
            info: "Mostrando de _START_ até _END_ de _TOTAL_ registros",
            infoFiltered: "(Filtrados de _MAX_ registros)",
            infoPostFix: "",
            lengthMenu: "_MENU_ resultados por página"
        },
        "pagingType": "full_numbers"
    });
    
    // Garantir que o evento de exclusão está vinculado após a criação da tabela
    $('#tabela_antecipacao_assoc').off('click', '.btnexcluir').on('click', '.btnexcluir', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var id_antecipacao = $(this).data('id');
        var matricula = $(this).data('matricula');
        var empregador = $(this).data('empregador');
        var mes = $(this).data('mes');
        
        // Capturar os novos campos ocultos da DataTable
        var row_data = tabela_antecipacao.row($(this).parents('tr')).data();
        var associado_id = row_data["associado_id"];
        var associado_id_divisao = row_data["associado_id_divisao"];
        
        if (!id_antecipacao || !matricula || !empregador || !mes) {
            Swal.fire({
                title: "Erro!",
                text: "Dados incompletos para exclusão. Verifique se o botão foi criado corretamente.",
                icon: "error"
            });
            return;
        }
        
        console.log('🗑️ Solicitando exclusão:', {
            id: id_antecipacao, 
            matricula: matricula, 
            empregador: empregador, 
            mes: mes,
            associado_id: associado_id,
            associado_id_divisao: associado_id_divisao
        });
        
        // Confirmação antes de excluir
        Swal.fire({
            title: 'Confirmar Exclusão',
            text: `Deseja realmente excluir a antecipação da matrícula ${matricula} do mês ${mes}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar',
            focusCancel: true,
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Excluindo...',
                    text: 'Aguarde enquanto a antecipação é excluída',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Executar exclusão
                $.ajax({
                    url: "pages/antecipacao/antecipacao_excluir.php",
                    method: "POST",
                    data: {
                        id_antecipacao: id_antecipacao,
                        matricula: matricula,
                        empregador: empregador,
                        mes: mes,
                        associado_id: associado_id,
                        associado_id_divisao: associado_id_divisao
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.status === 'sucesso') {
                            Swal.fire({
                                title: "Sucesso!",
                                text: response.mensagem,
                                icon: "success",
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // Recarregar tabela
                            tabela_antecipacao.ajax.reload();
                            
                            console.log('✅ Antecipação excluída com sucesso:', response.detalhes);
                        } else {
                            Swal.fire({
                                title: "Erro!",
                                text: response.mensagem,
                                icon: "error"
                            });
                            
                            console.error('❌ Erro na exclusão:', response.mensagem);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Erro AJAX na exclusão:', xhr.responseText);
                        
                        Swal.fire({
                            title: "Erro!",
                            text: "Erro ao comunicar com o servidor. Tente novamente.",
                            icon: "error"
                        });
                    }
                });
            }
        });
    });
    
    // Add event listener for opening and closing details
    $('#tabela_antecipacao_assoc').off('click', 'tr td.details-control').on('click', 'tr td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = tabela_antecipacao.row( tr );
        var idx = $.inArray( tr.attr('id'), detailRows );

        if ( row.child.isShown() ) {
            tr.removeClass( 'details' );
            row.child.hide();

            // Remove from the 'open' array
            detailRows.splice( idx, 1 );
        }
        else {
            tr.addClass( 'details' );
            row.child( format( row.data() ) ).show();

            // Add to the 'open' array
            if ( idx === -1 ) {
                detailRows.push( tr.attr('id') );
            }
        }
    });
}

$("#C_situacao_assoc").change(function () {
    
    if(controle === false) {
        if($("#C_situacao_assoc").val() === "2" || $("#C_situacao_assoc").val() === "3"){//desfiliado or falecido
            $("#C_datadesfiliacao").val(curr_date + "/" + curr_month + "/" + curr_year);
            $("#C_filiado").prop("checked", false);

        }else{
            $("#C_datadesfiliacao").val('');
            $("#C_filiado").prop("checked", true);

        }
    }else{
        controle = false;
    }
})
$('#C_filiado').change(function() {
    
    controle = true;
    if ($(this).is(':checked')) {
        $("#C_datadesfiliacao").val('');
        $("#C_situacao_assoc").val('1').change();
        //$("#C_filiado").prop("checked", true);
    } else {
        $("#C_datadesfiliacao").val(curr_date + "/" + curr_month + "/" + curr_year);
        $("#C_situacao_assoc").val('2').change();
        //$("#C_filiado").prop("checked", false);
    }
});
function pad (str, max) {
    str = str.toString();
    str = str.length < max ? pad("0" + str, max) : str; // zero à esquerda
    str = str.length > max ? str.substr(0,max) : str; // máximo de caracteres
    return str;
}

// ===== FUNCIONALIDADE DE EXCLUSÃO =====
// Evento de exclusão movido para dentro da função filtra_antecipacao() 
// para garantir que seja vinculado após a criação da tabela

// ===== FUNCIONALIDADE DE EXPORTAÇÃO EXCEL/XLSX =====

// Evento para exportar dados em formato XLSX
$('#btnExportarExcel').click(function() {
    console.log('🔄 Iniciando exportação XLSX...');
    
    // Verificar se a tabela existe
    if (typeof tabela_antecipacao === 'undefined' || !tabela_antecipacao) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Tabela não carregada. Aguarde o carregamento da tabela e tente novamente.'
        });
        return;
    }
    
    // Verificar se a biblioteca XLSX está carregada
    if (typeof XLSX === 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Biblioteca não encontrada',
            text: 'Biblioteca XLSX não está carregada. Por favor, recarregue a página e tente novamente.'
        });
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Exportando...',
        text: 'Gerando arquivo Excel (XLSX)',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    try {
        // Obter situação selecionada para o nome do arquivo
        var situacaoSelecionada = $('input[name="RadioSituacao"]:checked').val();
        var situacaoTexto = '';
        if (situacaoSelecionada === 'true') situacaoTexto = 'Aprovados';
        else if (situacaoSelecionada === 'false') situacaoTexto = 'Reprovados';
        else if (situacaoSelecionada === 'null') situacaoTexto = 'Analisando';
        else situacaoTexto = 'Todos';
        
        console.log('📊 Situação selecionada:', situacaoTexto);
        
        // Cabeçalhos da planilha
        var headers = [
            'Matrícula', 'Nome', 'Nome Empregador', 'Mês', 'Data Solicitação',
            'Valor', 'Valor Taxa', 'Valor a Descontar', 'Aprovado', 'Data Aprovação', 'Chave PIX'
        ];
        
        // Preparar dados para XLSX
        var dadosXLSX = [];
        dadosXLSX.push(headers); // Adicionar cabeçalhos
        
        // Obter todos os dados da tabela e calcular totais
        var totalLinhas = 0;
        var totalValor = 0;
        var totalValorTaxa = 0;
        var totalValorDescontar = 0;
        
        tabela_antecipacao.rows().every(function() {
            var rowData = this.data();
            
            // Somar os valores (usar dados originais para cálculo correto)
            totalValor += parseFloat(rowData.valor) || 0;
            totalValorTaxa += parseFloat(rowData.valor_taxa) || 0;
            totalValorDescontar += parseFloat(rowData.valor_a_descontar) || 0;
            
            var linha = [
                rowData.matricula || '',
                rowData.nome || '',
                rowData.nome_empregador || '',
                rowData.mes || '',
                rowData.data_solicitacao || '',
                parseFloat(rowData.valor) || 0, // Manter como número para Excel
                parseFloat(rowData.valor_taxa) || 0, // Manter como número para Excel
                parseFloat(rowData.valor_a_descontar) || 0, // Manter como número para Excel
                rowData.aprovado || '',
                rowData.data_aprovacao || '',
                rowData.chave_pix || ''
            ];
            
            dadosXLSX.push(linha);
            totalLinhas++;
        });
        
        // Adicionar linha vazia e linha de totais
        dadosXLSX.push(['', '', '', '', '', '', '', '', '', '', '']); // Linha vazia
        dadosXLSX.push([
            '', '', '', '', 'TOTAL:',
            totalValor,
            totalValorTaxa, 
            totalValorDescontar,
            '', '', ''
        ]);
        
        console.log('📋 Total de linhas exportadas:', totalLinhas);
        console.log('💰 Totais calculados:', {
            valor: totalValor,
            taxa: totalValorTaxa,
            descontar: totalValorDescontar
        });
        
        // Criar workbook e worksheet
        var wb = XLSX.utils.book_new();
        var ws = XLSX.utils.aoa_to_sheet(dadosXLSX);
        
        // Definir larguras das colunas
        ws['!cols'] = [
            {wch: 12}, // Matrícula
            {wch: 25}, // Nome
            {wch: 30}, // Nome Empregador
            {wch: 10}, // Mês
            {wch: 15}, // Data Solicitação
            {wch: 12}, // Valor
            {wch: 12}, // Valor Taxa
            {wch: 15}, // Valor a Descontar
            {wch: 12}, // Aprovado
            {wch: 15}, // Data Aprovação
            {wch: 20}  // Chave PIX
        ];
        
        // Adicionar worksheet ao workbook
        XLSX.utils.book_append_sheet(wb, ws, "Antecipações");
        
        // Nome do arquivo com data atual
        var hoje = new Date();
        var dataFormatada = hoje.getFullYear() + '-' + 
                           String(hoje.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(hoje.getDate()).padStart(2, '0');
        
        var nomeArquivo = 'Antecipacoes_' + situacaoTexto + '_' + dataFormatada + '.xlsx';
        
        // Salvar arquivo XLSX
        XLSX.writeFile(wb, nomeArquivo);
        
        Swal.fire({
            icon: 'success',
            title: 'Exportação concluída',
            text: 'Arquivo Excel (XLSX) baixado com sucesso!',
            timer: 2000,
            showConfirmButton: false
        });
        
        console.log('✅ Exportação XLSX concluída:', nomeArquivo);
        
    } catch (error) {
        console.error('❌ Erro na exportação:', error);
        
        Swal.fire({
            icon: 'error',
            title: 'Erro na exportação',
            text: 'Ocorreu um erro ao gerar o arquivo. Tente novamente.'
        });
    }
});