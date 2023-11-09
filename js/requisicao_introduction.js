var browser_name;
if ($.browser.chrome) {
    browser_name = "chrome";
}else if (!!navigator.userAgent.match(/Trident\/7\./)) {
    browser_name = "iexplorer";
}else if ($.browser.mozilla) {
    browser_name = "firefox";
}else if ($.browser.opera) {
    browser_name = "opera";
}else if ($.browser.safari) {
    browser_name = "safari";
}
var cod_convenio;
var mes_atual;
var mescorrente = "";
var table;
var table_estorno;
var parcelas_conv;
var aceita_parce_individ;
var cod_convenio_estorno;
var senha_estorno;
var valor_estorno;
var subtitle;
var registro_lancamento;
var data_row_estorno;
var lancamento_estorno;
var cod_convenio_estorno;
var uuid_conta;
var valor_estorno;
var nome_operador;
var nome_titular;
var saida_foco = "cod_carteira";
$(document).ready(function(){
    cod_convenio = $("#cod_convenio").val();
    mes_atual = $("#C_mes").val();
    mes_atual = $("#m_p").val();
    aceita_parce_individ = $("#aceita_parce_individ").val();
    //debugger;
    $('#cod_convenio').val(cod_convenio);
    mescorrente = mes_atual;
    $.getJSON( "meses_conta.php",{ "origem": "convenio" }, function( data ) {
        $.each(data, function (index, value) {
            if (value.abreviacao !== undefined) {
                if (mescorrente === value.abreviacao) {
                    $('#C_mes').append('<option selected data-subtext="' + value.periodo + '"  value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                    $('#C_mesestorno').append('<option selected data-subtext="' + value.periodo + '"  value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                } else {
                    $('#C_mes').append('<option data-subtext="' + value.periodo + '"  value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                    $('#C_mesestorno').append('<option data-subtext="' + value.periodo + '"  value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                }
            }
        });
    });
    mes_atual = $("#m_p").val();
    $("#valor_parcela_input").toggle(false);
    $("#valor_parcela").prop("readonly", true);

    //**********OCULTAR OS PARAMETROS DA URL NO NAVEGADOR*************
    var uri = window.location.toString();
    if (uri.indexOf("?") > 0) {
        var clean_uri = uri.substring(0, uri.indexOf("?"));
        window.history.replaceState({}, document.title, clean_uri);
    }
    //****************************************************************
    $("#pass").val("");
    $("#cod_carteira").focus();
    // bloquear tecla F5**********************************************
    window.addEventListener('keydown', function (e) {
        var code = e.which || e.keyCode;
        if (code === 116) e.preventDefault();
        else return true;
        // fazer algo aqui para quando a tecla F5 for premida
    });
    // se clicar em atualiar o browser ele redireciona para index.html
    if($("#razaosocial").val() === "" ){
        document.location.href = 'index.html';
    }
    
    $("#pass").prop( "disabled", true );
    $('#valor_pedido').prop( "disabled", true );
    $("#nparcelas").prop( "disabled", true );
    $("#btnconfirmar").prop( "disabled", true );
    $(function(){
        $('#cod_carteira').bind('keydown',soNums); // o "#input" é o input que vc quer aplicar a funcionalidade
    });
    $("#valor_pedido").maskMoney({
        prefix: "",
        decimal: ",",
        thousands: "."
    });
    $('.tab-menu a').click(function (e) {
        e.preventDefault();
        $(this).tab('show');
        //debugger;
        //table.ajax.reload();
        //table_estorno.ajax.reload();
        $("#cod_carteira").val("");
        $("#nome_associado_exibir").html("");
        $("#cartao_exibir").html("");
        $("#valor_pedido").val("");
        $("#valor_parcela").html("");
        $("#pass").val("");
        $("#cod_carteira").focus();
    });
    carrega_grid_todos();
    // Hide submenus
    $('#body-row .collapse').collapse('hide');
    // Collapse/Expand icon
    $('#collapse-icon').addClass('fa-angle-double-left');
    // Collapse click
    $('[data-toggle=sidebar-colapse]').click(function() {
        SidebarCollapse();
    });
    $('#valor_pedido').val("");
    $('#pass').val("");
    // focus ao abrir o modal senha
    $(document).on('shown.bs.modal', '#ModalSenhaEstor', function () {
        $("#C_Senha_Estor").focus();
    });
    $(document).on('shown.bs.modal', '#modal_confirma_estorno', function () {
        $("#nome_op").focus();
    });
    $("#pass").keypress(function(e){
        //debugger;
        if (e.which == 13) {
            saida_foco = "pass";
            $('#btnconfirmar').click();
        }
   });$('#btnLocaliza')
 /*   $("#valor_pedido").keypress(function(e){
        debugger;
        if (e.which == 13) {
            $('#pass').focus();
        }
    }); */
   /* let $modal = $(".modal-bootbox-confirm");
   $modal.draggable({
        handle: ".modal-header",
   });
   $modal.resizable(); */
   $('#cod_carteira').on('keyup', function(e){
        //debugger;
        if (e.keyCode === 13) {
            //e.preventDefault();
            saida_foco = "cod_carteira";
            $('#btnLocaliza').click();
        }
    });
    $('#valor_pedido').on('keyup', function(e){
            //debugger;
        saida_foco = "valor_pedido";
        if (e.keyCode != 13) {
            var total_digitado          = $("#valor_pedido").val();
            var total_digitadox         = moedaParaNumero(total_digitado);
            var limitex                 = $("#txtSaldo").html();
            var saldo                   = moedaParaNumero(limitex);
            var valor_parcela           = $("#valor_parcela").val();
            valor_parcela               = parseInt(valor_parcela);
            var nparcela_escolhida      = $("#nparcelas").val();
            var nparcela_escolhida_int  =  parseInt(nparcela_escolhida);
            if (total_digitadox > 0) {
                if (saldo === 0) {
                    $("#valor_pedido").val("");
                    $("#valor_pedido").focus();
                    Swal.fire({
                        title: "Atenção!",
                        text: "Não tem saldo !",
                        icon: "warning"
                    });
                } else {
                    if (nparcela_escolhida_int > 1) {
                        $('#msg_parcela').css('display','block');
                        nparcela_escolhida = parseInt(nparcela_escolhida);
                        valor_parcela      = numeroParaMoeda(total_digitadox / nparcela_escolhida);
                        $("#valor_parcela").html(valor_parcela);
                        valor_parcela      = moedaParaNumero(valor_parcela);
                        $("#col_parcela_input").show("fast");
                        $("#col_parcela_rotulo").show("fast");
                        valor_parcela      = numeroParaMoeda(valor_parcela);
                        $("#valor_parcela").html(valor_parcela);
                        valor_parcela      = moedaParaNumero(valor_parcela);
        
                        if (valor_parcela > saldo) {
                            $("#col_parcela_input").hide("fast");
                            $("#col_parcela_rotulo").hide("fast");
                            $("#valor_parcela").html("");
                            $("#nparcelas option[value=1]").prop('selected', true);
                            Swal.fire({
                                title: "Atenção!",
                                text: "O valor da parcela é maior que o saldo !",
                                icon: "warning"
                            });
                            $("#valor_pedido").val("");
                            $("#valor_pedido").focus();
                        }
                    }
                }
            }else{
                $("#valor_parcela").html("");
                $("#val_parcela").val("");
                $('#msg_parcela').css('display','none');
            }
        }else{
            //e.preventDefault();
            $('#pass').focus();
        }
    });
    $("#btnLocaliza").on("click", function(e){
       e.preventDefault();
       debugger;
       if(saida_foco === "cod_carteira"){
            var nomex;
            var situacaox;
            var cod_cartaox;
            var saldox;
            var saldox2;
            var matricula;
            var mesescolhido;
            var empregador;
            var razao_social;
            var parcelas_permitidas;
            var valor_pedido_gravado;
            var ultimo_mes;
            $.ajax({
                url: "localiza_associado.php",
                type: "POST",
                async: true,
                cache: false,
                data: $('#busca_associado').serialize(),
                dataType: 'json',
                beforeSend: function() {
                    $("#divLoading").css("display", "block");
                },
                complete: function() {
                    $("#divLoading").css("display", "none");
                },
                success: function(data){
    
                    nomex = data.nome;
                    situacaox = data.situacao;
                    cod_cartaox = data.cod_cart;
                    saldox = data.limite;
                    saldox2 = saldox;
                    saldox2 = numeroParaMoeda(saldox2);
                    matricula = data.matricula;
                    mesescolhido = data.mes_desconto;
                    empregador = data.empregador;
                    razao_social = data.razaosocial;
                    parcelas_permitidas = data.parcelas_permitidas;
                    valor_pedido_gravado = data.valorpedido;
                    ultimo_mes = data.ultimo_mes;
                    //debugger;
                    if (nomex !== "") {
                        if (situacaox === 1 || situacaox === 4 || situacaox === 5 || situacaox === 6 || situacaox === 7 || situacaox === 8) {
                            $("#nome_associado_exibir").html(nomex + " ( " + data.nome_empregador + " )");
                            $("#nome").val(nomex);
                            $("#cartao_exibir").html(cod_cartaox + '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-success">   Liberado</span>');
                            $("#txtSaldo").html("R$ " + saldox2);
                            $("#txtSaldoCard").html(saldox2);
                            $("#valor_pedido").val(valor_pedido_gravado);
                            $("#matricula").val(matricula);
                            $("#m_p").html(mesescolhido);
                            $("#e_p").val(empregador);
                            $("#razao_social").val(razao_social);
                            $("#parcelas_permitidas").val(parcelas_permitidas);
                            //document.getElementById("cartao_exibir").style.background = "#80FF80";
                            //document.getElementById("cartao_exibir").style.color = "#000";
                            $("#pass").prop("disabled", false);
                            $("#valor_pedido").prop("disabled", false);
                            $("#nparcelas").prop("disabled", false);
                            $("#btnconfirmar").prop("disabled", false);
                            $("#valor_pedido").focus();
    
                            var parcelas_convenio = $("#parcelas_a_exibir").val();
                            parcelas_conv = $("#parcelas_a_exibir").val();
                            if (parcelas_convenio === undefined) {
                                parcelas_convenio = 0;
                            } else {
                                parcelas_convenio = parseInt(parcelas_convenio);
                            }
                            var parcelas_associado = $("#parcelas_permitidas").val();
                            parcelas_associado = parseInt(parcelas_associado);
    
                            var parcelas_a_exibir;
    
                            if (parcelas_associado === null || parcelas_associado === 0) {
                                parcelas_a_exibir = parcelas_convenio;
                                if (parcelas_associado === 0) {
                                    parcelas_a_exibir = 1;
                                }
                            } else {
                                parcelas_a_exibir = parcelas_associado;
                            }
                            if(aceita_parce_individ === "true"){
                                parcelas_a_exibir = parcelas_associado;
                            }else{
                                parcelas_a_exibir = parcelas_convenio;
                            }
    
                            var $dropdown = $("#nparcelas");
                            $dropdown.empty();
                            for (var i = 1; i < parcelas_a_exibir + 1; i++) {
                                $dropdown.append($("<option />").val(i).text(i));
                            }
    
    
                        } else if (situacaox === 0) {
                            $("#nome_associado_exibir").html(nomex);
                            $("#cartao_exibir").html(cod_cartaox + '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-danger">Bloqueado</span>');
                            //document.getElementById("cartao_exibir").style.background = "#F00";
                            //document.getElementById("cartao_exibir").style.color = "#F7F7F7";
                            $("#pass").prop("disabled", true);
                            $("#valor_pedido").prop("disabled", true);
                            $("#nparcelas").prop("disabled", true);
                            $("#btnconfirmar").prop("disabled", true);
                            $("#txtSaldo").html("");
                            $("#valor_pedido").focus();
                        } else if (situacaox === 2) {
                            //debugger
                            $("#nome_associado_exibir").html("");
                            $("#cartao_exibir").html("");
                            $("#txtSaldo").html("");
                            $("#btnconfirmar").prop("disabled", true);
                            if (browser_name === "iexplorer") {
                                $.fallr.show({icon: 'error', content: '<p>Cartão Não encontrado</p>', position: 'center'});
                            } else {
                                Swal.fire.fire({
                                    title: "Atenção!",
                                    text: "Cartão Não encontrado !",
                                    icon: "warning"
                                });
                            }
                        }/*else if (situacaox === 8) {
                            $("#nome_associado_exibir").html("");
                            $("#cartao_exibir").html("");
                            $("#txtSaldo").html("");
                            $("#btnconfirmar").prop( "disabled", true );
                            Swal.fire({
                                title: "Atenção Associado ",
                                text: "( "+nomex+" ), É NECESSARIO RENOVAR O CONTRATO COM A CASSERV.      " +
                                    "Favor comparecer à Av Ministro Bias fortes, 79 - Centro, para retirar o novo cartão e renovar o contrato, para continuar usando o cartão. Atenciosamente, Casserv. Telefone : 3221-6502.",
                                icon: "warning"
                            });
                        }*/
                        if (situacaox === 8) {
                            $("#nome_associado_exibir").html("");
                            $("#cartao_exibir").html("");
                            $("#txtSaldo").html("");
                            $("#btnconfirmar").prop("disabled", true);
                            Swal.fire({
                                title: "CARTÃO BLOQUEADO!",
                                text: "( " + nomex + " ), É NECESSARIO RETIRAR O NOVO CARTÃO COM A CASSERV.      " +
                                    "Favor comparecer à Av Ministro Bias fortes, 79 - Centro, para retirar o novo cartão e renovar o contrato, para continuar usando os convenios. Atenciosamente, Casserv. Telefone : 3221-6502.",
                                icon: "warning"
                            }).then(() => {
                                /*if (data.cod_situacao2 === 2) {
                                    Swal.fire({
                                        title: "Atenção conveniado!",
                                        text: "ESTE NÚMERO DE CARTÃO ESTÁ LIBERADO!             " +
                                            "Favor comunicar ao associado ( " + nomex + " ) para comparecer ao escritório do CASSERV Av Ministro Bias fortes, 79 - Centro, para a troca do cartão antigo, pelo novo cartão CASSERV o mais breve possível. Click OK para prosseguir na venda !",
                                        icon: "warning"
                                    });
                                }*/
                            });
                        }
                        if (data.cod_situacao2 === 2) {
                            Swal.fire({
                                title: "Atenção conveniado!",
                                text: "ESTE NÚMERO DE CARTÃO ESTÁ LIBERADO!             " +
                                    "Favor comunicar ao associado ( " + nomex + " ) para comparecer ao escritório do CASSERV Av Ministro Bias fortes, 79 - Centro, para a troca do cartão antigo, pelo novo cartão CASSERV o mais breve possível. Click OK para prosseguir na venda !",
                                icon: "warning"
                            }).then(() => {
                                if (situacaox === 8) {
                                    $("#nome_associado_exibir").html("");
                                    $("#cartao_exibir").html("");
                                    $("#txtSaldo").html("");
                                    $("#btnconfirmar").prop("disabled", true);
                                    Swal.fire({
                                        title: "CARTÃO BLOQUEADO!",
                                        text: "( " + nomex + " ), É NECESSARIO RETIRAR O NOVO CARTÃO COM A CASSERV.      " +
                                            "Favor comparecer à Av Ministro Bias fortes, 79 - Centro, para retirar o novo cartão e renovar o contrato, para continuar usando os convenios. Atenciosamente, Casserv. Telefone : 3221-6502.",
                                        icon: "warning"
                                    })
                                }
                            });
                        }
                    } else {
                        //debugger;
                        $("#nome_associado_exibir").html("");
                        $("#cartao_exibir").html("CARTAO NÃO ENCONTRADO");
                        //document.getElementById("cartao_exibir").style.background = "#FFFF80";
                        //document.getElementById("cartao_exibir").style.color = "#000";
                        $("#pass").prop("disabled", true);
                        $("#valor_pedido").prop("disabled", true);
                        $("#nparcelas").prop("disabled", true);
                        $("#btnconfirmar").prop("disabled", true);
                        $("#txtSaldo").html("");
                        if (browser_name === "iexplorer") {
                            $.fallr.show({icon: 'error', content: '<p>Cartão Não encontrado</p>', position: 'center'});
                        }else{
                            Swal.fire({
                                title: "Atenção!",
                                text: "Cartão Não encontrado !",
                                icon: "warning"
                            });
                        }
                    }
                },
                error: function(request, status, erro) {
                    alert("Problema ocorrido: " + status + "\nDescição: " + erro);
                    //Abaixo está listando os header do conteudo que você requisitou, só para confirmar se você setou os header e dataType corretos
                    alert("Informações da requisição: \n" + request.getAllResponseHeaders());
                }
                // Caso o request termine em sucesso, mostra o resultado recebido na div#resultado
            });
    
            $("#valor_parcela").html("");
            $("#val_parcela").val("");
            $("#pass").val("");
            $('#msg_parcela').css('display','none');
        }
    });
   

});
function SidebarCollapse () {
    $('.menu-collapsed').toggleClass('d-none');
    $('.sidebar-submenu').toggleClass('d-none');
    $('.submenu-icon').toggleClass('d-none');
    $('#sidebar-container').toggleClass('sidebar-expanded sidebar-collapsed');

    // Treating d-flex/d-none on separators with title
    var SeparatorTitle = $('.sidebar-separator-title');
    if ( SeparatorTitle.hasClass('d-flex') ) {
        SeparatorTitle.removeClass('d-flex');
    } else {
        SeparatorTitle.addClass('d-flex');
    }

    // Collapse/Expand icon
    $('#collapse-icon').toggleClass('fa-angle-double-left fa-angle-double-right');
}
function carrega_grid_todos(){
    table = $('#tabela_producao').DataTable({
        "destroy": true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "serverSide": false,
        "responsive": true,
        "autoWidth": false,
        "deferRender": true,
        "ajax": {
            "url": 'list_vendas_conv2.php',
            "method": 'POST',
            "data": {
                cod_convenio: cod_convenio,
                mes_atual: mes_atual
            },
            "dataType": 'json'
        },
        "columnDefs": [
            { "targets": 0,"width": "80px" },
            { "targets": 1,"width": "340px" },
            { "targets": 2,"width": "70px" },
            { "targets": 3,"width": "50px" },
            { "targets": 4,"width": "80px" },
            { "targets": 5,"width": "80px" },
            { "targets": 6,"width": "100px" },
            {
                "targets": 7,
                "data": null,
                "render": function ( data, type, row, meta ) {
                    return '<button type="button" class="btn btn-primary btnvia2 btn-xs">2ª via</button>';
                }
            },
            {
                "targets": 8,
                "data": null,
                "render": function ( data, type, row, meta ) {
                    return '<button type="button" class="btn btn-danger btnestornar btn-xs">Estornar</button>';
                }
            },
        ],
        "error":       
        function(xhr,status,error) {
            alert(xhr.responseText);
        },
        "order": [[ 0, "desc" ]],
        "columns": [
            { "data": "lancamento" },
            { "data": "associado" },
            { "data": "data",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            },
            { "data": "hora" },
            { "data": "valor",
                "render": $.fn.dataTable.render.number( '.', ',', 2, '' ),
                "className": "text-right"
            },
            { "data": "parcela" },
            { "data": "data_fatura",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            },
            { "data": "lancamento" },
            { "data": "lancamento" }

        ],
        "pagingType": "full_numbers",
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
        "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
            // Remove the formatting to get integer data for summation
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
            // Total over all pages
            total = api
                .column( 4 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Total over this page
            pageTotal = api
                .column( 4, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Update footer
            $( api.column( 0 ).footer() ).html(
                'Total relatório: R$ '+ total.toLocaleString()
            );
            $( api.column( 2 ).footer() ).html(
                'Soma página: R$ '+ pageTotal.toLocaleString()
            );
        }
    });
    table_estorno = $('#tabela_producao_estorno').DataTable({
        "destroy": true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "serverSide": false,
        "responsive": true,
        "autoWidth": false,
        "deferRender": true,
        "ajax": {
            "url": 'list_vendas_conv2_estorno.php',
            "method": 'POST',
            "data": {
                cod_convenio: cod_convenio,
                mes_atual: mes_atual
            },
            "dataType": 'json'
        },
        "columnDefs": [
            {
                "targets": 10,
                "data": null,
                "render": function ( data, type, row, meta ) {
                    return '<button type="button" class="btn btn-danger btncancelarestornar btn-xs">Cancelar estorno</button>';
                }
            },
            { "targets": 0,"width": "80px" },
            { "targets": 1,"width": "340px" },
            { "targets": 2,"width": "70px" },
            { "targets": 3,"width": "50px" },
            { "targets": 4,"width": "80px" },
            { "targets": 8,"width": "80px" }
        ],
        "error":
            function(xhr,status,error) {
            alert(xhr.responseText);
        },
        "order": [[ 0, "desc" ]],
        "columns": [
            { "data": "lancamento" },
            { "data": "associado" },
            { "data": "data",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            },
            { "data": "hora" },
            { "data": "valor",
                "render": $.fn.dataTable.render.number( '.', ',', 2, '' ),
                "className": "text-right"
            },
            { "data": "parcela" },
            { "data": "descricao" },
            { "data": "data_estorno",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            },
            { "data": "hora_estorno" },
            { "data": "data_fatura",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            },
            { "data": "lancamento" }
        ],
        "pagingType": "full_numbers",
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
        "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
            // Remove the formatting to get integer data for summation
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
            // Total over all pages
            total = api
                .column( 4 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Total over this page
            pageTotal = api
                .column( 4, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Update footer
            $( api.column( 0 ).footer() ).html(
                'Total relatório: R$ '+ total.toLocaleString()
            );
            $( api.column( 2 ).footer() ).html(
                'Soma página: R$ '+ pageTotal.toLocaleString()
            );
        }
    });
}
function carrega_gridestorno(){
    table_estorno = $('#tabela_producao_estorno').DataTable({
        "destroy": true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "serverSide": false,
        "responsive": true,
        "autoWidth": false,
        "deferRender": true,
        "ajax": {
            "url": 'list_vendas_conv2_estorno.php',
            "method": 'POST',
            "data": {
                cod_convenio: cod_convenio,
                mes_atual: mes_atual
            },
            "dataType": 'json'
        },
        "columnDefs": [
            {
                "targets": 10,
                "data": null,
                "render": function ( data, type, row, meta ) {
                    return '<button type="button" class="btn btn-danger btncancelarestornar btn-xs">Cancelar estorno</button>';
                }
            },
            { "targets": 0,"width": "80px" },
            { "targets": 1,"width": "340px" },
            { "targets": 2,"width": "70px" },
            { "targets": 3,"width": "50px" },
            { "targets": 4,"width": "80px" },
            { "targets": 8,"width": "80px" }
        ],
        "error":
            function(xhr,status,error) {
            alert(xhr.responseText);
        },
        "order": [[ 0, "desc" ]],
        "columns": [
            { "data": "lancamento" },
            { "data": "associado" },
            { "data": "data",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            },
            { "data": "hora" },
            { "data": "valor",
                "render": $.fn.dataTable.render.number( '.', ',', 2, '' ),
                "className": "text-right"
            },
            { "data": "parcela" },
            { "data": "descricao" },
            { "data": "data_estorno",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            },
            { "data": "hora_estorno" },
            { "data": "data_fatura",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            }
        ],
        "pagingType": "full_numbers",
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
        "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
            // Remove the formatting to get integer data for summation
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
            // Total over all pages
            total = api
                .column( 4 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Total over this page
            pageTotal = api
                .column( 4, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Update footer
            $( api.column( 0 ).footer() ).html(
                'Total relatório: R$ '+ total.toLocaleString()
            );
            $( api.column( 2 ).footer() ).html(
                'Soma página: R$ '+ pageTotal.toLocaleString()
            );
        }
    });
}
function carrega_grid(){
    table = $('#tabela_producao').DataTable({
        "destroy": true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "serverSide": false,
        "responsive": true,
        "autoWidth": false,
        "deferRender": true,
        "ajax": {
            "url": 'list_vendas_conv2.php',
            "method": 'POST',
            "data": {
                cod_convenio: cod_convenio,
                mes_atual: mes_atual
            },
            "dataType": 'json'
        },
        "columnDefs": [
            {
                "targets": 7,
                "data": null,
                "render": function ( data, type, row, meta ) {
                    return '<button type="button" class="btn btn-primary btnvia2 btn-xs">2ª via</button>';
                }
            },
            {
                "targets": 8,
                "data": null,
                "render": function ( data, type, row, meta ) {
                    return '<button type="button" class="btn btn-danger btnestornar btn-xs">Estornar</button>';
                }
            },
            { "targets": 0,"width": "80px" },
            { "targets": 1,"width": "340px" },
            { "targets": 2,"width": "70px" },
            { "targets": 3,"width": "50px" },
            { "targets": 4,"width": "80px" },
            { "targets": 5,"width": "80px" },
            { "targets": 6,"width": "100px" },
        ],
        "error":       function(xhr,status,error) {
            alert(xhr.responseText);
        },
        "order": [[ 0, "desc" ]],
        "columns": [
            { "data": "lancamento" },
            { "data": "associado" },
            { "data": "data",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            },
            { "data": "hora" },
            { "data": "valor",
                "render": $.fn.dataTable.render.number( '.', ',', 2, '' ),
                "className": "text-right"
            },
            { "data": "parcela" },
            { "data": "data_fatura",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            },
            { "data": "lancamento" },
            { "data": "lancamento" }
        ],
        "pagingType": "full_numbers",
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
        "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
            // Remove the formatting to get integer data for summation
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
            // Total over all pages
            total = api
                .column( 4 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Total over this page
            pageTotal = api
                .column( 4, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Update footer
            $( api.column( 0 ).footer() ).html(
                'Total relatório: R$ '+ total.toLocaleString()
            );
            $( api.column( 2 ).footer() ).html(
                'Soma página: R$ '+ pageTotal.toLocaleString()
            );
        }
    });
}
$('#btnconfirmar').on('click', function(event){
    event.preventDefault();
    //debugger;
    var cod_cartaox = $("#cod_carteira").val();
    var valor_pedidox = moedaParaNumero($("#valor_pedido").val());
    var passx = $("#pass").val();
    var limitex = $("#txtSaldo").html();
    var pede_senha = $("#pede_senha").val();
    var nparcelas = $("#nparcelas").val();
    var valor_parcela = $("#valor_parcela").html();
    valor_parcela = moedaParaNumero(valor_parcela);
    $("#btnconfirmar").prop( "disabled", true );
    limitex = moedaParaNumero(limitex);

    if(cod_cartaox === ""){
        Swal.fire({
            title: "Atenção!",
            text: "Informe primeiro o numero do cartao !",
            icon: "warning"
        });
        $("#btnconfirmar").prop( "disabled", false );
    }else if(isNaN(valor_pedidox)){
        Swal.fire({
            title: "Atenção!",
            text: "Informe o total !",
            icon: "warning"
        });
        $("#pass").val("");
        $("#btnconfirmar").prop("disabled", false);
    }else if(nparcelas !== "1" && valor_parcela > limitex){
        Swal.fire({
            title: "Atenção!",
            text: "O valor da parcela R$ " + $("#valor_parcela").val() + " é maior que o saldo " + $("#txtSaldo").html() + " !",
            icon: "warning"
        });
        $( "#col_parcela_input" ).hide( "fast" );
        $( "#col_parcela_rotulo" ).hide( "fast" );
        $( "#valor_parcela" ).val("");
        $("#btnconfirmar").prop( "disabled", false );
        $( "#nparcelas option[value=1]" ).prop('selected', true);
    }else if(nparcelas === "1"  && valor_pedidox > limitex){
        Swal.fire({
            title: "Atenção!",
            text: "O valor total digitado R$ " + $("#valor_pedido").val() + " é maior que o saldo " + $("#txtSaldo").html() + " !",
            icon: "warning"
        });
        $( "#col_parcela_input" ).hide( "fast" );
        $( "#col_parcela_rotulo" ).hide( "fast" );
        $( "#valor_parcela" ).val("");
        $("#btnconfirmar").prop( "disabled", false );
        $( "#nparcelas option[value=1]" ).prop('selected', true);
    }else {

        $.ajax({
            url: "grava_transacao.php",
            type: "POST",
            async: true,
            cache: false,
            data: $('#busca_associado').serialize(),
            dataType: 'json',
        
            success: function (data) {
               // debugger
                if (data.situacao === 1) {
                   // debugger;
                    $("#divLoading").css("display", "none");
                    comprovante = $('#comprovante');
                    principal = $('#link_principal');
                    principal.removeClass('active in active');
                    comprovante.removeClass('fade');
                    comprovante.addClass('active in active');
                    $("#nome_gravado").html(data.nome);
                    $("#valorpedido").html(data.valorpedido);
                    var data_fire;
                    // ***************** primeira via *******************
                    // inicio limpar campos
                    $("#nome_cupon").html("");
                    $("#matricula_cupon").html("");
                    $("#datacad_cupon").html("");
                    $("#hora_cupon").html("");
                    $("#codcarteira_cupon").html("");
                    $("#registrolan_cupon").html("");
                    $("#valorpedido_cupon").html("");
                    $("#valorpedido_cupon").text("");
                    $('#tabela_parcelas').html("");
                    $('#data').html("");
                    $('#tabela_parcelas').html("");
                    $('#tabela_parcelas').text("");
                    // fim limpar campos
                    $("#nomefantasia_cupon").html(data.nomefantasia.substring(0,32));
                    $("#razaosocial_cupon").html(data.razaosocial);
                    $("#endereco_cupon").html(data.endereco);
                    $("#cnpj_cupon").html("CNPJ:"+data.cnpj.substring(0,23));
                    $("#cidade_cupon").html(data.cidade);
                    $("#cidade").html(data.cidade);
                    $("#cidade").val(data.cidade);
                    $("#nome_cupon").html(data.nome);
                    $("#matricula_cupon").html(data.matricula);
                    $("#datacad_cupon").html(data.datacad.substring(8,10)+"/"+data.datacad.substring(5,7)+"/"+data.datacad.substring(0,4));
                    $("#hora_cupon").html(data.hora);
                    $("#codcarteira_cupon").html(data.codcarteira.substring(6,10));
                    $("#valorpedido_cupon").html(data.valorpedido);
                    //debugger;
                    var valor_pedido = data.valorpedido;
                    var valor_pedido_x = "R$ "+data.valorpedido;

                    $('#tabela_parcelas').html("");
                    $('#tabela_parcelas').text("");
                    if(data.nparcelas === 1) {
                        $("#registrolan_cupon").html("Registro:"+data.registrolan);
                        $('#tabela_parcelas').append("<div class='row'><div class='col-md-12' style='font-weight: bold; padding-left: 14px; font-size: 12pt!important;'><span id='mes_seq_cupon'>Mês:&nbsp;&nbsp;&nbsp;"+data.mes_seq+"</span></div></div>");
                        $('#tabela_parcelas').append("<div class='row'><div class='col-md-12' id='valorpedido_cupon' style='font-weight: bold; padding-left: 14px; font-size: 12pt!important;'>Total: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+valor_pedido_x+"</div></div>");
                    }else{
                        $('#tabela_parcelas').append("<div class='row'><div class='col-md-11' style='font-weight: bold;border-bottom: 0.3pt solid #000;width: 60%;margin-left: 12pt;margin-right:12pt;padding-left: 0;'>parc&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;valor&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;mes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;registro</div></div>");
                        for (var i = 1; i <= data.nparcelas; i++) {
                            $('#tabela_parcelas').append("<div class='row'><div class='col-md-12'>" +
                                "<div style='width:30pt;float:left;font-weight: bold;font-size: 11pt;'>" + data[i].numero + "</div>" +
                                "<div style='width:40pt;float:left;font-weight: bold;font-size: 11pt;'>" + parseFloat(data[i].valor_parcela).toFixed(2).replace(".", ",") + "</div>" +
                                "<div style='width:60pt;float:left;font-weight: bold;font-size: 11pt;'>" + data[i].mes_seq + "</div>" +
                                "<div style='width:40pt;float:left;font-weight: bold;font-size: 11pt;'>" + data[i].registrolan + "</div>" +
                                "</div></div>");
                        }
                        $('#tabela_parcelas').append("<div class='row'><div class='col-md-12' id='valorpedido_cupon' style='font-weight: bold; padding-left: 10px; font-size: 14pt!important;'>Total: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp"+valor_pedido+"</div></div>");
                    }
                    // ***************** segunda via *******************
                    // inicio limpar campos
                    $("#nome2_cupon").html("");
                    $("#matricula2_cupon").html("");
                    $("#datacad2_cupon").html("");
                    $("#hora2_cupon").html("");
                    $("#codcarteira2_cupon").html("");
                    $("#registrolan2_cupon").html("");
                    $("#valorpedido2_cupon").html("");
                    $("#valorpedido2_cupon").text("");
                    $('#data2').html("");
                    $('#tabela_parcelas2').html("");
                    $('#tabela_parcelas2').text("");
                    // fim limpar campos
                    $("#nomefantasia2_cupon").html(data.nomefantasia);
                    $("#razaosocial2_cupon").html(data.razaosocial);
                    $("#endereco2_cupon").html(data.endereco);
                    $("#cnpj2_cupon").html("CNPJ:"+data.cnpj.substring(0,23));
                    $("#cidade2_cupon").html(data.cidade.substring(0,16));
                    $("#nome2_cupon").html(data.nome);
                    $("#matricula2_cupon").html(data.matricula);
                    $("#datacad2_cupon").html(data.datacad.substring(8,10)+"/"+data.datacad.substring(5,7)+"/"+data.datacad.substring(0,4));
                    $("#hora2_cupon").html(data.hora);
                    $("#codcarteira2_cupon").html(data.codcarteira.substring(6,10));
                    $("#valorpedido2_cupon").html(data.valorpedido);
                    var valor_pedido = data.valorpedido;
                    var valor_pedido_x = "R$ "+data.valorpedido.replace(".", ",");;

                    $('#tabela_parcelas2').html("");
                    $('#tabela_parcelas2').text("");
                    if(data.nparcelas === 1) {
                        $("#registrolan2_cupon").html("Registro:"+data.registrolan);
                        $('#tabela_parcelas2').append("<div class='row'><div class='col-md-12' style='font-weight: bold; padding-left: 14px; font-size: 12pt!important;'><span id='mes_seq_cupon'>Mês:&nbsp;&nbsp;&nbsp;"+data.mes_seq+"</span></div></div>");
                        $('#tabela_parcelas2').append("<div class='row'><div class='col-md-12' id='valorpedido_cupon' style='font-weight: bold; padding-left: 14px; font-size: 12pt!important;'>Total: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+valor_pedido_x+"</div></div>");
                        data_fire = {
                            registrolan:    data.registrolan,
                            valor_pedido:   data.valorpedido,
                            codconvenio:    data.cod_convenio,
                            userconv:       data.userconv,
                            razaosocial:    data.razaosocial,
                            datacad:        data.datacad,
                            hora:           data.hora,
                            nome:           data.nome,
                            matricula:      data.matricula,
                            mes_seq:        data.mes_seq,
                            codcarteira:    data.codcarteira,
                            numeroparcelas: data.nparcelas
                        };
                        db.collection("conta").add(data_fire)
                            .then(function(docRef){
                                console.log("Adcionado com ID: ", docRef.id);
                            })
                            .catch(function(error) {
                                console.error("Error ao gravar firestore: ", error);
                            });
                    }else{
                        $('#tabela_parcelas2').append("<div class='row'><div class='col-md-11' style='font-weight: bold;border-bottom: 0.3pt solid #000;width: 60%;margin-left: 12pt;margin-right:12pt;padding-left: 0;'>parc&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;valor&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;mes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;registro</div></div>");
                        for (var i = 1; i <= data.nparcelas; i++) {
                            $('#tabela_parcelas2').append("<div class='row'><div class='col-md-12'>" +
                                "<div style='width:30pt;float:left;font-weight: bold;font-size: 11pt;'>" + data[i].numero + "</div>" +
                                "<div style='width:40pt;float:left;font-weight: bold;font-size: 11pt;'>" + parseFloat(data[i].valor_parcela).toFixed(2).replace(".", ",") + "</div>" +
                                "<div style='width:60pt;float:left;font-weight: bold;font-size: 11pt;'>" + data[i].mes_seq + "</div>" +
                                "<div style='width:40pt;float:left;font-weight: bold;font-size: 11pt;'>" + data[i].registrolan + "</div>" +
                                "</div></div>");
                            data_fire = {
                                registrolan:    data[i].registrolan,
                                valor_pedido:   data[i].valor_parcela,
                                codconvenio:    data.cod_convenio,
                                userconv:       data.userconv,
                                razaosocial:    data.razaosocial,
                                datacad:        data.datacad,
                                hora:           data.hora,
                                nome:           data.nome,
                                matricula:      data.matricula,
                                mes_seq:        data[i].mes_seq,
                                codcarteira:    data.codcarteira,
                                numeroparcelas: data.nparcelas,
                                numeroparcela:  data[i].numero
                            };
                            db.collection("conta").add(data_fire)
                                .then(function(docRef){
                                    console.log("Adcionado com ID: ", docRef.id);
                                })
                                .catch(function(error) {
                                    console.error("Error ao gravar firestore: ", error);
                                });
                        }
                        $('#tabela_parcelas2').append("<div class='row'><div class='col-md-12' id='valorpedido_cupon' style='font-weight: bold; padding-left: 10px; font-size: 14pt!important;'>Total: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp"+valor_pedido+"</div></div>");
                    }
                    $('#myModal').modal('show');
                }else if(data.situacao === 2) {
                    //debugger;
                    Swal.fire({
                        title: "Atenção!",
                        text: "Senha errada !!",
                        icon: "warning"
                    });
                    $("#btnconfirmar").prop( "disabled", false );
                    //$('#pass').focus();
                }
            }
        });
    }
});
$('#sair_sistema').click(function () {
    $.redirect('index.html');
});

/*
$('#pass').on('keyup', function(e){
    debugger;
    if (e.keyCode === 13) {
        e.preventDefault();
        $("#btnconfirmar").click();
    }
});
/* $('#valor_pedido').on('keyup', function(e){
    debugger
    if (e.keyCode === 13) {
        e.preventDefault();
        $('#pass').focus();
    }
}); */

$('#pagina_relatorio').click(function () {
    $.ajaxSetup({
        cache:true
    });
    caminho = '';
    $('#pagina_conteudo').load('list_vendas_conv.php',
        {
            cod_convenio:cod_convenio,

        },function () {
        });
});
$("#C_mes").change(function() {
    table = $('#tabela_producao').DataTable({
        "destroy": true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "serverSide": false,
        "responsive": true,
        "autoWidth": false,
        "deferRender": true,
        "ajax": {
            "url": 'list_vendas_conv2.php',
            "method": 'POST',
            "data": {
                cod_convenio: cod_convenio,
                mes_atual:  $("#C_mes").val()
            },
            "dataType": 'json'
        },
        "columnDefs": [
            {
                "targets": 7,
                "data": null,
                "render": function ( data, type, row, meta ) {
                    return '<button type="button" class="btn btn-primary btnvia2 btn-xs">2ª via</button>';
                }
            },
            {
                "targets": 8,
                "data": null,
                "render": function ( data, type, row, meta ) {
                    return '<button type="button" class="btn btn-danger btnestornar btn-xs">Estornar</button>';
                }
            },
            { "targets": 0,"width": "80px" },
            { "targets": 1,"width": "340px" },
            { "targets": 2,"width": "70px" },
            { "targets": 3,"width": "50px" },
            { "targets": 4,"width": "80px" },
            { "targets": 5,"width": "80px" },
            { "targets": 6,"width": "100px" },
        ],
        "order": [[ 0, "desc" ]],
        "columns": [
            { "data": "lancamento" },
            { "data": "associado" },
            { "data": "data",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            },
            { "data": "hora" },
            { "data": "valor",
                "render": $.fn.dataTable.render.number( '.', ',', 2, '' ),
                "className": "text-right"
            },
            { "data": "parcela" },
            { "data": "data_fatura",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            }
        ],
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
        "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
            // Remove the formatting to get integer data for summation
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
            // Total over all pages
            total = api
                .column( 4 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Total over this page
            pageTotal = api
                .column( 4, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Update footer
            $( api.column( 0 ).footer() ).html(
                'Total relatório R$ '+ total.toLocaleString()
            );
            $( api.column( 2 ).footer() ).html(
                'Soma página R$ '+ pageTotal.toFixed(2)
            );
        }
    });
});
$("#C_mesestorno").change(function() {

    table_estorno = $('#tabela_producao_estorno').DataTable({
        "destroy": true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "serverSide": false,
        "responsive": true,
        "autoWidth": false,
        "deferRender": true,
        "ajax": {
            "url": 'list_vendas_conv2_estorno.php',
            "method": 'POST',
            "data": {
                cod_convenio: cod_convenio,
                mes_atual: $("#C_mesestorno").val()
            },
            "dataType": 'json'
        },
        "columnDefs": [
            {
                "targets": 10,
                "data": null,
                "render": function ( data, type, row, meta ) {
                    return '<button type="button" class="btn btn-danger btncancelarestornar btn-xs">Cancelar estorno</button>';
                }
            },
            { "targets": 0,"width": "80px" },
            { "targets": 1,"width": "340px" },
            { "targets": 2,"width": "70px" },
            { "targets": 3,"width": "50px" },
            { "targets": 4,"width": "80px" },
            { "targets": 8,"width": "80px" }
        ],
        "error":       function(xhr,status,error) {
            alert(xhr.responseText);
        },
        "order": [[ 0, "desc" ]],
        "columns": [
            { "data": "lancamento" },
            { "data": "associado" },
            { "data": "data",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            },
            { "data": "hora" },
            { "data": "valor",
                "render": $.fn.dataTable.render.number( '.', ',', 2, '' ),
                "className": "text-right"
            },
            { "data": "parcela" },
            { "data": "descricao" },
            { "data": "data_estorno",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            },
            { "data": "hora_estorno" },
            { "data": "data_fatura",
                "render": function (data, type, row) {
                    data = moment(data).format('DD/MM/YYYY');
                    if(data !== "Invalid date") {
                        return data; // d is a valid date.
                    }else{
                        return "";
                    }
                }
            }
        ],
        "pagingType": "full_numbers",
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
        "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
            // Remove the formatting to get integer data for summation
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
            // Total over all pages
            total = api
                .column( 4 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Total over this page
            pageTotal = api
                .column( 4, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
            // Update footer
            $( api.column( 0 ).footer() ).html(
                'Total relatório: R$ '+ total.toLocaleString()
            );
            $( api.column( 2 ).footer() ).html(
                'Soma página: R$ '+ pageTotal.toLocaleString()
            );
        }
    });
});
$('#tabela_producao tbody').on( 'click', '.btnvia2', function () {
    $("#divLoading").css("display", "block");
    //debugger;
    var linha = table.row( $(this).parents('tr') ).data();
    var lancamento     = linha["lancamento"];
    var matricula      = linha["matricula"];
    var valor          = linha["valor"];
    var data           = linha["data"];
    var hora           = linha["hora"];
    var cod_empregador = linha["codigo_empregador"];
    var cod_convenio   = linha["cod_convenio"];

    $.ajax({
        url: "segunda_via.php",
        type: "POST",
        async: true,
        data: {lancamento:lancamento,matricula : matricula,valor:valor,data:data,hora:hora,cod_empregador:cod_empregador,cod_convenio:cod_convenio},
        dataType: 'json',
        error: function (request, error) {
            console.log(arguments);
            $("#divLoading").css("display", "none");
            alert(" Can't do because: " + error);
        },
        success: function (data) {
            $("#nome_gravado").html(data.nome);
            $("#valorpedido").html(parseFloat(data.valorpedido).toFixed(2));
            // ***************** primeira via *******************
            // inicio limpar campos
            $("#nome_cupon").html("");
            $("#matricula_cupon").html("");
            $("#datacad_cupon").html("");
            $("#hora_cupon").html("");
            $("#codcarteira_cupon").html("");
            $("#registrolan_cupon").html("");
            $("#valorpedido_cupon").html("");
            $("#valorpedido_cupon").text("");
            $('#tabela_parcelas').html("");
            $('#data').html("");
            // fim limpar campos
            $("#nomefantasia_cupon").html(data.nomefantasia.substring(0,32));
            $("#razaosocial_cupon").html(data.razaosocial);
            $("#endereco_cupon").html(data.endereco);
            $("#cnpj_cupon").html("CNPJ:"+data.cnpj.substring(0,23));
            $("#cidade_cupon").html(data.cidade.substring(0,16));
            $("#nome_cupon").html(data.nome);
            $("#matricula_cupon").html(data.matricula);
            $("#datacad_cupon").html(data.datacad);
            $("#hora_cupon").html(data.hora);
            $("#codcarteira_cupon").html(data.codcarteira.substring(6,9));
            $("#valorpedido_cupon").html(parseFloat(data.valorpedido).toFixed(2));
            var valor_pedido = "Total: &nbsp;&nbsp;&nbsp;"+parseFloat(data.valorpedido).toFixed(2);
            var valor_pedido_x = "R$ "+parseFloat(data.valorpedido).toFixed(2);
            var total = parseFloat(data.total).toFixed(2);

            $("#tabela_parcelas").html("");
            $("#tabela_parcelas").text("");
            if(data.parcelas === 1 || data.parcelas === 0) {
                $("#registrolan_cupon").html("Registro:"+data.registrolan);
                $('#tabela_parcelas').append("<div class='row'><div class='col-md-12' style='font-weight: bold; padding-left: 14px; font-size: 12pt!important;'><span id='mes_seq_cupon'>Mês:&nbsp;&nbsp;&nbsp;"+data.mes_seq+"</span></div></div>");
                $('#tabela_parcelas').append("<div class='row'><div class='col-md-12' id='valorpedido_cupon' style='font-weight: bold; padding-left: 14px; font-size: 12pt!important;'>Total: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+valor_pedido_x+"</div></div>");
            }else{
                $("#registrolan_cupon").html("");
                $("#tabela_parcelas").html("");
                $("#tabela_parcelas").text("");
                $('#tabela_parcelas').append("<div class='row'><div class='col-md-11' style='font-weight: bold;border-bottom: 0.3pt solid #000;width: 60%;margin-left: 12pt;margin-right:12pt;padding-left: 0;'>parc&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;valor&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;mes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;registro</div></div>");
                for (var i = 1; i <= data.parcelas; i++) {
                    $('#tabela_parcelas').append("<div class='row'><div class='col-md-12'>" +
                        "<div style='width:30pt;float:left;font-weight: bold;font-size: 11pt;'>" + data[i].numero + "</div>" +
                        "<div style='width:40pt;float:left;font-weight: bold;font-size: 11pt;'>" + parseFloat(data[i].valor_parcela).toFixed(2).replace(".",",") + "</div>" +
                        "<div style='width:60pt;float:left;font-weight: bold;font-size: 11pt;'>" + data[i].mes_seq + "</div>" +
                        "<div style='width:40pt;float:left;font-weight: bold;font-size: 11pt;'>" + data[i].registrolan + "</div>" +
                        "</div></div>");
                }
                $('#tabela_parcelas').append("<div class='row'><div class='col-md-12' id='valorpedido_cupon' style='font-weight: bold; padding-left: 10px; font-size: 14pt!important;'>Total: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp"+total+"</div></div>");
            }
            // ***************** segunda via *******************
            // inicio limpar campos
            $("#nome2_cupon").html("");
            $("#matricula2_cupon").html("");
            $("#datacad2_cupon").html("");
            $("#hora2_cupon").html("");
            $("#codcarteira2_cupon").html("");
            $("#registrolan2_cupon").html("");
            $("#valorpedido2_cupon").html("");
            $("#valorpedido2_cupon").text("");
            $('#tabela2_parcelas').html("");
            $('#data2').html("");
            // fim limpar campos
            $("#nomefantasia2_cupon").html(data.nomefantasia);
            $("#razaosocial2_cupon").html(data.razaosocial);
            $("#endereco2_cupon").html(data.endereco);
            $("#cnpj2_cupon").html("CNPJ:"+data.cnpj.substring(0,23));
            $("#cidade2_cupon").html(data.cidade.substring(0,16));
            $("#nome2_cupon").html(data.nome);
            $("#matricula2_cupon").html(data.matricula);
            $("#datacad2_cupon").html(data.datacad);
            $("#hora2_cupon").html(data.hora);
            $("#codcarteira2_cupon").html(data.codcarteira.substring(6,9));
            $("#valorpedido2_cupon").html(data.valorpedido);
            var valor_pedido = "Total: &nbsp;&nbsp;&nbsp;"+parseFloat(data.valorpedido).toFixed(2);
            var valor_pedido_x = "R$ "+parseFloat(data.valorpedido).toFixed(2);
            var total = parseFloat(data.total).toFixed(2);

            $('#tabela_parcelas2').html("");
            $('#tabela_parcelas2').text("");
            if(data.parcelas === 1 || data.parcelas === 0) {
                $("#registrolan2_cupon").html("Registro:"+data.registrolan);
                $('#tabela_parcelas2').append("<div class='row'><div class='col-md-12' style='font-weight: bold; padding-left: 14px; font-size: 12pt!important;'><span id='mes_seq_cupon'>Mês:&nbsp;&nbsp;&nbsp;"+data.mes_seq+"</span></div></div>");
                $('#tabela_parcelas2').append("<div class='row'><div class='col-md-12' id='valorpedido_cupon' style='font-weight: bold; padding-left: 14px; font-size: 12pt!important;'>Total: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+valor_pedido_x+"</div></div>");
            }else{
                $("#registrolan_cupon").html("");
                $("#tabela_parcelas2").html("");
                $("#tabela_parcelas2").text("");
                $('#tabela_parcelas2').append("<div class='row'><div class='col-md-11' style='font-weight: bold;border-bottom: 0.3pt solid #000;width: 60%;margin-left: 12pt;margin-right:12pt;padding-left: 0;'>parc&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;valor&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;mes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;registro</div></div>");
                for (var i = 1; i <= data.parcelas; i++) {
                    $('#tabela_parcelas2').append("<div class='row'><div class='col-md-12'>" +
                        "<div style='width:30pt;float:left;font-weight: bold;font-size: 11pt;'>" + data[i].numero + "</div>" +
                        "<div style='width:40pt;float:left;font-weight: bold;font-size: 11pt;'>" + parseFloat(data[i].valor_parcela).toFixed(2).replace(".",",") + "</div>" +
                        "<div style='width:60pt;float:left;font-weight: bold;font-size: 11pt;'>" + data[i].mes_seq + "</div>" +
                        "<div style='width:40pt;float:left;font-weight: bold;font-size: 11pt;'>" + data[i].registrolan + "</div>" +
                        "</div></div>");
                }
                $('#tabela_parcelas2').append("<div class='row'><div class='col-md-12' id='valorpedido_cupon' style='font-weight: bold; padding-left: 10px; font-size: 14pt!important;'>Total: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp"+total+"</div></div>");
            }
            $("#divLoading").css("display", "none");
            $('#myModal').modal('show');
        }
    });
} );
$('#tabela_producao tbody').on( 'click', '.btnestornar', function () {
    //debugger;
    data_row_estorno = table.row($(this).closest('tr')).data();
    lancamento_estorno =  data_row_estorno.lancamento;
    cod_convenio_estorno = data_row_estorno.cod_convenio;
    nome_titular = data_row_estorno.associado;
    uuid_conta = "";	
    valor_estorno = 0;
    $.ajax({
        url:"convenio_senha_estorno.php",
        method: "POST",
        data: {cod_convenio : cod_convenio_estorno},
        dataType: "json",
        success:function (data) {
            if(data.senha_estorno == null){
                
                        $.ajax({
                            url:"parcela_uuid.php",
                            method: "POST",
                            data: {lancamento : lancamento_estorno},
                            dataType: "json",
                            success:function (data) {
                                //debugger;
                                messagetable  = '<div class="panel panel-default"><div class="panel-heading">Titular</div><div class="panel-body">'+nome_titular+'</div></div>';	
                                messagetable += '<table class="table table-striped table-sm" style="width:100%;">';
                                messagetable += '<thead><tr>' +
                                                '<th scope="col">#</th>' +
                                                '<th scope="col">Registro</th>' +
                                                '<th scope="col">Valor</th>' +
                                                '<th scope="col" style="text-align: center">Data</th>' +
                                                '<th scope="col" style="text-align: center">Parcela</th>' +
                                                '<th scope="col" style="text-align: center">Mes</th>' +
                                                '</tr>' +
                                                '</thead><tbody>';
                                if(data[1].uuid_conta != ""){
                                    subtitle = 'Deseja realmente efetuar o estorno ?'
                                    bsd.mydialog('Atenção',subtitle, function(value){
                                        if(value === 'yes'){
                                            nome_operador = $('#nome_op').val()
                                            $.each(data, function (index, registro) {

                                                uuid_conta = registro.uuid_conta;

                                                valor = registro.valor;
                                                valor = valor.replace(".", ",");
                                                
                                                messagetable += '<tr><th style="text-align: center;"><h6>' + index +
                                                                '</h6></th><th><h6>' + registro.lancamento +
                                                                '</h6></th><th style="text-align: right"><h6>' + valor +
                                                                '</h6></th><th style="text-align: center"><h6>' + registro.data +
                                                                '</h6></th><th style="text-align: center"><h6>' + registro.parcela +
                                                                '</h6></th><th style="text-align: center"><h6>' + registro.mes +
                                                                '</h6></th></tr>'
                                            });
                                            messagetable += '<tbody/></table>';
                                            bootbox.confirm({
                                                title: 'Confirma a exclusão dos registros abaixo ?',
                                                message: messagetable,
                                                buttons: {
                                                    cancel: {
                                                        label: '&nbsp<i class="fa fa-times"></i>&nbsp&nbsp Cancela'                                  
                                                    },
                                                    confirm: {
                                                        label: '&nbsp<i class="fa fa-check"></i>&nbsp&nbsp Confirma'
                                                    }
                                                },
                                                callback: function (result) {
                                                    if (result) {
                                                        waitingDialog.show('Excluindo, aguarde ...');
                                                        
                                                        $.ajax({
                                                            url: "conta_excluir_parcelas.php",
                                                            method: "POST",
                                                            dataType: "json",
                                                            data: {
                                                                data: data,
                                                                nome_operador: nome_operador
                                                            },
                                                            success: function (data) {
                                                                if (data.Resultado === "excluido") {
                                                                    waitingDialog.hide();
                                                                    Swal.fire(
                                                                        'Parabéns!',
                                                                        'Estorno efetuado com sucesso !',
                                                                        'success'
                                                                    );
                                                                    carrega_grid_todos()
                                                                } else {
                                                                    Swal.fire(
                                                                        'Atenção!',
                                                                        'Não Excluiu !',
                                                                        'error'
                                                                    );
                                                                    waitingDialog.hide();
                                                                }
                                                            }
                                                        });
                                                    } else {
                                                        //alert('No');
                                                    }
                                                }
                                            });
                                        }
                                    });
                                }else{

                                    var $button = $(this);
                                    var valor;
                                    //debugger;
                                    valor =  parseFloat(data_row_estorno.valor).toFixed(2).replace(".", ",");
                                    valor_estorno = parseFloat(data_row_estorno.valor).toFixed(2).replace(".", ",");
                                    subtitle = 'Registro... : <span style="color:blue;font-weight:bold">'
                                                +data_row_estorno.lancamento+'</span><br>Associado : <span style="color:blue;font-weight:bold">'
                                                +data_row_estorno.associado+'</span><br>Valor.......... : <span style="color:blue;font-weight:bold">'
                                                +valor+'</span><br>Data........... : <span style="color:blue;font-weight:bold">'
                                                +data_row_estorno.data+'</span><br>Hora........... : <span style="color:blue;font-weight:bold">'
                                                +data_row_estorno.hora+'</span><br>Parcela...... : <span style="color:blue;font-weight:bold">'
                                                +data_row_estorno.parcela+'</span>';
                                    var cod_convenio = data_row_estorno.cod_convenio;
                                    cod_convenio_estorno = data_row_estorno.cod_convenio;
                                    registro_lancamento = data_row_estorno.lancamento;
                                

                                    bsd.mydialog('Confirma o estorno das informações abaixo ?',subtitle, function(value){
                                        if(value === 'yes'){
                                            waitingDialog.show('Estornando, aguarde ...');  
                                            $.ajax({
                                                url: "Adm/pages/conta/conta_exclui.php",
                                                method: "POST",
                                                dataType: "json",
                                                data: {"lancamento": data_row_estorno.lancamento,"mes":$('#C_mes').val(),"usuario_codigo":null,"descricao":$('#nome_op').val()},
                                                success: function (data) {
                                                    if (data.Resultado === "excluido") {
                                                        table.row( $button.parents('tr') ).remove().draw();
                                                        //alert("Excluido com sucesso");
                                                        waitingDialog.hide();
                                                        //bsd.alert('Estornado com sucesso !',"");
                                                        Swal.fire(
                                                            'Parabéns!',
                                                            'Estorno efetuado com sucesso !',
                                                            'success'
                                                        );
                                                    }else if (data.Resultado === "mes_bloqueado") {
                                                        waitingDialog.hide();
                                                        //bsd.alert('Estorno cancelado porque o mês já fechou !');
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: 'Atenção',
                                                            text: 'Estorno cancelado porque o mês ja fechou !',
                                                        });
                                                    }
                                                }
                                            });
                                        }else if(value === 'no'){
                                            //bsd.alert('Estorno cancelado !');
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Atenção!',
                                                text: 'Estorno cancelado !'
                                            });
                                        }
                                    });

                                        
                                }
                            
                            }
                        });

            }else{
                senha_estorno = data.senha_estorno;
                $("#frmSenhaEstor")[0].reset();
                $("#ModalSenhaEstor").modal("show");

            }
        }
    });
    
});
$('#tabela_producao_estorno tbody').on( 'click', '.btncancelarestornar', function () {
    var data_row = table_estorno.row($(this).closest('tr')).data();
    var $button = $(this);
    var valor;
    valor =  parseFloat(data_row.valor).toFixed(2).replace(".", ",");
    var subtitle = 'Registro... : <span style="color:blue;font-weight:bold">'+data_row.lancamento+'</span><br>Associado : <span style="color:blue;font-weight:bold">'+data_row.associado+'</span><br>Valor.......... : <span style="color:blue;font-weight:bold">'+valor+'</span><br>Data........... : <span style="color:blue;font-weight:bold">'+data_row.data+'</span><br>Hora........... : <span style="color:blue;font-weight:bold">'+data_row.hora+'</span><br>Parcela...... : <span style="color:blue;font-weight:bold">'+data_row.parcela+'</span>';
    bsd.mydialog('Confirma o cancelamento deste estorno ?',subtitle, function(value){
        if(value === 'yes'){
            waitingDialog.show('Estornando, aguarde ...');
            $.ajax({
                url: "Adm/pages/conta/cancelar_estorno.php",
                method: "POST",
                dataType: "json",
                data: {"lancamento": data_row.lancamento,"mes":$('#C_mesestorno').val(),"usuario_codigo":null,"descricao":null},
                success: function (data) {
                    if (data.Resultado === "excluido") {
                        table_estorno.row( $button.parents('tr') ).remove().draw();
                        //alert("Excluido com sucesso");
                        waitingDialog.hide();
                        //bsd.alert('Estornado com sucesso !',"");
                        Swal.fire(
                            'Parabéns!',
                            'Cancelamento do estorno efetuado com sucesso !',
                            'success'
                        );
                    }else if (data.Resultado === "mes_bloqueado") {
                        waitingDialog.hide();
                        //bsd.alert('Estorno cancelado porque o mês já fechou !');
                        Swal.fire({
                            icon: 'error',
                            title: 'Atenção',
                            text: 'Cancelamento do estorno não pode ser executado, porque o mês ja fechou !',
                        });
                    }
                }
            });
        }else if(value === 'no'){
            //bsd.alert('Estorno cancelado !');
            Swal.fire({
                icon: 'error',
                title: 'Atenção!',
                text: 'Cancelamento do estorno cancelado !'
            });
        }
    });
});
$('#tabela_producao_estorno tbody').on( 'click', '.btncancelarestorno', function () {

    var data_row = table.row($(this).closest('tr')).data();
    var $button = $(this);
    var valor;  
    valor =  parseFloat(data_row.valor).toFixed(2).replace(".", ",");
    var subtitle = 'Registro... : <span style="color:blue;font-weight:bold">'+data_row.lancamento+'</span><br>Associado : <span style="color:blue;font-weight:bold">'+data_row.associado+'</span><br>Valor.......... : <span style="color:blue;font-weight:bold">'+valor+'</span><br>Data........... : <span style="color:blue;font-weight:bold">'+data_row.data+'</span><br>Hora........... : <span style="color:blue;font-weight:bold">'+data_row.hora+'</span><br>Parcela...... : <span style="color:blue;font-weight:bold">'+data_row.parcela+'</span>';
    bsd.mydialog('Confirma o estorno das informações abaixo ?',subtitle, function(value){
        if(value === 'yes'){
                waitingDialog.show('Cancelando estorno, aguarde ...');
                $.ajax({
                    url: "pages/conta/cancelar_estorno.php",
                    method: "POST",
                    dataType: "json",
                    data: {"lancamento": data_row.lancamento,"mes":$('#C_mes').val()},
                    success: function (data) {
                        if (data.Resultado === "excluido") {
                            table.row( $button.parents('tr') ).remove().draw();
                            //alert("Excluido com sucesso");
                            waitingDialog.hide();
                            Swal.fire(
                                'Parabéns!',
                                'Estorno cancelado com sucesso !',
                                'success'
                            );
                            carrega_grid_todos();
                        }else if (data.Resultado === "mes_bloqueado") {
                            waitingDialog.hide();
                            Swal.fire({
                                icon: 'error',
                                title: 'Atenção',
                                text: 'Cancelamento de Estorno não efetuado !',
                            });
                        }
                    }
                });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Atenção!',
                text: 'Cancelamento de estorno abortado !'
            });
        }
    });
});
bsd.mydialog = function (title, subtitle, options) {
    return this.dialog('mydialog', options, function ($dialog, settings) {
        // Manage dialog texts.
        $('.bsd-title', $dialog).html(title || '');
        $('.bsd-subtitle', $dialog).html(subtitle || '');

        // Customize buttons.
        $('.bsd-no', $dialog).addClass(settings.noClass).text(settings.noText).click(function () {
            $dialog.trigger('dismiss.bsd');
        });
      /*  $('.bsd-maybe', $dialog).addClass(settings.maybeClass).text(settings.maybeText).click(function () {
            $dialog.trigger('maybe.bsd');
        });*/
        $('.bsd-yes', $dialog).addClass(settings.yesClass).text(settings.yesText).click(function () {
            $dialog.trigger('yes.bsd');
        });

        // Manage dialog callback.
        if (typeof settings.callback === 'function') {
            $dialog
                .on('dismiss.bsd', function () {
                    settings.callback('no');
                })
                .on('maybe.bsd', function () {
                    if (settings.callback('maybe') !== false) {
                        $dialog.modal('hide');
                    }
                })
                .on('yes.bsd', function () {
                    if (settings.callback('yes') !== false) {
                        $dialog.modal('hide');
                    }
                })
            ;
        }
    });
};
bsd.defaults.mydialog = {
    // The template used to generate dialog.
    template: `<div class="modal fade" tabindex="-1" id="modal_confirma_estorno" name="modal_confirma_estorno" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title bsd-title"></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="bsd-subtitle"></p>
                                <div class="form-group">
                                    <label for="nome_op">Favor informar o nome do(a) operador(a) que está efetuando o estorno:</label>
                                    <input id="nome_op" type="text" class="form-control">
                                </div>                                
                            </div>
                            <div class="modal-footer">
                                <div class="bsd-controls d-flex flex-grow-1 align-items-center">
                                    <button type="button" class="btn bsd-no mr-1" data-dismiss="modal">Não</button>
                                    <button type="button" class="btn bsd-maybe mx-1"></button>
                                    <button type="submit" class="btn bsd-yes ml-1">Sim</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`,

    // No button.
    noText: 'Não',
    noClass: 'btn-danger',

    // Maybe button.
    maybeText: 'Maybe',
    maybeClass: 'btn-primary',

    // Yes button.
    yesText: 'Sim',
    yesClass: 'btn-success',

    // DEFAULTS OVERRIDE EXAMPLE
    // Center horizontally the dialog text: true | [false]
    hcenter: false,
};
$("#btnSenha_Estor").click(function(event){
    event.preventDefault();
    var $button = $(this);
    var senha = $("#C_Senha_Estor").val();
    //debugger;
    lancamento_estorno =  data_row_estorno.lancamento;
    cod_convenio_estorno = data_row_estorno.cod_convenio;
    nome_titular = data_row_estorno.associado;
    uuid_conta = "";	
    valor_estorno = 0;
    //debugger;
    if(senha !== ""){
            if(senha === senha_estorno){
                $("#ModalSenhaEstor").modal('hide');

                    nome_operador = $('#nome_op').val();
                    $.ajax({
                        url:"parcela_uuid.php",
                        method: "POST",
                        data: {lancamento : lancamento_estorno},
                        dataType: "json",
                        success:function (data) {
                            //debugger;
                            messagetable  = '<div class="panel panel-default"><div class="panel-heading">Titular</div><div class="panel-body">'+nome_titular+'</div></div>';	
                            messagetable += '<table class="table table-striped table-sm" style="width:100%;">';
                            messagetable += '<thead><tr>' +
                                            '<th scope="col">#</th>' +
                                            '<th scope="col">Registro</th>' +
                                            '<th scope="col">Valor</th>' +
                                            '<th scope="col" style="text-align: center">Data</th>' +
                                            '<th scope="col" style="text-align: center">Parcela</th>' +
                                            '<th scope="col" style="text-align: center">Mes</th>' +
                                            '</tr>' +
                                            '</thead><tbody>';
                            if(data[1].uuid_conta != ""){
                                subtitle = 'Deseja realmente efetuar o estorno ?'
                                bsd.mydialog('Atenção',subtitle, function(value){
                                    if(value === 'yes'){
                                        nome_operador = $('#nome_op').val()
                                        $.each(data, function (index, registro) {

                                            uuid_conta = registro.uuid_conta;

                                            valor = registro.valor;
                                            valor = valor.replace(".", ",");
                                            
                                            messagetable += '<tr><th style="text-align: center;"><h6>' + index +
                                                            '</h6></th><th><h6>' + registro.lancamento +
                                                            '</h6></th><th style="text-align: right"><h6>' + valor +
                                                            '</h6></th><th style="text-align: center"><h6>' + registro.data +
                                                            '</h6></th><th style="text-align: center"><h6>' + registro.parcela +
                                                            '</h6></th><th style="text-align: center"><h6>' + registro.mes +
                                                            '</h6></th></tr>'
                                        });
                                            messagetable += '<tbody/></table>';
                                        bootbox.confirm({
                                            title: 'Confirma a exclusão dos registros abaixo ?',
                                            message: messagetable,
                                            buttons: {
                                                cancel: {
                                                label: '&nbsp<i class="fa fa-times"></i>&nbsp&nbsp Cancela',
                                                id: 'btn_cancel'
                                                },
                                                confirm: {
                                                label: '&nbsp<i class="fa fa-check"></i>&nbsp&nbsp Confirma',
                                                id: 'btn_confirm'
                                                }
                                            },
                                            callback: function (result) {
                                                if (result) {
                                                    waitingDialog.show('Excluindo, aguarde ...');
                                                    
                                                    $.ajax({
                                                        url: "conta_excluir_parcelas.php",
                                                        method: "POST",
                                                        dataType: "json",
                                                        data: {
                                                            data: data,
                                                            nome_operador: nome_operador
                                                        },
                                                        success: function (data) {
                                                            if (data.Resultado === "excluido") {
                                                                waitingDialog.hide();
                                                                Swal.fire(
                                                                    'Parabéns!',
                                                                    'Estorno efetuado com sucesso !',
                                                                    'success'
                                                                );
                                                                carrega_grid_todos()
                                                            } else {
                                                                Swal.fire(
                                                                    'Atenção!',
                                                                    'Não Excluiu !',
                                                                    'error'
                                                                );
                                                                waitingDialog.hide();
                                                            }
                                                        }
                                                    });
                                                } else {
                                                    //alert('No');
                                                }
                                            }
                                        });
                                    }
                                });
                        
                            }else{

                                var $button = $(this);
                                var valor;
                                //debugger;
                                valor =  parseFloat(data_row_estorno.valor).toFixed(2).replace(".", ",");
                                valor_estorno = parseFloat(data_row_estorno.valor).toFixed(2).replace(".", ",");
                                subtitle = 'Registro... : <span style="color:blue;font-weight:bold">'
                                            +data_row_estorno.lancamento+'</span><br>Associado : <span style="color:blue;font-weight:bold">'
                                            +data_row_estorno.associado+'</span><br>Valor.......... : <span style="color:blue;font-weight:bold">'
                                            +valor+'</span><br>Data........... : <span style="color:blue;font-weight:bold">'
                                            +data_row_estorno.data+'</span><br>Hora........... : <span style="color:blue;font-weight:bold">'
                                            +data_row_estorno.hora+'</span><br>Parcela...... : <span style="color:blue;font-weight:bold">'
                                            +data_row_estorno.parcela+'</span>';
                                var cod_convenio = data_row_estorno.cod_convenio;
                                cod_convenio_estorno = data_row_estorno.cod_convenio;
                                registro_lancamento = data_row_estorno.lancamento;
                            

                                bsd.mydialog('Confirma o estorno das informações abaixo ?',subtitle, function(value){
                                    if(value === 'yes'){
                                        waitingDialog.show('Estornando, aguarde ...');  
                                        $.ajax({
                                            url: "Adm/pages/conta/conta_exclui.php",
                                            method: "POST",
                                            dataType: "json",
                                            data: {"lancamento": data_row_estorno.lancamento,"mes":$('#C_mes').val(),"usuario_codigo":null,"descricao":$('#nome_op').val()},
                                            success: function (data) {
                                                if (data.Resultado === "excluido") {
                                                    table.row( $button.parents('tr') ).remove().draw();
                                                    //alert("Excluido com sucesso");
                                                    waitingDialog.hide();
                                                    //bsd.alert('Estornado com sucesso !',"");
                                                    Swal.fire(
                                                        'Parabéns!',
                                                        'Estorno efetuado com sucesso !',
                                                        'success'
                                                    );
                                                }else if (data.Resultado === "mes_bloqueado") {
                                                    waitingDialog.hide();
                                                    //bsd.alert('Estorno cancelado porque o mês já fechou !');
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Atenção',
                                                        text: 'Estorno cancelado porque o mês ja fechou !',
                                                    });
                                                }
                                            }
                                        });
                                    }else if(value === 'no'){
                                        //bsd.alert('Estorno cancelado !');
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Atenção!',
                                            text: 'Estorno cancelado !'
                                        });
                                    }
                                });   
                            }
                        }
                    });

            }else{
                Swal.fire({
                    icon: 'error',
                    title: 'Atenção!',
                    text: 'Senha incorreta !'
                });
            }
    }else{
        Swal.fire({
            icon: 'error',
            title: 'Atenção!',
            text: 'Digite a Senha !'
        });
    }
});
$('#gerarpdf').click(function () {
    var cod_convenio = $('#cod_convenio').val();
    var mes_atual = $('#C_mes').val();
    $.redirect('gerador_pdf.php',{ cod_convenio: cod_convenio, mes_atual: mes_atual}, "POST", "_blank");
});
$("#botaoimprir").click(function () {
    //pega o conteudo do modal e carrega dentro da div printableFull
    $(".printableLeft").html($("#myModal").html());
    $(".printableLeft #botaoimprir").remove();
    $(".printableLeft #botaofechar").remove();
    $(".printableLeft #titulo").remove();

    $(".printableFull").printThis();

});
$("#botaoretornar").click(function () {
    //debugger;
    var cod_convenio = $('#cod_convenio').val();
    var userconv = $('#userconv').val();
    var passconv = $('#passconv').val();
    var razaosocial = $('#razaosocial').val();
    var nomefantasia = $('#nomefantasia').val();
    var endereco = $('#endereco').val();
    var bairro = $('#bairro').val();
    var cidade = $("#cidade").val();
    var parcela_conv = $('#parcelas_a_exibir').val();
    var pede_senha = $('#pede_senha').val();
    var cnpj = $('#cnpj').val();
    var aceita_parce_individ = $('#aceita_parce_individ').val();
    var numero = $('#numero').val();
    var divisao = $('#divisao').val();
    $.redirect('pagina_principal.php',{ cod_convenio: cod_convenio, userconv: userconv, passconv: passconv, razaosocial: razaosocial, nomefantasia: nomefantasia, endereco: endereco, bairro: bairro, parcela_conv: parcela_conv, pede_senha: pede_senha, cnpj: cnpj, cidade: cidade, aceita_parce_individ: aceita_parce_individ, numero: numero, divisao: divisao });
});
$("#sairsistema").click(function () {
    document.location.href = 'index.html';
});
function btsair() {
    //debugger;
    $.redirect('index.html');
}
$("#reexibir").click(function () {
    $('#myModal').modal('show');
});
$("#relatorio").click(function () {
    debugger;
    carrega_grid();
    //table.ajax.reload();
});
$("#rel_estono").click(function () {
    debugger;
    carrega_gridestorno();
    //table.ajax.reload();
});
/*$("#link_principal").click(function () {
    debugger;
    $("#cod_carteira").val("");
    $("#nome_associado_exibir").html("");
    $("#cartao_exibir").html("");
    $("#valor_pedido").val("");
    $("#valor_parcela").html("");
    $("#pass").val("");
    //$("#cod_carteira").focus();
});*/
function soNums(e){
    //teclas adicionais permitidas (tab,delete,backspace,setas direita e esquerda)
    keyCodesPermitidos = new Array(8,9,37,39,46);
    //numeros e 0 a 9 do teclado alfanumerico
    for(x=48;x<=57;x++){
        keyCodesPermitidos.push(x);
    }
    //numeros e 0 a 9 do teclado numerico
    for(x=96;x<=105;x++){
        keyCodesPermitidos.push(x);
    }
    //Pega a tecla digitada
    keyCode = e.which;
    //Verifica se a tecla digitada é permitida
    if ($.inArray(keyCode,keyCodesPermitidos) != -1){
        return true;
    }
    return false;
}
function mudarparcela() {
    debugger;
    var msg_parcela = $('#msg_parcela');
    var nparcela_escolhida = $("#nparcelas").val();
    nparcela_escolhida  = parseInt(nparcela_escolhida);
    var valor_total = $("#valor_pedido").val();
    valor_total = valor_total.replace('.','').replace(',','.');
    var limitex = $("#txtSaldo").html();
    var valor_totalx;
    var valor_parcela;
    valor_total = parseFloat(valor_total);
    limitex = moedaParaNumero(limitex);
    valor_totalx = moedaParaNumero(valor_total);
    debugger;
    if (nparcela_escolhida === 1) {
        if (valor_totalx <= limitex) {
            $("#valor_parcela").html("");
            $("#val_parcela").val("");
            msg_parcela.css('display','none');
        }else {
            if (valor_totalx > 0 ) {
                Swal.fire({
                    title: "Atenção!",
                    text: "O valor digitado é maior que o saldo !",
                    icon: "warning"
                });
                msg_parcela.css('display','none');
                $("#valor_parcela").html("");
                $("#val_parcela").val("");
                $("#valor_pedido").val("");
                $("#pass").val("");
                $("#valor_pedido").focus();
            }
        }
    }else if (valor_total > 0 && nparcela_escolhida > 0) {

        nparcela_escolhida = parseInt(nparcela_escolhida);
        valor_parcela = moedaParaNumero(valor_totalx / nparcela_escolhida);
        if(valor_parcela > limitex){

            msg_parcela.css('display','none');
            $("#valor_parcela").html("");
            $("#val_parcela").val("");
            $("#pass").val("");
            $("#valor_pedido").val("");
            $("#nparcelas").val("1");
            if (browser_name === "iexplorer") {
                Swal.fire({
                    icon: 'warning',
                    text: 'O valor da parcela é maior que o saldo',
                    title: 'Atenção'
                });
            } else {
                Swal.fire({
                    title: "Atenção!",
                    text: "O valor da parcela é maior que o saldo !",
                    icon: "warning"
                });
            }
        }else {
            msg_parcela.css('display','block');
            valor_parcela = numeroParaMoeda(valor_parcela);
            $("#valor_parcela").html("R$ " + valor_parcela);
            $("#val_parcela").val(valor_parcela);
        }
    }
}
function moedaParaNumero(valor){
    return isNaN(valor) === false ? parseFloat(valor) :   parseFloat(valor.toString().replace("R$","").replace(".","").replace(",","."));
}
function numeroParaMoeda(n, c, d, t){
    c = isNaN(c = Math.abs(c)) ? 2 : c, d = d === undefined ? "," : d, t = t === undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}
function getUrlVars(url) {
    var hash;
    var myJson = {};
    var aux;
    var hashes = url.slice(url.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        aux = hash[1];
        myJson[hash[0]] = aux.replace(/\%20/g," ");
    }
    return myJson;
}
function create_logo_firebase(registrolan, matricula, nome) {
    var data = {
        registrolan: registrolan,
        matricula: matricula,
        nome: nome
    };
    return firebase.database().ref().child('CONTA').push(data);
}
function queryObj() {
    var result = {}, keyValuePairs = location.search.slice(1).split("&");
    keyValuePairs.forEach(function(keyValuePair) {
        keyValuePair = keyValuePair.split('=');
        result[decodeURIComponent(keyValuePair[0])] = decodeURIComponent(keyValuePair[1]) || '';
    });
    return result;
}
function variar_mes(mes,parcel,plano) {
    var result2 = "";
    var mes2  = "";
    var resultadoh = "";
    var mes_x = mes.substring(0, 3);
    var ano_x = mes.substring(4, 8);
    ano_x = parseInt(ano_x);
    var a = [
        {mes : "JAN", numes: 1},
        {mes : "FEV", numes: 2},
        {mes : "MAR", numes: 3},
        {mes : "ABR", numes: 4},
        {mes : "MAI", numes: 5},
        {mes : "JUN", numes: 6},
        {mes : "JUL", numes: 7},
        {mes : "AGO", numes: 8},
        {mes : "SET", numes: 9},
        {mes : "OUT", numes: 10},
        {mes : "NOV", numes: 11},
        {mes : "DEZ", numes: 12},
        {mes : "JAN", numes: 13},
        {mes : "FEV", numes: 14},
        {mes : "MAR", numes: 15},
        {mes : "ABR", numes: 16},
        {mes : "MAI", numes: 17},
        {mes : "JUN", numes: 18},
        {mes : "JUL", numes: 19},
        {mes : "AGO", numes: 20},
        {mes : "SET", numes: 21},
        {mes : "OUT", numes: 22},
        {mes : "NOV", numes: 23},
        {mes : "DEZ", numes: 24}
    ];
    var result = a.filter(function (posicao) { return posicao.mes === mes_x; });
    var nummesx  = result[0]['numes'];

    if (parcel === 1)
    {
        return mes;
    }
    else if (parcel === 2)
    {
        var nova_posicao;
        if((nummesx + 1) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 1;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12
            }
        }else {
            nova_posicao = nummesx + 1;
        }
        result2 = a.filter(function (posicao) { return posicao.numes ===nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        mes2 + "/" + ano_x;
    }
    else if (parcel === 3)
    {
        if((nummesx + 2) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 2;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12
            }
        }else {
            nova_posicao = nummesx + 2;
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 4)
    {
        if((nummesx + 3) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 3;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12
            }
        }else {
            nova_posicao = nummesx + 3;
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 5)
    {
        if((nummesx + 4) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 4;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12
            }
        }else {
            nova_posicao = nummesx + 4;
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 6)
    {
        if((nummesx + 5) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 5;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12
            }
        }else {
            nova_posicao = nummesx + 5;
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 7)
    {
        if((nummesx + 6) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 6;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12
            }
        }else {
            nova_posicao = nummesx + 6;
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 8)
    {
        if((nummesx + 7) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 7;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 7;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 9)
    {
        if((nummesx + 8) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 8;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 8;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 10)
    {
        if((nummesx + 9) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 9;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 9;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 11)
    {
        if((nummesx + 10) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 10;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 10;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 12)
    {
        if((nummesx + 11) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 11;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 11;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 13)
    {
        if((nummesx + 12) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 12;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 12;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 14)
    {
        if((nummesx + 13) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 13;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 13;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 15)
    {
        if((nummesx + 14) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 14;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 14;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 16)
    {
        if((nummesx + 15) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 15;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 15;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 17)
    {
        if((nummesx + 16) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 16;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 16;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 18)
    {
        if((nummesx + 17) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 17;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 17;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 19)
    {
        if((nummesx + 18) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 18;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 18;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 20)
    {
        if((nummesx + 19) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 19;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 19;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 21)
    {
        if((nummesx + 20) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 20;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 20;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 22)
    {
        if((nummesx + 21) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 21;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 21;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 23)
    {
        if((nummesx + 22) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 22;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 22;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function () { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 24)
    {
        if((nummesx + 23) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 23;
            if (nova_posicao === 12){
                nova_posicao = 1;
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 23;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
    else if (parcel === 25)
    {
        if((nummesx + 24) > 12){
            nova_posicao = 1;
            ano_x = ano_x + 1;
            nova_posicao = nummesx + 24;
            if (nova_posicao === 12){
                nova_posicao = 1
            }else{
                nova_posicao = nova_posicao - 12;
                if (nova_posicao > 13){ano_x = ano_x + 1;}
            }
        }else {
            nova_posicao = nummesx + 24;
            if (nova_posicao > 13){ano_x = ano_x + 1;}
        }
        result2 = a.filter(function (posicao) { return posicao.numes === nova_posicao; });
        mes2  = result2[0]['mes'];
        resultadoh = mes2 + "/" + ano_x;
        return mes2 + "/" + ano_x;
    }
}
//aux.replace(/\%20/g," "); SUBSTITUI %20 POR ESPAÇO " " NA URL
