-- name: ListEmpregadores :many
SELECT * FROM empregador;

-- name: ListEmpregador :many
SELECT * FROM empregador WHERE id = $1 LIMIT 1;

-- name: CreateEmpregador :one
INSERT INTO empregador(
	id, nome, responsavel, telefone, abreviacao, divisao)
	VALUES ($1, $2, $3, $4, $5, $6) RETURNING *;

-- name: UpdateEmpregador :one
UPDATE empregador
	SET id=$2, nome=$3, responsavel=$4, telefone=$5, abreviacao=$6, divisao=$7
	WHERE id=$1 RETURNING *;

-- name: DeleteEmpregador :exec
DELETE FROM empregador
	WHERE id=$1;	

-- name: ListDivisao :many
SELECT nome, cidade, id_divisao, descricao
	FROM divisao;	