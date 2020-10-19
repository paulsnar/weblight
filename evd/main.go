package main

import (
	"fmt"
	"net/http"
)

type programId struct {
	ID       string
	Revision uint32
}

func (id *programId) IsEmpty() bool {
	return id.ID == "" && id.Revision == 0
}
func (id *programId) String() string {
	return fmt.Sprintf("%s.%d", id.ID, id.Revision)
}

var s *clientServer

func handleConnectedCheck(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json; charset=UTF-8")
	w.WriteHeader(http.StatusOK)
	if len(s.clients) == 0 {
		w.Write([]byte("false"))
	} else {
		w.Write([]byte("true"))
	}
}

func handleSubmission(w http.ResponseWriter, r *http.Request) {
	if r.Method != "POST" {
		w.WriteHeader(http.StatusBadRequest)
		return
	}

	eventName := r.URL.Query().Get("event")
	var eventType eventType
	switch eventName {
	case "off":
		eventType = eventStop
	case "launch-last":
		eventType = eventRunLast
	case "launch":
		eventType = eventRun
	default:
		w.WriteHeader(http.StatusBadRequest)
		return
	}
	event := event{Type: eventType}
	if event.Type == eventRun {
		p := &event.Program
		_, err := fmt.Sscanf(r.URL.Query().Get("data"), "%8s-%d", &p.ID, &p.Revision)
		if err != nil {
			w.WriteHeader(http.StatusBadRequest)
			return
		}
	}

	s.events <- &event
	w.WriteHeader(http.StatusNoContent)
}

func main() {
	var err error
	s, err = initServer("weblight")
	if err != nil {
		panic(err)
	}

	http.HandleFunc("/meta", meta.HandleClient)
	http.HandleFunc("/submit", handleSubmission)
	http.HandleFunc("/connected", handleConnectedCheck)
	if err := http.ListenAndServe("weblight:8080", nil); err != nil {
		close(s.events)
		panic(err)
	}
}
