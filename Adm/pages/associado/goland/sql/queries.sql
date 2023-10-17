-- name: ListAssociados :many
SELECT * FROM associado;

-- name: ListAssociado :many
SELECT * FROM associado WHERE codigo = $1 LIMIT 1;

-- name: CreateAssociado :one
INSERT INTO associado(
	codigo, nome, endereco, numero, nascimento, salario, limite, empregador, cep, telres, telcom, cel, bairro, id, complemento, cidade, foto, rg, cpf, funcao, filiado, obs, id_situacao, data_filiacao, data_desfiliacao, email, tipo, codigo_isa, parcelas_permitidas, uf, celwatzap, token_associado, cartao_entregue, data_entreg_cartao, ultimo_mes, id_divisao, id_secretaria, localizacao)
	VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $22, $23, $24, $25, $26, $27, $28, $29, $30, $31, $32, $33, $34, $35, $36, $37, $38) RETURNING *;

-- name: UpdateEmpregador :one
UPDATE empregador
	SET id=$2, nome=$3, responsavel=$4, telefone=$5, abreviacao=$6, divisao=$7
	WHERE id=$1 RETURNING *;

-- name: UpdateAssociado :one
UPDATE associado
	SET codigo=$2, nome=$3, endereco=$4, numero=$5, nascimento=$6, salario=$7, limite=$8, empregador=$9, cep=$10, telres=$11, telcom=$12, cel=$13, bairro=$14, id=$15, complemento=$16, cidade=$17, foto=$18, rg=$19, cpf=$20, funcao=$21, filiado=$22, obs=$23, id_situacao=$24, data_filiacao=$25, data_desfiliacao=$26, email=$27, tipo=$28, codigo_isa=$29, parcelas_permitidas=$30, uf=$31, celwatzap=$32, token_associado=$33, cartao_entregue=$34, data_entreg_cartao=$35, ultimo_mes=$36, id_divisao=$37, id_secretaria=$38, localizacao=$39
	WHERE codigo=$1 RETURNING *;	

-- name: DeleteAssociado :exec
DELETE FROM empregador
	WHERE id=$1;	

-- name: ListDivisao :many
SELECT nome, cidade, id_divisao, descricao
	FROM divisao;	