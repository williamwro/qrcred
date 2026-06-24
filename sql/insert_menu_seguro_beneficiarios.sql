-- Script SQL para adicionar o item de menu "Seguro Beneficiários"
-- Autor: Sistema QRCred
-- Data: 2026-05-21

-- Inserir novo item de menu principal "Seguro Beneficiários"
-- O próximo ID disponível é 47 (baseado no último ID 46)
-- parent_id = 0 significa que é um item de menu principal (nível 0)
-- menu_order = 44 (próximo na sequência)
-- status = 1 (ativo)
-- level = 0 (menu principal, não é submenu)

INSERT INTO sind.dynamic_menu 
    (parent_id, title, url, menu_order, status, level, icon, description, id)
VALUES 
    (0, 'Seguro Beneficiários', 'link_seguro_beneficiarios', 44, 1, 0, 'fa fa-users', 'Seguro Beneficiários', 47);

-- Verificar se o registro foi inserido corretamente
SELECT id, parent_id, title, url, menu_order, level, icon, description 
FROM sind.dynamic_menu 
WHERE id = 47;

-- IMPORTANTE: Após executar este script, você precisa:
-- 1. Criar a página HTML: Adm/pages/seguro-beneficiarios/seguro_beneficiarios_read.html
-- 2. Criar o arquivo JavaScript: Adm/pages/seguro-beneficiarios/js/seguro_beneficiarios_script.js
-- 3. Adicionar permissão para os usuários na tabela sind.usuarios_menu
-- 4. Atualizar o arquivo Adm/index.js para carregar a página quando o link for clicado

-- Para dar permissão a um usuário específico (exemplo: codigo_usuario = 1):
-- INSERT INTO sind.usuarios_menu (codigo_usuario, id_menu, status)
-- VALUES (1, 47, 1);
