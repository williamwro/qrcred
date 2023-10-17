package models

import (
	"context"
	"log"

	"makecard.com.br/db"
	dbsql "makecard.com.br/pages/empregador/goland/dbsql/db"
)

func Update(id int64, empregador dbsql.UpdateEmpregadorParams) (int64, error) {
	conn, err := db.OpenConnection()
	if err != nil {
		return 0, err
	}
	defer conn.Close()

	conn.Exec(`set search_path='sind'`)

	dt := dbsql.New(conn)

	ctx := context.Background()

	empregador_result, err := dt.UpdateEmpregador(ctx, dbsql.UpdateEmpregadorParams(empregador))
	if err != nil {
		log.Fatal(err)
	}

	log.Println(empregador_result)

	if err != nil {
		return 0, err
	}

	return 0, err

}
