-- ========================================================
-- SISTEMA DE NOTIFICAÇÃO EM TEMPO REAL
-- Trigger PostgreSQL + LISTEN/NOTIFY + Server-Sent Events
-- ========================================================

-- 1. Criar função que será executada pelo trigger
CREATE OR REPLACE FUNCTION notify_new_signature()
RETURNS TRIGGER AS $$
DECLARE
    notification_payload JSON;
BEGIN
    -- Criar payload com dados relevantes da nova assinatura
    notification_payload = json_build_object(
        'event_type', 'new_signature',
        'timestamp', EXTRACT(EPOCH FROM CURRENT_TIMESTAMP),
        'data', json_build_object(
            'id', NEW.id,
            'codigo', NEW.codigo,
            'nome', NEW.nome,
            'celular', NEW.celular,
            'email', NEW.email,
            'cpf', NEW.cpf,
            'autorizado', NEW.autorizado,
            'aceitou_termo', NEW.aceitou_termo,
            'has_signed', NEW.has_signed,
            'event', NEW.event,
            'doc_token', NEW.doc_token,
            'doc_name', NEW.doc_name,
            'signed_at', NEW.signed_at,
            'data_hora', NEW.data_hora
        )
    );

    -- Enviar notificação via NOTIFY
    PERFORM pg_notify('new_assinatura_digital', notification_payload::text);
    
    -- Log para debug (opcional)
    RAISE NOTICE 'Notificação enviada: %', notification_payload;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 2. Criar trigger na tabela associados_sasmais
DROP TRIGGER IF EXISTS trigger_notify_new_signature ON sind.associados_sasmais;

CREATE TRIGGER trigger_notify_new_signature
    AFTER INSERT ON sind.associados_sasmais
    FOR EACH ROW
    EXECUTE FUNCTION notify_new_signature();

-- 3. Criar trigger para atualizações importantes (quando status muda)
CREATE OR REPLACE FUNCTION notify_signature_update()
RETURNS TRIGGER AS $$
DECLARE
    notification_payload JSON;
BEGIN
    -- Notificar apenas mudanças importantes (autorizado, has_signed, aceitou_termo)
    IF (OLD.autorizado != NEW.autorizado) OR 
       (OLD.has_signed != NEW.has_signed) OR 
       (OLD.aceitou_termo != NEW.aceitou_termo) THEN
       
        notification_payload = json_build_object(
            'event_type', 'signature_updated',
            'timestamp', EXTRACT(EPOCH FROM CURRENT_TIMESTAMP),
            'data', json_build_object(
                'id', NEW.id,
                'codigo', NEW.codigo,
                'nome', NEW.nome,
                'changes', json_build_object(
                    'autorizado', json_build_object('old', OLD.autorizado, 'new', NEW.autorizado),
                    'has_signed', json_build_object('old', OLD.has_signed, 'new', NEW.has_signed),
                    'aceitou_termo', json_build_object('old', OLD.aceitou_termo, 'new', NEW.aceitou_termo)
                )
            )
        );

        PERFORM pg_notify('update_assinatura_digital', notification_payload::text);
        RAISE NOTICE 'Atualização notificada: %', notification_payload;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 4. Criar trigger para updates
DROP TRIGGER IF EXISTS trigger_notify_signature_update ON sind.associados_sasmais;

CREATE TRIGGER trigger_notify_signature_update
    AFTER UPDATE ON sind.associados_sasmais
    FOR EACH ROW
    EXECUTE FUNCTION notify_signature_update();

-- 5. Verificar se triggers foram criados corretamente
SELECT 
    trigger_name, 
    event_manipulation, 
    action_timing,
    action_statement
FROM information_schema.triggers 
WHERE event_object_table = 'associados_sasmais' 
AND event_object_schema = 'sind';

-- ========================================================
-- INSTRUÇÕES DE USO:
-- ========================================================

-- Para testar manualmente:
-- SELECT pg_notify('new_assinatura_digital', '{"test": true, "message": "Teste de notificação"}');

-- Para verificar listeners ativos:
-- SELECT pid, application_name, state, query FROM pg_stat_activity WHERE query LIKE '%LISTEN%';

-- Para remover triggers (se necessário):
-- DROP TRIGGER IF EXISTS trigger_notify_new_signature ON sind.associados_sasmais;
-- DROP TRIGGER IF EXISTS trigger_notify_signature_update ON sind.associados_sasmais;
-- DROP FUNCTION IF EXISTS notify_new_signature();
-- DROP FUNCTION IF EXISTS notify_signature_update();

-- ========================================================
-- PRÓXIMOS PASSOS:
-- ========================================================
-- 1. Execute este script no PostgreSQL
-- 2. Configure realtime_notifications.php
-- 3. Integre realtime_notifications.js na página HTML
-- 4. Teste o sistema completo 