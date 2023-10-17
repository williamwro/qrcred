package handlers

import (
	"encoding/json"
	"fmt"
	"log"
	"net/http"

	dbsql "makecard.com.br/pages/associado/goland/dbsql/db"
	"makecard.com.br/pages/associado/goland/models"
)

func Create(w http.ResponseWriter, r *http.Request) {
	var associado dbsql.Associado

	err := json.NewDecoder(r.Body).Decode(&associado)
	if err != nil {
		log.Printf("Erro ao fazer decode do json %v", err)
		http.Error(w, http.StatusText(http.StatusInternalServerError), http.StatusInternalServerError)
		return
	}
	id, err := models.Insert(associado)

	var resp map[string]any

	if err != nil {
		resp = map[string]any{
			"Error":   true,
			"Message": fmt.Sprintf("Ocorreu um erro ao tentar inserir %v", err),
		}
	} else {
		resp = map[string]any{
			"Error":   false,
			"Message": fmt.Sprintf("Empragador inseriro com sucesso! ID: %d", id),
		}
	}

	w.Header().Add("Content-Type", "application/json")
	json.NewEncoder(w).Encode(resp)
}
