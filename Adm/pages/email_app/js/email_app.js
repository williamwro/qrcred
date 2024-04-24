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
    $('#email_app').prop('disabled', true);
    $.ajax({
        url: "pages/email_app/exibe_email.php",
        method: "POST",
        data: {},
        dataType: "json",
        success:function (data) {
            $("#email_app").val(data.email);
        }
    })
    $("#btnSalvar").click(function (e) {
        e.preventDefault();
        debugger;
        if( $('#btnSalvar').val() === 'Alterar') {
            $('#btnSalvar').val('Salvar');
            $('#email_app').prop('disabled', false);
        }else{
            $('#email_app').prop('disabled', true);
            var email = $("#email_app").val();
            debugger;
            $.ajax({
                url: "pages/email_app/salvar_email.php",
                method: "POST",
                data: {email : email},
                dataType: "json",
                success:function (data) {
                    debugger;
                    if(data.success === "true") {
                        Swal.fire({
                            title: "Parabens!",
                            text: "Email salvo com sucesso !",
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
