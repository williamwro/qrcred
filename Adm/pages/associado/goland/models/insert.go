package models

import (
	"context"
	"log"

	"makecard.com.br/db"
	dbsql "makecard.com.br/pages/associado/goland/dbsql/db"
)

func Insert(associado dbsql.Associado) (id int64, err error) {
	conn, err := db.OpenConnection()
	if err != nil {
		return
	}
	defer conn.Close()

	conn.Exec(`set search_path='sind'`)

	dt := dbsql.New(conn)

	ctx := context.Background()

	associado_result, err := dt.CreateAssociado(ctx, dbsql.CreateAssociadoParams(associado))
	if err != nil {
		log.Fatal(err)
	}

	log.Println(associado_result)

	return
}
