-- Fix for the sind.insert_estorno() trigger
-- The trigger was trying to access old.id_divisao but the field in sind.conta is named 'divisao'

-- First, let's drop the existing trigger and function
DROP TRIGGER IF EXISTS trigger_insert_estorno ON sind.conta;
DROP FUNCTION IF EXISTS sind.insert_estorno();

-- Create the corrected function
CREATE OR REPLACE FUNCTION sind.insert_estorno()
RETURNS TRIGGER AS $$
BEGIN
    -- Insert into estornos table when a record is deleted from conta
    INSERT INTO sind.estornos(
        lancamento, associado, convenio, valor, data, hora, descricao, mes, 
        empregador, funcionario, parcela, ip_convenio, mac_adress, exclui, 
        user_exclui, uri_cupom, tipo, id_situacao, data_estorno, hora_estorno, 
        data_fatura, uuid_conta, id_divisao
    )
    VALUES (
        OLD.lancamento, OLD.associado, OLD.convenio, OLD.valor, OLD.data, OLD.hora, 
        OLD.descricao, OLD.mes, OLD.empregador, OLD.funcionario, OLD.parcela, 
        OLD.ip_convenio, OLD.mac_adress, OLD.exclui, OLD.user_exclui, OLD.uri_cupom, 
        OLD.tipo, OLD.id_situacao, CURRENT_DATE, CURRENT_TIME, OLD.data_fatura, 
        OLD.uuid_conta, OLD.divisao  -- Changed from OLD.id_divisao to OLD.divisao
    );
    
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

-- Recreate the trigger
CREATE TRIGGER trigger_insert_estorno
    BEFORE DELETE ON sind.conta
    FOR EACH ROW
    EXECUTE FUNCTION sind.insert_estorno();

-- Verify the trigger was created successfully
SELECT 
    trigger_name, 
    event_manipulation, 
    action_timing,
    action_statement
FROM information_schema.triggers 
WHERE event_object_table = 'conta' 
AND event_object_schema = 'sind'
AND trigger_name = 'trigger_insert_estorno';
