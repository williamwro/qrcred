package models

import (
	"context"
	"log"

	"makecard.com.br/db"
	dbsql "makecard.com.br/pages/associado/goland/dbsql/db"
)

func Get(codigo string) (associado []dbsql.Associado, err error) {
	conn, err := db.OpenConnection()
	if err != nil {
		return
	}
	defer conn.Close()

	conn.Exec(`set search_path='sind'`)

	dt := dbsql.New(conn)

	ctx := context.Background()

	associado, err = dt.ListAssociado(ctx, codigo)
	if err != nil {
		log.Fatal(err)
	}

	log.Println(associado)

	return
}
