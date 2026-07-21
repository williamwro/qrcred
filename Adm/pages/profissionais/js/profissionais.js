$(document).ready(function(){

    var especialidadesTemporarias = [];
    var salvandoEspecialidades = false; // Flag para evitar múltiplas submissões
    var todasEspecialidades = []; // Array para armazenar todas as especialidades para filtro
    var especialidadesJaSalvasAutomaticamente = false; // Flag para controlar se já foram salvas automaticamente
    
    // Função para limpar completamente o array de especialidades
    function limparEspecialidadesTemporarias() {
        console.log(">>> Limpando especialidades temporárias completamente");
        console.log(">>> Array ANTES da limpeza:", especialidadesTemporarias);
        console.log(">>> Operation atual:", $('#operation').val());
        
        // Limpeza múltipla para garantir
        especialidadesTemporarias.length = 0; // Limpar array mantendo a referência
        especialidadesTemporarias.splice(0); // Remover todos os elementos
        especialidadesTemporarias = []; // Reatribuir para garantir
        
        // Limpar também possíveis referências globais
        if (window.especialidadesTemporarias) {
            window.especialidadesTemporarias = [];
        }
        
        // Limpar elemento visual de todas as formas possíveis
        var container = $('#lista_especialidades');
        container.empty(); // Limpar visualmente também
        container.html(''); // Garantir limpeza do HTML
        container.text(''); // Garantir limpeza do texto
        container.removeClass(); // Remover classes que possam interferir
        container.addClass('tag-container'); // Restaurar classe necessária
        
        // Limpar campos relacionados
        $('#C_especialidade_select').val('');
        $('#C_filtro_especialidade').val('');
        
        console.log(">>> Array DEPOIS da limpeza:", especialidadesTemporarias);
        console.log(">>> Container HTML limpo e resetado");
        
        // Forçar atualização visual após limpeza
        setTimeout(function() {
            console.log(">>> Verificação pós-limpeza:", especialidadesTemporarias);
            if (especialidadesTemporarias.length > 0) {
                console.error("ERRO: Array ainda contém especialidades após limpeza!");
                especialidadesTemporarias = []; // Limpeza final forçada
            }
        }, 50);
    }

    var dataTable = $('#tabela_profissionais').DataTable({
        "processing":true,
        "serverSide":false,
        "order":[],
        "ajax":{
            url:"pages/profissionais/profissionais_datatable.php",
            type:"POST",
            "dataSrc": function(json) {
                console.log("DataTable recebeu dados:", json);
                return json.data;
            },
            "error": function(xhr, error, thrown) {
                console.log("Erro no DataTable:", error);
                console.log("Response:", xhr.responseText);
            }
        },
        "columns": [
            { "data": "id_profissional" },
            { "data": "nome_profissional" },
            { "data": "botao" },
            { "data": "botaoexcluir" }
        ],
        "columnDefs":[
            {
                "targets":[0, 2, 3],
                "orderable":false,
            },
            {
                "targets":[0],
                "visible":false,
            },
            {
                "targets": [1],
                "width": "70%"
            },
            {
                "targets": [2, 3],
                "width": "15%"
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

    // Forçar redimensionamento das colunas após carregar
    dataTable.on('draw.dt', function() {
        setTimeout(function() {
            // Remover qualquer CSS conflitante
            $('#custom-table-style').remove();
            
            // Adicionar CSS mais específico e agressivo
            var customCSS = '<style id="profissionais-table-style">' +
                '#tabela_profissionais { table-layout: fixed !important; width: 100% !important; }' +
                '#tabela_profissionais th:nth-child(2), #tabela_profissionais td:nth-child(2) { ' +
                '    width: 70% !important; min-width: 70% !important; max-width: 70% !important; ' +
                '    word-wrap: break-word !important; overflow: hidden !important; ' +
                '}' +
                '#tabela_profissionais th:nth-child(3), #tabela_profissionais td:nth-child(3) { ' +
                '    width: 15% !important; min-width: 15% !important; max-width: 15% !important; ' +
                '    text-align: center !important; ' +
                '}' +
                '#tabela_profissionais th:nth-child(4), #tabela_profissionais td:nth-child(4) { ' +
                '    width: 15% !important; min-width: 15% !important; max-width: 15% !important; ' +
                '    text-align: center !important; ' +
                '}' +
                '</style>';
            
            $('head').append(customCSS);
            
            // Ajustar colunas do DataTable
            dataTable.columns.adjust();
        }, 100);
    });

    // Customizar input de busca do DataTable
    setTimeout(function() {
        var searchInput = $('input[aria-controls="tabela_profissionais"]');
        if (searchInput.length) {
            // Aumentar largura em 50%
            var currentWidth = searchInput.width();
            searchInput.css({
                'width': (currentWidth * 1.5) + 'px',
                'position': 'relative',
                'padding-right': '25px'
            });
            
            // Adicionar botão X para limpar
            var clearButton = $('<span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999; font-size: 14px; z-index: 10;">×</span>');
            
            // Envolver o input em um container relativo
            searchInput.wrap('<div style="position: relative; display: inline-block;"></div>');
            searchInput.parent().append(clearButton);
            
            // Funcionalidade do botão X
            clearButton.on('click', function() {
                searchInput.val('').trigger('keyup');
                searchInput.focus();
            });
            
            // Mostrar/esconder X baseado no conteúdo
            searchInput.on('input keyup', function() {
                if ($(this).val().length > 0) {
                    clearButton.show();
                } else {
                    clearButton.hide();
                }
            });
            
            // Inicialmente esconder o X
            clearButton.hide();
        }
    }, 100);

    // Carregar especialidades disponíveis
    function carregarEspecialidadesDisponiveis() {
        $.ajax({
            url: "pages/profissionais/especialidades_listar.php",
            method: "POST",
            dataType: "json",
            success: function(data) {
                todasEspecialidades = data; // Armazenar todas as especialidades para filtro
                exibirEspecialidades(data);
            },
            error: function() {
                alert_msg('danger', 'Erro ao carregar especialidades');
            }
        });
    }

    // Configurar drag and drop
    function configurarDragAndDrop() {
        var dragData = null;
        
        // Configurar drag das options do select (mouse e touch)
        $('#C_especialidade_select').on('mousedown touchstart', 'option', function(e) {
            // Normalizar evento para touch e mouse
            var clientX = e.type === 'touchstart' ? e.originalEvent.touches[0].clientX : e.clientX;
            var clientY = e.type === 'touchstart' ? e.originalEvent.touches[0].clientY : e.clientY;
            if ($(this).val() === '') return; // Não permitir arrastar a opção vazia
            
            var option = $(this);
            dragData = {
                id: option.val(),
                text: option.text()
            };
            
            // Criar elemento de arraste visual
            var dragElement = $('<div class="drag-preview">' + 
                '<i class="fa fa-hand-rock-o"></i> ' + option.text() + 
                '</div>');
            dragElement.css({
                position: 'fixed',
                left: clientX + 10 + 'px',
                top: clientY - 10 + 'px',
                background: '#337ab7',
                color: 'white',
                padding: '8px 12px',
                borderRadius: '4px',
                zIndex: 10000,
                pointerEvents: 'none',
                fontSize: '12px',
                fontWeight: 'bold',
                boxShadow: '0 4px 8px rgba(0,0,0,0.3)',
                border: '1px solid #2e6da4',
                whiteSpace: 'nowrap'
            });
            $('body').append(dragElement);
            
            var isDragging = false;
            var dragStarted = false;
            
            $(document).on('mousemove.drag touchmove.drag', function(e) {
                var moveX = e.type === 'touchmove' ? e.originalEvent.touches[0].clientX : e.clientX;
                var moveY = e.type === 'touchmove' ? e.originalEvent.touches[0].clientY : e.clientY;
                
                if (!dragStarted && (Math.abs(moveX - dragData.startX) > 5 || Math.abs(moveY - dragData.startY) > 5)) {
                    dragStarted = true;
                    isDragging = true;
                    $('body').addClass('dragging');
                    e.preventDefault(); // Só previne quando realmente inicia o drag
                }
                
                if (isDragging) {
                    dragElement.css({
                        left: moveX + 10 + 'px',
                        top: moveY - 10 + 'px'
                    });
                    
                    // Verificar se está sobre a área de drop
                    var dropTarget = $('#lista_especialidades')[0];
                    var dropRect = dropTarget.getBoundingClientRect();
                    
                    if (moveX >= dropRect.left && moveX <= dropRect.right &&
                        moveY >= dropRect.top && moveY <= dropRect.bottom) {
                        $('#lista_especialidades').addClass('drag-over');
                        dragElement.css({
                            background: '#5cb85c',
                            borderColor: '#4cae4c'
                        });
                    } else {
                        $('#lista_especialidades').removeClass('drag-over');
                        dragElement.css({
                            background: '#337ab7',
                            borderColor: '#2e6da4'
                        });
                    }
                }
            });
            
            $(document).on('mouseup.drag touchend.drag', function(e) {
                $(document).off('mousemove.drag touchmove.drag mouseup.drag touchend.drag');
                dragElement.remove();
                $('body').removeClass('dragging');
                $('#lista_especialidades').removeClass('drag-over');
                
                if (isDragging) {
                    // Verificar se o mouse/touch está sobre a lista de especialidades
                    var endX = e.type === 'touchend' ? e.originalEvent.changedTouches[0].clientX : e.clientX;
                    var endY = e.type === 'touchend' ? e.originalEvent.changedTouches[0].clientY : e.clientY;
                    
                    var dropTarget = $('#lista_especialidades')[0];
                    var dropRect = dropTarget.getBoundingClientRect();
                    
                    if (endX >= dropRect.left && endX <= dropRect.right &&
                        endY >= dropRect.top && endY <= dropRect.bottom) {
                        
                        console.log("Drag and drop: Tentativa de adicionar especialidade", dragData);
                        
                        // Simular adição da especialidade
                        $('#C_especialidade_select').val(dragData.id);
                        $('#btn_adicionar_especialidade').click();
                        
                        // Mostrar feedback visual
                        $('#lista_especialidades').addClass('drop-success');
                        setTimeout(function() {
                            $('#lista_especialidades').removeClass('drop-success');
                        }, 600);
                    }
                }
                
                dragData = null;
            });
            
            // Armazenar posição inicial
            dragData.startX = clientX;
            dragData.startY = clientY;
        });
        
        // Melhorar visual da área de drop
        $('#lista_especialidades').hover(
            function() {
                if (!$(this).hasClass('drag-over')) {
                    $(this).addClass('drop-hint');
                }
            },
            function() {
                $(this).removeClass('drop-hint');
            }
        );
    }

    // Exibir especialidades no select
    function exibirEspecialidades(especialidades) {
        $('#C_especialidade_select').empty();
        $('#C_especialidade_select').append('<option value="">Selecione uma especialidade...</option>');
        $.each(especialidades, function(index, especialidade) {
            $('#C_especialidade_select').append('<option value="' + especialidade.id_especialidade + '">' + especialidade.nome_especialidade + '</option>');
        });
        
        // Reconfigurar drag and drop após atualizar as opções
        configurarDragAndDropOptions();
    }
    
    // Configurar drag and drop específico para as options
    function configurarDragAndDropOptions() {
        $('#C_especialidade_select option').each(function() {
            if ($(this).val() !== '') {
                $(this).attr('draggable', 'true');
            }
        });
    }

    // Filtrar especialidades
    function filtrarEspecialidades(filtro) {
        if (!filtro || filtro.trim() === '') {
            // Se não há filtro, mostrar todas
            exibirEspecialidades(todasEspecialidades);
            return;
        }
        
        var especialidadesFiltradas = todasEspecialidades.filter(function(especialidade) {
            var nome = especialidade.nome_especialidade.toLowerCase();
            return nome.includes(filtro.toLowerCase());
        });
        
        exibirEspecialidades(especialidadesFiltradas);
    }

    // Carregar especialidades vinculadas ao profissional
    function carregarEspecialidadesVinculadas(id_profissional) {
        console.log("=== carregarEspecialidadesVinculadas chamada ===");
        console.log("ID Profissional:", id_profissional);
        console.log("Array ANTES do carregamento:", especialidadesTemporarias);
        
        if (!id_profissional) {
            console.log("Sem ID - limpando array");
            especialidadesTemporarias = [];
            atualizarListaEspecialidadesTemporarias();
            return;
        }

        return $.ajax({
            url: "pages/profissionais/profissionais_especialidades_listar.php",
            method: "POST",
            data: { id_profissional: id_profissional },
            dataType: "json",
            success: function(data) {
                console.log("=== RESPOSTA do carregamento de especialidades ===");
                console.log("Dados recebidos do servidor:", data);
                console.log("Tipo dos dados:", typeof data);
                console.log("É array?", Array.isArray(data));
                console.log("Length dos dados:", data ? data.length : 'undefined');
                
                // Limpar array antes de atribuir novos valores
                especialidadesTemporarias = [];
                
                // Verificar se data é um array válido
                if (Array.isArray(data)) {
                    especialidadesTemporarias = data;
                    console.log("Especialidades carregadas com sucesso:", especialidadesTemporarias.length, "itens");
                } else {
                    console.warn("AVISO: Resposta não é um array válido");
                    console.log("Dados recebidos:", data);
                    especialidadesTemporarias = [];
                }
                
                console.log("Array APÓS carregamento:", especialidadesTemporarias);
                console.log("Total de especialidades carregadas:", especialidadesTemporarias.length);
                
                // Atualizar interface
                atualizarListaEspecialidadesTemporarias();
                
                // Verificação final
                console.log("Verificação final - especialidades no array:", especialidadesTemporarias);
            },
            error: function(xhr, status, error) {
                console.log("=== ERRO no carregamento de especialidades ===");
                console.log("Status:", status);
                console.log("Error:", error);
                console.log("Response Text:", xhr.responseText);
                console.log("Response Status:", xhr.status);
                alert_msg('danger', 'Erro ao carregar especialidades do profissional');
                
                // Garantir que o array seja limpo em caso de erro
                especialidadesTemporarias = [];
                atualizarListaEspecialidadesTemporarias();
            }
        }).catch(function () {});
    }

    // Atualizar lista visual de especialidades
    function atualizarListaEspecialidadesTemporarias() {
        console.log("=== atualizarListaEspecialidadesTemporarias chamada ===");
        console.log("Operation atual:", $('#operation').val());
        console.log("Especialidades no array:", especialidadesTemporarias);
        
        var operation = $('#operation').val();
        var container = $('#lista_especialidades');
        
        container.empty();

        if (especialidadesTemporarias.length === 0) {
            console.log("Nenhuma especialidade para exibir - OK");
            // O CSS ::after vai mostrar a mensagem de instrução automaticamente
            return;
        }

        console.log("Exibindo", especialidadesTemporarias.length, "especialidades");
        $.each(especialidadesTemporarias, function(index, especialidade) {
            var tag = $('<div class="tag-item">' +
                '<span>' + especialidade.nome_especialidade + '</span>' +
                '<button type="button" class="tag-remove" data-id="' + especialidade.id_especialidade + '">&times;</button>' +
                '</div>');
            container.append(tag);
        });
    }

    // Event listener para filtro de especialidades
    $('#C_filtro_especialidade').on('input', function() {
        var filtro = $(this).val();
        filtrarEspecialidades(filtro);
    });

    // Event listener para duplo clique no select (adiciona a especialidade)
    $('#C_especialidade_select').on('dblclick', function() {
        $('#btn_adicionar_especialidade').click();
    });

    // Configurar drag and drop para especialidades
    configurarDragAndDrop();

    // Adicionar especialidade
    $('#btn_adicionar_especialidade').click(async function() {
        var id_especialidade = $('#C_especialidade_select').val();
        var nome_especialidade = $('#C_especialidade_select option:selected').text();
        var operation = $('#operation').val();
        var id_profissional = $('#C_idx').val();

        console.log(">>> Tentativa de adicionar especialidade");
        console.log("Operation:", operation);
        console.log("ID Profissional:", id_profissional);
        console.log("ID Especialidade:", id_especialidade);

        if (!id_especialidade) {
            alert_msg('warning', 'Selecione uma especialidade');
            return;
        }

        // VERIFICAÇÃO CRÍTICA: Se estiver em modo Update, garantir que as especialidades estão sincronizadas
        if (operation === 'Update' && id_profissional) {
            console.log(">>> VERIFICAÇÃO DE SINCRONIZAÇÃO <<<");
            console.log("Especialidades atuais no array:", especialidadesTemporarias);
            
            // Recarregar especialidades do banco para garantir sincronização
            await $.ajax({
                url: "pages/profissionais/profissionais_especialidades_listar.php",
                method: "POST",
                data: { id_profissional: id_profissional },
                dataType: "json",
                success: function(especialidadesDoServidor) {
                    console.log("Especialidades do servidor:", especialidadesDoServidor);
                    
                    // Verificar se o array local está desatualizado
                    if (especialidadesDoServidor.length !== especialidadesTemporarias.length) {
                        console.warn("ATENÇÃO: Array local desatualizado!");
                        console.log("Servidor tem:", especialidadesDoServidor.length, "especialidades");
                        console.log("Array local tem:", especialidadesTemporarias.length, "especialidades");
                        
                        // Sincronizar array local com o servidor
                        especialidadesTemporarias = especialidadesDoServidor;
                        atualizarListaEspecialidadesTemporarias();
                        console.log("Array sincronizado com servidor");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Erro ao verificar sincronização:", error);
                    alert_msg('danger', 'Erro ao verificar especialidades existentes');
                    return;
                }
            }).catch(function () {});
        }

        // Verificar se já foi adicionada
        var jaExiste = especialidadesTemporarias.some(function(esp) {
            return esp.id_especialidade == id_especialidade;
        });

        if (jaExiste) {
            alert_msg('warning', 'Esta especialidade já foi adicionada');
            return;
        }

        // Adicionar à lista temporária
        especialidadesTemporarias.push({
            id_especialidade: id_especialidade,
            nome_especialidade: nome_especialidade
        });

        console.log("Especialidade adicionada ao array temporário:", especialidadesTemporarias);
        console.log("Total de especialidades agora:", especialidadesTemporarias.length);
        
        atualizarListaEspecialidadesTemporarias();
        $('#C_especialidade_select').val('');
        $('#C_filtro_especialidade').val(''); // Limpar filtro
        exibirEspecialidades(todasEspecialidades); // Mostrar todas novamente

        // Se estiver editando um profissional existente, salvar imediatamente
        if (operation === 'Update' && id_profissional && !salvandoEspecialidades) {
            console.log("Adicionando especialidade e salvando para profissional:", id_profissional);
            console.log("Especialidades que serão enviadas para salvamento:", especialidadesTemporarias);
            especialidadesJaSalvasAutomaticamente = true; // Marcar que foram salvas automaticamente
            salvarEspecialidadesProfissional(id_profissional, 'especialidades_apenas');
        }
    });

    // Remover especialidade
    $(document).on('click', '.tag-remove', async function() {
        var id_especialidade = $(this).data('id');
        var operation = $('#operation').val();
        var id_profissional = $('#C_idx').val();
        
        console.log("Removendo especialidade ID:", id_especialidade);
        console.log("Especialidades antes da remoção:", especialidadesTemporarias);
        
        // VERIFICAÇÃO CRÍTICA: Se estiver em modo Update, garantir que as especialidades estão sincronizadas
        if (operation === 'Update' && id_profissional) {
            console.log(">>> VERIFICAÇÃO DE SINCRONIZAÇÃO ANTES DA REMOÇÃO <<<");
            
            // Recarregar especialidades do banco para garantir sincronização
            await $.ajax({
                url: "pages/profissionais/profissionais_especialidades_listar.php",
                method: "POST",
                data: { id_profissional: id_profissional },
                dataType: "json",
                success: function(especialidadesDoServidor) {
                    console.log("Especialidades do servidor antes da remoção:", especialidadesDoServidor);
                    
                    // Verificar se o array local está desatualizado
                    if (especialidadesDoServidor.length !== especialidadesTemporarias.length) {
                        console.warn("ATENÇÃO: Array local desatualizado antes da remoção!");
                        console.log("Servidor tem:", especialidadesDoServidor.length, "especialidades");
                        console.log("Array local tem:", especialidadesTemporarias.length, "especialidades");
                        
                        // Sincronizar array local com o servidor
                        especialidadesTemporarias = especialidadesDoServidor;
                        console.log("Array sincronizado com servidor antes da remoção");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Erro ao verificar sincronização antes da remoção:", error);
                    alert_msg('danger', 'Erro ao verificar especialidades existentes');
                    return;
                }
            }).catch(function () {});
        }
        
        especialidadesTemporarias = especialidadesTemporarias.filter(function(esp) {
            return esp.id_especialidade != id_especialidade;
        });
        
        console.log("Especialidades após remoção:", especialidadesTemporarias);
        console.log("Total de especialidades agora:", especialidadesTemporarias.length);
        atualizarListaEspecialidadesTemporarias();

        // Se estiver editando um profissional existente, salvar imediatamente
        if (operation === 'Update' && id_profissional && !salvandoEspecialidades) {
            console.log("Removendo especialidade e salvando para profissional:", id_profissional);
            console.log("Especialidades que serão enviadas após remoção:", especialidadesTemporarias);
            especialidadesJaSalvasAutomaticamente = true; // Marcar que foram salvas automaticamente
            salvarEspecialidadesProfissional(id_profissional, 'especialidades_apenas');
        }
    });

    $('#btnInserir').click(function(){
        console.log("=== INICIANDO NOVO CADASTRO ===");
        console.log("Especialidades temporárias ANTES de limpar:", especialidadesTemporarias);
        
        // 1. PRIMEIRO: Definir operation como Add
        $('#operation').val("Add");
        console.log("Operation definida como:", $('#operation').val());
        
        // 2. SEGUNDO: Limpar todos os campos do formulário
        $('#C_id_profissional').val("");
        $('#C_idx').val("");
        $('#C_nome_profissional').val("");
        $('#C_contato_nome1').val("");
        $('#C_cel_telefone1').val("");
        $('#C_contato_nome2').val("");
        $('#C_cel_telefone2').val("");
        $('#rotulo_associado').text("Cadastrando");
        
        // 3. TERCEIRO: Reset flags
        especialidadesJaSalvasAutomaticamente = false;
        
        // 4. QUARTO: LIMPEZA MÚLTIPLA E SEQUENCIAL
        console.log(">>> EXECUTANDO LIMPEZA MÚLTIPLA...");
        
        // Limpeza 1: Usar função dedicada
        limparEspecialidadesTemporarias();
        
        // Limpeza 2: Forçar novamente após delay
        setTimeout(function() {
            console.log(">>> LIMPEZA SEGUNDA FASE");
            console.log("Array antes da segunda limpeza:", especialidadesTemporarias);
            
            especialidadesTemporarias.length = 0;
            especialidadesTemporarias = [];
            
            if (window.especialidadesTemporarias) {
                window.especialidadesTemporarias = [];
            }
            
            $('#lista_especialidades').empty().html('').text('');
            $('#C_especialidade_select').val('');
            $('#C_filtro_especialidade').val('');
            
            console.log("Array após segunda limpeza:", especialidadesTemporarias);
            
            // 5. QUINTO: Carregar dados disponíveis
            carregarEspecialidadesDisponiveis();
            
            // 6. SEXTO: Aguardar mais um pouco e mostrar modal
            setTimeout(function() {
                console.log(">>> VERIFICAÇÃO FINAL ANTES DE ABRIR MODAL");
                console.log("Operation:", $('#operation').val());
                console.log("Array final:", especialidadesTemporarias);
                
                if (especialidadesTemporarias.length > 0) {
                    console.error("ERRO CRÍTICO: Array ainda contém especialidades!");
                    // Limpeza de emergência
                    especialidadesTemporarias = [];
                    $('#lista_especialidades').empty().html('');
                }
                
                $('#ModalEditaProfissional').modal('show');
                
                // Focar no campo nome após abrir o modal
                $('#ModalEditaProfissional').on('shown.bs.modal.focus-nome', function() {
                    $(this).off('shown.bs.modal.focus-nome'); // Remove this specific handler
                    $('#C_nome_profissional').focus();
                });
            }, 100);
        }, 50);
    });

    $(document).on('submit', '#frmprofissional', function(event){
        event.preventDefault();
        
        // Evitar múltiplos cliques simultâneos no botão salvar
        if (window.salvandoProfissional) {
            console.log("Salvamento já em andamento, ignorando...");
            return;
        }
        
        window.salvandoProfissional = true;
        
        // Timeout de segurança para liberar flag caso algo dê errado
        setTimeout(function() {
            if (window.salvandoProfissional) {
                console.log("Liberando flag de salvamento por timeout de segurança");
                window.salvandoProfissional = false;
            }
        }, 15000);
        
        var error = '';
        var C_nome_profissional = $('#C_nome_profissional').val();
        
        if(C_nome_profissional == ''){
            error += 'Nome Profissional é obrigatório ';
        }

        if(error == ''){
            $.ajax({
                url:"pages/profissionais/profissionais_verifica_repitido.php",
                method:"POST",
                data:$(this).serialize(),
                success:function(data){
                    if(data.trim() == 'Nao'){
                        $.ajax({
                            url:"pages/profissionais/profissionais_salvar.php",
                            method:"POST",
                            data:$('#frmprofissional').serialize(),
                            success:function(data){
                                console.log("Resposta do salvamento do profissional:", data);
                                if(data.includes('cadastrado') || data.includes('atualizado')){
                                    // Extrair ID do profissional da resposta
                                    var partes = data.split('|');
                                    var tipo = partes[0];
                                    var id_profissional = partes[1];
                                    
                                    console.log("Tipo:", tipo, "ID Profissional:", id_profissional);
                                    console.log("Especialidades temporárias length:", especialidadesTemporarias.length);
                                    console.log("Operation:", $('#operation').val());
                                    console.log("Especialidades para salvar:", especialidadesTemporarias);
                                    
                                    // Para operações de Update, apenas finalizar se as especialidades já foram salvas automaticamente
                                    if ($('#operation').val() === 'Update') {
                                        console.log("Modo Update - finalizando salvamento");
                                        finalizarSalvamento(tipo);
                                    } else {
                                        // Para operações de Add (novo profissional), salvar especialidades se houver
                                        if (especialidadesTemporarias.length > 0) {
                                            console.log("Modo Add - salvando especialidades para o novo profissional");
                                            console.log("Especialidades que serão salvas:", especialidadesTemporarias);
                                            salvarEspecialidadesProfissional(id_profissional, tipo);
                                        } else {
                                            console.log("Nenhuma especialidade para salvar - finalizando");
                                            finalizarSalvamento(tipo);
                                        }
                                    }
                                } else {
                                    alert_msg('danger', 'Erro ao salvar dados: ' + data);
                                    // Liberar flag em caso de erro
                                    window.salvandoProfissional = false;
                                }
                            },
                            error: function(xhr, status, error) {
                                console.log("Erro ao salvar dados:", error);
                                console.log("Response:", xhr.responseText);
                                alert_msg('danger', 'Erro ao salvar dados');
                                // Liberar flag em caso de erro
                                window.salvandoProfissional = false;
                            }
                        });
                    } else {
                        console.log("Profissional já cadastrado - dados:", data);
                        alert_msg('danger','Profissional já cadastrado');
                        // Liberar flag em caso de duplicata
                        window.salvandoProfissional = false;
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Erro na verificação de duplicata:", error);
                    console.log("Response:", xhr.responseText);
                    alert_msg('danger', 'Erro ao verificar duplicata');
                    // Liberar flag em caso de erro
                    window.salvandoProfissional = false;
                }
            });
        } else {
            alert_msg('danger', error);
            // Liberar flag em caso de erro de validação
            window.salvandoProfissional = false;
        }
    });

    // Salvar especialidades do profissional
    function salvarEspecialidadesProfissional(id_profissional, tipo) {
        console.log("=== INÍCIO salvarEspecialidadesProfissional ===");
        console.log("ID Profissional recebido:", id_profissional);
        console.log("Tipo:", tipo);
        console.log("Array especialidadesTemporarias:", especialidadesTemporarias);
        
        // Evitar múltiplas submissões simultâneas
        if (salvandoEspecialidades) {
            console.log("Salvamento já em andamento, ignorando...");
            return;
        }

        salvandoEspecialidades = true;
        
        var especialidades_ids = especialidadesTemporarias.map(function(esp) {
            return esp.id_especialidade;
        });

        console.log("Salvando especialidades para profissional:", id_profissional);
        console.log("Especialidades IDs extraídos:", especialidades_ids);
        console.log("Especialidades temporárias completas:", especialidadesTemporarias);

        $.ajax({
            url: "pages/profissionais/profissionais_especialidades_salvar.php",
            method: "POST",
            data: {
                id_profissional: id_profissional,
                especialidades: JSON.stringify(especialidades_ids)
            },
            success: function(response) {
                console.log("=== RESPOSTA do salvamento de especialidades ===");
                console.log("Response completa:", response);
                console.log("Response trimmed:", response.trim());
                console.log("Response lowercase:", response.trim().toLowerCase());
                
                if (response.trim().toLowerCase() === 'sucesso') {
                    console.log("Especialidades salvas com SUCESSO!");
                    
                    // VERIFICAÇÃO PÓS-SALVAMENTO: Confirmar que todas as especialidades foram salvas
                    setTimeout(function() {
                        console.log("=== VERIFICAÇÃO PÓS-SALVAMENTO ===");
                        $.ajax({
                            url: "pages/profissionais/profissionais_especialidades_listar.php",
                            method: "POST",
                            data: { id_profissional: id_profissional },
                            dataType: "json",
                            success: function(especialidadesPosSalvamento) {
                                console.log("Especialidades no banco após salvamento:", especialidadesPosSalvamento);
                                console.log("Quantidade no banco:", especialidadesPosSalvamento.length);
                                console.log("Quantidade enviada:", especialidades_ids.length);
                                
                                if (especialidadesPosSalvamento.length !== especialidades_ids.length) {
                                    console.error("ERRO: Quantidade no banco diferente da enviada!");
                                    console.log("Enviadas:", especialidades_ids);
                                    console.log("No banco:", especialidadesPosSalvamento.map(e => e.id_especialidade));
                                    alert_msg('warning', 'Possível problema na sincronização das especialidades');
                                } else {
                                    console.log("✓ Verificação pós-salvamento: OK - todas as especialidades foram salvas");
                                }
                            },
                            error: function() {
                                console.log("Erro na verificação pós-salvamento");
                            }
                        });
                    }, 500);
                    
                    if (tipo === 'especialidades_apenas') {
                        alert_msg('success', 'Especialidades atualizadas com sucesso!');
                        // Não é necessário recarregar, pois as especialidades já estão atualizadas no array temporário
                    } else {
                        console.log("Finalizando salvamento após especialidades...");
                        finalizarSalvamento(tipo);
                    }
                } else {
                    console.log("ERRO no salvamento de especialidades:", response);
                    alert_msg('danger', 'Erro ao salvar especialidades: ' + response);
                }
            },
            error: function(xhr, status, error) {
                console.log("=== ERRO AJAX ao salvar especialidades ===");
                console.log("Status:", status);
                console.log("Error:", error);
                console.log("Response Text:", xhr.responseText);
                console.log("Response Status:", xhr.status);
                alert_msg('danger', 'Erro ao salvar especialidades');
            },
            complete: function() {
                console.log("=== FINALIZANDO salvarEspecialidadesProfissional ===");
                salvandoEspecialidades = false; // Liberar flag
            }
        });
    }

    // Finalizar salvamento
    function finalizarSalvamento(tipo) {
        console.log("=== FINALIZANDO SALVAMENTO ===");
        console.log("Tipo:", tipo);
        
        // Reset flags
        especialidadesJaSalvasAutomaticamente = false;
        
        $('#frmprofissional')[0].reset();
        
        if (tipo === 'cadastrado') {
            alert_msg('success', 'Cadastro realizado com sucesso!!');
        } else {
            alert_msg('success', 'Dados atualizados com sucesso!!');
        }
        
        // Fechar modal primeiro
        $('#ModalEditaProfissional').modal('hide');
        
        // Aguardar modal fechar completamente antes de atualizar tabela
        $('#ModalEditaProfissional').on('hidden.bs.modal.reload', function() {
            $(this).off('hidden.bs.modal.reload'); // Remove this specific handler
            
            console.log("Modal fechado - iniciando atualização do DataTable...");
            
            // Múltiplas tentativas de atualização para garantir
            console.log("Tentativa 1: ajax.reload()");
            dataTable.ajax.reload(function() {
                console.log("DataTable.ajax.reload() executado com sucesso");
            }, false);
            
            // Tentativa 2 após delay
            setTimeout(function() {
                console.log("Tentativa 2: draw()");
                dataTable.draw();
            }, 300);
            
            // Tentativa 3 forçada após delay maior
            setTimeout(function() {
                console.log("Tentativa 3: ajax.reload() forçado");
                dataTable.ajax.reload(null, true); // true = reset pagination
            }, 600);
        });
        
        // Trigger manual do evento caso não seja acionado automaticamente
        setTimeout(function() {
            $('#ModalEditaProfissional').trigger('hidden.bs.modal.reload');
        }, 100);
        
        // Liberar flag de proteção
        window.salvandoProfissional = false;
        
        // Adicionar botão manual de reload para debug (temporário)
        console.log("Para teste manual, use: dataTable.ajax.reload()");
        
        // Verificar se a instância do DataTable ainda é válida
        if (dataTable && typeof dataTable.ajax !== 'undefined') {
            console.log("DataTable instance é válida");
        } else {
            console.log("ERRO: DataTable instance inválida!");
        }
    }

    $(document).on('click', '.update_profissional', function(){
        var profissional_id = $(this).attr("id");
        console.log("Clicando em update profissional, ID:", profissional_id);
        
        // Limpar array antes de carregar novo profissional
        console.log("=== INICIANDO EDIÇÃO ===");
        console.log("Especialidades temporárias ANTES de limpar:", especialidadesTemporarias);
        limparEspecialidadesTemporarias();
        
        $.ajax({
            url:"pages/profissionais/profissionais_exibe.php",
            method:"POST",
            data:{id:profissional_id},
            dataType:"json",
            success:function(data){
                console.log("Dados recebidos do PHP:", data);
                $('#C_id_profissional').val(data.id_profissional);
                $('#C_idx').val(data.id_profissional);
                $('#C_nome_profissional').val(data.nome_profissional);
                $('#C_contato_nome1').val(data.contato_nome1 || '');
                $('#C_cel_telefone1').val(data.cel_telefone1 || '');
                $('#C_contato_nome2').val(data.contato_nome2 || '');
                $('#C_cel_telefone2').val(data.cel_telefone2 || '');
                $('#operation').val("Update");
                $('#rotulo_associado').text("Alterando");
                
                // Reset flags
                especialidadesJaSalvasAutomaticamente = false;
                
                console.log("Valores definidos nos campos:");
                console.log("Nome:", data.nome_profissional);
                console.log("Contato 1:", data.contato_nome1);
                console.log("Telefone 1:", data.cel_telefone1);
                console.log("Contato 2:", data.contato_nome2);
                console.log("Telefone 2:", data.cel_telefone2);

                
                // Limpar array novamente antes de carregar
                limparEspecialidadesTemporarias();
                
                // Carregar especialidades
                $('#C_filtro_especialidade').val(''); // Limpar filtro
                carregarEspecialidadesDisponiveis();
                
                // Usar setTimeout para garantir que a limpeza ocorra antes do carregamento
                setTimeout(async function() {
                    // Limpeza adicional antes de carregar especialidades
                    limparEspecialidadesTemporarias();
                    await carregarEspecialidadesVinculadas(data.id_profissional);
                    $('#ModalEditaProfissional').modal('show');
                }, 100);
            },
            error: function(xhr, status, error) {
                console.log("Erro ao carregar dados do profissional:", error);
                console.log("Response:", xhr.responseText);
                alert_msg('danger', 'Erro ao carregar dados do profissional');
            }
        });
    });

    // Limpar array de especialidades quando o modal for fechado
    $('#ModalEditaProfissional').on('hidden.bs.modal', function () {
        console.log("Modal fechado - limpando especialidades temporárias");
        limparEspecialidadesTemporarias();
        $('#C_filtro_especialidade').val('');
    });

    // Limpar também quando o modal for mostrado (segurança adicional)
    $('#ModalEditaProfissional').on('show.bs.modal', function () {
        console.log("Modal sendo aberto - verificando estado das especialidades");
        console.log("Operation atual:", $('#operation').val());
        console.log("Especialidades no momento da abertura:", especialidadesTemporarias);
    });

    $(document).on('click', '.delete_profissional', function(){
        var profissional_id = $(this).attr("id");
        var profissional_nome = $(this).closest('tr').find('td').eq(1).text();
        
        // Criar modal de confirmação personalizado com foco no botão "Não"
        var modalHtml = '<div class="modal fade" id="modalConfirmDelete" tabindex="-1" role="dialog">' +
            '<div class="modal-dialog" role="document">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<h4 class="modal-title">Confirmar Exclusão</h4>' +
            '</div>' +
            '<div class="modal-body">' +
            '<p><strong>Deseja realmente excluir o profissional:</strong></p>' +
            '<p style="color: #d9534f; font-weight: bold;">' + profissional_nome + '</p>' +
            '<p>Esta ação não pode ser desfeita!</p>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn-primary" id="btnNaoExcluir" data-dismiss="modal">Não</button>' +
            '<button type="button" class="btn btn-danger" id="btnSimExcluir">Sim, Excluir</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
        
        // Remover modal anterior se existir
        $('#modalConfirmDelete').remove();
        
        // Adicionar modal ao body
        $('body').append(modalHtml);
        
        // Mostrar modal e focar no botão "Não"
        $('#modalConfirmDelete').modal('show');
        $('#modalConfirmDelete').on('shown.bs.modal', function() {
            $('#btnNaoExcluir').focus();
        });
        
        // Event listener para o botão "Sim, Excluir"
        $('#btnSimExcluir').off('click').on('click', function() {
            console.log("Verificando se profissional pode ser excluído, ID:", profissional_id);
            
            // Verificar se o profissional está vinculado a convênios
            $.ajax({
                url: "pages/profissionais/profissionais_verificar_vinculo.php",
                method: "POST",
                data: {id_profissional: profissional_id},
                success: function(response) {
                    console.log("Resposta da verificação de vínculo:", response);
                    
                    if (response.trim() === "vinculado") {
                        $('#modalConfirmDelete').modal('hide');
                        alert_msg('danger', 'Não é possível excluir este profissional pois ele está vinculado a um ou mais convênios!');
                    } else if (response.trim() === "nao_vinculado") {
                        // Pode excluir
                        $.ajax({
                            url: "pages/profissionais/profissionais_deletar.php",
                            method: "POST",
                            data: {id_profissional: profissional_id},
                            success: function(deleteResponse) {
                                console.log("Resposta da exclusão:", deleteResponse);
                                $('#modalConfirmDelete').modal('hide');
                                
                                if (deleteResponse.trim() === "deletado") {
                                    alert_msg('success', 'Profissional excluído com sucesso!');
                                    dataTable.ajax.reload();
                                } else {
                                    alert_msg('danger', 'Erro ao excluir profissional: ' + deleteResponse);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.log("Erro ao excluir profissional:", error);
                                $('#modalConfirmDelete').modal('hide');
                                alert_msg('danger', 'Erro ao excluir profissional!');
                            }
                        });
                    } else {
                        $('#modalConfirmDelete').modal('hide');
                        alert_msg('danger', 'Erro ao verificar vínculos do profissional: ' + response);
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Erro na verificação de vínculo:", error);
                    $('#modalConfirmDelete').modal('hide');
                    alert_msg('danger', 'Erro ao verificar vínculos do profissional!');
                }
            });
        });
        
        // Remover modal quando fechado
        $('#modalConfirmDelete').on('hidden.bs.modal', function() {
            $('#modalConfirmDelete').remove();
        });
    });

});

// Função de DEBUG global para verificar estado das especialidades
window.debugEspecialidades = function() {
    console.log("=== DEBUG ESPECIALIDADES ===");
    console.log("Operation atual:", $('#operation').val());
    console.log("ID Profissional:", $('#C_idx').val());
    
    // Extrair especialidades do DOM
    var especialidadesAtual = [];
    var container = $('#lista_especialidades');
    var tags = container.find('.tag-item');
    console.log("Tags encontradas no DOM:", tags.length);
    
    tags.each(function() {
        var nome = $(this).find('span').text();
        var id = $(this).find('.tag-remove').data('id');
        especialidadesAtual.push({id_especialidade: id, nome_especialidade: nome});
    });
    
    console.log("Especialidades extraídas do DOM:", especialidadesAtual);
    
    var id_profissional = $('#C_idx').val();
    if (id_profissional) {
        console.log("Consultando banco de dados...");
        $.ajax({
            url: "pages/profissionais/profissionais_especialidades_listar.php",
            method: "POST",
            data: { id_profissional: id_profissional },
            dataType: "json",
            success: function(dadosBanco) {
                console.log("Especialidades no banco:", dadosBanco);
                console.log("Quantidade no banco:", dadosBanco.length);
                
                console.log("=== COMPARAÇÃO ===");
                console.log("DOM tem:", especialidadesAtual.length);
                console.log("Banco tem:", dadosBanco.length);
                
                if (especialidadesAtual.length === dadosBanco.length) {
                    console.log("✓ Sincronizado");
                } else {
                    console.warn("✗ Dessincronizado!");
                    console.log("No DOM:", especialidadesAtual.map(e => e.nome_especialidade));
                    console.log("No banco:", dadosBanco.map(e => e.nome_especialidade));
                }
            },
            error: function() {
                console.error("Erro ao consultar banco");
            }
        });
    } else {
        console.log("Nenhum profissional selecionado");
    }
};

function alert_msg(tipo, msg){
    $('#alert_mensagem').removeClass();
    $('#alert_mensagem').addClass('alert alert-'+tipo+'').html(msg);
    $('#alert_mensagem').show();
    setTimeout(function(){
        $('#alert_mensagem').fadeOut('slow');
    }, 3000);
} 