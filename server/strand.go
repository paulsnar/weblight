package main

import (
  "net/http"
)

type Strand struct {
  es *EventSource
}

func NewStrand(rw http.ResponseWriter, rq *http.Request) (*Strand, error) {
  es, err := NewEventSource(rw, rq)
  if err != nil {
    return nil, err
  }
  return &Strand{es}, nil
}

func (s *Strand) Loop() error {
  return s.es.Loop()
}

func (s *Strand) SendProgram(prog []byte) {
  s.es.Events <- Event{Event: "reprogram", Data: prog}
}

func (s *Strand) SendStop() {
  s.es.Events <- Event{Event: "off", Data: nil}
}

type StrandManager struct {
  CurrentStrand *Strand
}

func (sm *StrandManager) ServeHTTP(w http.ResponseWriter, r *http.Request) {
  if sm.CurrentStrand != nil {
    w.Header().Set("Content-Type", "text/plain; charset=UTF-8")
    w.WriteHeader(http.StatusConflict)
    w.Write([]byte("another strand is already registered\n"))
    return
  }

  str, err := NewStrand(w, r)
  if err != nil {
    w.Header().Set("Content-Type", "text/plain; charset=UTF-8")
    w.WriteHeader(http.StatusInternalServerError)
    w.Write([]byte("something went wrong\n"))
    panic(err)
  }

  defer func() { sm.CurrentStrand = nil }()
  sm.CurrentStrand = str

  if err := str.Loop(); err != nil {
    panic(err)
  }
}
