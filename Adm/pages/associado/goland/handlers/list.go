package handlers

import (
	"encoding/json"
	"log"
	"net/http"

	"makecard.com.br/pages/associado/goland/models"
)

func List(w http.ResponseWriter, r *http.Request) {
	associados, err := models.GetAll()
	if err != nil {
		log.Printf("Error ao obter registros %v", err)
	}

	w.Header().Add("Content-Type", "application/json")
	json.NewEncoder(w).Encode(associados)
}
