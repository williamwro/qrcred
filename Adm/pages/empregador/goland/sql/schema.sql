CREATE TABLE IF NOT EXISTS empregador
(
    id bigint NOT NULL,
    nome character varying(50),
    responsavel character varying(50),
    telefone character varying(30),
    abreviacao character varying(50),
    divisao integer,
    CONSTRAINT empregador_pkey PRIMARY KEY (Id),
    CONSTRAINT divisao_fk FOREIGN KEY (divisao)
        REFERENCES divisao (id_divisao));

CREATE TABLE IF NOT EXISTS divisao
(
    nome character varying(50) NOT NULL,
    cidade character varying(50) NOT NULL,
    id_divisao bigint NOT NULL,
    descricao character varying,
    CONSTRAINT divisao_pkey PRIMARY KEY (id_divisao)
)
