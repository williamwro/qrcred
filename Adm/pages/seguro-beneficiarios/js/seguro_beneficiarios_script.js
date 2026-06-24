var table_beneficiarios;
var detailRows = [];

function formatBeneficiario(d) {
    return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
        '<tr><td><strong>ID:</strong></td><td>' + (d.id_beneficiario || '') + '</td></tr>' +
        '<tr><td><strong>Associado:</strong></td><td>' + (d.nome_associado || '') + '</td></tr>' +
        '<tr><td><strong>CPF Associado:</strong></td><td>' + (d.cpf_associado || '') + '</td></tr>' +
        '<tr><td><strong>Beneficiário:</strong></td><td>' + (d.nome || '') + '</td></tr>' +
        '<tr><td><strong>CPF:</strong></td><td>' + (d.cpf || '') + '</td></tr>' +
        '<tr><td><strong>Parentesco:</strong></td><td>' + (d.parentesco || '') + '</td></tr>' +
        '<tr><td><strong>Data Nascimento:</strong></td><td>' + (d.data_nascimento || '') + '</td></tr>' +
        '<tr><td><strong>Status:</strong></td><td>' + (d.status || '') + '</td></tr>' +
        '<tr><td><strong>Data Cadastro:</strong></td><td>' + (d.data_criacao || '') + '</td></tr>' +
        '</table>';
}

$(document).ready(function() {
    var usuario_global = $('#usuario').val();
    var divisao = sessionStorage.getItem('divisao');
    var usuario_cod = sessionStorage.getItem('usuario_cod');
    
    table_beneficiarios = $('#tabela_seguro_beneficiarios').DataTable({
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "todos"]],
        "processing": true,
        "serverSide": true,
        "paging": true,
        "deferRender": true,
        "autoWidth": false,
        "ajax": {
            "url": 'pages/seguro-beneficiarios/seguro_beneficiarios_read.php',
            "method": 'POST',
            "data": {
                'usuario_global': usuario_global,
                'divisao': divisao,
                'usuario_cod': usuario_cod
            },
            "dataType": 'json'
        },
        "order": [[9, "desc"]],
        "columns": [
            {
                "class": "details-control",
                "orderable": false,
                "data": null,
                "defaultContent": ""
            },
            { "data": "id_beneficiario" },
            { "data": "nome_associado" },
            { "data": "cpf_associado" },
            { "data": "nome" },
            { "data": "cpf" },
            { "data": "parentesco" },
            { "data": "data_nascimento" },
            { "data": "status" },
            { "data": "data_criacao" },
            { "data": "acoes" }
        ],
        "columnDefs": [
            {
                "targets": [1],
                "visible": false,
                "searchable": true
            }
        ],
        "createdRow": function(row, data, dataIndex) {
            if (data.status === 'pendente') {
                $(row).addClass('pendente');
            } else if (data.status === 'assinado') {
                $(row).addClass('assinado');
            } else if (data.status === 'cancelado') {
                $(row).addClass('cancelado');
            }
        },
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Portuguese-Brasil.json"
        },
        "pagingType": "full_numbers"
    });

    table_beneficiarios.on('draw', function() {
        $.each(detailRows, function(i, id) {
            $('#' + id + ' td.details-control').trigger('click');
        });
    });

    $('#tabela_seguro_beneficiarios tbody').on('click', 'tr td.details-control', function() {
        var tr = $(this).closest('tr');
        var row = table_beneficiarios.row(tr);
        var idx = $.inArray(tr.attr('id'), detailRows);

        if (row.child.isShown()) {
            tr.removeClass('details');
            row.child.hide();
            detailRows.splice(idx, 1);
        } else {
            tr.addClass('details');
            row.child(formatBeneficiario(row.data())).show();
            if (idx === -1) {
                detailRows.push(tr.attr('id'));
            }
        }
    });

    // Editar beneficiário
    $('#tabela_seguro_beneficiarios').on('click', '.btn-editar', function() {
        var id_beneficiario = $(this).data('id');
        
        $.ajax({
            url: 'pages/seguro-beneficiarios/seguro_beneficiarios_exibe.php',
            method: 'POST',
            data: { id_beneficiario: id_beneficiario },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#id_beneficiario').val(data.id_beneficiario);
                    $('#id_associado').val(data.id_associado);
                    $('#id_divisao').val(data.id_divisao);
                    $('#nome_associado').val(data.nome_associado);
                    $('#nome_beneficiario').val(data.nome_beneficiario);
                    $('#cpf_beneficiario').val(data.cpf_zap);
                    $('#parentesco').val(data.parentesco);
                    $('#data_nascimento').val(data.data_nascimento);
                    $('#status_beneficiario').val(data.status);
                    
                    $('#ModalEditaBeneficiario').modal('show');
                } else {
                    Swal.fire('Erro', response.error, 'error');
                }
            },
            error: function() {
                Swal.fire('Erro', 'Erro ao carregar dados do beneficiário', 'error');
            }
        });
    });

    // Salvar beneficiário
    $('#btn_salvar_beneficiario').click(function() {
        var formData = {
            id_beneficiario: $('#id_beneficiario').val(),
            id_associado: $('#id_associado').val(),
            id_divisao: $('#id_divisao').val(),
            nome: $('#nome_beneficiario').val(),
            cpf: $('#cpf_beneficiario').val(),
            parentesco: $('#parentesco').val(),
            data_nascimento: $('#data_nascimento').val(),
            status: $('#status_beneficiario').val()
        };

        $.ajax({
            url: 'pages/seguro-beneficiarios/seguro_beneficiarios_atualizar.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('Sucesso', response.message, 'success');
                    $('#ModalEditaBeneficiario').modal('hide');
                    table_beneficiarios.ajax.reload();
                } else {
                    Swal.fire('Erro', response.error, 'error');
                }
            },
            error: function() {
                Swal.fire('Erro', 'Erro ao salvar beneficiário', 'error');
            }
        });
    });

    // Excluir beneficiário
    $('#tabela_seguro_beneficiarios').on('click', '.btn-excluir', function() {
        var id_beneficiario = $(this).data('id');
        var id_associado = $(this).data('associado');
        
        Swal.fire({
            title: 'Tem certeza?',
            text: "Esta ação não poderá ser revertida!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../api/seguro-beneficiarios/seguro_beneficiarios_excluir.php',
                    method: 'POST',
                    data: JSON.stringify({
                        id_beneficiario: id_beneficiario,
                        id_associado: id_associado
                    }),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Excluído!', response.message, 'success');
                            table_beneficiarios.ajax.reload();
                        } else {
                            Swal.fire('Erro', response.error, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Erro', 'Erro ao excluir beneficiário', 'error');
                    }
                });
            }
        });
    });

    // Máscara para CPF
    $('#cpf_beneficiario').mask('999.999.999-99');
});
