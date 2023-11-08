var usuario;
var cpf;
$(document).ready(function () {
    $("#btn-enviar").click(function (e) {
        waitingDialog.show("Enviando solicitação, aguarde ...");
        e.preventDefault();
        var tipo_loginx;
        debugger;
        usuario = $("#nome_completo").val();
        cpf = $("#cpf").val();

        if (usuario === "" && cpf === "") {
            Swal.fire({icon: "error", title: "Atenção!", text: "Informe o nome, cpf e email !"});
            waitingDialog.hide();
        } else {
            if (usuario === "" && cpf !== "") {
                Swal.fire({icon: "error", title: "Atenção!", text: "Informe o nome, cpf e email !"});
                waitingDialog.hide();
            } else {
                if (usuario !== "" && cpf === "") {
                    Swal.fire({title: "Atenção!", text: "Informe a nome !", icon: "error"});
                    waitingDialog.hide();
                } else {
                    $.ajax({
                        url: "cancelar_conta.php", 
                        type: "POST", 
                        data: $("#loginform").serialize(), 
                        dataType: "json", 
                        success: function (response) {
                            debugger;
                            if (response.Resultado == "cadastrado") {
                                $("#divLoading").hide();
                                waitingDialog.hide();
                                clearInputFields();
                                showSuccessAlert();
                            }
                        },
                        error: function(xhr, textStatus , xhr) {
                            console.log(xhr.status);
                        } 
                    });
                    function clearInputFields() {
                        $("#nome_completo, #cpf, #email, #motivo").val("");
                    }
                    function showSuccessAlert() {
                        Swal.fire({
                            icon: "success", 
                            title: "Atenção!", 
                            text: "Solicitação enviada com sucesso !"
                        });
                    }
                }
            }
        }
    });
});