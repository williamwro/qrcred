package models

import (
	"context"
	"log"

	"makecard.com.br/db"
	dbsql "makecard.com.br/pages/empregador/goland/dbsql/db"
)

func Get(id int64) (empregador []dbsql.Empregador, err error) {
	conn, err := db.OpenConnection()
	if err != nil {
		return
	}
	defer conn.Close()

	conn.Exec(`set search_path='sind'`)

	dt := dbsql.New(conn)

	ctx := context.Background()

	empregador, err = dt.ListEmpregador(ctx, id)
	if err != nil {
		log.Fatal(err)
	}

	log.Println(empregador)

	return
}
