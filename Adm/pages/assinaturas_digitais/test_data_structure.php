<?php
/**
 * Arquivo de teste para verificar estrutura de dados do DataTables
 * Use este arquivo temporariamente para testar se o problema é nos dados
 */

header('Content-Type: application/json; charset=utf-8');

// Dados de teste com a estrutura esperada
$test_data = array(
    'data' => array(
        array(
            'id' => '1',
            'codigo' => 'TEST001',
            'nome' => 'Usuario Teste',
            'celular' => '(11) 99999-9999',
            'data_hora' => date('d/m/Y H:i:s'),
            'autorizado' => 'Sim',
            'aceitou_termo' => 'Sim',
            'event' => 'doc_signed',
            'doc_token' => 'test_token_123',
            'doc_name' => 'Termo Adesão SasPyx',
            'signed_at' => date('Y-m-d H:i:s'),
            'name' => 'Usuario Teste',
            'email' => 'teste@email.com',
            'cpf' => '123.456.789-00',
            'has_signed' => 'Sim',
            'cel_informado' => '(11) 99999-9999',
            'botao_vincular' => '<button type="button" name="vincular_codigo" data-id="1" data-cpf="123.456.789-00" data-codigo-atual="TEST001" class="btn btn-primary btn-xs vincular_codigo" data-toggle="tooltip" data-placement="top" title="Vincular código do associado">
                                    <span class="glyphicon glyphicon-link"></span> Vincular
                                </button>',
            'botao' => '<button type="button" name="update_assinatura" id="1" class="btn btn-warning glyphicon glyphicon-edit btn-xs update_assinatura" data-toggle="tooltip" data-placement="top" title="Alterar"></button>',
            'botaoexcluir' => '<button type="button" name="btnexcluir" id="1" class="btn btn-danger glyphicon glyphicon-trash btn-xs btnexcluir" data-toggle="tooltip" data-placement="top" title="Excluir"></button>'
        ),
        array(
            'id' => '2',
            'codigo' => '',
            'nome' => 'Usuario Teste 2',
            'celular' => '(11) 88888-8888',
            'data_hora' => date('d/m/Y H:i:s'),
            'autorizado' => 'Não',
            'aceitou_termo' => 'Sim',
            'event' => 'doc_pending',
            'doc_token' => 'test_token_456',
            'doc_name' => 'Termo Adesão SasPyx',
            'signed_at' => '',
            'name' => 'Usuario Teste 2',
            'email' => 'teste2@email.com',
            'cpf' => '987.654.321-00',
            'has_signed' => 'Não',
            'cel_informado' => '(11) 88888-8888',
            'botao_vincular' => '<button type="button" name="vincular_codigo" data-id="2" data-cpf="987.654.321-00" data-codigo-atual="" class="btn btn-primary btn-xs vincular_codigo" data-toggle="tooltip" data-placement="top" title="Vincular código do associado">
                                    <span class="glyphicon glyphicon-link"></span> Vincular
                                </button>',
            'botao' => '<button type="button" name="update_assinatura" id="2" class="btn btn-warning glyphicon glyphicon-edit btn-xs update_assinatura" data-toggle="tooltip" data-placement="top" title="Alterar"></button>',
            'botaoexcluir' => '<button type="button" name="btnexcluir" id="2" class="btn btn-danger glyphicon glyphicon-trash btn-xs btnexcluir" data-toggle="tooltip" data-placement="top" title="Excluir"></button>'
        )
    )
);

echo json_encode($test_data);

/*
INSTRUÇÕES DE USO:
1. Para testar, altere temporariamente o arquivo assinaturas_digitais_read_script.js
2. Mude a URL de 'pages/assinaturas_digitais/assinaturas_digitais_read2.php' 
   para 'pages/assinaturas_digitais/test_data_structure.php'
3. Recarregue a página para ver se o DataTable funciona com dados de teste
4. Se funcionar, o problema está na consulta SQL ou estrutura de dados real
5. Se não funcionar, o problema está na configuração do DataTable
6. Após o teste, volte a URL original e delete este arquivo
*/
?> 