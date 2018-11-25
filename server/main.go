package main

import (
  "bytes"
  "fmt"
  "net/http"
  "os"
)

func _main() int {
  s := new(StrandManager)

  mux := http.NewServeMux()

  mux.HandleFunc("/api/1/program", func(w http.ResponseWriter, r *http.Request) {
    if r.Method != "PUT" {
      w.Header().Set("Content-Type", "text/plain; charset=UTF-8")
      w.WriteHeader(http.StatusMethodNotAllowed)
      w.Write([]byte("this endpoint requires PUT\n"))
      return
    }

    if s.CurrentStrand == nil {
      w.Header().Set("Content-Type", "text/plain; charset=UTF-8")
      w.WriteHeader(http.StatusBadRequest)
      w.Write([]byte("strand is not connected\n"))
      return
    }

    b := new(bytes.Buffer)
    if _, err := b.ReadFrom(r.Body); err != nil {
      panic(err)
    }

    s.CurrentStrand.SendProgram(b.Bytes())
    w.WriteHeader(http.StatusAccepted)
  })

  mux.Handle("/api/1/strand/stream", s)

  go fmt.Printf("listening on :8000\n")
  http.ListenAndServe(":8000", mux)
  return 0
}

func main() {
  defer func() {
    if err := recover(); err != nil {
      fmt.Printf("error: %s\n", err)
      os.Exit(1)
    }
  }()

  if c := _main(); c != 0 {
    defer os.Exit(c)
  }
}
