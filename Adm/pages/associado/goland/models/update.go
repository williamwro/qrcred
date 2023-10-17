package models

import (
	"context"
	"log"

	"makecard.com.br/db"
	dbsql "makecard.com.br/pages/associado/goland/dbsql/db"
)

func Update(id string, associado dbsql.UpdateAssociadoParams) (int64, error) {
	conn, err := db.OpenConnection()
	if err != nil {
		return 0, err
	}
	defer conn.Close()

	conn.Exec(`set search_path='sind'`)

	dt := dbsql.New(conn)

	ctx := context.Background()

	associado_result, err := dt.UpdateAssociado(ctx, dbsql.UpdateAssociadoParams(associado))
	if err != nil {
		log.Fatal(err)
	}

	log.Println(associado_result)

	if err != nil {
		return 0, err
	}

	return 0, err

}
