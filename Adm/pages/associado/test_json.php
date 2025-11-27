<?PHP
// Teste simples para verificar se o JSON está sendo retornado corretamente
header('Content-Type: application/json');

$test_data = array(
    'data' => array(
        array(
            'codigo' => '12345',
            'nome' => 'TESTE USUARIO',
            'endereco' => 'RUA TESTE',
            'numero' => '123',
            'nascimento' => '01/01/1990',
            'salario' => '1000.00',
            'limite' => '500.00',
            'empregador' => 'EMPRESA TESTE',
            'cep' => '78000-000',
            'cpf' => '000.000.000-00',
            'telres' => '(65) 0000-0000',
            'telcom' => '(65) 0000-0000',
            'cel' => '(65) 00000-0000',
            'complemento' => '',
            'nome_situacao' => 'ATIVO',
            'bairro' => 'CENTRO',
            'abreviacao' => 'EMP',
            'id' => 1,
            'botao' => '<button>Editar</button>',
            'botaosenha' => '<button>Senha</button>',
            'botaoexcluir' => '<button>Excluir</button>',
            'id_empregador' => 1
        )
    ),
    'recordsTotal' => 1,
    'recordsFiltered' => 1
);

echo json_encode($test_data, JSON_UNESCAPED_UNICODE);
?>
