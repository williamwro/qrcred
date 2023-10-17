package handlers

import (
	"encoding/json"
	"log"
	"net/http"

	"makecard.com.br/pages/empregador/goland/models"
)

func List(w http.ResponseWriter, r *http.Request) {
	empregadores, err := models.GetAll()
	if err != nil {
		log.Printf("Error ao obter registros %v", err)
	}

	w.Header().Add("Content-Type", "application/json")
	json.NewEncoder(w).Encode(empregadores)
}
