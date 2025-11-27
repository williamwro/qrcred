-- Script para adicionar os campos usuario e senha na tabela sind.empregador
-- Execute este script no banco de dados PostgreSQL

-- Adicionar campo usuario
ALTER TABLE sind.empregador ADD COLUMN IF NOT EXISTS usuario character varying(50);

-- Adicionar campo senha
ALTER TABLE sind.empregador ADD COLUMN IF NOT EXISTS senha character varying(6);

-- Criar índices únicos para garantir que não haja duplicatas
-- (apenas para valores não nulos)
CREATE UNIQUE INDEX IF NOT EXISTS idx_empregador_usuario 
ON sind.empregador (usuario) 
WHERE usuario IS NOT NULL AND usuario != '';

CREATE UNIQUE INDEX IF NOT EXISTS idx_empregador_senha 
ON sind.empregador (senha) 
WHERE senha IS NOT NULL AND senha != '';
