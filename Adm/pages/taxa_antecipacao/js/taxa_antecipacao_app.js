//script_empregador.js
var usuario_global;
var divisao;
var tabela_empregador;
$(document).ready(function(){
    debugger;
    $('#operation').val("Add");
    divisao = sessionStorage.getItem("divisao");
    usuario_global = sessionStorage.getItem("usuario_global");
    $('#btnSalvar').val('Alterar');
    $('#taxa_app').prop('disabled', true);
    $.ajax({
        url: "pages/taxa_antecipacao/exibe_taxa_antecipacao.php",
        method: "POST",
        data: {},
        dataType: "json",
        success:function (data) {
            $("#taxa_app").val(data.porcentagem);
        }
    })
    $("#btnSalvar").click(function (e) {
        e.preventDefault();
        debugger;
        if( $('#btnSalvar').val() === 'Alterar') {
            $('#btnSalvar').val('Salvar');
            $('#taxa_app').prop('disabled', false);
        }else{
            $('#taxa_app').prop('disabled', true);
            var taxa_app = $("#taxa_app").val();
            debugger;
            $.ajax({
                url: "pages/taxa_antecipacao/salvar_taxa_antecipacao.php",
                method: "POST",
                data: {taxa_app : taxa_app},
                dataType: "json",
                success:function (data) {
                    debugger;
                    if(data.success === "true") {
                        Swal.fire({
                            title: "Parabens!",
                            text: "Taxa salva com sucesso !",
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1500
                        });
                        $('#btnSalvar').val('Alterar');
                    }
                }
            })
        }
    });
});
