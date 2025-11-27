var table;
var table;
var tableconsulta;
var codigo_isa;
var nome_titular;
var dependente;
var parentesco;
var nascimento;
var empregador;
var divisao;
var nome_divisao;
var idade;

$(document).ready(function() {
    waitingDialog.show('Carregando, aguarde ...');

    divisao = sessionStorage.getItem("divisao");
    nome_divisao = sessionStorage.getItem("divisao_nome");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");
   
    var mescorrente = "";
    var lote_aux = "";

    $('#C_tipoarquivo').append("<option value='1'> Planilha excel </option>");
    $('#C_tipoarquivo').append("<option value='2'> Arquivos texto </option>");

    listar_cartoes();
    $('#C_lotes').empty();
    $.getJSON("../Adm/pages/cartoes/lotes.php",{ "divisao": divisao },function (data) {
        $('#C_lotes').append('<option value="aberto">Aberto</option>');
        $.each(data, function (index, value) {
            lote_aux = value.datalote;
            lote_aux = lote_aux.substr(0, 10);
            $('#C_lotes').append('<option value="' + value.lote + '">(' + lote_aux + ") - " + value.lote + '</option>');
        });
    });
    waitingDialog.hide();
    table.ajax.reload();
    // Garantir que os botões de exclusão estejam habilitados após carregamento inicial
    setTimeout(function(){
        $('#tabela_dados tbody .btnexcluirCartao').prop('disabled', false).removeClass('disabled').css('pointer-events','auto');
    }, 0);
    // Reforço: ao redesenhar a tabela, reabilitar
    $('#tabela_dados').on('draw.dt', function(){
        $('#tabela_dados tbody .btnexcluirCartao').prop('disabled', false).removeClass('disabled').css('pointer-events','auto');
    });
    document.getElementById("btnCancelar").style.display = "none";
    document.getElementById("btnEntregue").style.display = "none";
    document.getElementById("btnBloquearMsg").style.display = "none";
    document.getElementById("obs_cartao").style.display = "none";

});
function listar_cartoes() {
    // constroi uma datatabe no primeiro carregamento da tela

    if ($.fn.dataTable.isDataTable('#tabela_dados')) {
        table.destroy();
        table = $('#tabela_dados').DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            serverSide: false,
            responsive: true,
            autoWidth: true,
            JQueryUI: true,
            searching: true,
            deferRender: true,
            paging:   false,
            ajax: {
                url: '../Adm/pages/cartoes/selecionar_dados.php',
                method: 'POST',
                data: function (data) {
                    data.lote = "aberto";
                    data.divisao = divisao;
                },
                dataType: 'json'
            },
            order: [[1, "asc"]],
            columns: [
                {data: "cartao"},
                {data:
                        "codigo",
                    "class": "noExl"
                },
                {data:
                        "abreviacao",
                    "class": "noExl"
                },
                {data: "nome"},
                {data: "botaoexcluir",
                    orderable: false,
                    "class": "noExl"
                }
            ],            
            dom: '<"top"ifl><"clear">rt<"bottom"p><"clear">',
            stateSave: true,
            pagingType: "full_numbers",
            language: {
                //url: "pages/conta/Portuguese-Brasil.json"
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
            }
        });
    } else {

        table = $('#tabela_dados').DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            serverSide: false,
            responsive: true,
            autoWidth: true,
            JQueryUI: true,
            searching: true,
            deferRender: true,
            paging:   false,
            ajax: {
                url: '../Adm/pages/cartoes/selecionar_dados.php',
                method: 'POST',
                data: function (data) {
                    data.lote = "aberto";
                    data.divisao = divisao;
                  
                },
                dataType: 'json'
            },
            order: [[1, "asc"]],
            columns: [
                {data: "cartao"},
                {data:
                       "codigo",
                       "class": "noExl"
                },
                {data:
                       "abreviacao",
                       "class": "noExl"
                },
                {data: "nome"},
                {data: "botaoexcluir",
                    orderable: false,
                    "class": "noExl"
                }
                
            ],
            
            dom: '<"top"ifl><"clear">rt<"bottom"lp><"clear">',
            stateSave: true,
            pagingType: "full_numbers",
            language: {
                //url: "pages/conta/Portuguese-Brasil.json"
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
            }
        });
    }
}
$("#gerararquivo").click(function () {
    var data = table.rows().data();
    var texto = '';
    var obj = {};
    obj.dados = [];
    var d = new Date();
    var dataHora = (d.toLocaleString());
    dataHora.substring(0,10);

    var linha = '';
    if ($("#C_tipoarquivo").val() === '2' ) {
        if (table.rows().count() > 0) {
            data.each(function (value, index) {
                linha += value.cartao + ' ' + value.nome + "\r\n";
            });
            let blob = new Blob([linha], {type: "text/plain;charset=utf-8"});
            saveAs(blob, nome_divisao + "_CARTOES_" + dataHora.substring(0, 10));
        }
    }else{

        BootstrapDialog.confirm({
            message: '<table style="width: 100%;">' +
                     '<tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">Confirma a criação do arquivo?</th></tr>' +
                     '</table>',
            title: 'Geração de arquivo para fabrica',
            type: BootstrapDialog.TYPE_PRIMARY,
            closable: true,
            draggable: true,
            btnCancelLabel: 'Não',
            btnOKLabel: 'Sim',
            btnOKClass: 'btn btn-success',
            btnCancelClass: 'btn btn-warning',
            callback: function (result) {

                if (result) {

                    $.ajax({
                       url: "pages/cartoes/lote_cadastro.php",
                       method: "POST",
                       dataType: "json",
                       async:false,
                       data: {"divisao": divisao},
                       success: function (data) {
                           $("#tabela_dados").table2excel({
                               exclude: ".noExl",
                               name:"Cartoes",
                               filename:"Cartoes-"+nome_divisao+"-"+Date()+".xls",//do not include extension
                               fileext:".xls",
                               exclude_img:true,
                               exclude_links:true,
                               exclude_inputs:true
                           });

                           listar_cartoes();
                           $('#C_lotes').empty();
                           $.getJSON( "../Adm/pages/cartoes/lotes.php",{ "divisao": divisao }, function( data ) {
                               $('#C_lotes').append('<option value="aberto">Aberto</option>');
                               $.each(data, function (index, value) {
                                   $('#C_lotes').append('<option value="' + value.lote+ '">' + value.datalote + " - " + value.lote+ '</option>');
                               });
                           });
                           table.ajax.reload();
                       }
                   });
                } else {
                    //alert('No');
                }
            }
        });
    }
});
$("#btnConsultar").click(function () {
    $("#ModalBuscaAssociado").modal("show");

    if ( $.fn.dataTable.isDataTable( '#tabela_busca_associado' ) ) {
        tableconsulta = $('#tabela_busca_associado').DataTable();
    }
    else {
        tableconsulta = $('#tabela_busca_associado').DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            ServerSide: false,
            responsive: true,
            autoWidth: true,
            ajax: {
                url: 'pages/cartoes/exibe_todos_associados.php',
                method: 'POST',
                data: {"divisao": divisao},
                dataType: 'json'
            },
            deferRender: true,
            order: [[1, "desc"]],
            columns: [
                {data: "codigo"},
                {data: "nome"},
                {data: "empregador"},
                {data: "codempregador"},
                {data: "id"}
            ],
            "columnDefs": [
                {
                    "targets": [ 3, 4 ],
                    "visible": false,
                    "searchable": false
                }
            ],
            language: {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Portuguese-Brasil.json",
                decimal: ",",
                thousands: "."
            },
            pagingType: "full_numbers"
        });
        $('#ModalBuscaAssociado tbody').on('click', 'tr', function () {
            if ($(this).hasClass('selected')) {
                $(this).removeClass('selected');
            } else {
                tableconsulta.$('tr.selected').removeClass('selected');
                $(this).addClass('selected');
            }
        });
    }
});
$('#tabela_dados').on('click', 'tbody .btnexcluirCartao', function () {
    var data_row = table.row($(this).closest('tr')).data();
    var $button = $(this);
    Swal.fire({
        title: 'Confirma a exclusão do cartão?',
        html: '<table style="width: 100%;"><tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">CARTÃO:</th><th style="background-color: #dddddd;"><b>' + data_row.cartao + '</b></th>' +
              '<tr><th style="text-align: right;padding: 8px;">NOME:</th><th><b>' + data_row.nome + '</b></th></tr></table>',
        icon: 'question',
        showCancelButton: true,
        focusCancel: true,
        confirmButtonText: 'Sim',
        cancelButtonText: 'Não'
    }).then(function(result){
        if (result.isConfirmed) {
            waitingDialog.show('Excluindo, aguarde ...');
            $.ajax({
                url: "pages/cartoes/cartao_exclui.php",
                method: "POST",
                dataType: "json",
                data: {"cartao": data_row.cartao, "id": data_row.id},
                success: function (data) {
                    waitingDialog.hide();
                    if (data.resultado === "excluido") {
                        table.row( $button.parents('tr') ).remove().draw();
                        Swal.fire({ icon: 'success', title: 'Excluído com Sucesso!!!' });
                        table.ajax.reload();
                    } else if (data.resultado === "bloqueado_por_dependencias") {
                        Swal.fire({ 
                            icon: 'warning', 
                            title: 'Não é possível excluir', 
                            text: data.mensagem || 'Existem lançamentos vinculados (conta/antecipação).' 
                        });
                    } else if (data.resultado === "erro_parametros") {
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Parâmetros inválidos', 
                            text: data.mensagem || 'Dados insuficientes para exclusão.' 
                        });
                    } else if (data.resultado === "erro_banco") {
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Erro no banco de dados', 
                            text: data.mensagem || 'Erro ao acessar o banco de dados.' 
                        });
                    } else {
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Erro ao excluir', 
                            text: data.mensagem || 'Não foi possível excluir o cartão.' 
                        });
                    }
                },
                error: function(xhr, status, error) {
                    waitingDialog.hide();
                    var errorMsg = 'Erro de comunicação com o servidor.';
                    if (xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            errorMsg = response.mensagem || errorMsg;
                        } catch(e) {
                            // Se não for JSON, mostra parte do texto de erro
                            errorMsg += '\n' + xhr.responseText.substring(0, 200);
                        }
                    }
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Erro de comunicação', 
                        text: errorMsg 
                    });
                }
            });
        }
    });
    $('#tab_matricula_origem tbody').on( 'click', 'tr', function () {
        $(this).toggleClass('selected');
    } );
});
$("#C_lotes").change(function () {
    waitingDialog.show('Carregando, aguarde ...',);
    //waitingDialog.show('Carregando, aguarde ...',);
    var lote = $("#C_lotes").val();
    if (lote === "aberto"){
        $("#gerararquivo").prop("disabled",false);
        $("#btnConsultar").prop("disabled",false);
        $("#C_tipoarquivo").prop("disabled",false);

        if ( $.fn.dataTable.isDataTable( '#tabela_dados' ) ) {
            table.destroy();
            table = $('#tabela_dados').DataTable({
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                processing: false,
                serverSide: false,
                responsive: true,
                autoWidth: true,
                JQueryUI: true,
                searching: true,
                paging:   false,
                info: true,
                dom: '<"top"ifl><"clear">rt<"bottom"p><"clear">',
                ajax: {
                    url: 'pages/cartoes/selecionar_dados.php',
                    method: 'POST',
                    async:true,
                    data: function (data) {
                        data.lote = lote;
                        data.divisao = divisao;
                       
                    },
                    dataType: 'json'
                },
                order: [[1, "asc"]],
                columns: [
                    {data: "cartao"},
                    {data:
                            "codigo",
                        "class": "noExl"
                    },
                    {data:
                            "abreviacao",
                        "class": "noExl"
                    },
                    {data: "nome"},
                    {data: "botaoexcluir",
                        orderable: false,
                        "class": "noExl"
                    }
                ],
                language: {
                    //url: "pages/conta/Portuguese-Brasil.json",
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
                }
            });
        }else{
            table = $('#tabela_dados').DataTable({
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                processing: false,
                serverSide: false,
                responsive: true,
                autoWidth: true,
                JQueryUI: true,
                searching: true,
                paging:   false,
                info: true,
                dom: '<"top"ifl><"clear">rt<"bottom"p><"clear">',
                ajax: {
                    url: 'pages/cartoes/selecionar_dados.php',
                    method: 'POST',
                    async:true,
                    data: function (data) {
                        data.lote = lote;
                        data.divisao = divisao;
                       
                    },
                    dataType: 'json'
                },
                order: [[1, "asc"]],
                columns: [
                    {data: "cartao"},
                    {data:
                            "codigo",
                        "class": "noExl"
                    },
                    {data:
                            "abreviacao",
                        "class": "noExl"
                    },
                    {data: "nome"},
                    {data: "botaoexcluir",
                        orderable: false,
                        "class": "noExl"
                    }
                ],
                language: {
                    //url: "pages/conta/Portuguese-Brasil.json",
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
                }
            });
        }
        table.ajax.reload();
    }else{
        $("#gerararquivo").prop("disabled",true);
        $("#btnConsultar").prop("disabled",true);
        //$("#C_tipoarquivo").prop("disabled",true);
        if ( $.fn.dataTable.isDataTable( '#tabela_dados' ) ) {
            table.destroy();
            table = $('#tabela_dados').DataTable({
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                processing: false,
                serverSide: false,
                responsive: true,
                autoWidth: true,
                JQueryUI: true,
                searching: true,
                paging:false,
                info: true,
                dom: '<"top"ifl><"clear">rt<"bottom"p><"clear">',
                ajax: {
                    url: 'pages/cartoes/selecionar_dados.php',
                    method: 'POST',
                    async:true,
                    data: function (data) {
                        data.lote = lote;
                        data.divisao = divisao;
                        
                    },
                    dataType: 'json'
                },
                order: [[1, "asc"]],
                columns: [
                    {data: "cartao"},
                    {data:
                           "codigo",
                           "class": "noExl"
                    },
                    {data:
                           "abreviacao",
                           "class": "noExl"
                    },
                    {data: "nome"},
                    {data: "botaoexcluir",
                        orderable: false,
                        "class": "noExl"
                    }
                ],
                language: {
                    //url: "pages/conta/Portuguese-Brasil.json",
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
                }
            });
        }else{
            table = $('#tabela_dados').DataTable({
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                processing: false,
                serverSide: false,
                responsive: true,
                autoWidth: true,
                JQueryUI: true,
                searching: true,
                paging:   false,
                info: true,
                dom: '<"top"ifl><"clear">rt<"bottom"p><"clear">',
                ajax: {
                    url: 'pages/cartoes/selecionar_dados.php',
                    method: 'POST',
                    async:true,
                    data: function (data) {
                        data.lote = lote;
                        data.divisao = divisao;
                      
                    },
                    dataType: 'json'
                },
                order: [[1, "asc"]],
                columns: [
                    {data: "cartao"},
                    {data:
                            "codigo",
                        "class": "noExl"
                    },
                    {data:
                            "abreviacao",
                        "class": "noExl"
                    },
                    {data: "nome"},
                    {data: "botaoexcluir",
                        orderable: false,
                        "class": "noExl"
                    }
                ],
                language: {
                    //url: "pages/conta/Portuguese-Brasil.json",
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
                }
            });
        }
    }
    waitingDialog.hide();
});
$('#tabela_busca_associado').on( 'dblclick', 'tr', function () {
    // CAPTURA O VALOR DA LINHA SELECIONADA EM DUPLOCLICK

    waitingDialog.show('Criando cartão, aguarde ...',);
    var data = tableconsulta.row( this ).data();
    matricula  = data["codigo"];
    Codempregador_origem = data["codempregador"];
    id_associado = data["id"]; // Capturar o ID do associado
    
    console.log('📋 Dados capturados:', {matricula: matricula, empregador: Codempregador_origem, id_associado: id_associado});
    
    $.ajax({
        url: "pages/cartoes/cadastra_cartao.php",
        method: "POST",
        dataType: "json",
        data: {
            "matricula": matricula,
            "empregador": Codempregador_origem,
            "id_divisao": divisao,
            "id_associado": id_associado
        },
        success: function (data) {
            if (data.resultado === "cadastrado") {
                listar_cartoes();
                waitingDialog.hide();
                table.ajax.reload();
                BootstrapDialog.show({
                    closable: false,
                    title: 'Atenção',
                    message: 'Cadastrato com Sucesso!!!',
                    buttons: [{
                        cssClass: 'btn-warning',
                        label: 'Ok',
                        action: function (dialogItself) {
                            dialogItself.close();
                            $("#ModalBuscaAssociado").modal("hide");
                        }
                    }]
                });
            }
        }
    });
    waitingDialog.hide();
    table.ajax.reload();
    $("#ModalBuscaAssociado").modal("hide");
});
$("#btnRelatorio").click(function () {
    
    var selectedText = $("#C_lotes option:selected").html();
    var x = selectedText.split("-");
    var lote = $.trim(x[1]);
    var data = $.trim(x[0]);
    $.redirect('pages/cartoes/relatorio_cartoes.php', {
        lote: lote,
        data: data
    }, "POST", "_blank");
});

