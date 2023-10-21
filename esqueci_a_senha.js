$(document).ready(function(){
    
    $("#btn-login").click(function (e) {
         e.preventDefault();
         var username = $("#login-username").val();
         var email = $("#email").val();
         if (username === "") {
             swal.fire({
                 title: "Atenção!",
                 text: "Favor retornar a pagina anterior e informar o usuário !",
                 icon: "warning",
                 dangerMode: true
             })
             exit();
         }else{
             if (email === "") {
                 swal.fire({
                    icon: 'error',
                    title: 'Atenção',
                    text: 'Favor informar o e-mail cadastrado !',
                 })
                 exit();
             }else{
                 $.redirect('envia_nova_senha.php', {email:email, usuario:username});
             }
             debugger;
         }
     });
 });
 