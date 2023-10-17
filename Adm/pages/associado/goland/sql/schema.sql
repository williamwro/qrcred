CREATE TABLE IF NOT EXISTS associado
(
    codigo character varying(13) NOT NULL,
    nome character varying(50),
    endereco character varying(50),
    numero character varying(25),
    nascimento timestamp with time zone,
    salario double precision,
    limite double precision,
    empregador integer NOT NULL,
    cep character varying(9),
    telres character varying(13),
    telcom character varying(13),
    cel character varying(15),
    bairro character varying(60),
    id bigint NOT NULL,
    complemento character varying(20),
    cidade character varying(50),
    foto bytea,
    rg character varying(20),
    cpf character varying(14),
    funcao integer,
    filiado boolean,
    obs text,
    id_situacao integer,
    data_filiacao timestamp with time zone,
    data_desfiliacao timestamp with time zone,
    email character varying(50),
    tipo integer,
    codigo_isa character varying(50),
    parcelas_permitidas integer,
    uf character varying(2),
    celwatzap boolean DEFAULT false,
    token_associado character varying,
    cartao_entregue boolean,
    data_entreg_cartao timestamp with time zone,
    ultimo_mes character varying,
    id_divisao integer,
    id_secretaria integer,
    localizacao character varying,
    CONSTRAINT associado_pkey PRIMARY KEY (codigo, empregador),
    CONSTRAINT unique_associado UNIQUE (codigo, empregador),
    CONSTRAINT empregador_fk FOREIGN KEY (empregador)
        REFERENCES sind.empregador (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE NO ACTION
        NOT VALID,
    CONSTRAINT funcao_fk FOREIGN KEY (funcao)
        REFERENCES sind.funcao (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE NO ACTION
        NOT VALID
);

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
        REFERENCES divisao (id_divisao)
);

CREATE TABLE IF NOT EXISTS secretarias
(
    id_secretaria bigint NOT NULL,
    nome_secretaria character varying,
    descri character varying,
    CONSTRAINT secretarias_pkey PRIMARY KEY (id_secretaria)
);

CREATE TABLE IF NOT EXISTS tipo_associado
(
    id_tipo_associado bigint NOT NULL,
    nome character varying(50),
    CONSTRAINT tipo_associado_pkey PRIMARY KEY (id_tipo_associado)
);

CREATE TABLE IF NOT EXISTS situacao_associado
(
    codigo bigint NOT NULL,
    nome character varying(50),
    CONSTRAINT situacao_associado_pkey PRIMARY KEY (codigo)
);

CREATE TABLE IF NOT EXISTS divisao
(
    nome character varying(50) NOT NULL,
    cidade character varying(50) NOT NULL,
    id_divisao bigint NOT NULL,
    descricao character varying,
    CONSTRAINT divisao_pkey PRIMARY KEY (id_divisao)
);
