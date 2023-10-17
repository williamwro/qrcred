var table_origem;
var tableconsulta;
var matricula;
var empregador;
var nome = "";
var abreviacao = "";
var botao_clicado = "";
var resultjson;
var Codempregador_origem;
var Codempregador_destino;
var matricula_destino;
var abreviacao_destino;
$(document).ready(function(){
    $('#btnTransferir').attr("disabled", true);
    divisao = sessionStorage.getItem("divisao");
    //usuario_global = sessionStorage.getItem("usuario_global");
});
$("#btnConsultar").click(function () {
    botao_clicado = "origem";
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
            JQueryUI: true,
            searching: true,
            ajax: {
                url: 'pages/conta/exibe_todos_associados.php',
                method: 'POST',
                data: {"divisao": divisao},
                dataType: 'json'
            },
            deferRender: true,
            order: [[1, "asc"]],
            columns: [
                {data: "Matricula"},
                {data: "Nome"},
                {data: "Endereco"},
                {data: "Numero"},
                {data: "Bairro"},
                {data: "Nascimento"},
                {data: "Empregador"},
                {data: "Abreviacao"}
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
$("#btnFechar").click(function () {
    $("#ModalBuscaAssociado").modal("hide");
});
$('#tabela_busca_associado').on( 'dblclick', 'tr', function () {
    // CAPTURA O VALOR DA LINHA SELECIONADA EM DUPLOCLICK
    var data = tableconsulta.row( this ).data();

    nome = data["Nome"];
    abreviacao = data["Abreviacao"];




    if(botao_clicado === "origem") {
        matricula = data["Matricula"];
        Codempregador_origem = data["CodEmpregador"];
        matricula_destino =
        $("#C_matricula_origem").val(matricula);
        $("#C_nome_origem").val(nome);
        $("#C_empregador_origem").val(abreviacao);
        $("#C_id_empregador_origem").val(Codempregador_origem);
        carrega_origem();
    }else if(botao_clicado === "destino"){
        matricula = data["Matricula"];
        Codempregador_destino = data["CodEmpregador"];
        matricula_destino =  matricula;
        abreviacao_destino = abreviacao;
        $("#C_matricula_destino").val(matricula);
        $("#C_nome_destino").val(nome);
        $("#C_empregador_destino").val(abreviacao);
        $("#C_id_empregador_destino").val(Codempregador_destino);
    }
    if($("#C_matricula_origem").val() === '' ||  $("#C_matricula_destino").val() === '') {
        $('#btnTransferir').attr("disabled", true);
    }else{
        $('#btnTransferir').attr("disabled", false);
    }
    $("#ModalBuscaAssociado").modal("hide");
});

$("#btnConsultarDestino").click(function () {
    botao_clicado = 'destino';
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
            JQueryUI: true,
            searching: true,
            ajax: {
                url: 'pages/conta/exibe_todos_associados.php',
                method: 'POST',
                data: {"divisao": divisao},
                dataType: 'json'
            },
            deferRender: true,
            order: [[1, "asc"]],
            columns: [
                {data: "Matricula"},
                {data: "Nome"},
                {data: "Endereco"},
                {data: "Numero"},
                {data: "Bairro"},
                {data: "Nascimento"},
                {data: "Empregador"},
                {data: "Abreviacao"}
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

$('#btnTransferir').click(function () {
    $(this).prop("disabled", true);
    $(this).html(
        "<span class='spinner-border spinner-border-sm' role='status' aria-hidden='true'></span>'Transferindo...'"
    );
    $("#btnConsultar").prop("disabled", true);
    $("#btnConsultarDestino").prop("disabled", true);
    var heads = [];
    $("#tab_matricula_origem thead").find("th").each(function () {
        heads.push($(this).text().trim());
    });
    var rows = [];
    $("table#tab_matricula_origem tbody tr").each(function () {
        cur = {};

        $(this).find("td").each(function(i, v) {
            cur[heads[i]] = $(this).text().trim();
        });
        cur["MATRICULA_ORIGEM"] = $('#C_matricula_origem').val();
        cur["EMPREGADOR_ORIGEM"] = $('#C_id_empregador_origem').val();
        cur["MATRICULA_DESTINO"] = $('#C_matricula_destino').val();
        cur["EMPREGADOR_DESTINO"] = $('#C_id_empregador_destino').val();

        rows.push(cur);
        cur = {};
    });
    resultjson = JSON.stringify(rows);


    /*var map = new Map();
    table_origem.rows().eq(0).each( function ( index ) {
        var row = table_origem.row( index );
        map.set(row.data().Registro,$('#C_matricula_destino').val()+"&"+$('#C_empregador_destino').val());
    } );
    var resultado = resultjson;
    */
    $.ajax({
        url:'pages/conta/conta_transfere.php',
        method: "POST",
        data: {data : resultjson} ,
        dataType: "json",
        success:function (data) {
            debugger
            matricula = $("#C_matricula_origem").val();
            abreviacao = $("#C_empregador_origem").val();
            carrega_origem();
            carrega_destino();
        }
    });

});
function carrega_origem() {
    $.ajax({
        url:"pages/conta/transferencia.php",
        method: "POST",
        async: false,
        data: {matricula : matricula, empregador: abreviacao},
        dataType: "json",
        success:function (datab) {
            var length=0;

            Object.keys(datab).forEach(function(key) {
                length++;
            });
            if (length > 0){
                if ( $.fn.dataTable.isDataTable( '#tab_matricula_origem' ) ) {
                    table_origem.destroy();
                    table_origem = $('#tab_matricula_origem').DataTable({
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                        processing: false,
                        ServerSide: false,
                        responsive: true,
                        autoWidth: true,
                        JQueryUI: true,
                        searching: true,
                        ajax: {
                            url: 'pages/conta/transferencia.php',
                            method: 'POST',
                            data: {'matricula': matricula, 'empregador': abreviacao},
                            dataType: 'json'
                        },
                        columns: [
                            { data: "Registro" },
                            {
                                data: "Valor",
                                render: $.fn.dataTable.render.number( '.', ',', 2 ),
                                className: "text-right"
                            },
                            { data: "Data" },
                            { data: "Mes" },
                            { data: "Parcela" },
                            { data: "Empregador" }
                        ],
                        columnDefs: [
                            { "visible": false, "targets": 5 }
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
                        },
                        pagingType: "full_numbers"
                    });
                }else {
                    table_origem = $('#tab_matricula_origem').DataTable({
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                        processing: false,
                        ServerSide: false,
                        responsive: true,
                        autoWidth: true,
                        JQueryUI: true,
                        searching: true,
                        ajax: {
                            url: 'pages/conta/transferencia.php',
                            method: 'POST',
                            data: {'matricula': matricula, 'empregador': abreviacao},
                            dataType: 'json'
                        },
                        columns: [
                            { data: "Registro" },
                            {
                                data: "Valor",
                                render: $.fn.dataTable.render.number( '.', ',', 2 ),
                                className: "text-right"
                            },
                            { data: "Data" },
                            { data: "Mes" },
                            { data: "Parcela" },
                            { data: "Empregador" }
                        ],
                        columnDefs: [
                            { "visible": false, "targets": 5 }
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
                        },
                        pagingType: "full_numbers"
                    });
                }
            }else{
                if ( $.fn.dataTable.isDataTable( '#tab_matricula_origem' ) ) {
                    table_origem.destroy();
                    table_origem = $('#tab_matricula_origem').DataTable({
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                        processing: false,
                        ServerSide: false,
                        responsive: true,
                        autoWidth: true,
                        JQueryUI: true,
                        searching: true,
                        ajax: {
                            url: 'pages/conta/transferencia.php',
                            method: 'POST',
                            data: {'matricula': matricula, 'empregador': abreviacao},
                            dataType: 'json'
                        },
                        columns: [
                            {data: "Registro"},
                            {
                                data: "Valor",
                                render: $.fn.dataTable.render.number( '.', ',', 2 ),
                                className: "text-right"
                            },
                            { data: "Data" },
                            {data: "Mes"},
                            {data: "Parcela"},
                            { data: "Empregador" }
                        ],
                        columnDefs: [
                            { "visible": false, "targets": 5 }
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
                        },
                        pagingType: "full_numbers"
                    });
                }else {
                    table_origem = $('#tab_matricula_origem').DataTable({
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
                        processing: false,
                        ServerSide: false,
                        responsive: true,
                        autoWidth: true,
                        JQueryUI: true,
                        searching: true,
                        ajax: {
                            url: 'pages/conta/transferencia.php',
                            method: 'POST',
                            data: {'matricula': matricula, 'empregador': abreviacao},
                            dataType: 'json'
                        },
                        columns: [
                            { data: "Registro" },
                            {
                                data: "Valor",
                                render: $.fn.dataTable.render.number( '.', ',', 2 ),
                                className: "text-right"
                            },
                            { data: "Data" },
                            { data: "Mes" },
                            { data: "Parcela" },
                            { data: "Empregador" }
                        ],
                        columnDefs: [
                            { "visible": false, "targets": 5 }
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
                        },
                        pagingType: "full_numbers"
                    });
                }
            }
        }
    });
}
function carrega_destino() {
    
    if ( $.fn.dataTable.isDataTable( '#tab_matricula_destino' ) ) {
        table_origem.destroy();
        table_origem = $('#tab_matricula_destino').DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            ServerSide: false,
            responsive: true,
            autoWidth: true,
            JQueryUI: true,
            searching: true,
            ajax: {
                url: 'pages/conta/transferencia.php',
                method: 'POST',
                data: {'matricula': matricula_destino, 'empregador': abreviacao_destino},
                dataType: 'json'
            },
            columns: [
                { data: "Registro" },
                {
                    data: "Valor",
                    render: $.fn.dataTable.render.number( '.', ',', 2 ),
                    className: "text-right"
                },
                { data: "Data" },
                { data: "Mes" },
                { data: "Parcela" },
                { data: "Empregador" }
            ],
            columnDefs: [
                { "visible": false, "targets": 5 }
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
            },
            pagingType: "full_numbers"
        });
    }else {
        table_origem = $('#tab_matricula_destino').DataTable({
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
            processing: false,
            ServerSide: false,
            responsive: true,
            autoWidth: true,
            JQueryUI: true,
            searching: true,
            ajax: {
                url: 'pages/conta/transferencia.php',
                method: 'POST',
                data: {'matricula': matricula_destino, 'empregador': abreviacao_destino},
                dataType: 'json'
            },
            columns: [
                { data: "Registro" },
                {
                    data: "Valor",
                    render: $.fn.dataTable.render.number( '.', ',', 2 ),
                    className: "text-right"
                },
                { data: "Data" },
                { data: "Mes" },
                { data: "Parcela" },
                { data: "Empregador" }
            ],
            columnDefs: [
                { "visible": false, "targets": 5 }
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
            },
            pagingType: "full_numbers"
        });
    }
    $("#btnTransferir").removeClass("spinner-border spinner-border-sm");
    $("#btnTransferir").html("<span class='glyphicon glyphicon-ok'>&nbsp;Transferido</span>");
}