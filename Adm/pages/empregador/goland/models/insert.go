package models

import (
	"context"
	"log"

	"makecard.com.br/db"
	dbsql "makecard.com.br/pages/empregador/goland/dbsql/db"
)

func Insert(empregador dbsql.Empregador) (id int64, err error) {
	conn, err := db.OpenConnection()
	if err != nil {
		return
	}
	defer conn.Close()

	conn.Exec(`set search_path='sind'`)

	dt := dbsql.New(conn)

	ctx := context.Background()

	empregador_result, err := dt.CreateEmpregador(ctx, dbsql.CreateEmpregadorParams(empregador))
	if err != nil {
		log.Fatal(err)
	}

	log.Println(empregador_result)

	return
}
