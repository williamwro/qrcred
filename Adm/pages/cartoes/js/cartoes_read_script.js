var tableconsulta;
var table_origem;
var nome = "";
var abreviacao = "";
var matricula = "";
var situacao = "";
var divisao = "";
var cartao = "";
var obs = "";
var usuario_global='';
var usuario_cod='';
var Codempregador_origem;
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

});
$("#btnConsultar").click(function () {
    $("#ModalBuscaAssociado").modal("show");

    if ( $.fn.dataTable.isDataTable( '#tabela_busca_associado' ) ) {
        tableconsulta = $('#tabela_busca_associado').DataTable();
    }
    else {
        tableconsulta = $('#tabela_busca_associado').DataTable({
            columnDefs: [
                { "targets": [ 2 ], "visible": false, "searchable": false }
            ],
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            keys: {
                blurable: true
            },
            ajax: {
                url: 'pages/cartoes/cartoes_todos.php',
                method: 'POST',
                data: {"divisao": divisao, 'card1': card1, 'card2': card2, 'card3': card3, 'card4': card4, 'card5': card5, 'card6': card6},
                dataType: 'json'
            },
            deferRender: true,
            order: [[0, "asc"]],
            columns: [
                {data: "matricula"},
                {data: "nome"},
                {data: "id_empregador"},
                {data: "empregador"},
                {data: "cod_verificacao"},
                {data: "descri_situacao"}
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
            fixedColumns: true,
            pagingType: "full_numbers",
        });
    }
});
$('#tabela_busca_associado').on( 'dblclick', 'tr', function () {
    // CAPTURA O VALOR DA LINHA SELECIONADA EM DUPLOCLICK

    var data = tableconsulta.row( this ).data();
    nome       = data["nome"];
    abreviacao = data["empregador"];
    matricula  = data["matricula"];
    cartao     = data["cod_verificacao"];
    situacao   = data["descri_situacao"];
    Codempregador_origem = data["id_empregador"];
    $("#C_matricula_origem").val(matricula);
    $("#C_nome_origem").val(nome);
    $("#C_empregador_origem").val(abreviacao);
    $("#C_id_empregador_origem").val(Codempregador_origem);
    $("#C_cartao").val(cartao);
    $("#C_situacao").val(situacao);
    $("#ModalBuscaAssociado").modal("hide");
    carrega_origem();
});
function carrega_origem() {

    if ( $.fn.dataTable.isDataTable( '#tabela_origem' ) ) {
        table_origem.destroy();
        table_origem = $('#tabela_origem').DataTable({
            columnDefs: [
                {
                    targets: 0, render: function (data) {
                        return data;
                    }
                },
                {
                    targets: 1, render: function (data) {
                        return data;
                    }
                },
            ],
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            ServerSide: false,
            responsive: true,
            autoWidth: true,
            paging: false,
            ordering: false,
            info: false,
            ajax: {
                url: 'pages/cartoes/list_mudanca_cartao.php',
                method: 'POST',
                data: {'cartao': cartao, 'codempregador': Codempregador_origem},
                dataType: 'json'
            },
            columns: [
                {data: "data"},
                {data: "hora"},
                {data: "descri_situacao"},
                {data: "operador"},
                {data: "obs"}
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
        table_origem = $('#tabela_origem').DataTable({
            columnDefs: [
                {
                    targets: 0, render: function (data) {
                        return data;
                    }
                },
                {
                    targets: 1, render: function (data) {
                        return data;
                    }
                },
            ],
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            ServerSide: false,
            responsive: true,
            autoWidth: true,
            paging: false,
            ordering: false,
            info: false,
            ajax: {
                url: 'pages/cartoes/list_mudanca_cartao.php',
                method: 'POST',
                data: {'cartao': cartao, 'codempregador': Codempregador_origem},
                dataType: 'json'
            },
            columns: [
                {data: "data"},
                {data: "hora"},
                {data: "descri_situacao"},
                {data: "operador"},
                {data: "obs"}
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
    table_origem.ajax.reload();

    waitingDialog.hide();
}
function atualiar_cartao(opcao){
    if($("#C_cartao").val() !== "") {
        waitingDialog.show('Atualizando, aguarde ...',);
        obs = $("#obs_cartao").val();
        $.ajax({
            url: "pages/cartoes/atualiza_cartao.php",
            method: "POST",
            async: false,
            data: {'cartao': cartao, 'opcao': opcao, 'obs': obs, 'matricula': matricula, 'usuario': usuario_global, 'empregador':Codempregador_origem},
            dataType: "json",
            success: function (data) {
                
                var msgresult = '';
                if (data.Resultado === '1') {
                    msgresult = 'O cartão ( ' + data.cod_verificacao + ' ) foi liberado com sucesso!';
                    $("#C_situacao").val('LIBERADO');
                } else if (data.Resultado === '2') {
                    msgresult = 'O cartão ( ' + data.cod_verificacao + ' ) foi bloqueado com sucesso!';
                    $("#C_situacao").val('BLOQUEADO');
                } else if (data.Resultado === '3') {
                    msgresult = 'O cartão ( ' + data.cod_verificacao + ' ) foi cancelado com sucesso!';
                    $("#C_situacao").val('CANCELADO');
                } else if (data.Resultado === '8') {
                    msgresult = 'O cartão ( ' + data.cod_verificacao + ' ) foi bloqueado com mensagem com sucesso!';
                    $("#C_situacao").val('BLOQUEIO COM MSG');
                } else {
                    msgresult = 'O cartão ( ' + data.cod_verificacao + ' ) não foi atualizado!';
                    Swal.fire({
                        title: "Ops!",
                        text: msgresult,
                        icon: "success",
                        timer: 3000
                    });
                }
                $("#obs_cartao").val('');
                table_origem.ajax.reload();
                waitingDialog.hide();
                Swal.fire({
                    title: "Parabens!",
                    text: msgresult,
                    icon: "success",
                    timer: 3000
                });
            }
        });
    }else{
        BootstrapDialog.show({
            closable: false,
            title: 'Atenção',
            message: 'Consulte o cartão primeiro.',
            buttons: [{
                cssClass: 'btn-warning',
                label: 'Ok',
                action: function (dialogItself) {
                    dialogItself.close();
                }
            }]
        });
    }
}

