var table_origem;
var table_lancado;
var tableconsulta;
var tableconsultaconv;
var matricula;
var empregador;
var nome = "";
var cod_convenio = "";
var nome_convenio = "";
var abreviacao = "";
var botao_clicado = "";
var Codempregador_origem;
var mes_escolhido;
var divisao;
var viaradio = false;
var mescorrente = "";
var checkedmes;
var KEYBOARD = {
    esc: 27
};
var card1;
var card2;
var card3;
var card4;
var card5;
var card6;

$(document).ready(function(){
    divisao = sessionStorage.getItem("divisao");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");
    card1 = sessionStorage.getItem("card1");
    card2 = sessionStorage.getItem("card2");
    card3 = sessionStorage.getItem("card3");
    card4 = sessionStorage.getItem("card4");
    card5 = sessionStorage.getItem("card5");
    card6 = sessionStorage.getItem("card6");
    //divisao = 1;
    //usuario_global = "w";
    //usuario_cod = 2;
    $("#btnCadastrarCadastroConta").prop("disabled",true);
    $("#btnBuscaAssociado").prop("disabled",false);
    $("#inputMatricula").prop("disabled",true);
    $('#funcionario_cad').val(usuario_cod);
    $('#situacao_reg').val(1); // 1 - Aberto
    $('#tipo_reg').val("C"); // C - Convneio
    // um modal por cima do outro
    $(document).on('show.bs.modal', '.modal', function (event) {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function() {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });
    $.getJSON( "../Adm/pages/conta/meses_conta.php",{ "origem": "convenio" }, function( data ) {
        $('#C_mes').append('<option value="todos">Todos os meses</option>');
        $.each(data, function (index, value) {
            if (value.mes_corrente !== undefined) {
                mescorrente = value.mes_corrente;
            }
            if (value.abreviacao !== undefined) {
                if (mescorrente === value.abreviacao) {
                    $('#C_mes').append('<option selected value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                } else {
                    $('#C_mes').append('<option value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                }
            }
        });
    });
    $.getJSON( "../Adm/pages/conta/meses_conta.php",{ "origem": "cadastro" }, function( data ) {
        $.each(data, function (index, value) {
            if (value.mes_corrente !== undefined) {
                mescorrente = value.mes_corrente;
            }
            if (value.abreviacao !== undefined) {
                if (mescorrente === value.abreviacao) {
                    $('#select_mes').append('<option selected value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                } else {
                    $('#select_mes').append('<option value="' + value.abreviacao + '">' + value.abreviacao + '</option>');
                }
            }
        });
    });
    $("#inputValor").maskMoney({
        prefix: "",
        decimal: ",",
        thousands: "."
    });
    $("#inputValorEdita").maskMoney({
        prefix: "",
        decimal: ",",
        thousands: "."
    });
    $('#inputParcela').mask("99/99");
    var d = new Date();
    dataHora = (d.toLocaleString());
    $('#inputDataCad').val(dataHora.substring(0,10));
    //$('#inputDataCad').mask("00/00/0000");
    $('#optUnica').attr("checked", true);
    $('#inputMatricula').focus();
    $('#tab_matricula_origem').on('click', 'tbody .btnexcluirList', function () {
        var data_row = table_origem.row($(this).closest('tr')).data();
        var $button = $(this);
        var valor;
        valor =  parseFloat(data_row.valor).toFixed(2).replace(".", ",");
        BootstrapDialog.confirm({
            message: '<table style="width: 100%;"><tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">REGISTRO:</th><th style="background-color: #dddddd;"><b>' + data_row.registro + '</b></th>' +
                '<tr><th style="text-align: right;padding: 8px;">VALOR:</th><th><b>' + valor + '</th>' +
                '<tr><th style="text-align: right;padding: 8px;background-color: #dddddd;">DATA:</th><th style="background-color: #dddddd;"><b>' + data_row.data + '</th>' +
                '<tr><th style="text-align: right;padding: 8px;">HORA:</th><th><b>' + data_row.hora + '</th>' +
                '<tr><th style="text-align: right;padding: 8px;background-color: #dddddd;"></b>CONVENIO:<b></th><th style="background-color: #dddddd;">' + data_row.razaosocial + '</b><th>',
            title: 'Confirma a exclusão do registro ?',
            type: BootstrapDialog.TYPE_PRIMARY,
            closable: true,
            draggable: true,
            btnCancelLabel: 'Não',
            btnOKLabel: 'Sim',
            btnOKClass: 'btn btn-success',
            btnCancelClass: 'btn btn-warning',
            callback: function (result) {
                if (result) {
                    waitingDialog.show('Excluindo, aguarde ...');
                    $.ajax({
                        url: "pages/conta/conta_exclui.php",
                        method: "POST",
                        dataType: "json",
                        data: {"lancamento": data_row.registro,"mes":data_row.mes,"usuario_codigo":usuario_cod},
                        success: function (data) {
                            if (data.Resultado === "excluido") {
                                table_origem.row( $button.parents('tr') ).remove().draw();
                                //alert("Excluido com sucesso");
                                waitingDialog.hide();
                                BootstrapDialog.show({
                                    closable: false,
                                    title: 'Atenção',
                                    message: 'Excluído com Sucesso!!!',
                                    buttons: [{
                                        cssClass: 'btn-primary',
                                        label: 'Ok',
                                        action: function (dialogItself) {
                                            dialogItself.close();
                                            //$("#C_Senha").focus();
                                        }
                                    }]
                                });
                            }else if (data.Resultado === "mes_bloqueado") {
                                waitingDialog.hide();
                                BootstrapDialog.show({
                                    closable: false,
                                    title: 'Atenção',
                                    message: 'Mês bloqueado não é possivel excluir!!!',
                                    buttons: [{
                                        cssClass: 'btn-warning',
                                        label: 'Ok',
                                        action: function (dialogItself) {
                                            dialogItself.close();
                                            //$("#C_Senha").focus();
                                        }
                                    }]
                                });
                            }
                        }
                    });
                } else {
                    //alert('No');
                }
            }
        });
        //$('#tab_matricula_origem tbody').on( 'click', 'tr', function () {
        //    $(this).toggleClass('selected');
        //} );
    });
    $('#tab_matricula_origem').on('click', 'tbody .btnalterarList', function () {
        var data_row = table_origem.row($(this).closest('tr')).data();
        var valor;
        valor =  parseFloat(data_row.valor).toFixed(2).replace(".", ",");
        //alert( data_row.Registro +"'xxxxx: "+ data_row.Nome_Empregador);
        $("#ModalEdita").modal("show");
        $('#inputRegistroEdita').val(data_row.registro);
        $('#inputNomeEdita').val($('#C_nome_origem').val());
        $('#inputConvenioEdita').val(data_row.razaosocial);
        $('#inputValorEdita').val(valor);
        $(this).find('#inputValorEdita').focus();
    });
    $('#btnSalvarEdicao').click(function (event) {
        event.preventDefault();
        var registro_alterado = $('#inputRegistroEdita').val();
        var valor_alterado = $('#inputValorEdita').val();

        waitingDialog.show('Alterando, aguarde ...',);
        $.ajax({
            url: "pages/conta/conta_edit.php",
            method: "POST",
            dataType: "json",
            data: {"lancamento": registro_alterado, "valor_alterado": valor_alterado},
            success: function (data) {

                if (data.Resultado === "alterado") {

                    //table_origem.row( $button.parents('tr') ).remove().draw();
                    //alert("Excluido com sucesso");
                    waitingDialog.hide();
                    table_origem.ajax.reload();
                    BootstrapDialog.show({
                        closable: false,
                        title: 'Atenção',
                        message: 'Alterado com Sucesso!!!',
                        buttons: [{
                            cssClass: 'btn-warning',
                            label: 'Ok',
                            action: function (dialogItself) {
                                dialogItself.close();
                                $("#ModalEdita").modal("hide");

                            }
                        }]
                    });
                    //alert("Alterado com sucesso.");
                }else{
                    //alert("Não Alterou");
                    waitingDialog.hide();
                }
            }
        });
    });
    $("input[type='radio'][name='radiobtnmes']").change( function() {
        waitingDialog.show('Selecionando, aguarde ...');
        matricula = $('#C_matricula_origem').val();
        var checked = $(this).val();
        checkedmes = $(this).val();
        //var checked = $('input', this).is(':checked');
        viaradio = true;
        if( checked === "todos" ){
            $('#btnImprimir').prop("disabled",true);
            $('#C_mes :nth-child(1)').prop('selected', true).trigger('change');
            if (matricula !== ""){
                matricula = $('#C_matricula_origem').val();
                mes_escolhido =  $('#C_mes').val();
            }
        }else{
            $('#btnImprimir').prop("disabled",false);
            $("#C_mes > option").each(function() {
                var index = this.index + 1;
                if(this.text === mescorrente){
                    $("#C_mes :nth-child(" + index  + ")").prop('selected', true).trigger('change');
                }
            });
            if (matricula !== ""){
                matricula = $('#C_matricula_origem').val();
                mes_escolhido =  $('#C_mes').val();
            }
        }
    });
    $('#btnExcluirVarios').click(function () {
        var dTRows = table_origem.rows({selected:true}).data().toArray();
        var messagetable = '';
        var messagetablejson = '';
        var x=0;
        var valor;
        var regis;
        if (dTRows.length > 1) {
            var obj = {};

            obj.usuario_codigo = [];
            obj.usuario_codigo.push({"usuario_codigo": usuario_cod});
            obj.registro = [];
            messagetable = '<table class="table table-striped table-sm" style="width:100%;">';
            messagetable += '<thead><tr>' +
                '<th scope="col">#</th>' +
                '<th scope="col">Registro</th>' +
                '<th scope="col" style="text-align: right">Valor</th>' +
                '<th scope="col" style="text-align: center">Data</th>' +
                '<th scope="col" style="text-align: center">Parcela</th>' +
                '</tr>' +
                '</thead><tbody>';

            var escolha=0;
            for (var i = 0; i < dTRows.length; i++) {
                x = i + 1;
                if(dTRows[i]['mes_controle'] === "<span class='label label-success'>Aberto</span>") {
                    escolha=escolha+1;
                    valor = parseFloat(dTRows[i]['valor']).toFixed(2).replace(".", ",");
                    regis = dTRows[i]["registro"];
                    obj.registro.push({"registro": regis});
                    messagetable += '<tr><th style="text-align: right;padding: 8px;"><h6>' + x +
                        '</h6></th><th><h6>' + dTRows[i]['registro'] +
                        '</h6></th><th style="text-align: right"><h6>' + valor +
                        '</h6></th><th style="text-align: center"><h6>' + dTRows[i]['data'] +
                        '</h6></th><th style="text-align: center"><h6>' + dTRows[i]['parcela'] +
                        '</h6></th></tr>'
                }
            }
            messagetable += '<tbody/></table>';

            messagetablejson = JSON.stringify(obj);

            if(escolha > 0) {
                BootstrapDialog.confirm({
                    message: messagetable,
                    title: 'Confirma a exclusão dos registros abaixo ?',
                    type: BootstrapDialog.TYPE_PRIMARY,
                    closable: true,
                    draggable: true,
                    btnCancelLabel: 'Não',
                    btnOKLabel: 'Sim',
                    btnOKClass: 'btn btn-success',
                    btnCancelClass: 'btn btn-warning',
                    callback: function (result) {
                        if (result) {
                            waitingDialog.show('Excluindo, aguarde ...');
                            
                            $.ajax({
                                url: "pages/conta/conta_exclui_serie.php",
                                method: "POST",
                                dataType: "json",
                                data: obj,
                                success: function (data) {
                                    if (data.Resultado === "excluido") {
                                        table_origem.rows('.selected').remove().draw();
                                        //alert("Excluido com sucesso");
                                        waitingDialog.hide();
                                        BootstrapDialog.show({
                                            closable: false,
                                            title: 'Atenção',
                                            message: 'Excluído com Sucesso!!!',
                                            buttons: [{
                                                cssClass: 'btn-warning',
                                                label: 'Ok',
                                                action: function (dialogItself) {
                                                    dialogItself.close();
                                                    //$("#C_Senha").focus();
                                                }
                                            }]
                                        });
                                    } else {
                                        alert("Não Excluiu");
                                        waitingDialog.hide();
                                    }
                                }
                            });
                        } else {
                            //alert('No');
                        }
                    }
                });
            }else{
                BootstrapDialog.show({
                    closable: false,
                    title: 'Atenção',
                    message: 'Somente registros em aberto pode ser excluidos !!!',
                    buttons: [{
                        cssClass: 'btn-warning',
                        label: 'Ok',
                        action: function (dialogItself) {
                            dialogItself.close();
                        }
                    }]
                });
            }
        }else{
            BootstrapDialog.show({
                closable: false,
                title: 'Atenção',
                message: 'Selecione mais de um registro para excluir selecionados !!!',
                buttons: [{
                    cssClass: 'btn-warning',
                    label: 'Ok',
                    action: function (dialogItself) {
                        dialogItself.close();
                    }
                }]
            });
        }
    });
    table_lancado = $('#tabela_lancado').DataTable();
    $('#tabela_lancado-wrapper').hide();

    function carrega_cadastro() {
        $.ajax({
            url: "pages/conta/conta_cadastro.php",
            method: "POST",
            dataType: "json",
            data: $('#frmCadConta').serialize()+'&divisao='+divisao,
            success: function (data) {
                if (data.Resultado === "cadastrado") {
                    delete data.Resultado;
                    waitingDialog.show('Cadastrando, aguarde ...');
                    waitingDialog.hide();
                    if ( $.fn.dataTable.isDataTable('#tabela_lancado') ) {
                        table_lancado.destroy();
                        table_lancado = $('#tabela_lancado').DataTable({
                            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                            processing: false,
                            ServerSide: false,
                            responsive: true,
                            autoWidth: true,
                            paging:   false,
                            ordering: false,
                            info:     false,
                            searching: false,
                            scrollY:   "120px",
                            scrollCollapse: true,
                            keys: {
                                blurable: false,
                            },
                            ajax: {
                                url: 'pages/conta/conta_manutencao.php',
                                method: 'POST',
                                data: data,
                                dataType: 'json'
                            },
                            deferRender:true,
                            columns: [
                                {data: "registro"},
                                {data: "nome_convenio"},
                                {
                                    data: "valor",
                                    render: $.fn.dataTable.render.number('.', ',', 2),
                                    className: "text-center"
                                },
                                {data: "data"},
                                {data: "hora"},
                                {data: "mes"},
                                {data: "parcela"},
                                {data: "descricao"}
                            ],
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
                            },
                            pagingType: "full_numbers"
                        });
                    }else {
                        table_lancado = $('#tabela_lancado').DataTable({
                            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                            processing: false,
                            ServerSide: false,
                            responsive: true,
                            autoWidth: true,
                            paging: false,
                            ordering: false,
                            info: false,
                            searching: false,
                            scrollY: "120px",
                            scrollCollapse: true,
                            keys: {
                                blurable: false,
                            },
                            ajax: {
                                url: 'pages/conta/conta_manutencao.php',
                                method: 'POST',
                                data: data,
                                dataType: 'json'
                            },
                            deferRender: true,
                            columns: [
                                {data: "registro"},
                                {data: "nome_convenio"},
                                {
                                    data: "valor",
                                    render: $.fn.dataTable.render.number('.', ',', 2),
                                    className: "text-center"
                                },
                                {data: "data"},
                                {data: "hora"},
                                {data: "mes"},
                                {data: "parcela"},
                                {data: "descricao"}
                            ],
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
                            },
                            pagingType: "full_numbers"
                        });
                    }
                    BootstrapDialog.show({
                        closable: false,
                        title: 'Atenção',
                        message: 'Cadastrado com Sucesso!!!',
                        buttons: [{
                            cssClass: 'btn-warning',
                            label: 'Ok',
                            action: function (dialogItself) {
                                dialogItself.close();
                            }
                        }]
                    });
                }
            },
        });
        $("#frmCadConta")[0].reset();
        var d = new Date();
        dataHora = (d.toLocaleString());
        $('#inputDataCad').val(dataHora.substring(0,10));
        $('#inputDataCad').mask("00/00/0000");
        table_lancado.columns.adjust().draw();
        $("#btnCadastrarCadastroConta").prop("disabled",false);
        $("#inputMatricula").prop("disabled",true);
        $("#inputValor").prop("disabled",true);
        $("#inputDataCad").prop("disabled",true);
        $("#inputParcela").prop("disabled",true);
        $("#select_mes").prop("disabled",true);
        $("#obsCad").prop("disabled",true);
        $("#optTodas").prop("disabled",true);
        $("#optUnica").prop("disabled",true);
        $("#btnBuscaAssociado").prop("disabled",true);
        $("#btnBuscaConvenio").prop("disabled",true);
        $("#divAssociado").html('');
        $("#divEmpregador").html('');
        $("#divNomeConvenio").html('');
    }
    $(document).on('keyup.modal', function (event) {
        if (event.which === KEYBOARD.esc) {
            // handle action
        }
    });
    $("#C_mes").change(function () {

        matricula = $('#C_matricula_origem').val();
        mes_escolhido =  $('#C_mes').val();
        if (matricula !== ""){
            if(mes_escolhido === 'todos'){
                $("#input_check_todos").prop("checked", true);
            }else{
                $("#input_check_atual").prop("checked", true);
            }
            carrega_origem();
        }
    });
    $("#btnCadastrarCadastroConta").click(function () {
        $("#frmCadConta")[0].reset();
        var d = new Date();
        dataHora = (d.toLocaleString());
        $('#inputDataCad').val(dataHora.substring(0,10));
        $("#inputMatricula").prop("disabled",true);
        $("#inputValor").prop("disabled",false);
        $("#inputDataCad").prop("disabled",false);
        $("#inputParcela").prop("disabled",false);
        $("#select_mes").prop("disabled",false);
        $("#obsCad").prop("disabled",false);
        $("#optTodas").prop("disabled",false);
        $("#optUnica").prop("disabled",false);
        $("#btnBuscaAssociado").prop("disabled",false);
        $("#btnBuscaConvenio").prop("disabled",false);
        table_lancado.clear().draw();
        $('#btnBuscaAssociado').focus();
        $("#btnSalvarCadastroConta").prop("disabled",false);
        $(this).prop("disabled",true);
    });
    $("#btnCadastrar").click(function () {
        $("#frmCadConta")[0].reset();
        var d = new Date();
        dataHora = (d.toLocaleString());
        $('#inputDataCad').val(dataHora.substring(0,10));
        //$('#inputDataCad').mask("00/00/0000");
        $('#divNomeConvenio').html("");
        $('#divAssociado').html("");
        $('#divEmpregador').html("");
        $("#ModalCadastra").modal("show");
        $('#btnBuscaAssociado').focus();
        table_lancado.clear().draw();
    });
    $("#btnFechar").click(function(){
        $("#btnSalvar").prop("disabled",false);
    });
    $("#btnBuscaAssociado").click(function () {
        botao_clicado = "busca_cad";
        $("#ModalBuscaAssociado").modal("show");
        /*55555555*/
        if ( $.fn.dataTable.isDataTable( '#tabela_busca_associado' ) ) {
            tableconsulta = $('#tabela_busca_associado').DataTable();
        }
        else {
            tableconsulta = $('#tabela_busca_associado').DataTable({
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                keys: {
                    blurable: true,
                },
                processing: false,
                ajax: {
                    url: 'pages/conta/exibe_todos_associados.php',
                    method: 'POST',
                    data: {"divisao": divisao, 'card1': card1, 'card2': card2, 'card3': card3, 'card4': card4, 'card5': card5, 'card6': card6},
                    dataType: 'json'
                },
                deferRender:true,
                order: [[0, "asc"]],
                columns: [
                    {data: "matricula"},
                    {data: "nome"},
                    {data: "endereco"},
                    {data: "numero"},
                    {data: "bairro"},
                    {data: "nascimento"},
                    {data: "empregador"},
                    {data: "abreviacao"}
                ],
                columnDefs: [
                    { width: '30%', targets: 1 }
                ],
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
                },
                fixedColumns: true,
                pagingType: "full_numbers",
            });
            /* $('#ModalBuscaAssociado tbody').on('click', 'tr', function () {
                 if ($(this).hasClass('selected')) {
                     $(this).removeClass('selected');
                 } else {
                     tableconsulta.$('tr.selected').removeClass('selected');
                     $(this).addClass('selected');
                 }
             });*/
        }
    });
    $("#btnConsultar").click(function () {
        botao_clicado = "busca_conta";
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
                    url: 'pages/conta/exibe_todos_associados.php',
                    method: 'POST',
                    data: {"divisao": divisao, 'card1': card1, 'card2': card2, 'card3': card3, 'card4': card4, 'card5': card5, 'card6': card6},
                    dataType: 'json'
                },
                deferRender: true,
                order: [[1, "asc"]],
                columns: [
                    {data: "matricula"},
                    {data: "nome"},
                    {data: "endereco"},
                    {data: "numero"},
                    {data: "bairro"},
                    {data: "nascimento"},
                    {data: "empregador"},
                    {data: "abreviacao"}
                ],
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
        tableconsulta.on( 'key', function ( e, datatable, key, cell, originalEvent ) {
            //events.prepend( '<div>Key press: '+key+' for cell <i>'+cell.data()+'</i></div>' );
            if(key === 13){
                //alert("funcionou.");
                //$('#tabela_busca_associado').on( 'dblclick', 'tr', function () {});
            }
        });
    });
    $('#tabela_busca_associado').on( 'click', 'tr', function () {
        // CAPTURA O VALOR DA LINHA SELECIONADA EM DUPLOCLICK

        var data = tableconsulta.row( this ).data();
        nome       = data["nome"];
        abreviacao = data["abreviacao"];
        matricula  = data["matricula"];
        Codempregador_origem = data["codempregador"];
        if(botao_clicado === "busca_conta") {
            $("#C_matricula_origem").val(matricula);
            $("#C_nome_origem").val(nome);
            $("#C_empregador_origem").val(abreviacao);
            $("#C_id_empregador_origem").val(Codempregador_origem);
            mes_escolhido = $('#C_mes').val();
            $("#btnImprimir").prop("disabled",false);
            carrega_origem();
        }else if(botao_clicado === "busca_cad"){
            $("#inputMatricula").val(matricula);
            $("#divAssociado").html(nome);
            $("#divEmpregador").html(abreviacao);
            $('#btnBuscaConvenio').focus();
        }
        $("#ModalBuscaAssociado").modal("hide");
        //$("#input_check_atual").prop("checked", true);
        /*$("#C_mes > option").each(function() {
            var index = this.index + 1;
            if(this.text === mescorrente){
                $("#C_mes :nth-child(" + index  + ")").prop('selected', true).trigger('change');
            }
        });*/
    });
    $("#btnBuscaConvenio").click(function () {
        $("#ModalBuscaConvenio").modal("show");
        if ( $.fn.dataTable.isDataTable( '#tabela_busca_convenio' ) ) {
            tableconsultaconv = $('#tabela_busca_convenio').DataTable();
        }
        else {
            tableconsultaconv = $('#tabela_busca_convenio').DataTable({
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                processing: false,
                ServerSide: false,
                responsive: true,
                autoWidth: true,
                JQueryUI: true,
                searching: true,
                ajax: {
                    url: 'pages/convenio/convenio_read2.php',
                    method: 'POST',
                    data: '',
                    dataType: 'json'
                },
                deferRender: true,
                order: [[2, "asc"]],
                columns: [
                    {
                        class: "details-control",
                        orderable: false,
                        data: null,
                        defaultContent: ""
                    },
                    { data: "codigo" },
                    { data: "razaosocial" },
                    { data: "nomefantasia" },
                    { data: "endereco" },
                    { data: "telefone" },
                ],
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
                },
                pagingType: "full_numbers"
            });
            $('#ModalBuscaConvenio tbody').on('click', 'tr', function () {
                if ($(this).hasClass('selected')) {
                    $(this).removeClass('selected');
                } else {
                    tableconsultaconv.$('tr.selected').removeClass('selected');
                    $(this).addClass('selected');
                }
            });
        }
    });
    $('#tabela_busca_convenio').on( 'click', 'tr', function () {
        // CAPTURA O VALOR DA LINHA SELECIONADA EM DUPLOCLICK
        var data = tableconsultaconv.row( this ).data();
        cod_convenio = data["codigo"];
        nome_convenio = data["razaosocial"];
        $('#cod_convenio').val(cod_convenio);
        $('#nome_convenio').val(nome_convenio);
        $('#inputCodConvenio').val(cod_convenio);
        $("#divNomeConvenio").html(nome_convenio);
        $("#ModalBuscaConvenio").modal("hide");

        $('#inputValor').focus();

    });
    $('#btnSalvarCadastroConta').click(function (event) {
        event.preventDefault();

        $('#frmCadConta').validator('validate');
        var campo_vazio = validar();
        if (campo_vazio === "validou") {
            $('#id_empregador').val($('#divEmpregador').html());
            $('#inputMatricula_aux').val($('#inputMatricula').val());
            var radioValue = $("input[name='optParcela']:checked").val();
            if (radioValue === "unica") {
                $('#tipo_parcela').val("unica");
            } else {
                $('#tipo_parcela').val("todas");
            }
            $(this).prop("disabled",true);
            carrega_cadastro();
            //table_origem.ajax.reload();
        }else {
            var nome_campo;
            switch (campo_vazio) {
                case 'inputMatricula':
                    nome_campo = "matricula";
                    break;
                case 'inputCodConvenio':
                    nome_campo = "convenio";
                    break;
                case 'inputValor':
                    nome_campo = "valor";
                    break;
            }
            BootstrapDialog.show({
                closable: false,
                title: 'Atenção',
                message: 'O campo ' + nome_campo + ' é obrigatório !!!',
                buttons: [{
                    cssClass: 'btn-warning',
                    label: 'Ok',
                    action: function (dialogItself) {
                        dialogItself.close();
                        if(nome_campo === "convenio"){
                            $("#btnBuscaConvenio").focus();
                        }
                        $("#" + campo_vazio).focus();
                        $("#btnSalvar").prop("disabled",false);
                    }
                }]
            });
        }
    });
    function carrega_origem() {
        $.ajax({
            url:"pages/conta/conta_list_mes.php",
            method: "POST",
            async: false,
            data: {'matricula': matricula, 'mes': mes_escolhido, 'codempregador': Codempregador_origem },
            dataType: "json",
            success:function (datab) {
                var fdesc = 0;
                var cdesc = 0;
                var edesc = 0;
                var ddesc = 0;
                var totaldesc = 0;
                if(checkedmes !== 'todos') {
                    var length = 0;
                    var farmacia = parseFloat(datab["categorias"].Farmacia);
                    var compras = parseFloat(datab["categorias"].Compras);
                    var financeira = parseFloat(datab["categorias"].Financeira);
                    var unimed = parseFloat(datab["categorias"].Unimed);
                    var total = farmacia + compras + financeira + unimed;
                    if (farmacia === 0) {
                        farmacia = '';
                    }
                    if (compras === 0) {
                        compras = '';
                    }
                    if (financeira === 0) {
                        financeira = '';
                    }
                    if (unimed === 0) {
                        unimed = '';
                    }
                    if (total === 0) {
                        total = '';
                    }
                    $("#fargasto").html(farmacia.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                    $("#comgasto").html(compras.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                    $("#fingasto").html(financeira.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                    $("#unigasto").html(unimed.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                    $("#totalgasto").html(total.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                    if (datab["naodescontado"] !== undefined) {
                        var fnd = parseFloat(datab["naodescontado"].FND);
                        var cnd = parseFloat(datab["naodescontado"].CND);
                        var endes = parseFloat(datab["naodescontado"].ENDES);
                        var dnd = parseFloat(datab["naodescontado"].DND);
                        var totalndesc = fnd + cnd + endes + dnd;
                        if (fnd === 0) {
                            fnd = '';
                        }
                        if (cnd === 0) {
                            cnd = '';
                        }
                        if (endes === 0) {
                            endes = '';
                        }
                        if (dnd === 0) {
                            dnd = '';
                        }
                        if (totalndesc === 0) {
                            totalndesc = '';
                        }
                        $("#farndesc").html(fnd.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                        $("#comndesc").html(cnd.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                        $("#finndesc").html(endes.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                        $("#unindesc").html(dnd.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                        $("#totalndesc").html(totalndesc.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                        fdesc = farmacia - fnd;
                        cdesc = compras - cnd;
                        edesc = financeira - endes;
                        ddesc = unimed - dnd;
                        totaldesc = fdesc + cdesc + edesc + ddesc;
                        var limite = datab["limite"].limite * 1;
                        var saldo = datab["limite"].limite - total;
                        if (fdesc === 0) {
                            fdesc = '';
                        }
                        if (cdesc === 0) {
                            cdesc = '';
                        }
                        if (edesc === 0) {
                            edesc = '';
                        }
                        if (ddesc === 0) {
                            ddesc = '';
                        }
                        if (totaldesc === 0) {
                            totaldesc = '';
                        }
                    }
                }else{
                    $("#fargasto").html('');
                    $("#comgasto").html('');
                    $("#fingasto").html('');
                    $("#unigasto").html('');
                    $("#totalgasto").html('');
                    fnd = '';
                    cnd = '';
                    financeira = '';
                    unimed = '';
                    totalndesc = '';
                    $("#farndesc").html('');
                    $("#comndesc").html('');
                    $("#finndesc").html('');
                    $("#unindesc").html('');
                    $("#totalndesc").html('');
                    fdesc = '';
                    cdesc = '';
                    edesc = '';
                    ddesc = '';
                    totaldesc = '';
                    $("#fardesc").html('');
                    $("#comdesc").html('');
                    $("#findesc").html('');
                    $("#unidesc").html('');
                    $("#totaldesc").html('');
                }
                if(isNaN(limite)){
                    limite = '';
                }else{
                    limite = parseFloat(limite).toFixed(2).replace(".", ",");
                }
                if(isNaN(saldo )){
                    saldo = '';
                }else{
                    saldo = saldo.toString();
                    saldo = parseFloat(saldo).toFixed(2).replace(".", ",");
                }
                if (datab["naodescontado"] !== undefined) {
                    $("#fardesc").html(fdesc.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                    $("#comdesc").html(cdesc.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                    $("#findesc").html(edesc.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                    $("#unidesc").html(ddesc.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                    $("#totaldesc").html(totaldesc.toLocaleString("pt-BR", {style: "decimal", currency: "BRL"}));
                }
                $("#limite").val(limite);
                $("#C_limite").val(limite.toLocaleString("pt-BR", { style: "decimal" , currency:"BRL"}));
                $("#C_saldo").val(saldo.toLocaleString("pt-BR", { style: "decimal" , currency:"BRL"}));
                Object.keys(datab).forEach(function(key) {
                    length++;
                });
                if (length > 0){
                    if ( $.fn.dataTable.isDataTable( '#tab_matricula_origem' ) ) {
                        table_origem.destroy();
                        table_origem = $('#tab_matricula_origem').DataTable({
                            "createdRow": function ( row, data, index ) {
                                if ( data['mes_controle'] !== "<span class='label label-success'>Aberto</span>" ) {
                                    $( row ).find('td:eq(0)').html('');
                                    $('td:nth-child(11)', row).html('');//botao alterar
                                    $('td:nth-child(12)', row).html('');//botao excluir
                                    $('td:nth-child(0)', row).html('');//selecionar
                                }
                            },
                            columnDefs: [
                                { type: 'time-uni', targets: 5 },
                                { "targets": [ 2 ], "visible": false, "searchable": false },
                                { 'targets': [ 0 ],
                                    "width": "20px",
                                    "searchable": false,
                                    "orderable": false,
                                    checkboxes: {
                                        selectRow: true
                                    }
                                },
                                /*{ "targets": [ 13 ], "visible": false, "searchable": false }*/
                            ],
                            fixedColumns: {
                                'leftColumns': 1
                            },
                            order: [[1, 'asc']],
                            select: {
                                style: 'multi'
                            },
                            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                            processing: false,
                            ServerSide: false,
                            responsive: true,
                            autoWidth: true,
                            ajax: {
                                url: 'pages/conta/conta_list_mes.php',
                                method: 'POST',
                                data: {'matricula': matricula, 'mes': mes_escolhido, 'codempregador': Codempregador_origem },
                                dataType: 'json'
                            },
                            deferRender:true,
                            columns: [
                                { data: "excluir" },
                                { data: "registro" },
                                { data: "matricula" },
                                {
                                    data: "valor",
                                    render: $.fn.dataTable.render.number( '.', ',', 2 ),
                                    className: "text-center"
                                },
                                { data: "data" },
                                { data: "hora" },
                                { data: "parcela" },
                                { data: "mes" },
                                { data: "razaosocial" },
                                { data: "nomefantasia" },
                                { data: "funcionario" },
                                {
                                    data: "botaoalterar",
                                    orderable:false
                                },
                                {
                                    data: "botaoexcluir",
                                    orderable:false
                                },
                                { data: "mes_controle" }
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
                        });
                    }else {
                        table_origem = $('#tab_matricula_origem').DataTable({
                            "createdRow": function ( row, data, index ) {
                                if ( data['mes_controle'] !== "<span class='label label-success'>Aberto</span>" ) {
                                    $( row ).find('td:eq(0)').html('');
                                    $('td:nth-child(11)', row).html('');//botao alterar
                                    $('td:nth-child(12)', row).html('');//botao excluir
                                    $('td:nth-child(0)', row).html('');//selecionar
                                }
                            },
                            columnDefs: [
                                { type: 'time-uni', targets: 5 },
                                { "targets": [ 2 ], "visible": false, "searchable": false },
                                { 'targets': [ 0 ],
                                    "width": "20px",
                                    "searchable": false,
                                    "orderable": false,
                                    checkboxes: {
                                        selectRow: true
                                    }
                                },
                                /*{ "targets": [ 13 ], "visible": false, "searchable": false }*/
                            ],
                            fixedColumns: {
                                'leftColumns': 1
                            },
                            order: [[1, 'asc']],
                            select: {
                                style: 'multi'
                            },
                            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                            processing: false,
                            ServerSide: false,
                            responsive: true,
                            autoWidth: true,
                            ajax: {
                                url: 'pages/conta/conta_list_mes.php',
                                method: 'POST',
                                data: {'matricula': matricula, 'mes': mes_escolhido, 'codempregador': Codempregador_origem },
                                dataType: 'json'
                            },
                            deferRender:true,
                            columns: [
                                { data: "excluir" },
                                { data: "registro" },
                                { data: "matricula" },
                                {
                                    data: "valor",
                                    render: $.fn.dataTable.render.number( '.', ',', 2 ),
                                    className: "text-center"
                                },
                                { data: "data" },
                                { data: "hora" },
                                { data: "parcela" },
                                { data: "mes" },
                                { data: "razaosocial" },
                                { data: "nomefantasia" },
                                { data: "funcionario" },
                                {
                                    data: "botaoalterar",
                                    orderable:false
                                },
                                {
                                    data: "botaoexcluir",
                                    orderable:false
                                },
                                { data: "mes_controle" }
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
                        });
                    }
                }else{
                    if ( $.fn.dataTable.isDataTable( '#tab_matricula_origem' ) ) {
                        table_origem.destroy();
                        table_origem = $('#tab_matricula_origem').DataTable({
                            "createdRow": function ( row, data, index ) {
                                if ( data['mes_controle'] !== "<span class='label label-success'>Aberto</span>" ) {
                                    $( row ).find('td:eq(0)').html('');
                                    $('td:nth-child(11)', row).html('');//botao alterar
                                    $('td:nth-child(12)', row).html('');//botao excluir
                                    $('td:nth-child(0)', row).html('');//selecionar
                                }
                            },
                            columnDefs: [
                                { type: 'time-uni', targets: 5 },
                                { "targets": [ 2 ], "visible": false, "searchable": false },
                                { 'targets': [ 0 ],
                                    "width": "20px",
                                    "searchable": false,
                                    "orderable": false,
                                    checkboxes: {
                                        selectRow: true
                                    }
                                },
                                /*{ "targets": [ 13 ], "visible": false, "searchable": false }*/
                            ],
                            fixedColumns: {
                                'leftColumns': 1
                            },
                            order: [[1, 'asc']],
                            select: {
                                style: 'multi'
                            },
                            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                            processing: false,
                            ServerSide: false,
                            responsive: true,
                            autoWidth: true,
                            ajax: {
                                url: 'pages/conta/conta_list_mes.php',
                                method: 'POST',
                                data: {'matricula': matricula, 'mes': mes_escolhido, 'codempregador': Codempregador_origem },
                                dataType: 'json'
                            },
                            deferRender:true,
                            columns: [
                                { data: "excluir" },
                                { data: "registro" },
                                { data: "matricula" },
                                {
                                    data: "valor",
                                    render: $.fn.dataTable.render.number( '.', ',', 2 ),
                                    className: "text-center"
                                },
                                { data: "data" },
                                { data: "hora" },
                                { data: "parcela" },
                                { data: "mes" },
                                { data: "razaosocial" },
                                { data: "nomefantasia" },
                                { data: "funcionario" },
                                {
                                    data: "botaoalterar",
                                    orderable:false
                                },
                                { data: "botaoexcluir" },
                                { data: "mes_controle" }
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
                        });
                    }else {
                        table_origem = $('#tab_matricula_origem').DataTable({
                            "createdRow": function ( row, data, index ) {
                                if ( data['mes_controle'] !== "<span class='label label-success'>Aberto</span>" ) {
                                    $( row ).find('td:eq(0)').html('');
                                    $('td:nth-child(11)', row).html('');//botao alterar
                                    $('td:nth-child(12)', row).html('');//botao excluir
                                    $('td:nth-child(0)', row).html('');//selecionar
                                }
                            },
                            columnDefs: [
                                { targets: 5, type: 'time-uni'  },
                                { "targets": [ 2 ], "visible": false, "searchable": false },
                                { 'targets': [ 0 ],
                                    "width": "20px",
                                    "searchable": false,
                                    "orderable": false,
                                    checkboxes: {
                                        selectRow: true
                                    }
                                },
                                /*{ "targets": [ 13 ], "visible": false, "searchable": false }*/
                            ],
                            fixedColumns: {
                                'leftColumns': 1
                            },
                            order: [[1, 'asc']],
                            select: {
                                style: 'multi'
                            },
                            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                            processing: false,
                            ServerSide: false,
                            responsive: true,
                            autoWidth: true,
                            ajax: {
                                url: 'pages/conta/conta_list_mes.php',
                                method: 'POST',
                                data:{'matricula': matricula, 'mes': mes_escolhido, 'codempregador': Codempregador_origem },
                                dataType: 'json'
                            },
                            deferRender:true,
                            columns: [
                                { data: "excluir" },
                                { data: "registro" },
                                { data: "matricula" },
                                {
                                    data: "Valor",
                                    render: $.fn.dataTable.render.number( '.', ',', 2 ),
                                    className: "text-center"
                                },
                                { data: "data" },
                                { data: "hora" },
                                { data: "parcela" },
                                { data: "mes" },
                                { data: "razaosocial" },
                                { data: "nomefantasia" },
                                { data: "funcionario" },
                                {
                                    data: "botaoalterar",
                                    orderable:false
                                },
                                { data: "botaoexcluir" },
                                { data: "mes_controle" }
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
                        });
                    }
                }
            }
        });
        waitingDialog.hide();
    }
    function format ( d ) {
        return '<b>Parcela : </b><i>'+d.Parcela+'</i><br>'+
            '<b>Hora  : </b><i>'+d.Hora+'</i><br>';

    }
    function validar(){
        var matricula   = $('#inputMatricula').val();
        var codconvenio = $('#inputCodConvenio').val();
        var valor       = $('#inputValor').val();
        if (matricula === "") {
            return $('#inputMatricula').attr('name');
        }else if (codconvenio === "") {
            return $('#inputCodConvenio').attr('name');
        }else if (valor === "") {
            return $('#inputValor').attr('name');
        }else{
            return "validou";
        }
    }
    $('#btnImprimir').click(function () {

        var matricula      = $('#C_matricula_origem').val();
        var mes            = $('#C_mes').val();
        var cod_empregador = $('#C_id_empregador_origem').val();
        var empregador     = $('#C_empregador_origem').val();
        var farmacia       = $('#fargasto').html();
        var compras        = $('#comgasto').html();
        var emprestimo     = $('#fingasto').html();
        var unimed         = $('#unigasto').html();
        var fnd            = $('#farndesc').html();
        var cnd            = $('#comndesc').html();
        var endes          = $('#finndesc').html();
        var dnd            = $('#unindesc').html();
        var limite         = $('#limite').val();
        if( table_origem.data().count() > 0 ) {
            $.redirect('../Adm/pages/conta/conta_imprimir_pdf.php',
                {
                    matricula: matricula,
                    mes: mes,
                    cod_empregador: cod_empregador,
                    empregador: empregador,

                    farmacia: farmacia,
                    compras: compras,
                    emprestimo: emprestimo,
                    unimed: unimed,

                    fnd: fnd,
                    cnd: cnd,
                    endes: endes,
                    dnd: dnd,
                    limite: limite
                }, "POST", "_blank");
        }else{
            BootstrapDialog.show({
                closable: false,
                title: 'Atenção',
                message: 'Não há dados para impressão!!!',
                buttons: [{
                    cssClass: 'btn-warning',
                    label: 'Ok',
                    action: function (dialogItself) {
                        dialogItself.close();
                        //$("#C_Senha").focus();
                    }
                }]
            });
        }
    });
});