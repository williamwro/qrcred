$(document).ready(function(){

    var dataTable = $('#tabela_tipo_especialidade').DataTable({
        "processing":true,
        "serverSide":false,
        "order":[],
        "ajax":{
            url:"pages/tipo_especialidade/tipo_especialidade_datatable.php",
            type:"POST"
        },
        "columns": [
            { "data": "id_tipo_especialidade" },
            { "data": "nome_tipo" },
            { "data": "botao" },
            { "data": "botaoexcluir" }
        ],
        "columnDefs":[
            {
                "targets":[0, 2, 3],
                "orderable":false,
            },
        ],
        "pageLength": 25,
        dom: '<"col-sm-6"l><"col-sm-6"f><"col-sm-12"t><"col-sm-5"i><"col-sm-7"p>',
        language: {
            "sEmptyTable": "Nenhum registro encontrado",
            "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
            "sInfoFiltered": "(Filtrados de _MAX_ registros)",
            "sInfoPostFix": "",
            "sInfoThousands": ".",
            "sLengthMenu": "_MENU_ resultados por página",
            "sLoadingRecords": "Carregando...",
            "sProcessing": "Processando...",
            "sZeroRecords": "Nenhum registro encontrado",
            "sSearch": "Pesquisar",
            "oPaginate": {
                "sNext": "Próximo",
                "sPrevious": "Anterior",
                "sFirst": "Primeiro",
                "sLast": "Último"
            },
            "oAria": {
                "sSortAscending": ": Ordenar colunas de forma ascendente",
                "sSortDescending": ": Ordenar colunas de forma descendente"
            }
        }
    });

    $('#btnInserir').click(function(){
        $('#operation').val("Add");
        $('#C_id').val("");
        $('#C_idx').val("");
        $('#C_nome_tipo').val("").focus();
        $('#rotulo_associado').text("Cadastrando");
        $('#ModalEditaTipoEspecialidade').modal('show');
    });

    $(document).on('submit', '#frmtipoespecialidade', function(event){
        event.preventDefault();
        
        // Prevenir múltiplas submissões
        if($(this).data('submitting')) {
            return false;
        }
        
        var error = '';
        var C_nome_tipo = $('#C_nome_tipo').val();
        
        if(C_nome_tipo == ''){
            error += 'Nome do Tipo é obrigatório ';
        }

        if(error == ''){
            $(this).data('submitting', true);
            
            $.ajax({
                url:"pages/tipo_especialidade/tipo_especialidade_verifica_repitido.php",
                method:"POST",
                data:$(this).serialize(),
                success:function(data){
                    if(data.trim() == 'nao repitido'){
                        $.ajax({
                            url:"pages/tipo_especialidade/tipo_especialidade_salvar.php",
                            method:"POST",
                            data:$('#frmtipoespecialidade').serialize(),
                            success:function(data){
                                if(data.trim()=='cadastrado'){
                                    $('#frmtipoespecialidade')[0].reset();
                                    $('#ModalEditaTipoEspecialidade').modal('hide');
                                    dataTable.ajax.reload();
                                    alert_msg('success','Cadastro realizado com sucesso!!');
                                } else if(data.trim()=='atualizado'){
                                    $('#frmtipoespecialidade')[0].reset();
                                    $('#ModalEditaTipoEspecialidade').modal('hide');
                                    dataTable.ajax.reload();
                                    alert_msg('success','Dados atualizados com sucesso!!');
                                } else {
                                    alert_msg('danger', 'Erro ao salvar dados: ' + data);
                                }
                            },
                            complete: function() {
                                $('#frmtipoespecialidade').data('submitting', false);
                            }
                        });
                    } else {
                        $('#frmtipoespecialidade').data('submitting', false);
                        alert_msg('danger','Tipo já cadastrado');
                    }
                },
                error: function() {
                    $('#frmtipoespecialidade').data('submitting', false);
                }
            });
        } else {
            alert_msg('danger', error);
        }
    });

    $(document).on('click', '.update_tipo_especialidade', function(){
        var tipo_id = $(this).attr("id");
        console.log("Clicou no botão update, ID:", tipo_id);
        $.ajax({
            url:"pages/tipo_especialidade/tipo_especialidade_exibe.php",
            method:"POST",
            data:{id:tipo_id},
            dataType:"json",
            success:function(data){
                console.log("Dados recebidos:", data);
                $('#C_id').val(data.id_tipo_especialidade);
                $('#C_idx').val(data.id_tipo_especialidade);
                $('#C_nome_tipo').val(data.nome_tipo);
                $('#operation').val("Update");
                $('#rotulo_associado').text("Alterando");
                $('#ModalEditaTipoEspecialidade').modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Erro AJAX:", error);
                console.log("Response:", xhr.responseText);
                alert_msg('danger', 'Erro ao carregar dados do tipo');
            }
        });
    });

});

function alert_msg(tipo, msg){
    $('#alert_mensagem').removeClass();
    $('#alert_mensagem').addClass('alert alert-'+tipo+'').html(msg);
    $('#alert_mensagem').show();
    setTimeout(function(){
        $('#alert_mensagem').fadeOut('slow');
    }, 3000);
} 