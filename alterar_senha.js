$(document).ready(function(){
    debugger;
    var chave             = $("#chave").val();
    $("#btn-login").click(function (e) {
         e.preventDefault();

         var email = $("#email").val();
         var novasenha         = $("#novasenha").val();
         var confirmanovasenha = $("#confirmanovasenha").val();
         
         var usuario           = $("#user").val();  

         if (email == "") {
            swal({
                title: "Atenção!",
                text: "Favor informar o e-mail cadastrado !",
                icon: "warning",
                dangerMode: true
            })
        }else{
            if (novasenha == "" && confirmanovasenha == "") {
                swal({
                    title: "Atenção!",
                    text: "Favor informar a nova senha !",
                    icon: "warning",
                    dangerMode: true
                })
               
            }else{
                if (novasenha != confirmanovasenha) {
                    swal({
                        title: "Atenção!",
                        text: "(Confirma nova senha) não está igual a (Nova senha), corriga.",
                        icon: "warning",
                        dangerMode: true
                    })
                 
                }else{
                    debugger;
                    $.redirect('set_nova_senha.php', {user:usuario, email:email, novasenha:novasenha, confirmanovasenha:confirmanovasenha, chave:chave});
                }
            }
        }
     });
 });
 