// Adicionar manipulador de evento para o botão "Gerar Cartões para Todos"
$("#gerarTodosCartoes").click(function() {
    BootstrapDialog.confirm({
        message: '<table style="width: 100%;">' +
                 '<tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">Confirma a geração de cartões para todos os associados?</th></tr>' +
                 '<tr><td style="text-align: center;padding: 8px;">Esta ação criará números de cartão para todos os associados que ainda não possuem cartão.</td></tr>' +
                 '</table>',
        title: 'Geração de Cartões em Massa',
        type: BootstrapDialog.TYPE_PRIMARY,
        closable: true,
        draggable: true,
        btnCancelLabel: 'Não',
        btnOKLabel: 'Sim',
        btnOKClass: 'btn btn-success',
        btnCancelClass: 'btn btn-warning',
        callback: function(result) {
            if (result) {
                // Mostrar diálogo de espera
                waitingDialog.show('Gerando cartões para todos os associados, aguarde...');
                
                // Chamar a API para gerar os cartões
                $.ajax({
                    url: "pages/cartoes/gerar_todos_cartoes.php",
                    method: "POST",
                    dataType: "json",
                    data: {
                        "divisao": divisao
                    },
                    success: function(data) {
                        waitingDialog.hide();
                        
                        if (data.status === "success") {
                            // Mostrar mensagem de sucesso com estatísticas
                            BootstrapDialog.show({
                                closable: false,
                                title: 'Sucesso',
                                message: '<strong>' + data.mensagem + '</strong><br><br>' +
                                         'Total de associados processados: ' + data.total_associados + '<br>' +
                                         'Cartões gerados com sucesso: ' + data.cartoes_gerados + '<br>' +
                                         (data.erros > 0 ? 'Erros: ' + data.erros : ''),
                                buttons: [{
                                    cssClass: 'btn-success',
                                    label: 'Ok',
                                    action: function(dialogItself) {
                                        dialogItself.close();
                                        // Recarregar a tabela
                                        table.ajax.reload();
                                    }
                                }]
                            });
                        } else {
                            // Mostrar mensagem de erro
                            BootstrapDialog.show({
                                closable: false,
                                title: 'Erro',
                                type: BootstrapDialog.TYPE_DANGER,
                                message: 'Erro ao gerar cartões: ' + data.mensagem,
                                buttons: [{
                                    cssClass: 'btn-danger',
                                    label: 'Ok',
                                    action: function(dialogItself) {
                                        dialogItself.close();
                                    }
                                }]
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        waitingDialog.hide();
                        
                        // Mostrar mensagem de erro na requisição
                        BootstrapDialog.show({
                            closable: false,
                            title: 'Erro',
                            type: BootstrapDialog.TYPE_DANGER,
                            message: 'Erro na requisição: ' + error,
                            buttons: [{
                                cssClass: 'btn-danger',
                                label: 'Ok',
                                action: function(dialogItself) {
                                    dialogItself.close();
                                }
                            }]
                        });
                    }
                });
            }
        }
    });
});