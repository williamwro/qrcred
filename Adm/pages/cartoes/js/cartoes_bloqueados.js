var table;
var divisao;
var nome_divisao;
$(document).ready(function(){
    divisao = sessionStorage.getItem("divisao");
    nome_divisao = sessionStorage.getItem("divisao_nome");
    usuario_global = sessionStorage.getItem("usuario_global");
    usuario_cod = sessionStorage.getItem("usuario_cod");
    $.getJSON( "pages/cartoes/situacao_cartao.php", function( data ) {
        $.each(data, function (index, value) {
            if(value.descri === "BLOQUEADO") {
                $('#C_situacaocartao').append('<option selected value="' + value.id + '">' + value.descri + '</option>');
            }else{
                $('#C_situacaocartao').append('<option value="' + value.id + '">' + value.descri + '</option>');
            }
        });
    });
    listar_cartoes(2);//2 = bloqueado
});
function listar_cartoes(idsituacao) {
    table = $('#tabela_dados').DataTable({
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        destroy: true,
        processing: false,
        serverSide: false,
        responsive: true,
        autoWidth: true,
        JQueryUI: true,
        searching: true,
        deferRender: true,
        paging: false,
        ajax: {
            url: '../Adm/pages/cartoes/cartoes_bloqueados.php',
            method: 'POST',
            data: function (data) {
                data.idsituacao = idsituacao;
                data.divisao = divisao;
            },
            dataType: 'json'
        },
        order: [[2, "asc"]],
        columns: [
            {data: "data_pedido"},
            {data: "cartao"},
            {data: "nome"},
            {data: "empregador"},
            {data: "botaoexcluir",
                orderable: false,
                "class": "noExl"
            }
        ],
        dom: '<"top"ifl><"clear">rt<"bottom"p><"clear">',
        stateSave: true,
        pagingType: "full_numbers",
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
        }
    });

}

$('#btnRelatorio').click(function () {
    //var selectedText = $("#C_lotes option:selected").html();
    //var x = selectedText.split("-");
    //var lote = $.trim(x[1]);
    $.redirect('pages/cartoes/relatorio_cartoes_bloqueados_pdf.php',{divisao:divisao,nome_divisao:nome_divisao}, "POST", "_blank");
});

$('[href="#btnDesbloquear"]').click(function (){

    //var data_row = table.row($(this).closest('tr')).data();
    //var $button = $(this);
    //var cartao;
    alert('data_row');
    console.log("passtou");
    //valor = data_row.cartao;
    /*$.ajax({
        url: "pages/cartoes/desbloquear_cartao.php",
        method: "POST",
        dataType: "json",
        async:false,
        data: {"divisao": divisao},
        success: function (data) {
            table.ajax.reload();
        }
    });*/
});
$("#C_situacaocartao").change(function () {
    
    waitingDialog.show('Carregando, aguarde ...',);
    var idsituacao = $("#C_situacaocartao").val();
    listar_cartoes(idsituacao);
    //table.ajax().reload();
    waitingDialog.hide();
});