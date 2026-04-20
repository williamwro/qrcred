-- Adicionar campo data_pretendida na tabela sind.agendamento
-- Este campo armazenará a data pretendida para o agendamento

ALTER TABLE sind.agendamento 
ADD COLUMN IF NOT EXISTS data_pretendida TIMESTAMP WITH TIME ZONE;

-- Comentário explicativo
COMMENT ON COLUMN sind.agendamento.data_pretendida IS 'Data pretendida para o agendamento (escolhida pelo usuário)';

-- Verificar se o campo foi criado
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_schema = 'sind' 
  AND table_name = 'agendamento' 
  AND column_name = 'data_pretendida';
